<?php
namespace backend\models;

use Yii;
use MongoId;
use MongoDate;
use backend\components\BaseModel;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\components\ActiveDataProvider;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\modules\product\models\Product;
use backend\utils\StringUtil;
use backend\modules\product\models\ProductCategory;
use backend\modules\product\models\ReceiveAddress;

/**
 * Model class for Goods.
 * The followings are the available columns in collection 'Goods':
 * @property MongoId $_id
 * @property MongoId $productId
 * @property string $productName
 * @property string $sku
 * @property MongoId $categoryId
 * @property array $pictures:{url}
 * @property int $score
 * @property string or int $total
 * @property int $usedCount
 * @property  string $status
 * @property MongoDate $onSaleTime
 * @property string $url
 * @property int $order
 * @property int clicks
 * @property string description
 * @property boolean $isDeleted
 * @property array $reveiveModes
 * @property array $addresses
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property MongoId $accountId
 **/
class Goods extends BaseModel
{
    public static $productCondition = [];

    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';
    const GOODS_ORDER = 'order';
    const GOODS_REDEEM = 'redeem';

    const PICTURES_MAX_COUNT = 5;

    const RECEIVE_MODE_EXPRESS = 'express';
    const RECEIVE_MODE_SELF = 'self';

    /**
    * Declares the name of the Mongo collection associated with Goods.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'goods';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'categoryId', 'productName', 'sku', 'productId',
                'pictures', 'score', 'total', 'usedCount',
                'status', 'onSaleTime', 'offShelfTime', 'url',
                'order', 'clicks', 'description', 'receiveModes',
                'addresses',
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'categoryId', 'productName', 'sku', 'productId', 'pictures',
                'score', 'total', 'usedCount', 'status', 'onSaleTime',
                'offShelfTime', 'url', 'order', 'clicks', 'description',
                'receiveModes', 'addresses',
            ]
        );
    }

    /**
    * Returns the list of all rules of Goods.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['productId', 'score'], 'required'],
                ['status', 'default', 'value' => 'off'],
                ['order', 'default', 'value' => 100],
                ['usedCount', 'default', 'value' => 0],
                ['clicks', 'default', 'value' => 0],
                ['pictures', 'default', 'value' => []],
                [['score', 'usedCount'], 'number', 'min' => 0, 'integerOnly' => true],
                ['pictures', 'validatePictrues'],
                ['receiveModes', 'checkReceiveModesAndPlaces']
            ]
        );
    }

    public function validatePictrues($attribute)
    {
        if ($attribute != 'pictures') {
            return true;
        }

        $pictures = $this->$attribute;
        if (!is_array($pictures) || empty($pictures)) {
            throw new BadRequestHttpException(Yii::t('product', 'invalide_params'));
        }
        if (count($pictures) > self::PICTURES_MAX_COUNT) {
            throw new InvalidParameterException(['goodsPictures' => Yii::t('product', 'goods_pictures_too_much')]);
        }
        foreach ($pictures as $picture) {
            if (!preg_match(StringUtil::URL_REGREX, $picture)) {
                throw new InvalidParameterException(['goodsPictures' => Yii::t('product', 'invalid_pictures')]);
            }
        }
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into Goods.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'productId' => function () {
                    return $this->productId . '';
                },
                'pictures',
                'score',
                'total',
                'usedCount',
                'status',
                'onSaleTime' => function () {
                    if ($this->onSaleTime) {
                        return MongodbUtil::MongoDate2String($this->onSaleTime, 'Y-m-d H:i');
                    } else {
                        return '';
                    }
                },
                'offShelfTime' => function () {
                    return (empty($this->offShelfTime) || !empty($this->onSaleTime)) ? '' : MongodbUtil::MongoDate2String($this->offShelfTime, 'Y-m-d H:i');
                },
                'url',
                'order',
                'categoryName' => function () {
                    $category = ProductCategory::findByPk($this->categoryId);
                    if ($category) {
                        return $category['name'];
                    } else {
                        return '';
                    }
                },
                'productName',
                'sku',
                'clicks',
                'description',
                'receiveModes',
                'addresses' => function () {
                    $addresses = [];
                    if (!empty($this->addresses)) {
                        foreach ($this->addresses as $address) {
                            $addresses[] = (string)$address;
                        }
                    }
                    return $addresses;
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i');
                },
            ]
        );
    }

    /**
     * get goods id list
     * @return array
     * @param $params. array
     */
    public static function getGoodsIdList($params, $accountId)
    {
        $idList = [];
        if (!empty($params['all'])) {
            switch (strtolower($params['operation'])) {
                //on shelves
                case self::STATUS_ON:
                    $where = ['status' => self::STATUS_OFF];
                    break;

                //off shelves
                case self::STATUS_OFF:
                    $where = ['status' => self::STATUS_ON];
                    break;

            }
            $where['accountId'] = $accountId;
            $idLists = Goods::findAll($where);
            if (!empty($idLists)) {
                foreach ($idLists as $value) {
                    $idList[] = $value['_id'];
                }
            }
        } else {
            $idList = array_keys($params['id']);
            if (count($idList) <= 0) {
                throw new BadRequestHttpException('param id is invailid');
            }

            foreach ($idList as $key => $value) {
                $idList[$key] = new MongoId($value);
            }
        }
        return $idList;
    }


