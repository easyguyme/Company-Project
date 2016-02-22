<?php
namespace backend\modules\product\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\product\models\Product;
use yii\web\ServerErrorHttpException;
use backend\utils\LogUtil;

/**
* Job for ClassJob
*/
class PromotionCheckCode
{
    const PER_BATCH = 1000;
    const EXPIRE = 3600;
    const SET_HEAD = "checkCode:";
    const PRODUCT_SKU_WRONG = -1;

    public function perform()
    {
        # Run task
        $args = $this->args;
        if (empty($args['locationPath']) || empty($args['productId'])
            || empty($args['filePath']) || empty($args['accountId'])
            || empty($args['qiniuBucket']) || empty($args['fileName'])) {
            LogUtil::error(['message' => 'missing productId or filePath in PromotionCheckCode', 'params' => $args], 'resque_product');
        }
        //get qiniu file, keep bom because of excel content
        $qiniuFile = Yii::$app->curl->get($args['filePath'], [], false);

        if (false === file_put_contents($args['locationPath'], $qiniuFile)) {
            LogUtil::error(['message' => 'Fail to get file from qiniu', 'args' => $args], 'resque_product');
            return false;
        }

        $phpreader = \PHPExcel_IOFactory::createReader('Excel2007');

        $filePath = $args['locationPath'];

        if (!$phpreader->canRead($filePath)) {
            $phpreader = \PHPExcel_IOFactory::createReader('Excel5');

            if (!$phpreader->canRead($filePath)) {
                $phpreader= \PHPExcel_IOFactory::createReader('CSV')
                ->setDelimiter(',')
                ->setInputEncoding('GBK')
                ->setEnclosure('"')
                ->setLineEnding("\r\n")
                ->setSheetIndex(0);
                if (!$phpreader->canRead($filePath)) {
                    LogUtil::error(['message' => 'FIle can not read in PromotionCheckCode'], 'resque_product');
                    return false;
                }
                LogUtil::info(['message' => 'Use Csv', 'filePath' => $filePath], 'resque_product');
            } else {
                LogUtil::info(['message' => 'Use Excel5', 'filePath' => $filePath], 'resque_product');
            }
        } else {
            LogUtil::info(['message' => 'Use Excel2007', 'filePath' => $filePath], 'resque_product');
        }

        $phpexcel = $phpreader->load($filePath);
        //read excel the first table
        $currentSheet = $phpexcel->getSheet(0);
        //get total row num
        $allRow = $currentSheet->getHighestRow();

        //product sku
        $productId = $args['productId'];
        //make a key to be called a name for redis set
        $cacheSet = self::SET_HEAD . md5($args['accountId'] . "_" .$productId . "_" . $args['fileName']);
        //make a key to be called a name for redis hash
        $cacheHash = md5($args['accountId'] . "_" .$productId . "_" .$args['fileName']);

        //the key for wrong number for store in redis
        $wrongKey = 'wrong';
        //the key for right number for  store in redis
        $rightKey = 'right';

        $total = $wrong = $right = 0;

        $redis = Yii::$app->cache->redis;

        $redis->expire($cacheHash, self::EXPIRE);

        LogUtil::info([
            'message' => 'get file info',
            'allRow' => $allRow,
            'sku' => $productId,
            'cacheSet' => $cacheSet,
            'cacheHash' => $cacheHash,
        ], 'resque_product');

        for ($rowIndex = 2; $rowIndex <= $allRow; ++ $rowIndex) {
            //get productid
            $A = 'A' . $rowIndex;
            //get code
            $B = 'B' . $rowIndex;

            $Acell = trim($currentSheet->getCell($A)->getValue());
            $Bcell = strtoupper(trim($currentSheet->getCell($B)->getValue()));

            if ($Acell != $productId && !empty($Bcell) && !empty($Acell)) {
                LogUtil::error([
                    'error' => 'the product number in the excel is not equal the selected product number',
                    'args' => [
                        'cell' => $Acell,
                        'sku' => $productId
                    ]
                ], 'resque_product');
                    // $wrong++;
                $redis->Hset($cacheHash, $wrongKey, self::PRODUCT_SKU_WRONG);
                $redis->Hset($cacheHash, $rightKey, 0);
                return false;
            } else if (!empty($Acell) && !empty($Bcell)) {
                $total ++;
                $redis->sadd($cacheSet, $Bcell);
            }
        }

        if ($total <= 0) {
            LogUtil::info(['message' => 'no datas'], 'resque_product');
        }
        //store the number of the wrong code and the number of the right code
        $right = $redis->scard($cacheSet);
        $wrong = $total - $right;
        $redis->Hset($cacheHash, $wrongKey, $wrong);
        $redis->Hset($cacheHash, $rightKey, $right);

        unlink($filePath);
        Yii::$app->qiniu->deleteFile($args['fileName'], $args['qiniuBucket']);

        return true;
    }
}
