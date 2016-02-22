<?php
namespace backend\modules\channel\job;

use backend\modules\resque\components\ResqueUtil;
use backend\models\Account;
use backend\models\Store;

/**
* Job for import stores
*/
class StoreSync
{
    const STORE_START_NUM = 0;
    const STORE_END_NUM = 1000;

    public function setUp()
    {
    # Set up environment for this job
    }

    public function perform()
    {
        $args = $this->args;
        $accountId = new \MongoId($args['accountId']);
        $channels = $args['channels'];
        if (!empty($args['storeIds'])) {
            // Sync store data to wechat
            $userId = $args['userId'];
            $storeIds = $args['storeIds'];
            $storeData = [];
            foreach ($storeIds as $key => $storeId) {
                $storeId = new \MongoId($storeId);
                $store = Store::findByPk($storeId);
                $storeData[] = $store->toData();
            }

            $failResult = [];
            foreach ($channels as $channelId) {
                try {
                    $result = \Yii::$app->weConnect->addStore($channelId, $storeData);
                    if (!empty($result['location_id_list']) && in_array(-1, $result['location_id_list'])) {
                        foreach ($result['location_id_list'] as $index => $locationId) {
                            if ($locationId === -1) {
                                $failResult[] = [
                                    'channelId' => $channelId,
                                    'storeId' => $storeIds[$index],
                                    'storeName' => $storeData[$index]['business_name']
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    ResqueUtil::log($e);
                    foreach ($storeData as $index => $store) {
                        $failResult[] = [
                            'channelId' => $channelId,
                            'storeId' => $storeIds[$index],
                            'storeName' => $store['business_name']
                        ];
                    }
                }
            }

            if (!empty($failResult)) {
                \Yii::$app->cache->set(Store::CACHE_PREFIX . $userId . Store::SYNC_TO_WECHAT, $failResult, Store::CACHE_EXPIRE_TIME);
                throw new \Exception('Sync store data to wechat failed.');
            }
        } else {
            // Sync store data from wechat
            $account = Account::findByPk($accountId);
            $syncWechat = [];
            if (!empty($account->syncWechat)) {
                $syncWechat = $account->syncWechat;
            }
            $sizeCondition = ['offset' => static::STORE_START_NUM, 'count' => static::STORE_END_NUM];
            foreach ($channels as $channelId) {
                $stores = \Yii::$app->weConnect->getStores($channelId, $sizeCondition);
                ResqueUtil::log($stores);
                if (!empty($stores['location_list'])) {
                    foreach ($stores['location_list'] as $storeData) {
                        $store = new Store();
                        $store->loadData($storeData);
                        $store->accountId = $accountId;
                        $store->save();
                    }
                }
                array_push($syncWechat, $channelId);
            }
            $account->syncWechat = $syncWechat;
            $account->save();
        }

    }

    public function tearDown()
    {
    # Remove environment for this job
    }
}
