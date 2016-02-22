<?php
namespace backend\modules\product\models;

use backend\components\BaseModel;
use backend\utils\MongodbUtil;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use yii;
use backend\components\GiftValidator;
use backend\utils\TimeUtil;

/**
 * Model class for campaign.
 *
 * The followings are the available columns in collection 'campaign':
 * @property MongoId     $_id
 * @property string      $name
 * @property MongoDate   $startTime
 * @property MongoDate   $endTime
 * @property int         $participantCount
 * @property int         $usedCount
 * @property int         $limitTimes
 * @property array       $promotion
 * @property boolean     $isActivated
 * @property boolean     $isDeleted
 * @property MongoDate   $createdAt
 * @property MongoDate   $updatedAt
 * @property boolean     $isAddTags
 * @property array       $userTags
 * @property Object      $accountId
 **/
class Campaign extends BaseModel
{
    const TYPE_PROMOTION_CODE = 'promotion_code';

    const TYPE_GIFT_PRODUCT_UNLIMITED = 'unlimited';
    const TYPE_GIFT_PRODUCT_FIRST = 'first';

    /**
     * Declares the name of the Mongo collection associated with campaign.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'campaign';
    }

    /**
     * Returns the list of all attribute names of campaign.
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
            ['name', 'startTime', 'endTime', 'participantCount', 'usedCount', 'limitTimes', 'promotion', 'isActivated', 'isAddTags', 'userTags']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'startTime', 'endTime', 'participantCount', 'usedCount', 'limitTimes', 'promotion', 'isActivated', 'isAddTags', 'userTags']
        );
    }

    /**
     * Returns the list of all rules of campaign.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['name', 'startTime', 'endTime'], 'required'],
                ['participantCount', 'default', 'value'=> null],
                ['usedCount', 'default', 'value' => 0],
                ['limitTimes', 'default', 'value'=> null],
                ['isActivated', 'default', 'value'=> false],
                ['startTime', 'validateTime', 'on' => [self::SCENARIO_CREATE]],
                ['endTime', 'validateTime', 'on' => [self::SCENARIO_CREATE]],
                ['promotion', 'validatePromotion'],
                ['name', 'validateName'],
                ['isAddTags', 'default', 'value' => false]
            ]
        );
    }

    public function validateName($attribute)
    {
        if ($attribute != 'name') {
            return true;
        }
        $name = $this->$attribute;
        $campaign = self::getByName($name, $this->accountId);
        if (!empty($campaign) && $this->_id . '' != $campaign->_id . '') {
            throw new InvalidParameterException(['campaignName' => \Yii::t('product', 'campaign_name_exists')]);
        }
    }

    public function validateTime($attribute)
    {
        $time = $this->$attribute;
        $time = TimeUtil::ms2sTime($time);

        $this->$attribute = new \MongoDate($time);

        if ($attribute == 'startTime') {
            $now = time();
            if ($time < $now) {
                throw new InvalidParameterException(['beginDatePicker' => \Yii::t('product', 'invalid_start_time')]);
            }
        } else if ($attribute == 'endTime') {
            if ($time <= MongodbUtil::MongoDate2TimeStamp($this->startTime)) {
                throw new InvalidParameterException(['endDatePicker' => \Yii::t('product', 'invalid_end_time')]);
            }
        }
    }

    public function validatePromotion($attribute)
    {
        if ($attribute !== 'promotion') {
            return true;
        }

        $promotion = $this->$attribute;

        if (empty($promotion['type'])) {
            throw new BadRequestHttpException('missing promotion type');
        }
        if (empty($promotion['data']) || !is_array($promotion['data'])) {
            throw new InvalidParameterException(['campaignProducts' => \Yii::t('product', 'product_required')]);
        }
        $promotion['data'] = MongodbUtil::toMongoIdList($promotion['data']);

        $productCount = Product::count(['_id' => ['$in' => $promotion['data']], 'isBindCode' => true]);
        if ($productCount != count($promotion['data'])) {
            throw new InvalidParameterException(['promotionProduct' => \Yii::t('product', 'error_promotion_product')]);
        } else {
            $this->$attribute = $promotion;
        }

        if (!in_array($promotion['products'], [self::TYPE_GIFT_PRODUCT_FIRST, self::TYPE_GIFT_PRODUCT_UNLIMITED]) && !is_array($promotion['products'])) {
            throw new InvalidParameterException(['promotionCampaigns' => \Yii::t('product', 'invalid_gift_campaigns')]);
        }
        if (is_array($promotion['products'])) {
            $promotion['products'] = MongodbUtil::toMongoIdList($promotion['products']);
            if (count($promotion['products']) == Product::count(['_id' => ['$in' => $promotion['products']]])) {
                $this->$attribute = $promotion;
            } else {
                throw new BadRequestHttpException('error products');
            }
        }
        if (!is_array($promotion['tags'])) {
            throw new BadRequestHttpException('tags must be array');
        }
        if (!is_array($promotion['channels'])) {
            throw new BadRequestHttpException('channels must be array');
        }

        if (empty($promotion['gift']) || !is_array($promotion['gift'])) {
            return new BadRequestHttpException('gift must be array');
        }

        $gift = GiftValidator::validateGift($promotion['gift']);
        $promotion['gift'] = $gift;
        $this->$attribute = $promotion;
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into campaign.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name',
                'startTime' => function () {
                    return MongodbUtil::MongoDate2String($this->startTime, 'Y-m-d H:i:00');
                },
                'endTime' => function () {
                    return MongodbUtil::MongoDate2String($this->endTime, 'Y-m-d H:i:00');
                }, 'participantCount', 'limitTimes',
                'promotion' => function () {
                    $promotion = $this->promotion;
                    $data = [];
                    if (!empty($promotion['data'])) {
                        $products = Product::getByIds($promotion['data']);
                        foreach ($products as $product) {
                            $data[] = [
                                'id' => $product->_id . '',
                                'name' => $product->name
                            ];
                        }
                        $promotion['data'] = $data;
                    }

                    if (is_array($promotion['products'])) {
                        foreach ($promotion['products'] as &$products) {
                            $products .= '';
                        }
                    }
                    return $promotion;
                },
                'isActivated',
                'userTags',
                'isAddTags',
                'isExpired' => function () {
                    $nextMinute = strtotime('+ 1 minute - ' . date('s') . 'seconds');
                    return MongodbUtil::MongoDate2TimeStamp($this->endTime) < $nextMinute;
                }
            ]
        );
    }

    public static function getByIds($campaignIds)
    {
        return self::findAll(['_id' => ['$in' => $campaignIds]]);
    }

    public static function getByProductId($productId)
    {
        return self::findOne(['promotion.data' => $productId]);
    }

    public static function getByProductIds($productIds)
    {
        $condition = [];
        foreach ($productIds as $productId) {
            $condition[] = ['promotion.data' => $productId];
        }
        $where = ['$or' => $condition];
        return self::findAll($where);
    }

    public static function getByAccount($accountId)
    {
        return self::find()->where(['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED])->orderBy(['createdAt' => SORT_DESC])->all();
    }

    public static function expiredByTime($time)
    {
        return self::updateAll(
            ['$set' => ['isActivated' => false]],
            ['endTime' => ['$lt' => $time], 'isActivated' => true]
        );
    }

    public static function getByName($name, $accountId)
    {
        return self::findOne(['name' => $name, 'accountId' => $accountId]);
    }

    /**
     * search the product id from the campaign
     * @param $accountId,MongoId
     * @param $pageSize,int,the number record for one page
     * @param $page,int, which page to show
     */
    public static function searchProductInfo($accountId, $pageSize, $page)
    {
        $where = ['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        $campaigns = Campaign::find()->select(['promotion.data'])->where($where)->all();

        $showData = [];
        //get productId
        if (!empty($campaigns)) {
            $productIds = [];
            foreach ($campaigns as $campaign) {
                if (!empty($campaign['promotion']['data'])) {
                    $ids = $campaign['promotion']['data'];
                    foreach ($ids as $id) {
                        $productIds[] = $id;
                    }
                }
            }
            //get product info from product with productId
            if (!empty($productIds)) {
                $productIds = array_values(array_unique($productIds));
                $len = count($productIds);

                $offset = 0;
                $where = ['_id' => ['$in' => $productIds]];

                $query = Product::find()->select(['_id', 'name'])->where($where);
                if ($len > $pageSize && $pageSize > 0) {
                    $offset = ($page - 1) * $pageSize;
                    $showData = $query->offset($offset)->limit($pageSize);
                }
                $showData = $query->all();
            }
        }
        $data = [
            'data' => $showData,
            'num' => empty($len) ? 0 : $len,
        ];
        return $data;
    }

    public static function renameTag($accountId, $name, $newName)
    {
        //add new tag to campaign
        Campaign::updateAll(['$addToSet' => ['tags' => $newName]], ['accountId' => $accountId, 'tags' => $name]);
        //remove old tags from campaign
        Campaign::updateAll(['$pull' => ['tags' => $name]], ['accountId' => $accountId, 'tags' => $name]);
    }
}