    /**
     * get the update data by user operation
     * @return array
     * @param $where, array
     * @param $params, array
     */
    public static function getUpdateDataByOperation($where, $params)
    {
        $data = [];
        switch (strtolower($params['operation'])) {
            //on shelves
            case self::STATUS_ON:
                //check the  goods whether on shelves
                if (Goods::findOne(array_merge($where, ['status' => self::STATUS_ON]))) {
                    throw new InvalidParameterException(Yii::t('product', 'on_shelves'));
                }

                if (empty($params['onSaleTime'])) {
                    //on shelves immediately
                    $data['onSaleTime'] = new \MongoDate(time());
                    $data['status'] = self::STATUS_ON;
                } else {
                    //on shelves by schedule
                    $data['onSaleTime'] = new \MongoDate(TimeUtil::ms2sTime($params['onSaleTime']));
                    $data['status'] = self::STATUS_OFF;
                }
                break;

            //off sheleves
            case self::STATUS_OFF:
                $data['onSaleTime'] = '';
                $data['offShelfTime'] = new \MongoDate();
                $data['status'] = self::STATUS_OFF;
                break;

            //order goods
            case self::GOODS_ORDER:
                foreach ($params['id'] as $key => $value) {
                    self::updateAll(['$set' => ['order' => new \MongoInt32($value)]], ['_id' => $key]);
                }
                break;

        }
        return $data;
    }

    /**
    * update the status of goods
    * @param params array
    * @param $accountId objectId
    */
    public static function updateGoodsStatus($params, $accountId)
    {
        $idList = self::getGoodsIdList($params, $accountId);
        $where = ['_id' => ['$in' => $idList]];

        $data = self::getUpdateDataByOperation($where, $params);
        if (count($data) > 0) {
            self::updateAll($data, $where);
        }

        $where = array_merge($where, ['isDeleted' => Goods::NOT_DELETED]);

        return self::find()->where($where)->orderBy(self::normalizeOrderBy($params))->all();
    }

    /**
     * check receive mode
     * @return boolean
     * @param $param, array
     */
    public static function checkGoodsReceiveModes($params, $accountId)
    {
        $receiveModes = [self::RECEIVE_MODE_EXPRESS, self::RECEIVE_MODE_SELF];
        $idList = self::getGoodsIdList($params, $accountId);
        $where = ['_id' => ['$in' => $idList], 'receiveModes' => ['$in' => $receiveModes]];

        $count = Goods::count($where);
        if ($count != count($idList)) {
            return false;
        }
        return true;
    }

