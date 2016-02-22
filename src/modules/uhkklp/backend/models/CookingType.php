<?php

namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;
use backend\models\Token;
use backend\models\User;
use yii\mongodb\Query;

class CookingType extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpCookingtype';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['_id', 'name', 'operator', 'category', 'radio']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['name'], 'required'],
            ]
        );
    }

    public static function saveByCookbook($cookbook, $accountId = null, $admin = null)
    {
        if ($accountId == null) {
            $accountId = Token::getAccountId();
        }
        if ($admin == null) {
            $accessToken = Token::getToken();
            $userId = $accessToken->userId;
            $admin = User::findOne(['_id' => $userId]);
        }
        $queryCookingtype = new Query();
        $queryCookingtype->from('uhkklpCookingtype')
            ->where(['accountId' => $accountId]);
        $cookingTypes = $queryCookingtype->all();

        if (isset($cookbook['category'])) {
            for ($j=0; $j < count($cookbook['category']); $j++) {
                if (self::checkExist($accountId, $cookbook['category'][$j])) {
                    $cookingType = new CookingType();
                    $cookingType->name = $cookbook['category'][$j];
                    $cookingType->category = '標簽';
                    $cookingType->operator = $admin['name'];
                    $cookingType->accountId = $accountId;
                    $cookingType->save();
                }
            }
        }
        /*if (isset($cookbook['restaurantName']) && $cookbook['restaurantName'] != '' && self::checkExist($accountId, $cookbook['restaurantName'])) {
            $cookingType = new CookingType();
            $cookingType->name = $cookbook['restaurantName'];
            $cookingType->category = '餐廳';
            $cookingType->operator = $admin['name'];
            $cookingType->accountId = $accountId;
            $cookingType->save();
        }*/
        if (isset($cookbook['subCategory'])) {
            for ($j=0; $j < count($cookbook['subCategory']); $j++) {
                if (self::checkExist($accountId, $cookbook['subCategory'][$j])) {
                    $cookingType = new CookingType();
                    $cookingType->name = $cookbook['subCategory'][$j];
                    $cookingType->category = '標簽';
                    $cookingType->operator = $admin['name'];
                    $cookingType->accountId = $accountId;
                    $cookingType->save();
                }
            }
        }
        if (isset($cookbook['tag'])) {
            for ($j=0; $j < count($cookbook['tag']); $j++) {
                if (self::checkExist($accountId, $cookbook['tag'][$j])) {
                    $cookingType = new CookingType();
                    $cookingType->name = $cookbook['tag'][$j];
                    $cookingType->category = '標簽';
                    $cookingType->operator = $admin['name'];
                    $cookingType->accountId = $accountId;
                    $cookingType->save();
                }
            }
        }
        if (isset($cookbook['cuisineType'])) {
            for ($j=0; $j < count($cookbook['cuisineType']); $j++) {
                if (self::checkExist($accountId, $cookbook['cuisineType'][$j])) {
                    $cookingType = new CookingType();
                    $cookingType->name = $cookbook['cuisineType'][$j];
                    $cookingType->category = '標簽';
                    $cookingType->operator = $admin['name'];
                    $cookingType->accountId = $accountId;
                    $cookingType->save();
                }
            }
        }
        return ['code' => 200, 'msg' => 'Update types success!'];
    }

    public static function initCookingType($accountId)
    {
        if (self::checkExist($accountId, '最新食譜')) {
            $cookingType = new CookingType();
            $cookingType->name = '最新食譜';
            $cookingType->category = '固定分類';
            $cookingType->operator = '';
            $cookingType->accountId = $accountId;
            $cookingType->save();
        }
    }

    private function checkExist($accountId, $name)
    {
        $queryCookingtype = new Query();
        $queryCookingtype->from('uhkklpCookingtype')
            ->where(['accountId' => $accountId]);
        $cookingTypes = $queryCookingtype->all();

        for ($i=0; $i < count($cookingTypes); $i++) {
            if ($cookingTypes[$i]['name'] == $name) {
                break;
            }
        }
        if ($i >= count($cookingTypes)) {
            return true;
        }
        return false;
    }
}
