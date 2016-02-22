<?php
namespace backend\modules\channel\modules\offlineStore\controllers;

use Yii;
use MongoId;
use backend\components\rest\RestController;
use backend\models\StoreLocation;
use backend\models\Store;
use backend\models\Token;
use backend\models\Account;
use yii\web\BadRequestHttpException;
use backend\models\Channel;
use backend\utils\LogUtil;
use backend\behaviors\StoreBehavior;

class StoreController extends RestController
{
    public $modelClass = "backend\models\Store";

    const STORE_START_NUM = 0;
    const STORE_END_NUM = 1000;

    const STATUS_WAITING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_FAILED = 3;
    const STATUS_COMPLETE = 4;

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * Create store and store locations in mongoDB
     */
    public function actionCreate()
    {
        $params = $this->getParams();
        $store = new Store();
        $store->attributes = $params;
        $store->_id = new MongoId();
        $token = Token::getToken();
        $store->accountId = $token->accountId;

        if ($store->validate()) {
            // all inputs are valid
            if ($store->save()) {
                $location = $store->location;
                unset($location['deatail']);
                $args = [
                    'location' => $location,
                    'storeId' => $store->_id . '',
                    'accountId' => $store->accountId . '',
                    'description' => 'Direct: Create store locations in storeLocation collection'
                ];
                // create a to create store locations
                Yii::$app->job->create('backend\modules\store\job\Location', $args);
                return $store;
            } else {
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        } else {
            // valid fail, return errors
            return $store->errors;
        }
    }

    /**
     * Update the store and store locations in mongoDB
     * @param  string $id store id
     */
    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $id = new MongoId($id);
        $store = Store::findByPk($id);
        $storeName = $store->name;
        $oldLocation = $store->location;
        $newLocation = $params['location'];
        $store->attributes = $params;

        if ($store->validate()) {
            // all inputs are valid
            if ($store->save()) {
                unset($oldLocation['detail'], $newLocation['detail']);

                $newStoreName = '';
                if ($oldLocation !== $newLocation) {
                    $newStoreName = $params['location'];
                }
                $newStoreName = '';
                if ($storeName !== $params['name']) {
                    $newStoreName = $params['name'];
                }

                $args = [
                    'oldLocation' => $oldLocation,
                    'newLocation' => $newLocation,
                    'newStoreName' => $newStoreName,
                    'storeId' => (string)$id,
                    'accountId' => $store->accountId . '',
                    'description' => 'Direct: Update store location in storeLocation collection'
                ];
                Yii::$app->job->create('backend\modules\store\job\Location', $args);
                return $store;
            } else {
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        } else {
            // valid fail, return errors
            return $store->errors;
        }
    }

    /**
     * Delete store and store locations in mongoDB
     */
    public function actionDelete($id)
    {
        $storeId = new MongoId($id);
        $accountId = $this->getAccountId();
        $store = Store::findOne(['_id' => $storeId, 'accountId' => $accountId]);

        if (!empty($store)) {
            $this->attachBehavior('StoreBehavior', new StoreBehavior);
            $this->deleteInfoByStore($store);
            Store::deleteAll(['_id' => $store->_id]);
        }
    }

    /**
     * Get the store locations
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/channel/offlinestore/store/location<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for getting store locations
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     name: string, the store location name<br/>
     *     <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * [
     *     {
     *         "id": "5518b4362736e7fa648b4567",
     *         "name": "北京"
     *     },
     *     {
     *         "id": "5518b4362736e7fa648b4568",
     *         "name": "上海"
     *     }
     * ]
     * </pre>
     */
    public function actionLocation()
    {
        $parentName = $this->getQuery('name', null);
        $accountId = Token::getAccountId();
        $locations = StoreLocation::find()
                    ->where(['parentName' => $parentName, 'accountId' => $accountId, 'isDeleted' => StoreLocation::NOT_DELETED])
                    ->all();
        return $locations;
    }

    /**
     * Sync the stores data from wechat
     */
    public function actionSync()
    {
        $accountId = Token::getAccountId();
        $result = ['finished' => true];
        $account = Account::find()
            ->select(['syncWechat'])
            ->where(['_id' => $accountId, 'isDeleted' => Account::NOT_DELETED])
            ->one();
        $wechat = Channel::getWechatByAccount($accountId, false);
        if (!empty($wechat)) {
            $unsyncWechat = array_diff($wechat, (array)$account->syncWechat);
            if (!empty($unsyncWechat)) {
                $args = ['accountId' => $accountId . '', 'channels' => $unsyncWechat, 'description' => 'Direct: Sync the stores data from wechat'];
                $token = Yii::$app->job->create('backend\modules\channel\job\StoreSync', $args);
                $result = ['finished' => false, 'token' => $token];
            }
        }

        return $result;
    }

    public function actionCheckSync()
    {
        $token = $this->getQuery('token');
        $type = $this->getQuery('type', Store::SYNC_TO_WECHAT);

        if (empty($token)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        // self::STATUS_WAITING - Job is still queued, 1
        // self::STATUS_RUNNING - Job is currently running, 2
        // self::STATUS_FAILED - Job has failed, 3
        // self::STATUS_COMPLETE - Job is complete, 4
        // false - Failed to fetch the status - is the token valid?
        $status = Yii::$app->job->status($token);

        switch ($status) {
            case self::STATUS_WAITING:
            case self::STATUS_RUNNING:
                return ['finished' => false];
            case self::STATUS_COMPLETE:
                return ['finished' => true];
            case self::STATUS_FAILED:
            default:
                $result = ['fail' => true];
                $token = Token::getToken();
                $key = Store::CACHE_PREFIX . $token->userId . $type;
                $failResult = Yii::$app->cache->get($key);
                if ($failResult !== false) {
                    $result['data'] = $failResult;
                }
                return $result;
        }
    }

    /**
     * Sync the stores data to wechat
     */
    public function actionPush()
    {
        $channelIds = $this->getParams('channelIds');
        $storeIds = $this->getParams('storeIds');
        $isAllStores = $this->getParams('isAllStores', false);

        if (empty($channelIds) || (empty($storeIds) && !$isAllStores)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $result = ['finished' => true];
        $token = Token::getToken();
        $accountId = $token->accountId;
        if ($isAllStores) {
            $stores = Store::find()
                ->select(['_id'])
                ->where(['accountId' => $accountId, 'isDeleted' => Store::NOT_DELETED])
                ->all();
            if (!empty($stores)) {
                $storeIds = [];
                foreach ($stores as $store) {
                    $storeIds[] = (string)$store->_id;
                }
            }
        }

        $args = [
            'accountId' => (string)$accountId,
            'channels' => $channelIds,
            'storeIds' => $storeIds,
            'userId' => (string)$token->userId,
            'description' => 'Direct: Sync the stores data to wechat'
        ];
        $token = Yii::$app->job->create('backend\modules\channel\job\StoreSync', $args);
        $result = ['finished' => false, 'token' => $token];
        return $result;
    }

    /**
     * Get store statistic
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/channel/offlinestore/store/statistic<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for getting store statistic
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     storeId: string, the store id, required<br/>
     *     <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     "wechat": {
     *         "scanNumber": 10,
     *         "followNumber": 220
     *     },
     *     "weibo": {
     *         "scanNumber": 101,
     *         "followNumber": 340
     *     }
     * }
     * </pre>
     */
    public function actionStatistic()
    {

        $storeId = $this->getQuery('storeId');

        if (empty($storeId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $storeId = new MongoId($storeId);
        $store = Store::findByPk($storeId);

        if (empty($store)) {
            throw new BadRequestHttpException(Yii::t('channel', 'no_such_store'));
        }

        $result = [
            'wechat' => ['scanNumber' => 0, 'followNumber' => 0],
            'weibo' => ['scanNumber' => 0, 'followNumber' => 0]
        ];

        if (!empty($store->wechat['channelId']) && !empty($store->wechat['qrcodeId'])) {
            $qrcode = Yii::$app->weConnect->getQrcodekeyIndicator($store->wechat['channelId'], $store->wechat['qrcodeId']);
            if (isset($qrcode['totalScan']) && isset($qrcode['totalSubscribe'])) {
                $result['wechat']['scanNumber'] = $qrcode['totalScan'];
                $result['wechat']['followNumber'] = $qrcode['totalSubscribe'];
            }
        }

        if (!empty($store->weibo['channelId']) && !empty($store->weibo['qrcodeId'])) {
            $qrcode = Yii::$app->weConnect->getQrcodekeyIndicator($store->weibo['channelId'], $store->weibo['qrcodeId']);
            if (isset($qrcode['totalScan']) && isset($qrcode['totalSubscribe'])) {
                $result['weibo']['scanNumber'] = $qrcode['totalScan'];
                $result['weibo']['followNumber'] = $qrcode['totalSubscribe'];
            }
        }

        return $result;
    }

    /**
     * Get store detail statistic
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/channel/offlinestore/store/analysis<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for getting store detail statistic
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     storeId: string, the store id, required<br/>
     *     startDate: string, the start date, such as '2015-01-01'<br/>
     *     endDate: string, the end date, such as '2015-01-07'<br/>
     *     <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     "wechat": {
     *         "statDate": ["2015-01-01", "2015-01-02", "2015-01-03", "2015-01-04", "2015-01-05", "2015-01-06", "2015-01-07"],
     *         "scanNumber": [11, 15, 35, 89, 90, 80, 10],
     *         "followNumber": [10, 10, 5, 8, 0, 8, 10]
     *     },
     *     "weibo": {
     *         "statDate": ["2015-01-01", "2015-01-02", "2015-01-03", "2015-01-04", "2015-01-05", "2015-01-06", "2015-01-07"],
     *         "scanNumber": [11, 15, 35, 89, 90, 80, 10],
     *         "followNumber": [13, 18, 34, 49, 30, 82, 20]
     *     }
     * }
     * </pre>
     */
    public function actionAnalysis()
    {
        $storeId = $this->getQuery('storeId');
        $startDate = $this->getQuery('startDate');
        $endDate = $this->getQuery('endDate');

        if (empty($storeId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $storeId = new MongoId($storeId);
        $store = Store::findByPk($storeId);

        if (empty($store)) {
            throw new BadRequestHttpException(Yii::t('channel', 'no_such_store'));
        }

        $dateCondition = ['startDate' => $startDate, 'endDate' => $endDate, 'type' => 'SCAN'];
        $keyMaps = ['scanNumber' => 'scan', 'followNumber' => 'subscribe'];
        $result = [
            'wechat' => [],
            'weibo' => []
        ];

        if (!empty($store->wechat['channelId']) && !empty($store->wechat['qrcodeId'])) {
            $qrcodeData = Yii::$app->weConnect->getQrcodeTimeSeries($store->wechat['channelId'], $store->wechat['qrcodeId'], $dateCondition);
            $result['wechat'] = $this->formateResponseData($qrcodeData, $keyMaps, $startDate, $endDate);
        }

        if (!empty($store->weibo['channelId']) && !empty($store->weibo['qrcodeId'])) {
            $qrcodeData = Yii::$app->weConnect->getQrcodeTimeSeries($store->weibo['channelId'], $store->weibo['qrcodeId'], $dateCondition);
            $result['weibo'] = $this->formateResponseData($qrcodeData, $keyMaps, $startDate, $endDate);
        }
        return $result;
    }
}