    /**
     * check the self address
     * @return boolean
     * @param params, array
     * @param accountId, objectId
     */
    public static function checkGoodsSelfAddress($params, $accountId)
    {
        $idList = self::getGoodsIdList($params, $accountId);
        //if the receiveModes is self,the address can not be empty
        $where = [
            '_id' => ['$in' => $idList],
            'receiveModes' => ['$in' => [self::RECEIVE_MODE_SELF]],
            'addresses' => []
        ];
        $goods = Goods::count($where);
        if (!empty($goods)) {
            return false;
        }
        return true;
    }


    /**
    * Search product by conditions
    * @param Array $params
    * @param string $accountId
    * @return product info
    */
    public static function search($params, $accountId)
    {
        $query = Goods::find();
        $comma = ',';
        $condition = ['accountId' => $accountId, 'isDeleted' => Goods::NOT_DELETED];

        if (!empty($params['category'])) {
            $categorys = explode($comma, $params['category']);
            $categoryIds = [];
            foreach ($categorys as $category) {
                $categoryIds[] = new MongoId($category);
            }
            $categorys = ['$in' => $categoryIds];

            $condition = array_merge($condition, ['categoryId' => $categorys]);
        }

        if (array_key_exists('searchKey', $params) && '' != $params['searchKey']) {
            $key = $params['searchKey'];
            $key = StringUtil::regStrFormat(trim($key));
            $keyReg = new \MongoRegex("/$key/i");
            $search = [
                '$or' => [
                    ['productName' => $keyReg],
                    ['sku' => $keyReg],
                ],
            ];
            $condition = array_merge($condition, $search);
        }

        if (!empty($params['notSoldOut'])) {
            $condition['total'] = ['$ne' => 0];
        }
        if (!empty($params['status'])) {
            $condition = self::createStatusCondition($params['status'], $condition);
        }

        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);

        $searchQuery = ['query' => $query];
        if (isset($params['isAll']) && $params['isAll']) {
            $searchQuery = array_merge($searchQuery, ['pagination' => ['pageSize' => 99999]]);
        }

