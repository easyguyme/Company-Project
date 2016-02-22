<?php
namespace backend\modules\product\models;

use Yii;
use MongoId;
use backend\components\BaseModel;
use backend\components\ActiveDataProvider;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\utils\StringUtil;
use backend\models\Goods;
use backend\modules\reservation\models\ReservationGoods;
use backend\modules\product\models\ProductCategory;
use yii\helpers\ArrayHelper;

/**
 * Model class for product.
 * The followings are the available columns in collection 'Goods':
 * @property MongoId      $_id
 * @property string       $name
 * @property string       $sku
 * @property string       $type
 * @property array        $category:{id,name,properties:[{id,name,value}]}
 * @property array        $pictures:[{name,url,size}]
 * @property int          $batchCode
 * @property boolean      $isBindCode
 * @property boolean      $isDeleted
 * @property MongoDate    $createdAt
 * @property MongoDate    $updatedAt
 * @property MongoId      $accountId
 **/
class Product extends BaseModel
{
    const MAX_SPECIFICATIONS = 4;

    /**
    * Declares the name of the Mongo collection associated with Product.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'product';
    }

    /**
    * Returns the list of all attribute names of Product.
    * This method must be overridden by child classes to define available attributes.
    * The parent's attributes function is:
    *
    * ```php
    * public function attributes()
    * {
    *     return ['_id', 'createdAt', 'updatedAt', 'isDeleted'];
    * }
    * ```
    *
    * @return array list of attribute names.
    */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'sku', 'type', 'name', 'pictures',
                'category', 'intro','isBindCode',
                'batchCode', 'specifications', 'qrcode'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'sku', 'type', 'name', 'pictures',
                'category', 'intro', 'isBindCode',
                'batchCode', 'specifications', 'qrcode'
            ]
        );
    }

    /**
    * Returns the list of all rules of Product.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['sku', 'name'], 'required'],
                ['category', 'formateId'],
                ['isBindCode', 'default', 'value' => false],
                ['batchCode', 'default', 'value' => 0],
                ['type', 'default', 'value' => ProductCategory::PRODUCT],
            ]
        );
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into Product.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'sku', 'type', 'name', 'pictures', 'category' => function () {
                    $categorys = $this->category;
                    if (!empty($categorys['id'])) {
                        $categorys['id'] = $categorys['id'] . '';
                    }
                    return $categorys;
                },
                'intro' => function () {
                    return ProductInfo::findByPk($this->_id)['intro'];
                },
                'isAssigned' => function () {
                    if ($this->isBindCode) {
                        return true;
                    } else {
                        return false;
                    }
                },
                'codeNum' => function () {
                    return PromotionCode::count(['productId' => $this->_id]);
                },
                'isSelected' => function () {
                    $goods = Goods::findOne(['productId' =>$this->_id]);
                    if (empty($goods)) {
                        return false;
                    } else {
                        return true;
                    }
                },
                'specifications',
                'qrcode' => function () {
                    $qrcode = $this->qrcode;
                    if (!empty($qrcode)) {
                        $qrcode['id'] .= '';
                        $qrcode['qrcodeUrl'] = Yii::$app->qrcode->getUrl($qrcode['qiniuKey']);
                        return $qrcode;
                    } else {
                        return [];
                    }
                },
                'isReservationGoods' => function () {
                    $result = ReservationGoods::findOne(['productId' => $this->_id]);
                    if (empty($result)) {
                        return false;
                    } else {
                        return true;
                    }
                }
            ]
        );
    }

    public function extraFields()
    {
        return array_merge(
            parent::extraFields(),
            [
                'isAssociated' => function () {
                    $campaign = Campaign::getByProductId($this->_id);
                    return !empty($campaign);
                },
                'promotionCodeCount' => function () {
                    return PromotionCode::countByProductIds([$this->_id]);
                },
            ]
        );
    }

    /**
    *format string to mongo object
    */
    public function formateId($attribute)
    {
        if ('category' == $attribute) {
            $categorys = $this->$attribute;

            if (!is_array($categorys)) {
                $this->addError($attribute, 'properties should be an array');
            }
            //$categoryProperty = []; //formate result
            $categorys['id'] = new MongoId($categorys['id']);
            //$categoryProperty[] = $categorys;
            $this->$attribute = $categorys;
        } else {
            return true;
        }
    }

    /**
    * Search product by conditions
    * @param Array $params
    * @param string $accountId
    * @return product info
    */
    public static function search($params, $accountId)
    {
        $query = Product::find();
        $comma = ',';
        $condition = ['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];

        if (!empty($params['assigned'])) {
            $condition = array_merge($condition, ['isBindCode' => true]);
        }

        if (!empty($params['category'])) {
            $categorys = explode($comma, $params['category']);
            $categoryIds = [];
            foreach ($categorys as $category) {
                $categoryIds[] = new MongoId($category);
            }
            $categorys = ['$in' => $categoryIds];
            $condition = array_merge($condition, ['category.id' => $categorys]);
        }

        if (array_key_exists('searchKey', $params) && '' != $params['searchKey']) {
            $key = $params['searchKey'];
            $key = StringUtil::regStrFormat(trim($key));
            $keyReg = new \MongoRegex("/$key/i");
            $search = [
                '$or' => [
                    ['name' => $keyReg],
                    ['sku' => $keyReg],
                ],
            ];
            $condition = array_merge($condition, $search);
        }

        if (!empty($params['categoryType'])) {
            $condition['type'] = $params['categoryType'];
        }
        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);
        $query->with('intro');

        $searchQuery = ['query' => $query];
        if (isset($params['isAll']) && $params['isAll']) {
            $searchQuery = array_merge($searchQuery, ['pagination' => ['pageSize' => 99999]]);
        }
        return new ActiveDataProvider($searchQuery);
    }

    /**
    * check the sku
    * @param $code string
    * @return product info
    */
    public static function getSku($code)
    {
        return Product::findOne(['sku' => $code]);
    }
    /**
    * add description
    * @param $product object
    * @param $info string desctption
    */
    public function addIntro($product, $info)
    {
        $productInfo = new ProductInfo;
        $productInfo->_id = $product->_id;
        $productInfo->intro = $info;
        $productInfo->accountId = $product->accountId;
        $productInfo->save();
        return $productInfo;
    }

    public static function getByIds($productIds)
    {
        return self::findAll(['_id' => ['$in' => $productIds]]);
    }

    public static function checkSkuWithCreateProduct($sku, $accountId)
    {
        //check the sku
        $product = Product::findOne(['sku' => $sku, 'accountId' => $accountId]);

        if (!empty($product)) {
            throw new InvalidParameterException(['number' => Yii::t("product", "number_isUsed")]);
        }
    }

    /**
     * if the sku is exists,throw a exception
     * @param sku, string
     * @param productId, objectId
     * @param accountId, objectId
     */
    public static function checkSKuWithUpdateProduct($sku, $productId, $accountId)
    {
        $product = Product::findOne(['sku' => $sku, 'accountId' => $accountId]);
        if (!empty($product) && $product->_id != $productId) {
            throw new InvalidParameterException(['number' => Yii::t("product", "number_isUsed")]);
        }
    }

    /**
     * if can not find the category,throw a exception
     * @param params, array
     * @param accountId, objectId
     */
    public static function checkCategory($params, $accountId)
    {
        if (empty($params['category'])) {
            return true;
        }
        $where = ['accountId' => $accountId, '_id' => new MongoId($params['category']['id'])];
        $categoryInfos = ProductCategory::findOne($where);

        if (empty($categoryInfos)) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
    }

    /**
     * get product description
     */
    public function getIntro()
    {
        return $this->hasOne(ProductInfo::className(), ['_id' => '_id']);
    }

    public static function getProductsName($params, $accountId)
    {
        $where = [
            'accountId' => $accountId,
            '_id' => ['$in' => $params['id']]
        ];
        return Product::findAll($where);
    }

    /**
     * create a number of product
     */
    public static function createSku()
    {
        $charlist = '0123456789';
        $rand = StringUtil::rndString(6, 0, $charlist);
        $currentChar = time() . $rand;

        $result = Product::getSku($currentChar);
        if (empty($result)) {
            return ['number' => $currentChar];
        } else {
            self::createNum();
        }
    }

    /**
     * pack the struct of specifications
     * @return array, [['id' => '', 'name' => 'xx', 'properties' => [['id' => 'px','name' => 'px']]]]
     * @param specifications, array
     */
    public static function packSpecifications($specifications)
    {
        $data = [];
        if (is_array($specifications)) {
            foreach ($specifications as $key => $specification) {
                if (isset($specification['name'])) {
                    $properties = self::getSpecificationProperty($specification);
                    $data[$key] = [
                        'id' => isset($specification['id']) ? $specification['id'] : StringUtil::uuid(),
                        'name' => $specification['name'],
                        'properties' => $properties,
                    ];
                }
            }
        }
        return $data;
    }

    private static function getSpecificationProperty($specification)
    {
        $properties = [];
        if (isset($specification['properties']) && is_array($specification['properties'])) {
            foreach ($specification['properties'] as $property) {
                if (is_array($property)) {
                    $properties[] = [
                        'id' => isset($property['id']) ? $property['id'] : StringUtil::uuid(),
                        'name' => isset($property['name']) ? $property['name'] : '',
                    ];
                } else {
                    $properties[] = [
                        'id' => StringUtil::uuid(),
                        'name' => $property,
                    ];
                }
            }
        }
        return $properties;
    }
}
