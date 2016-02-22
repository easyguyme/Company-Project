<?php
namespace backend\models;

use backend\components\BaseModel;
use backend\utils\MongodbUtil;
use backend\components\ActiveDataProvider;
use backend\utils\StringUtil;
use backend\utils\TimeUtil;
use backend\modules\product\models\ProductCategory;

/**
 * Model class for storeGoods.
 * The followings are the available columns in collection 'storeGoods':
 * @property MongoId $_id
 * @property MongoId $productId
 * @property string $productName
 * @property string $sku
 * @property float $price
 * @property MongoId $categoryId
 * @property array $pictures:{url}
 * @property  string $status
 * @property MongoDate $onSaleTime
 * @property string $description
 * @property boolean $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property MongoId $accountId
 **/
class StoreGoods extends BaseModel
{
    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';
    const PRICE_REG = '/(^[1-9]\d*(\.\d{1,2})?$)|(^0\.(([1-9]\d?)|(0[1-9]))$)/';
    /**
    * Declares the name of the Mongo collection associated with storeGoods.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'storeGoods';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['storeId', 'categoryId', 'productName', 'sku', 'productId', 'pictures', 'status', 'onSaleTime', 'offShelfTime', 'price']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['storeId', 'categoryId', 'productName', 'sku', 'productId', 'pictures', 'status', 'onSaleTime', 'offShelfTime', 'price']
        );
    }

    /**
    * Returns the list of all rules of storeGoods.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['status', 'default', 'value' => self::STATUS_OFF],
                ['price', 'number', 'numberPattern' => self::PRICE_REG]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into storeGoods.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'storeId' => function () {
                    return (string) $this->storeId;
                },
                'productId' => function () {
                    return (string) $this->productId;
                },
                'pictures',
                'status',
                'onSaleTime' => function () {
                    return empty($this->onSaleTime) ? '' : MongodbUtil::MongoDate2String($this->onSaleTime, 'Y-m-d H:i');
                },
                'offShelfTime' => function () {
                    return (empty($this->offShelfTime) || !empty($this->onSaleTime)) ? '' : MongodbUtil::MongoDate2String($this->offShelfTime, 'Y-m-d H:i');
                },
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
                'price' => function () {
                    return self::formatPrice($this->price);
                },
                'createdAt' => function () {
                    return empty($this->createdAt) ? '' : MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i');
                }
            ]
        );
    }

    /**
     * Get the total, onSaleTotal and offSaleTotal
     * @return array
     */
    public static function getTotal($storeId, $accountId)
    {
        $condition = ['status' => self::STATUS_ON, 'storeId' => $storeId, 'accountId' => $accountId];
        $onSaleTotal = self::count($condition);
        $condition['status'] = self::STATUS_OFF;
        $offSaleTotal = self::count($condition);
        return [
            'total' => $onSaleTotal + $offSaleTotal,
            'onSaleTotal' => $onSaleTotal,
            'offSaleTotal' => $offSaleTotal
        ];
    }

    public static function getByProductId($productId)
    {
        return self::findOne(['productId' => $productId]);
    }

    public static function getByProductAndStore($productId, $storeId)
    {
        return self::findAll(['productId' => $productId, 'storeId' => $storeId]);
    }

    public static function countByProductId($productIds, $storeId)
    {
        return self::count(['productId' => ['$in' => $productIds], 'storeId' => $storeId]);
    }

      /**
    * Search product by conditions
    * @param Array $params
    * @param string $accountId
    * @return product info
    */
    public static function search($params, $accountId)
    {
        $query = self::find();
        $comma = ',';
        $condition = ['accountId' => $accountId, 'isDeleted' => StoreGoods::NOT_DELETED];

        if (!empty($params['categoryIds'])) {
            $categorys = explode($comma, $params['categoryIds']);
            $categoryIds = [];
            foreach ($categorys as $category) {
                $categoryIds[] = new \MongoId($category);
            }
            $categorys = ['$in' => $categoryIds];

            $condition['categoryId'] = $categorys;
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

        if (!empty($params['status'])) {
            $condition['status'] = $params['status'];
        }
        if (!empty($params['storeId'])) {
            $condition['storeId'] = $params['storeId'];
        }

        if (isset($params['saleTimeFrom']) && $params['saleTimeFrom'] !== '') {
            $condition['onSaleTime']['$gte'] = new \MongoDate(TimeUtil::ms2sTime($params['saleTimeFrom']));
        }
        if (isset($params['saleTimeTo']) && $params['saleTimeTo'] !== '') {
            $condition['onSaleTime']['$lte'] = new \MongoDate(TimeUtil::ms2sTime($params['saleTimeTo']));
        }

        if (isset($params['priceFrom']) && $params['priceFrom'] !== '') {
            $condition['price']['$gte'] = floatval($params['priceFrom']);
        }
        if (isset($params['priceTo']) && $params['priceTo'] !== '') {
            $condition['price']['$lte'] = floatval($params['priceTo']);
        }

        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);

        $searchQuery = ['query' => $query];
        return new ActiveDataProvider($searchQuery);
    }

    public static function getByIds($storeGoodsIds)
    {
        return self::findAll(['_id' => ['$in' => $storeGoodsIds]]);
    }

    public static function updateStatusByIds($storeGoodsIds, $status, $onSaleTime, $offShelfTime = null)
    {
        $attributes = ['status' => $status, 'onSaleTime' => $onSaleTime];
        if ($offShelfTime !== null) {
            $attributes['offShelfTime'] = $offShelfTime;
        }
        StoreGoods::updateAll(
            ['$set' => $attributes],
            ['_id' => ['$in' => $storeGoodsIds]]
        );
    }

    public static function getOnSaleByIds($ids, $accountId)
    {
        $condition = [
            '_id' => ['$in' => $ids],
            'accountId' => $accountId,
            'isDeleted' => self::NOT_DELETED,
            'status' => self::STATUS_ON,
        ];
        return self::findAll($condition);
    }

    public static function formatPrice($price)
    {
        $price = sprintf("%.2f", $price);
        return floatval($price);
    }

    public static function validatePrice($price)
    {
        if (preg_match(self::PRICE_REG, $price)) {
            return true;
        }
        return false;
    }
}
