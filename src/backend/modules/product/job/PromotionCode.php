<?php
namespace backend\modules\product\job;

use MongoId;
use backend\modules\resque\components\ResqueUtil;
use backend\models\ProductAccociation;
use backend\modules\product\models\PromotionCode as ModelPromotionCode;
use yii\web\ServerErrorHttpException;
use backend\modules\product\models\Product;

/**
* Job for ClassJob
*/
class PromotionCode
{
    const PER_BATCH = 1000;

    const JOB_UPDATE = 'update';
    const JOB_GENERATE = 'generate';
    const JOB_DELETE = 'delete';
    const JOB_INSERT = 'insert';
    const JOB_DELETE_REDIS_CODE = 'deleteRedisCode';
    const PROMO_CODE_REPEATED = -2;


    public function setUp()
    {
        # Set up environment for this job
    }

    public function perform()
    {
        # Run task
        $args = $this->args;

        //job update productId
        switch ($args['type']) {
            case self::JOB_UPDATE:
                if (!empty($args['oldProductId']) && !empty($args['newProductId'])) {
                    ModelPromotionCode::updateAll(
                        ['$set' => ['productId' => new MongoId($args['newProductId'])]],
                        ['productId' => new MongoId($args['oldProductId']), 'isUsed' => false]
                    );

                    return true;
                }
                break;
            case self::JOB_GENERATE:
                //job generateCodes
                if (empty($accountId = $args['accountId']) || empty($productId = $args['productId'])
                    || !array_key_exists('count', $args)) {
                    ResqueUtil::log(['error' => 'generate codes params error', 'param' => $args]);
                    return false;
                }
                $createdAt =  new \MongoDate(time(), 0);
                $count = $args['count'];
                $successCount = 0;
                $batchCount = $count / self::PER_BATCH;
                for ($i = 0; $i < $batchCount; $i++) {
                    $restCount = $count - self::PER_BATCH * $i;
                    $rowsCount = $restCount >= self::PER_BATCH ? self::PER_BATCH : $restCount;
                    $codes = ModelPromotionCode::generateCodes($rowsCount, new MongoId($productId));
                    ResqueUtil::log(['info' => 'generate code ok', 'param' => $args]);
                    $promotionCodes = [];
                    foreach ($codes as $code) {
                        $promotionCode = [
                            'productId' => new MongoId($productId),
                            'code' => $code,
                            'isUsed' => false,
                            'accountId' => new MongoId($accountId),
                            'createdAt' => $createdAt,
                            'random' => rand()
                        ];
                        $promotionCodes[] = $promotionCode;
                    }
                    ResqueUtil::log(['info' => 'generate codes', 'param' => $args]);
                    $result = ModelPromotionCode::batchInsert($promotionCodes);

                    if (!$result) {
                        ResqueUtil::log(['error' => 'save codes failed', 'param' => $args, 'success count' => $successCount]);
                    } else {
                        $successCount += $rowsCount;
                    }
                }
                //change the isBindCode
                $this->changeProductStatus(new MongoId($productId));
                return true;

            case self::JOB_DELETE:
                if (!empty($args['productId'])) {
                    $productId = new MongoId($args['productId']);
                    $condition = ['productId' => $productId];
                } else {
                    ResqueUtil::log(['error' => 'delete codes params error', 'param' => $args]);
                    return false;
                }
                if (!empty($args['createdAt'])) {
                    $createdAt = new \MongoDate($args['createdAt']);
                    $condition = array_merge($condition, ['createdAt' => $createdAt]);
                }
                 //if delete all code successfully,judge to change isBindCode
                if (ModelPromotionCode::deleteAll($condition)) {
                    $count = ModelPromotionCode::count(["productId" => $productId]);
                    if ($count <= 0) {
                        Product::updateAll(['isBindCode' => false], ['_id' => $productId]);
                    }
                }
                return true;

            case self::JOB_INSERT:
                if (!isset($args['accountId']) || !isset($args['productId']) || !isset($args['filename'])) {
                      ResqueUtil::log(['error' => 'missing param accountId or productId or filename', 'param' => $args]);
                      return false;
                }
                if (!isset($args['import'])) {
                    $args['import'] = false;
                }
                $redis = \Yii::$app->cache->redis;
                $createdAt =  new \MongoDate(time());

                $product = Product::findOne($args['productId']);
                if (empty($product)) {
                    ResqueUtil::log(['error' => 'product has been deleted', 'param' => $args]);
                    return false;
                }

                $productId = $product['sku'];

                $cacheKey = $args['accountId'] . "_" . $productId . "_" . $args['filename'];
                $cacheSet = PromotionCheckCode::SET_HEAD . md5($cacheKey);
                $total = $redis->scard($cacheSet);
                //redis hash key name
                $cacheHash = md5($cacheKey);

                $k = 0;
                $promotionCodes = $insertCode = [];
                $accountId = new MongoId($args['accountId']);

                for ($i = 1; $i <= $total; ++$i) {
                    // insert the code
                    if ($args['import']) {
                        $key = $redis->spop($cacheSet);
                        if (!empty($key)) {
                            $promotionCode = [
                                'productId' => new MongoId($args['productId']),
                                'code' => $key,
                                'isUsed' => false,
                                'accountId' => $accountId,
                                'createdAt' => $createdAt
                            ];
                            $promotionCodes[$k++] = $promotionCode;
                            $insertCode[] = $key;
                        }
                        //set default value in redis to avoid to insert data in mongo failly
                        $redis->HSET($cacheHash, 'right', 0);
                        $redis->HSET($cacheHash, 'wrong', self::PROMO_CODE_REPEATED);
                        //have data to store
                        if (count($promotionCodes) > 0) {
                            if ($k % self::PER_BATCH == 0 || $i == $total) {
                                $exitsResult = ModelPromotionCode::findOne(['code' => ['$in' => $insertCode], 'accountId' => $accountId]);
                                if (!empty($exitsResult)) {
                                    ResqueUtil::log(['message' => 'code exits, job over', 'code' => $exitsResult->code]);
                                    $redis->del($cacheSet);
                                    return false;
                                }
                                $result = ModelPromotionCode::batchInsert($promotionCodes);

                                $promotionCodes = $insertCode = [];
                                if (!$result) {
                                    ResqueUtil::log(['error' => 'save codes failed', 'param' => $args, 'success count' => ($i - count($promotionCodes))]);
                                }
                            }
                        }
                    }
                }
                $redis->del($cacheSet);
                //change the isBindCode
                $this->changeProductStatus($product['_id']);
                //update duplicate key error
                $redis->HSET($cacheHash, 'wrong', 0);
                return true;
                break;
            case self::JOB_DELETE_REDIS_CODE:
                if (!isset($args['filename']) || !isset($args['productId']) || !isset($args['accountId'])) {
                    ResqueUtil::log(['error' => 'missing param', 'param' => $args]);
                    return false;
                }

                $redis = \Yii::$app->cache->redis;

                $cacheSet = PromotionCheckCode::SET_HEAD . md5($args['accountId'] . '_' . $args['productId'] . '_' . $args['filename']);
                $redis->del($cacheSet);
                unset($commonKey, $cacheTotalKey, $count);
                return true;
                break;
            default:
                break;
        }
    }

    public function tearDown()
    {
        # Remove environment for this job
    }

     /*
    * change the status for the product when add a code
    */
    public function changeProductStatus($productId)
    {
        $product = Product::findByPk($productId);
        if (false == $product['isBindCode']) {
            Product::updateAll(['isBindCode' => true], ['_id' => $productId]);
        }
    }
}
