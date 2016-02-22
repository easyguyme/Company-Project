<?php

namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;
use backend\utils\LogUtil;
use backend\models\Token;
use backend\modules\uhkklp\models\KlpAccountSetting;

class Product extends ActiveRecord
{

    public static function collectionName()
    {
        return 'uhkklpProduct';
    }

    public function attributes()
    {
        return [
            '_id',
            'name',
            'url',
            'creator',
            'createdAt',
            'updatedAt',
            'isDeleted',
            'accountId',
        ];
    }

    public function rules()
    {
        return [
            [
                [
                    'name',
                    'url',
                    'creator',
                    'createdAt',
                    'updatedAt',
                    'isDeleted',
                    'accountId',
                ],
                'safe'
            ]
        ];
    }

    public function getCount($condition = [])
    {
        $condition['isDeleted'] = false;
        return self::find()->where($condition)->count();
    }

    public function getList($currentPage = 1, $pageSize = 10, $condition = [], $sort = [])
    {
        if (empty($sort)) {
            $sort = ['_id' => SORT_DESC];
        } else {
            foreach ($sort as $key => $value) {
                if ($value) {
                    $sort = [$key => SORT_DESC];
                }
                else {
                    $sort = [$key => SORT_ASC];
                }
            }
        }

        $offset = ($currentPage - 1) * $pageSize;
        $condition['isDeleted'] = false;
        $query = new Query();
        $datas = $query->from(self::collectionName())->select($this->attributes())->where($condition)->orderBy($sort)->offset($offset)->limit($pageSize)->all();
        return $datas;
    }

    public static function getProductByName($name, $accountId)
    {
        //Token::getToken()
        // $query = new Query();
        // $one = $query->from(self::collectionName())->select(['url'])->where(['accountId' => $accountId, 'name' => $name])->one();
        // if ($one) {
        //     return $one['url'];
        // } else {
        //     return '';
        // }
        $site = KlpAccountSetting::getAccountSite(new \MongoId($accountId));
        if ($site == 'TW') {
            $products = require(dirname(__FILE__). '/../' . 'ProductTW.php');
        } else {
            $products = require(dirname(__FILE__). '/../' . 'ProductHK.php');
        }
        foreach ($products as $product) {
            $productName = trim(explode('(', $product['name'], 2)[0]);
            $name = trim(explode('(', $name, 2)[0]);
            $productName = trim(explode('（', $product['name'], 2)[0]);
            $name = trim(explode('（', $name, 2)[0]);
            if ($productName == $name) {
                return $product;
            }
        }
        return [];
    }
}
