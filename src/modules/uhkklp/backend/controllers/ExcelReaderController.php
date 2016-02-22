<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use backend\utils\LogUtil;
use backend\models\Token;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\Cookbook;
use backend\modules\uhkklp\models\Product;
use backend\modules\uhkklp\models\CookbookBatch;
use backend\modules\uhkklp\models\SmsModel;
use backend\modules\uhkklp\models\SmsTemplate;
use backend\models\User;


class ExcelReaderController extends BaseController
{
    public $enableCsrfValidation = false;

    public function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $uuid =  substr($charid, 0, 8)
                    .substr($charid, 8, 4)
                    .substr($charid, 12, 4)
                    .substr($charid, 16, 4)
                    .substr($charid, 20, 12);
            return $uuid;
        }
    }

    public function actionReadNumberExcel()
    {
        sleep(2);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $fileB64 = $request->post('fileB64');
        $content = $request->post('content');
        // $modelGroupId = $request->post('modelGroupId');

        //delete all sms model by groupId
        //根据groupid删除各自短信模板  但是导入之后前台刷新会丢失groupid 会造成数据库数据累积
        //表中含有accountid字段可以根据该字段删除所有模板
        // if ($modelGroupId != -1) {
        //     SmsModel::deleteAll(['groupId' => $modelGroupId]);
        // }

        $file = base64_decode(substr($fileB64, strpos($fileB64,";base64,") + 8));

        $filePath = Yii::getAlias('@runtime') . '/numberExcel' . date('his') . '.csv';
        file_put_contents($filePath, $file);

        //csv 由xlsx转换 第一列为号码 第二列替换param1 第三列param2
        $phpReader = new \PHPExcel_Reader_Excel2007();
        if (!$phpReader->canRead($filePath)){
            $phpReader = new \PHPExcel_Reader_Excel5();
            if (!$phpReader->canRead($filePath)){
                $phpReader = new \PHPExcel_Reader_CSV();
                if (!$phpReader->canRead($filePath)) {
                    unlink($filePath);
                    return ['fileError'=> true];
                }
            }
        }
        $phpExcel = $phpReader->load($filePath);

        $sheets = $phpExcel->getAllSheets();

        $recordCount = -1;

        for ($si = 0; $si < sizeof($sheets); $si++) {
            $sheet = $sheets[$si];

            $ingredientFinished = false;

            $rowCount = $sheet->getHighestRow();
            $recordCount = $rowCount;
            $highestCol = $sheet->getHighestColumn();
            // LogUtil::error(date('Y-m-d h:i:s') . ' $rowCount: ' . $rowCount . ' $highestCol: ' . $highestCol);
            $colCount = ord($highestCol) - 65;

            // $currentGroupId = $this->guid();
            // LogUtil::error(date('Y-m-d h:i:s') . ' $getAccountId: ' . $this->getAccountId());
            $resultList = array();
            for($row = 1; $row <= $rowCount; $row++){
                $realModel = $content;
                $number = '';
                for ($col = 0; $col <= $colCount; $col++) {
                    $val = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                    $val = trim((string)$val);
                    if ($val === '') {
                        continue;
                    }

                    if ($col == 0) {
                        $number = $val;
                    }

                    if ($col > 0) {
                        $realModel = str_replace('%param' . $col . '%', $val, $realModel);
                    }
                }

                //如果存在空白行则会把模板存入
                // $SmsModel = new SmsModel();
                // $SmsModel->groupId = $currentGroupId;
                // $SmsModel->mobile = $number;
                // $SmsModel->content = $realModel;
                $tmp = array('mobile' => $number, 'content' =>$realModel);
                array_push($resultList, $tmp);
                // $SmsModel->accountId = $this->getAccountId();
                // $SmsModel->save();
            }
        }
        // LogUtil::error(date('Y-m-d h:i:s') . ' $recordCount: ' . $recordCount);
        return ['recordCount' => $recordCount, 'resultList' => $resultList];
    }

    // public function actionSaveModel()
    // {
    //     Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    //     $request = Yii::$app->request;
    //     $modelContent = $request->post('modelContent');

    //     SmsTemplate::deleteAll(['accountId' => $this->getAccountId()]);

    //     $SmsTemplate = new SmsTemplate();
    //     $SmsTemplate->modelContent = $modelContent;
    //     $SmsTemplate->accountId = $this->getAccountId();
    //     $result = $SmsTemplate->save();

    //     if ($result > 0) {
    //         return ['result' => 'success'];
    //     } else {
    //         return ['result' => 'fail'];
    //     }
    // }

    // public function actionGetTemplate()
    // {
    //     Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    //     $model = SmsTemplate::findOne(['accountId' => $this->getAccountId()]);

    //     return ['model' => $model];
    // }
}
