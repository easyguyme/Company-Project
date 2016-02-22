<?php
namespace backend\modules\product\models;

use Yii;
use MongoDate;
use MongoId;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use backend\utils\LogUtil;
use backend\components\PlainModel;
use backend\components\ActiveDataProvider;

/**
 * Model class for campaign.
 *
 * The followings are the available columns in collection 'campaignLog':
 * @property MongoId    $_id
 * @property string     $code
 * @property MongoId    $productId
 * @property string     $productName
 * @property string     $campaignName
 * @property string     $sku
 * @property array      $member:{id,name,type,scoreAdded,score,prize}
 * @property array      $usedFrom:{id,name,type}
 * @property MongoId    $campaignId
 * @property MongoId    $accountId
 * @property MonogId    $productId
 **/
class CampaignLog extends PlainModel
{
    const CAMPAIGN_SCORE = 'score';
    const CAMPAIGN_LOTTERY = 'lottery';
    const MONGO_ID_LENGTH = 24;

    /**
     * Declares the name of the Mongo collection associated with campaignLog.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'campaignLog';
    }

    /**
     * Returns the list of all attribute names of campaignLog.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['code', 'productId', 'member', 'usedFrom', 'redeemTime', 'campaignId', 'productName', 'sku', 'operaterEmail', 'campaignName']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['code', 'productId', 'member', 'usedFrom', 'redeemTime', 'campaignId', 'productName', 'sku', 'operaterEmail', 'campaignName']
        );
    }

    /**
     * Returns the list of all rules of campaignLog.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['code', 'productId', 'member'], 'required'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into campaignLog.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'code',
                'campaignId' => function () {
                    return (string)$this->campaignId;
                },
                'member' => function () {
                    $member  = $this->member;
                    if ($member) {
                        $member['id'] .= '';
                    }
                    return $member;
                },
                'product' => function () {
                    return [
                            'id' => (string) $this->productId,
                            'name' => $this->productName,
                            'sku' => $this->sku
                        ];
                },
                'usedFrom',
                'redeemTime' => function () {
                    if (!empty($this->redeemTime)) {
                        return MongodbUtil::MongoDate2String($this->redeemTime, 'Y-m-d H:i:s');
                    } else {
                        return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                    }
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                }
            ]
        );
    }

    public static function createCondition($params, $accountId)
    {
        $comma = ',';
        $condition = ['accountId' => $accountId];

        if (!empty($params['code'])) {
            $condition = array_merge($condition, ['code' => $params['code']]);
        }

        if (!empty($params['memberId'])) {
            $condition = array_merge($condition, ['member.id' => new \MongoId($params['memberId'])]);
        }

        if (!empty($params['filter'])) {
            $condition = array_merge($condition, ['member.type' => ['$in' => $params['filter']]]);
        }

        if (array_key_exists('key', $params) && !empty($params['key'])) {
            $key = $params['key'];
            $key = StringUtil::regStrFormat(trim($key));
            $keyReg = new \MongoRegex("/$key/i");
            $search = [
                '$or' => [
                    ['code' => $keyReg],
                    ['member.name' => $keyReg],
                    ['member.phone' => $keyReg],
                    ['productName' => $keyReg],
                    ['sku' => $keyReg]
                ]
            ];
            $condition = array_merge($condition, $search);
        }

        if (!empty($params['startTime'])) {
            $condition['redeemTime']['$gte'] = new MongoDate(TimeUtil::ms2sTime($params['startTime']));
        }

        if (!empty($params['endTime'])) {
            $condition['redeemTime']['$lte'] = new MongoDate(TimeUtil::ms2sTime($params['endTime']));
        }

        if (!empty($params['campaignId'])) {
            $campaignIds = self::getCampaignIds($params['campaignId']);
            $condition['campaignId'] = ['$in' => $campaignIds];
        }

        if (!empty($params['accounts'])) {
            $accounts = explode($comma, $params['accounts']);

            $channelIds = [];
            foreach ($accounts as $account) {
                $channelIds[] = $account;
            }

            $channelCondition = [
                '$or' => [
                    ['usedFrom.id' => ['$in' => $channelIds]],
                    ['usedFrom.type' => ['$in' => $channelIds]]
                ]
            ];
            $condition = ['and', $condition, $channelCondition];
        }
        return $condition;
    }

    public static function getCampaignIds($campaignId)
    {
        $campaignIds = [];
        if (!empty($campaignId)) {
            $campaignIds = explode(',', $campaignId);
            foreach ($campaignIds as $key => $campaignId) {
                $campaignIds[$key] = new MongoId($campaignId);
            }
        }
        return $campaignIds;
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
        $condition = self::createCondition($params, $accountId);
        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);
        return new ActiveDataProvider(['query' => $query]);
    }

    /**
     *get the total  for the different type of campaigns
     *@param $memberId objectId     the member id
     */
    public static function getTypeTotal($memberId)
    {
        $where = ['member.id' => $memberId, 'member.type' => self::CAMPAIGN_SCORE];
        $scoreResults = CampaignLog::getCollection()->aggregate(
            [
                ['$match' => $where],
                ['$group' => ['_id' => '$member.id', 'scoreRecordNum' => ['$sum' => 1], 'scoreNum' => ['$sum' => '$member.scoreAdded']]],
            ]
        );
        //get the total score,the prize num is null now,beacause campaign only two types,they are score and lottery.
        $total = ['scoreNum'=> 0, 'prizeNum' => 0, 'scoreRecordNum' => 0];

        if (!empty($scoreResults)) {
            $total['scoreNum'] = $scoreResults[0]['scoreNum'];
            $total['scoreRecordNum'] = $scoreResults[0]['scoreRecordNum'];
        }
        return $total;
    }

