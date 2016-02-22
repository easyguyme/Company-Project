<?php
namespace backend\modules\store\job;

use backend\modules\resque\components\ResqueUtil;
use backend\models\Store;
use backend\models\StoreLocation;
use backend\modules\product\models\Coupon;
use backend\modules\product\models\CouponLog;
use MongoId;

/**
* Job for store location
*/
class Location
{
    public $levelMap = [
        'province' => StoreLocation::LOCATION_LEVEL_PROVINCE,
        'city' => StoreLocation::LOCATION_LEVEL_CITY,
        'district' => StoreLocation::LOCATION_LEVEL_DISTRICT
    ];

    public function perform()
    {
        $args = $this->args;
        $accountId = new MongoId($args['accountId']);
        $storeId = new MongoId($args['storeId']);
        $store = Store::findByPk($storeId);

        if (!empty($args['location'])) {
            // create new store location
            $location = $args['location'];

            if ($this->_addNewLocation($location, $accountId)) {
                $storeLocation = new StoreLocation();
                $storeLocation->_id = $store->_id;
                $storeLocation->name = $store->name;
                $storeLocation->parentName = $location['district'];
                $storeLocation->level = StoreLocation::LOCATION_LEVEL_STORE;
                $storeLocation->accountId = $accountId;
                $storeLocation->save();
            }
        } else if (!empty($args['oldLocation']) && !empty($args['newLocation'])) {
            // update store location
            $oldLocation = $args['oldLocation'];
            $newLocation = $args['newLocation'];
            StoreLocation::updateAll(
                ['name' => $store->name, 'parentName' => $newLocation['district']],
                ['_id' => $store->_id]
            );
            $this->_updateLocation($oldLocation, $newLocation, $accountId);
        } else if (!empty($args['removeLocation'])) {
            // delete store location
            $location = $args['removeLocation'];
            StoreLocation::deleteAll(['_id' => $storeId]);
            $this->_removeOldLocation($location, $accountId);
        }

        //update store name
        if (!empty($args['newStoreName'])) {
            //update storeLocation name
            StoreLocation::updateAll(['name' => $args['newStoreName']], ['_id' => $storeId]);
            //update couponLog
            CouponLog::updateAll(['store.name' => $store->name], ['_id' => $storeId]);
        }
        //update coupon store name
        if (!empty($store)) {
            $this->_updateCouponStroeInfo($store);
        }
    }

    /**
     * update store info in coupon
     * @param $store,object
     */
    private function _updateCouponStroeInfo($store)
    {
        $location = ['province', 'city', 'district', 'detail'];
        $address = '';
        $storeLocation = $store->location;
        foreach ($location as $key) {
            if (isset($storeLocation[$key])) {
                $address .= $storeLocation[$key];
            }
        }
        $address = $store->location['province'] . $store->location['city']
                . $store->location['district'] . $store->location['detail'];
        $storeData = [
            'stores.$.name' => $store->name,
            'stores.$.branchName' => $store->branchName,
            'stores.$.address' => $address,
            'stores.$.phone' => $store->telephone,
        ];
        Coupon::updateAll($storeData, ['stores.id' => $store->_id]);
    }

    /**
     * Add the new store locations
     * @param array  $location  contains 'province','city' and 'district'
     * @param string $accountId
     */
    private function _addNewLocation($location, $accountId)
    {
        $locationKeys = ['province', 'city', 'district'];
        foreach ($locationKeys as $key) {
            if (empty($location[$key])) {
                return false;
            }
        }
        $parentName = null;
        $result = true;
        foreach ($locationKeys as $key) {
            $storeLocation = StoreLocation::findOne(['name' => $location[$key], 'accountId' => $accountId]);
            if (empty($storeLocation)) {
                // if store location doesn't exist, add it
                $storeLocation = new StoreLocation();
                $storeLocation->_id = new MongoId();
                $storeLocation->name = $location[$key];
                $storeLocation->parentName = $parentName;
                $storeLocation->level = $this->levelMap[$key];
                $storeLocation->accountId = $accountId;
                $result &= $storeLocation->save();
            }
            // current location name is the next's parent
            $parentName = $storeLocation->name;
        }

        return (bool)$result;
    }

    /**
     * update the store locations
     * @param  array  $oldLocation
     * @param  array  $newLocation
     * @param  string $accountId
     * @return boolean
     */
    private function _updateLocation($oldLocation, $newLocation, $accountId)
    {
        $locationKeys = ['province', 'city', 'district'];
        foreach ($locationKeys as $key) {
            if (empty($newLocation[$key])) {
                return false;
            }
        }
        $result = true;
        $parentName = null;

        // remove the same location keys
        foreach ($locationKeys as $index => $key) {
            if ($oldLocation[$key] === $newLocation[$key]) {
                $parentName = $newLocation[$key];
                array_splice($locationKeys, $index, 1);
            } else {
                break;
            }
        }

        if (!empty($locationKeys)) {
            // create the new store locations
            foreach ($locationKeys as $key) {
                $storeLocation = StoreLocation::findOne(['name' => $newLocation[$key], 'accountId' => $accountId]);
                if (empty($storeLocation)) {
                    $storeLocation = new StoreLocation();
                    $storeLocation->_id = new MongoId();
                    $storeLocation->name = $newLocation[$key];
                    $storeLocation->parentName = $parentName;
                    $storeLocation->level = $this->levelMap[$key];
                    $storeLocation->accountId = $accountId;
                    $result &= $storeLocation->save();
                }
                $parentName = $storeLocation->name;
            }
            // remove the old store locations
            foreach (array_reverse($locationKeys) as $key) {
                if (!empty($oldLocation[$key])) {
                    $storeLocation = StoreLocation::findOne(['parentName' => $oldLocation[$key], 'accountId' => $accountId]);
                    if (empty($storeLocation)) {
                        $result &= StoreLocation::deleteAll(['name' => $oldLocation[$key], 'accountId' => $accountId]);
                    }
                }
            }
        }

        return (bool)$result;
    }

    /**
     * Remove the old store locations
     * @param array  $location  contains 'province','city' and 'district'
     * @param string $accountId
     */
    private function _removeOldLocation($location, $accountId)
    {
        $locationKeys = ['province', 'city', 'district'];
        foreach ($locationKeys as $key) {
            if (empty($location[$key])) {
                return false;
            }
        }
        $result = true;
        $parentName = null;

        if (!empty($locationKeys)) {
            // remove the old store locations
            foreach (array_reverse($locationKeys) as $key) {
                if (!empty($location[$key])) {
                    $storeLocation = StoreLocation::findOne(['parentName' => $location[$key], 'accountId' => $accountId]);
                    if (empty($storeLocation)) {
                        $result &= StoreLocation::deleteAll(['name' => $location[$key], 'accountId' => $accountId]);
                    }
                }
            }
        }

        return (bool)$result;
    }
}