        return new ActiveDataProvider($searchQuery);
    }

    public static function createStatusCondition($status, $condition)
    {
        switch (strtolower($status)) {
            case self::STATUS_ON:
                $condition = array_merge($condition, ['status' => self::STATUS_ON]);
                break;

            case self::STATUS_OFF:
                $condition = array_merge($condition, ['status' => self::STATUS_OFF]);
                break;

            case self::GOODS_REDEEM:
                $condition = array_merge($condition, ['total' => 0]);
                break;
        }
        return $condition;
    }

    /**
     * check the goods info
     * @return array
     * @param $goods array
     */
    public static function checkAndPackGoodsInfo($goods)
    {
        //get the productId
        $productIdList = [];
        foreach ($goods as $key => $info) {
            if (empty($info['productId'])) {
                throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
            }

            if (!isset($info['score'])) {
                throw new InvalidParameterException(Yii::t('product', 'score_not_empty'));
            }
            if (!isset($info['total'])) {
                throw new InvalidParameterException(Yii::t('product', 'total_not_empty'));
            }
            $productIdList[] = new MongoId($info['productId']);
        }

        //check  all product whether can be found in the product table
        $products = Product::findAll(['_id' => ['$in' => $productIdList]]);
        if (count($products) != count($productIdList) || count($products) <= 0) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
        }
        //check the goods
        if (Goods::findOne(['productId' => ['$in' => $productIdList]])) {
            throw new InvalidParameterException(Yii::t('product', 'not_add_again'));
        }

        $data = [];
        foreach ($products as $product) {
            $key = $product->_id . '';
            $data[$key]['productName'] = $product->name;
            $data[$key]['sku'] = $product->sku;
        }
        unset($key, $productIdList);

        foreach ($goods as $key => $value) {
            $productId = $value['productId'] . '';
            $goods[$key]['productName'] =  $data[$productId]['productName'];
            $goods[$key]['sku'] = $data[$productId]['sku'];
        }
        unset($productId, $data, $products);
        return $goods;
    }

    public static function getGoodsOrder($accountId)
    {
        $where = ['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        $goods = Goods::find()->where($where)->orderBy(['order' => SORT_DESC])->one();
        if (empty($goods)) {
            $order = 1;
        } else {
            $order = $goods['order'] + 1;
        }
        return $order;
    }

    /**
     * create goods
     * @param $result array
     * @param $accountId objectId
     */
    public static function createGoods($infos, $accountId)
    {
        $goodsData = [];
        //get max order
        $order = self::getGoodsOrder($accountId);

        foreach ($infos as $key => $info) {
            $goodsData[] = [
                'productId' => new MongoId($info['productId']),
                'productName' => $info['productName'],
                'sku' => $info['sku'],
                'categoryId' => !empty($info['categoryId']) ? new MongoId($info['categoryId']) : '',
                'score' => intval($info['score']),
                'total' => empty($info['total']) ? '' : intval($info['total']),
                'accountId' => $accountId,
                'order' => $order,
                'pictures' => empty($info['pictures']) ? [] : $info['pictures'],
                'status' => Goods::STATUS_OFF,
                'usedCount' => 0,
                'offShelfTime' => new Mongodate(time())
            ];

            $order ++;
        }

        Goods::batchInsert($goodsData);
        return ['status' => 'OK', 'message' => 'create successful'];
    }

    public static function getByIds($ids)
    {
        return self::findAll(['_id' => ['$in' => $ids]]);
    }

    public static function getGoodsName($params, $accountId)
    {
        $where = [
            'accountId' => $accountId,
            'productId' => ['$in' => $params['id']]
        ];
        return Goods::findAll($where);
    }

    /**
     * check the receive mode and places
     * @return array
     * @param $params, array
     */
    public function checkReceiveModesAndPlaces($attributes)
    {
        if (empty($this->receiveModes)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        if (!is_array($this->receiveModes)) {
            throw new InvalidParameterException(Yii::t('product', 'receive_mode_invalid'));
        }

        switch ($this->receiveModes) {
            case [self::RECEIVE_MODE_EXPRESS]:
                $this->addresses = [];
                break;

            case [self::RECEIVE_MODE_SELF]:
                if (empty($this->addresses)) {
                    throw new InvalidParameterException(Yii::t('product', 'pickup_location_invalid'));
                }
                break;

            default:
                $this->receiveModes = [self::RECEIVE_MODE_SELF, self::RECEIVE_MODE_EXPRESS];
                if (empty($this->addresses)) {
                    throw new InvalidParameterException(Yii::t('product', 'pickup_location_invalid'));
                }
                break;
        }
        if (!empty($this->addresses)) {
            $addresses = $this->addresses;
            foreach ($addresses as &$address) {
                $address = new MongoId($address);
            }
            $this->addresses = $addresses;
            unset($address, $addresses);
        }
        return true;
    }

    /**
     * set the goods status and sale time,offshelf time
     * @return obejct, goods object
     * @param $params, array
     * @param $goods, object
     */
    public static function setGoodsStatusAndTime($params, $goods)
    {
        if (!empty($params['status']) && $params['status'] == Goods::STATUS_ON) {
            $goods->status = Goods::STATUS_ON;
            $goods->onSaleTime = new MongoDate();
        } else if (!empty($params['status']) && $params['status'] == Goods::STATUS_OFF && isset($params['onSaleTime']) && $params['onSaleTime'] !== '') {
            if (time() > TimeUtil::ms2sTime($params['onSaleTime'])) {
                throw new InvalidParameterException(Yii::t('product', 'not_less_than_current'));
            } else {
                $goods->status = Goods::STATUS_OFF;
                $goods->onSaleTime = new MongoDate(TimeUtil::ms2sTime($params['onSaleTime']));
            }
        } else if (!empty($params['status']) && $params['status'] == Goods::STATUS_OFF && (!isset($params['onSaleTime']) || $params['onSaleTime'] === '')) {
            $goods->status = Goods::STATUS_OFF;
            $goods->onSaleTime = null;
            $goods->offShelfTime = new MongoDate();
        }
        return $goods;
    }
}
