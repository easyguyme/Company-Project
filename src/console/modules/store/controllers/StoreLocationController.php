<?php
namespace console\modules\store\controllers;

use backend\models\Store;
use backend\models\StoreLocation;
use yii\console\Controller;

/**
 * Refined store location
 */
class StoreLocationController extends Controller
{
    public $levelMap = [
        'province' => StoreLocation::LOCATION_LEVEL_PROVINCE,
        'city' => StoreLocation::LOCATION_LEVEL_CITY,
        'district' => StoreLocation::LOCATION_LEVEL_DISTRICT
    ];

    /**
     * Refined store location
     */
    public function actionIndex()
    {
        $stores = Store::findAll([]);
        if (!empty($stores)) {
            foreach ($stores as $store) {
                if (!empty($location = $store->location)) {
                    if (!empty($location['county'])) {
                        unset($location['county']);
                        $store->location = $location;
                        $store->save();
                    }

                    if (!empty($location['province']) && !empty($location['city']) && !empty($location['district'])) {
                        $accountId = $store->accountId;
                        $locationKeys = ['province', 'city', 'district'];
                        $parentName = null;
                        $storeLocation = StoreLocation::findByPk($store->_id);
                        if (empty($storeLocation)) {
                            $storeLocation = new StoreLocation();
                            $storeLocation->_id = $store->_id;
                            $storeLocation->name = $store->name;
                            $storeLocation->parentName = $location['district'];
                            $storeLocation->level = StoreLocation::LOCATION_LEVEL_STORE;
                            $storeLocation->accountId = $accountId;
                            $storeLocation->save();
                        }
                        foreach ($locationKeys as $key) {
                            $storeLocation = StoreLocation::findOne(['name' => $location[$key], 'accountId' => $accountId]);
                            if (empty($storeLocation)) {
                                // if store location doesn't exist, add it
                                $storeLocation = new StoreLocation();
                                $storeLocation->_id = new \MongoId();
                                $storeLocation->name = $location[$key];
                                $storeLocation->parentName = $parentName;
                                $storeLocation->level = $this->levelMap[$key];
                                $storeLocation->accountId = $accountId;
                                $storeLocation->save();
                            }
                            // current location name is the next's parent
                            $parentName = $storeLocation->name;
                        }
                    }
                }
            }
        }

        $storeLocations = StoreLocation::findAll(['level' => StoreLocation::LOCATION_LEVEL_STORE]);
        if (!empty($storeLocations)) {
            foreach ($storeLocations as $storeLocation) {
                $findStoreLocation = Store::findByPk($storeLocation->_id);
                if (empty($findStoreLocation)) {
                    $storeLocation->delete();
                }
            }
        }

        foreach (array_reverse($this->levelMap) as $level) {
            $storeLocations = StoreLocation::findAll(['level' => $level]);
            if (!empty($storeLocations)) {
                foreach ($storeLocations as $storeLocation) {
                    $accountId = $storeLocation->accountId;
                    $findStoreLocation = StoreLocation::findOne(['parentName' => $storeLocation->name, 'accountId' => $accountId]);
                    if (empty($findStoreLocation)) {
                        $storeLocation->delete();
                    }
                }
            }
        }
    }
}