    /**
     * get how many code to  exchange and how many scords to redeem
     * @param $codes
     */
    public static function getCodeRecord($codes)
    {
        if (!is_array($codes)) {
            $codes = explode(',', $codes);
        }
        $where = ['code' => ['$in' => $codes], 'member.type' => self::CAMPAIGN_SCORE];
        LogUtil::info(['msg' => 'get code and score', 'where' => $where], 'campaignLog');
        $results = CampaignLog::getCollection()->aggregate(
            [
                ['$match' => $where],
                ['$group' => [
                        '_id' => '$code',
                        'score' => ['$sum' => '$member.scoreAdded']
                    ]
                ]
            ]
        );
        LogUtil::info(['msg' => 'get struct', 'results' => $results], 'campaignLog');
        $codeCount = $scoreCount = 0;
        if (!empty($results)) {
            $codeCount = count($results);
            foreach ($results as $result) {
                $scoreCount += $result['score'];
            }
        }
        return [$codeCount, $scoreCount];
    }


    /**
     * add some data into the source data when export redeemed code
     * @param $data,array
     * @param $baseData,array
     */
    public static function preProcessRedeemedCodeData($campaignLog, $baseData)
    {
        $prize = $campaignLog['member']['type'];
        if ($prize == PromotionCode::CAMPAIGN_GIFT_SCORE) {
            $prize = $campaignLog['member']['scoreAdded']['$numberLong'];
        }

        //transform  telephone
        $tel = '';
        //if telephone first word is 0,show the 0
        if (isset($campaignLog['member']['phone'])) {
            $tel = $campaignLog['member']['phone'];
        }

        $createdAt = date('Y-m-d H:i:s', strtotime($campaignLog['createdAt']['$date']));
        if (isset($campaignLog['redeemTime']['$date']) && !is_string($campaignLog['redeemTime']['$date'])) {
            $redeemTime = $createdAt;
        } else {
            $redeemTime = !empty($campaignLog['redeemTime']['$date']) ? date('Y-m-d H:i:s', strtotime($campaignLog['redeemTime']['$date'])) : $createdAt;
        }
        $operaterEmail = isset($campaignLog['operaterEmail']) ? $campaignLog['operaterEmail'] : '';
        return [
            'id' => $campaignLog['_id']['$oid'],
            'cardNumber' => $campaignLog['member']['cardNumber'],
            'memberName' => $campaignLog['member']['name'],
            'tel' => $tel,
            'sku' => "'" . $campaignLog['sku'],//conver to string
            'productName' => empty($campaignLog['productName']) ? '' : $campaignLog['productName'],
            'code' => $campaignLog['code'],
            'prize' => $prize,
            'redeemTime' => $redeemTime,
            'createdAt' => $createdAt,
            'redeemptionChannelName' => Yii::t('common', $campaignLog['usedFrom']['type']),
            'campaignName' => empty($campaignLog['campaignName']) ? '' : $campaignLog['campaignName'],
            'backendUser' => $campaignLog['usedFrom']['type'] == self::PORTAL ? $operaterEmail : '',//only show the email when the member exchange code in offline
        ];
    }
}
