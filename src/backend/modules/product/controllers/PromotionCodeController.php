<?php
namespace backend\modules\product\controllers;

use backend\components\Controller;
use yii\web\BadRequestHttpException;
use backend\modules\product\models\PromotionCode;
use backend\modules\product\models\Product;
use backend\modules\member\models\Member;
use backend\modules\product\models\Campaign;
use backend\models\Token;
use backend\models\User;
use backend\components\Uploader;
use backend\utils\LogUtil;
use backend\utils\ExcelUtil;
use backend\utils\TimeUtil;
use backend\exceptions\InvalidParameterException;
use backend\modules\product\models\CampaignLog;
use yii\helpers\FileHelper;
use backend\components\Webhook;
use Yii;
use MongoDate;
use MongoId;
use yii\helpers\ArrayHelper;
use backend\modules\member\models\ScoreHistory;

class PromotionCodeController extends Controller
{
    const MAXSIZE = 51200000;/* limit for upload，单位B，default 50MB */
    const PATHFORMAT = '{yyyy}{mm}{dd}/{time}{rand:6}';//file path
    const EXCHANEG_TYPE_MOBILE = "mobile";
    const EXCHANEG_TYPE_OFFLINE = "offline";
    public static $ALLOWFILES = [".csv", ".xlsx", ".xls"];//file type

    public function actionHistory()
    {
        $productId = $this->getQuery('productId');

        if (empty($productId)) {
            throw new BadRequestHttpException('missing param productId');
        }
        return PromotionCode::getHistoryByProduct(new \MongoId($productId));
    }

    public function actionDelHistory()
    {
        $createdAt = $this->getParams('createdAt');
        $productId = $this->getParams('productId');

        if (empty($createdAt) || empty($productId)) {
            throw new BadRequestHttpException('missing param createdAt or productId');
        }

        $product = Product::findByPk(new \MongoId($productId));
        if (empty($product)) {
            throw new BadRequestHttpException('invalid productId');
        }

        //check the productId belong to which campaign that it is begining
        $campaignWhere = [
            'promotion.data' => ['$all' => [$product->_id]],
            'isActivated' => true
        ];
        $campaign = Campaign::findOne($campaignWhere);
        if (!empty($campaign)) {
            throw new BadRequestHttpException(Yii::t('product', 'can_not_delete'));
        }

        $usedCount = PromotionCode::countByProductIdAndCreatedAt(new \MongoId($productId), new \MongoDate($createdAt), true);

        if ($usedCount > 0) {
            throw new BadRequestHttpException(Yii::t('product', 'can_not_delete'));
        } else {
            $deleteArgs = [
                'productId' => $product->_id . '',
                'createdAt' => $createdAt,
                'type' => 'delete',
                'description' => 'Direct: Delete promotionCodes'
            ];
            $jobId = Yii::$app->job->create('backend\modules\product\job\PromotionCode', $deleteArgs);
        }
        return ['message' => 'OK', 'data' => $jobId];
    }

    /**
     * exchange the promotioncode
     */
    public function actionExchange()
    {
        $params = $this->getParams();
        if (empty($params['code']) || empty($params['memberId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        //get email for user
        $accesstoken = $this->getAccessToken();
        $tokenInfo = Token::findOne(['accessToken' => $accesstoken]);
        $userInfo = User::findByPk($tokenInfo['userId']);
        $params['operaterEmail'] = empty($userInfo['email']) ? '' : $userInfo['email'];

        $params['userInfo'] = empty($userInfo) ? null : ['id' => $userInfo->_id, 'name' => $userInfo->name];

        $memberId = $params['memberId'];
        $params['memberId'] = new MongoId($memberId);

        if (!empty($params['exchangeTime'])) {
            $params['exchangeTime'] = TimeUtil::ms2sTime($params['exchangeTime']);
        }
        //exchange the promotion code
        $accountId = $this->getAccountId();
        if (is_array($params['code'])) {
            //exchaneg code offline
            $codes = $params['code'];
            $successCode = [];
            foreach ($codes as $code) {
                $params['code'] = strtoupper($code);
                $result = PromotionCode::exchangeCampaignCode($params, $accountId, self::EXCHANEG_TYPE_OFFLINE);
                if ('success' == $result['result']) {
                    $successCode[] = $code;
                }
            }
            list($codeNumber, $score) = CampaignLog::getCodeRecord($successCode);
            if (!empty($params['useWebhook'])) {
                $eventData = [
                    'type'=> Webhook::EVENT_PROMOTION_CODE_REDEEMED,
                    'member_id'=> $memberId,
                    'codes'=> $params['code'],
                    'redeemed_codes'=> $successCode,
                    'score' => $score,
                    'origin' => Member::PORTAL,
                    'account_id' => (string) $accountId,
                    'created_at' => TimeUtil::msTime2String($params['exchangeTime'], \DateTime::ATOM)
                ];
                Yii::$app->webhook->triggerEvent($eventData);
            }
            //fix data
            $this->fixData($accountId, $params['memberId'], $successCode);
            return ['result' => 'success', 'codeNumber' => $codeNumber, 'totalScore' => $score, 'codes' => $successCode];
        } else {
            $params['code'] = strtoupper($params['code']);
            $result =  PromotionCode::exchangeCampaignCode($params, $accountId, self::EXCHANEG_TYPE_MOBILE);
            if ('error' == $result['result']) {
                throw new InvalidParameterException($result['message']);
            } else {
                return $result;
            }
        }
    }

    /**
     * check the code in the excel
     */
    public function actionCheckCode()
    {
        $params = Yii::$app->request->post();
        $productId = $params['productId'];
        //$productId = '1429862376675049';
        if (empty($productId)) {
            throw new BadRequestHttpException('missing param productId');
        }
        $fileKey = 'file';
        if (empty($_FILES[$fileKey])) {
            throw new BadRequestHttpException('missing param '.$fileKey);
        }

        $accountId = $this->getAccountId();

        $where = ['accountId' => $accountId, 'sku' => $productId];
        $result = Product::findOne($where);
        LogUtil::info(['file' => $_FILES, 'where' => $where], 'promotimeCode');

        if (empty($result)) {
            throw new BadRequestHttpException(Yii::t("product", "product_deleted"));
        }
        unset($where);
        //upload config
        $config = [
            'maxSize'=> self::MAXSIZE,
            'allowFiles'=>self::$ALLOWFILES,
            'pathFormat'=> self::PATHFORMAT,
            'privateBucket' => true,
        ];
        //upload to qiniu
        $upload = new Uploader($fileKey, $config, 'upload', 1);
        $fileInfo = $upload->getFileInfo();
        $rootPath = Yii::$app->getRuntimePath() . '/code/';
        if (!is_dir($rootPath)) {
            FileHelper::createDirectory($rootPath, 0777, true);
        }
        $fileName = $fileInfo['title'];
        $locationPath = $rootPath . $fileName . $fileInfo['type'];

        if (empty($fileName)) {
            throw new InvalidParameterException($fileInfo['state']);
        }
        $checkArgs = [
            'qiniuBucket' => QINIU_DOMAIN_PRIVATE,
            'productId' => $productId,
            'filePath' => Yii::$app->qiniu->getPrivateUrl($fileName),
            'locationPath' => $locationPath,
            'fileName' => $fileName,
            'accountId' => (string)$accountId,
            'description' => 'Direct: Check if promotion codes to be imported is unique'
        ];
        $jobId = Yii::$app->job->create('backend\modules\product\job\PromotionCheckCode', $checkArgs);
        return ['message' => 'OK', 'data' => ['token'=> $jobId, 'filename'=> $fileName]];
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        if (empty($params['productId']) || empty($params['codeType'])) {
            throw new BadRequestHttpException('missing param');
        }
        $product = Product::findByPk(new \MongoId($params['productId']));
        if (empty($product)) {
            throw new BadRequestHttpException(Yii::t("product", "product_deleted"));
        }

        $accountId = $this->getAccountId();
        if ($params['codeType'] == 'generate') {
            $count = intval($params['count']);
            if ($count <= 0) {
                throw new InvalidParameterException(['promotionCodeCount' => Yii::t('product', 'count_too_small')]);
            }
            if ($count > PromotionCode::BIGGEST_COUNT) {
                throw new InvalidParameterException(['promotionCodeCount' => Yii::t('product', 'count_too_large')]);
            }
            if (!empty($product->batchCode) && $product->batchCode >= 36) {
                throw new InvalidParameterException(Yii::t('product', 'batch_code_limit'));
            }
            $jobArgs = [
                'accountId' => $accountId . '',
                'productId' => $params['productId'],
                'count' => $count,
                'type' => 'generate',
                'description' => 'Direct: Generate promotion codes'
            ];
            $jobId = Yii::$app->job->create('backend\modules\product\job\PromotionCode', $jobArgs);
            Product::updateAll(['$inc' => ['batchCode' => 1]], ['_id' => $product->_id]);//update product batchcode
        } else {
            if (empty($params['filename']) && $params['import']) {
                throw new BadRequestHttpException('missing param filename');
            }
            $jobArgs = [
                'accountId' => $accountId . '',
                'productId' => $params['productId'],
                'type' => 'insert',
                'import' => $params['import'],
                'filename' => $params['filename'],
                'description' => 'Direct: Import promotion codes'
            ];
            $jobId = Yii::$app->job->create('backend\modules\product\job\PromotionCode', $jobArgs);
        }
        return ['message' => 'OK', 'data' => $jobId];
    }

    /**
     * get the status of resque
     */
    public function actionGetStatus()
    {
        $params = $this->getQuery();
        if (empty($params['token']) || empty($params['productId'])) {
            throw new BadRequestHttpException('param missing');
        }
        $accountId = (string)$this->getAccountId();
        $result = Yii::$app->job->status($params['token']);

        if (!empty($params['filename'])) {
            $filename = $params['filename'];

            $redis = Yii::$app->cache->redis;

            $hashName = md5($accountId . '_' . $params['productId'] . '_' . $filename);

            $wrongValue = $redis->Hget($hashName, 'wrong');
            $rightValue = $redis->Hget($hashName, 'right');

            if (4 == $result) {
                $redis->del($hashName);
            }
            return ['message' => 'OK', 'status' => $result, 'wrong' => $wrongValue, 'right' => $rightValue];
        } else {
            return ['message' => 'OK', 'status' => $result, 'wrong' => 0, 'right' => 0];
        }
    }

    /**
     * clear redis cache with the code from upload excel file
     */
    public function actionClearCache()
    {
        $params = $this->getQuery();
        if (!isset($params['filename']) || !isset($params['productId'])) {
            throw new BadRequestHttpException('params missing');
        }
        $accountId = $this->getAccountId();

        $deleteArgs = [
            'productId' => $params['productId'],
            'filename' => $params['filename'],
            'accountId' => $accountId . '',
            'type' => 'deleteRedisCode',
            'description' => 'Direct: Delete promotion codes cached in redis'
        ];
        $jobId = Yii::$app->job->create('backend\modules\product\job\PromotionCode', $deleteArgs);
        return ['message' => 'OK', 'data' => $jobId];
    }

    /**
     * check the promocode in the offline to get the score
     */
    public function actionCheck()
    {
        $params = $this->getQuery();

        if (empty($params['code']) || empty($params['memberId'])) {
            throw new BadRequestHttpException('missing params');
        }
        if (empty($params['exchangeTime'])) {
            $params['exchangeTime'] = time();
        } else {
            $params['exchangeTime'] = TimeUtil::ms2sTime($params['exchangeTime']);
        }
        $exchangeTime = new MongoDate($params['exchangeTime']);

        $member = Member::findByPk($params['memberId']);
        if (empty($member)) {
            throw new InvalidParameterException(Yii::t('member', 'no_member_find'));
        }
        if ($member->isDisabled) {
            throw new InvalidParameterException(Yii::t('member', 'member_invalid_not_exchange'));
        }

        $codes = explode(',', $params['code']);
        $codes = array_unique($codes);
        foreach ($codes as $key => $code) {
            $codes[$key] = (string)strtoupper(trim($code));
        }

        $accountId = $this->getAccountId();
        $datas = PromotionCode::checkCodeStatus($codes, $accountId, $member, $exchangeTime, $params);
        //sort the code
        $newCode = [];
        $totalScore = 0;
        foreach ($codes as $code) {
            foreach ($datas as $key => $data) {
                if ($data['code'] == $code) {
                    $newCode[] = $data;
                }
            }
        }
        return ['data' => $newCode];
    }

    /**
     * clear redis for record exchage code in offline(clear-exchange-record)
     * need memberId and code,if code is empty,i will clear all cache for this member
     * otherwise i only clear a record
     */
    public function actionClearExchangeRecord()
    {
        $params = $this->getQuery();
        if (empty($params['memberId'])) {
            throw new BadRequestHttpException('missing param memberId');
        }

        if (!isset($params['code'])) {
            $params['code'] = '';
        }
        PromotionCode::clearExchangeRecord($params['memberId'], $params['code']);
    }

    /**
     * export excel for the promotioncode to upload to qiniu,and return key to frontend
     */

    public function actionExport()
    {
        $params = $this->getQuery();
        if (empty($params['createdAt']) || empty($params['productId'])) {
            throw new BadRequestHttpException('missing param createdAt or productId');
        }

        $productId = new MongoId($params['productId']);

        $product = Product::findByPk($productId);
        if (empty($product)) {
            throw new BadRequestHttpException('invalid productId');
        }

        $startTime = new MongoDate($params['createdAt']);
        $endTime = new MongoDate($params['createdAt'] + 1);
        $condition = ['productId' => $productId, 'createdAt' => ['$gte' => $startTime, '$lt' => $endTime]];

        $data = PromotionCode::findOne($condition);

        if ($data) {
            $accountId = $this->getAccountId();
            list($sku, $code, $isUsed) = explode(',', Yii::t('product', 'export_promotioncode_title'));
            $header = ['code' => $code, 'isUsed' => $isUsed];
            $key = $product['name'] . '_' . date('Ymd') . '_' . $product['sku'];
            $status = ['vaild' => 'Y', 'unvaild' => 'N'];
            $fields = 'code,isUsed';
            $exportArgs = [
                'status' => $status,
                'header' => $header,
                'key' => $key,
                'sku' => $product->sku,
                'accountId' => (string)$accountId,
                'condition' => serialize($condition),
                'fields' => $fields,
                'description' => 'Direct: export promotionCodes'
            ];
            $jobId = Yii::$app->job->create('backend\modules\product\job\ExportPromotionCode', $exportArgs);
            $result = ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            $result = ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
        return $result;
    }

    /**
     * This function is just for fix error promotionCode redeem data
     * @param MongoId $accountId
     * @param MongoId $memberId
     * @param Array $codes
     * @return boolean, true, if there is no error data
     */
    private function fixData($accountId, $memberId, $codes)
    {
        $condition = [
            'accountId' => $accountId,
            'member.id' => $memberId,
            'code' => ['$in' => $codes]
        ];
        $pipeline = [
            ['$match' => $condition],
            [
                '$group' => [
                    '_id' => ['campaignId' => '$campaignId', 'code' => '$code'],
                    'count' => ['$sum' => 1],
                ]
            ],
            [
                '$match' => ['count' => ['$gt' => 1]]
            ]
        ];
        $stats = CampaignLog::getCollection()->aggregate($pipeline);
        if (empty($stats)) {
            return true;
        }
        $logCondition = [
            'accountId' => $accountId,
            'member.id' => $memberId
        ];
        $failedMessages = [];
        $successMessages = [];
        foreach ($stats as $stat) {
            $code = $stat['_id']['code'];
            //get campaign log
            $logCondition = array_merge($logCondition, $stat['_id']);
            $logs = CampaignLog::find()->where($logCondition)->orderBy(['createdAt' => SORT_ASC])->all();
            $memberId = $logs[0]['member']['id'];
            $productName = $logs[0]['productName'];
            //get score history
            $description = $productName . ' ' . $code;
            $scoreHistoryCondition = ['memberId' => $memberId, 'brief' => ScoreHistory::ASSIGNER_EXCHANGE_PROMOTION_CODE, 'description' => $description];
            $scoreHistorys = ScoreHistory::find()->where($scoreHistoryCondition)->orderBy(['createdAt' => SORT_ASC])->all();
            $keepScoreHistory = $scoreHistorys[0];
            unset($scoreHistorys[0]);
            $removeScoreHistoryIds = [];
            $deduct = 0;
            foreach ($scoreHistorys as $scoreHistory) {
                $removeScoreHistoryIds[] = $scoreHistory->_id;
                $deduct += $scoreHistory->increment;
            }
            $member = Member::findByPk($memberId);
            //if member score not enough, log continue
            if ($member->score <= $deduct || $member->totalScore <= $deduct || $member->totalScoreAfterZeroed <= $deduct) {
                $failedMessages[] = [
                    'Failed' => 'Member score not enough',
                    'member' => $member->toArray(),
                    'deduct' => $deduct
                ];
                continue;
            }
            //fix member score
            $deductScore = 0 - $deduct;
            Member::updateAll(['$inc' => ['score' => $deductScore, 'totalScore' => $deductScore, 'totalScoreAfterZeroed' => $deductScore]], ['_id' => $memberId]);
            //remove scorehistory
            ScoreHistory::deleteAll(['_id' => ['$in' => $removeScoreHistoryIds]]);
            //remove campaignlog
            $logIds = ArrayHelper::getColumn($logs, '_id');
            $keepLogId = $logIds[0];
            unset($logIds[0]);
            CampaignLog::deleteAll(['_id' => ['$in' => array_values($logIds)]]);
            $successMessages[] = [
                'Success' => $productName . ' ' . $code . ' ' . $stat['count'],
                'memberId' => $memberId,
                'deduct' => $deduct
            ];
        }
        LogUtil::error(['Failed' => $failedMessages, 'Success' => $successMessages], 'fix-campaign-data');
    }
}
