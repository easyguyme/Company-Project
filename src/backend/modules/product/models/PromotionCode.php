<?php
namespace backend\modules\product\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\Member;
use backend\utils\LogUtil;
use backend\modules\product\models\Campaign;
use backend\models\Account;
use backend\exceptions\InvalidParameterException;
use backend\models\User;
use backend\models\ReMemberCampaign;
use yii\web\ServerErrorHttpException;
use Yii;
use MongoDate;
use backend\modules\member\models\ScoreHistory;
use backend\modules\member\models\MemberLogs;
use backend\models\Channel;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/**
 * Model class for promotionCode
 *
 * @property MongoId    $_id
 * @property MongoId    $productId
 * @property string     $code
 * @property boolean    $isUsed
 * @property array      $usedBy:{memberId,memberNumber,channelId}
 * @property MongoDate  $usedAt
 * @property MongoId    $accountId
 * @property int        $random
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @property MongoId    $accountId
 */
class PromotionCode extends PlainModel
{
    const BIGGEST_COUNT = 100000;
    const PROMOTION_LOG = 'promotionCode';//path fo promotion code log
    const CAMPAIGN_GIFT_TIMES = 'times';
    const CAMPAIGN_GIFT_SCORE = 'score';
    const PROMOTION_CODE_EXCHANGE = 'portal';
    const PROMOTION_GIFT_TYPE_SCORE = 'score';

    const MEMBER_EXCHANGE_CODE = 'member_exchange_code';
    const CODE_STATUS_EXCEEDED = 'exceeded';
    const CODE_STATUS_REDEEMED = 'redeemed';
    const CODE_STATUS_EXPIRED = 'expired';
    const CODE_STATUS_INVALID = 'invalid';
    const CODE_STATUS_VALID = 'valid';

    const CHANNEL_NO_LIMIT = 'all';

    /**
     * Declares the name of the Mongo collection associated with promotionCode.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'promotionCode';
    }

    /**
     * Returns the list of all attribute names of promotionCode.
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
            ['productId', 'code', 'isUsed', 'usedBy', 'usedAt']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['productId', 'code', 'isUsed', 'usedBy', 'usedAt']
        );
    }
    /**
     * Returns a list of scenarios and the corresponding active attributes.
     * Add the 'createBasic' and 'addComponents' scenario and update 'update' scenario
     * @return array a list of scenarios and the corresponding active attributes.
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['productId', 'code'];
        return $scenarios;
    }

    /**
     * Returns the list of all rules of promotionCode.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['productId', 'code'], 'required'],
                ['isUsed', 'default', 'value' => false],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into promotionCode.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'productId' => function () {
                    return $this->productId . '';
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                'code', 'isUsed', 'usedBy', 'usedAt',
            ]
        );
    }

    /**
     * get score from campaign(a productId is suported to multi campaign)
     *
     **/
    public static function getCampaignScore($campaign)
    {
        if (!empty($campaign['promotion']['gift'])) {
            $gift = $campaign['promotion']['gift'];
            $type = $gift['type'];
            $config = $gift['config'];
            //only operate the type is score and the method is score
            if ($type == CampaignLog::CAMPAIGN_SCORE && $config['method'] == self::CAMPAIGN_GIFT_SCORE) {
                return $config['number'];
            }
        }
        return 0;
    }

    /**
     * exchange the campaign code
     * 1.check the code whether is vaild
     * 2.if the code is vaild,get the productId from the promotioncode,
     * then to find which campaign is fit with this product and add score for member and record log
     * 3.if code is invalid,throw a exception
     * @param $params, array, user to commit data to server
     * @param $accountId, MongoId, account id
     * @param $type, string, whitch chnanel that user to exchange
     */
    public static function exchangeCampaignCode($params, $accountId, $type = 'mobile')
    {
        $member = Member::findOne(['_id' => $params['memberId'], 'accountId' => $accountId]);
        if (empty($member) || $member->isDisabled) {
            throw new InvalidParameterException('无效的会员!');
        }
        //get productid
        $where = [
            'code' => $params['code'],
            'accountId' => $member->accountId,
            'isUsed' => false,
        ];
        $promotionCode = PromotionCode::findOne($where);
        if (empty($promotionCode)) {
            if ('mobile' == $type) {
                throw new InvalidParameterException(['exchange-tip' => '促销码无效!']);
            } else {
                return ['result' => 'error', 'message' => '促销码无效!'];
            }
        }
        $params['productId'] = $promotionCode->productId;

        //add a operation to clear cache  when member exchange code in offline
        if ($type != 'mobile') {
            self::clearExchangeRecord((string)$member->_id, '');
        }

        //add score for member and log log
        $isTip = $type == 'mobile' ? true : false;

        //whether have campaign to redeem this code
        list($result, $data) = self::getCode2Campaign($params, $member);
        LogUtil::info(['message' => 'whether have campaign to redeem this code', 'result' => $result, 'number' => count($data), 'params' => $params], 'exchangeCode');

        //update promotion code
        if ($result) {
            $row = self::updatePromotionCode($params, $member, $promotionCode->_id);
            LogUtil::info(['message' => 'update promotion code', 'affectRow' => $row, 'promotionCode' => Json::encode($promotionCode->toArray()), 'member' => Json::encode($member->toArray()), 'params' => $params], 'exchangeCode');
        }
        if ($result && $row == 1) {
            //redeem code in every camoaign, if the result is true and row is 1
            return self::redeemCampaign($params, $data, $member, true, $isTip);
        } else {
            if ('mobile' == $type) {
                throw new InvalidParameterException(['exchange-tip' => $data]);
            } else {
                LogUtil::error(['message' => 'Faild to exchange code', 'code' => $code, 'result' => $result, 'row' => $row], 'product');
                return ['result' => 'error', 'message' => $data];
            }
        }
    }

    /**
     * update promotion code
     * @param $params, array, argments fron request
     * @param $member, array, member info
     * @param $promotionCodeId, MongoId
     */
    public static function updatePromotionCode($params, $member, $promotionCodeId)
    {
        $update = [
            'isUsed' => true,
            'usedAt' => new MongoDate(),
            'usedBy' => [
                'memberId' => $params['memberId'],
                'memberNumber' => $member['cardNumber'],
                'channelId' => empty($params['channelId']) ? '' : $params['channelId'],
            ]
        ];

        return PromotionCode::updateAll($update, ['_id' => $promotionCodeId, 'isUsed' => false]);
    }

    /**
     * get the condition for search campaign
     * @return array
     * @param $params, array
     */
    public static function getCondition2Campaign($params)
    {
        $where = [];
        if (!empty($params['exchangeTime'])) {
            //exchange code in offline and pass exchangeTime
            $exchangeTime = new MongoDate($params['exchangeTime']);
        } else {
            $exchangeTime = new MongoDate();
            $where['isActivated'] = true;
        }
        $condition = [
            'promotion.data' => ['$all' => [$params['productId']]],
            'startTime' => ['$lte' => $exchangeTime],
            'endTime' => ['$gte' => $exchangeTime],
        ];
        $where = array_merge($condition, $where);
        unset($condition);
        return $where;
    }

     /**
     * get campaign whicth meet the campaign condition for member
     * @return list,[boolean, mixed] mixed is string or array
     * @param $params, array, arguments from request
     * @param $member, array, member info
     */
    public static function getCode2Campaign($params, $member)
    {
        //get campaign
        $where = self::getCondition2Campaign($params);
        $campaigns = Campaign::findAll($where);

        if (!empty($campaigns)) {
            foreach ($campaigns as $campaign) {
                //check the time for campaign is over,have exchangetime to exchage or the campaign is runing and the status is active
                $endTime = MongodbUtil::MongoDate2TimeStamp($campaign->endTime);
                if (time() > $endTime || (time() < $endTime && $campaign->isActivated == true)) {
                    //get campaign
                    $response = self::selectCampaign($campaign, $member, $params, false);
                    if (!empty($response['campaign'])) {
                        return [true, $campaigns];
                    }
                }
            }
            $message = isset($response['message']) ? $response['message'] : '';
        } else {
            $message = Yii::t('product', 'promotioncode_invalid');
        }
        return [false, $message];
    }


     /**
     * select campaign by exchangeTime and productId
     * 1.if the exchange time is exists:
     * 1.1 the code exchange in the offline
     * 1.2 the code exchange in the phone
     * @param $param, array, argmemts from request
     * @param $member, array, member info
     * @param $isLog,boolean, whether to recode log
     * @param $isTip,boolean, whether to give user a tip
     * */
    public static function redeemCampaign($params, $campaigns, $member, $islog = true, $isTip = false)
    {
        $totalScore = 0;
        //set this param to know whether have campaign meet member condition in many campaigns
        $flag = false;

        if (!empty($campaigns)) {
            foreach ($campaigns as $campaign) {
                //check the time for campaign is over
                $score = 0;
                $endTime = MongodbUtil::MongoDate2TimeStamp($campaign['endTime']);
                if (time() > $endTime || (time() < $endTime && $campaign['isActivated'] == true)) {
                    $response = self::selectCampaign($campaign, $member, $params);
                    if (!empty($response['campaign'])) {
                        //get score from campaign
                        if ($campaign['promotion']['gift']['type'] == CampaignLog::CAMPAIGN_SCORE) {
                            $score = self::getCampaignScore($campaign);
                        }
                        //whether to record log
                        if ($islog) {
                            //add member score and record campaign log
                            self::recordSelectedCampaign($params, $member, $campaign, $score);
                        }
                        $flag = true;
                    }
                }
                $totalScore += $score;
            }
            if ($flag == false) {
                $message = $isTip ? ['exchange-tip' => '产品码已失效，如有疑问，请联系客服。'] : '产品码已失效，如有疑问，请联系客服。';
                return ['result' => 'error', 'message' => $message];
            }
        } else {
            //if the campaign is empty,judge to notice the member
            if ($isTip) {
                return ['result' => 'error', 'message' => ['exchange-tip' => '产品码已失效，如有疑问，请联系客服。']];
            }
        }
        //not record log
        if (false == $islog) {
            return $totalScore;
        } else {
            return ['result' => 'success', 'message' => 'exchange code successful'];
        }
    }

    /**
     * check the member tag in campaign
     * @return array ,if the member meet the condition,just return a empty array,otherwise this funtion will
     * return a array(structure: campaign:campaign data, exchangeFailReason: the reason for exchange code failly,
     * campaignExchangeOver: the reason for guess to exchange code failly)
     * @param $member, object, member info
     * @param $campaign, object, campaign object
     */
    public static function checkCampaignMemberTag($campaign, $member)
    {
        if (!empty($campaign->promotion['tags'])) {
            if (empty($member->tags)) {
                $msg = Yii::t('product', 'member_tag_limit');
                LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
                return ['campaign' => [], 'status' => self::CODE_STATUS_INVALID, 'message' => $msg];
            }
            $flag = false;
            foreach ($member->tags as $tag) {
                if (in_array($tag, $campaign->promotion['tags'])) {
                    $flag = true;
                    break;
                }
            }
            if (false === $flag) {
                $msg = Yii::t('product', 'member_tag_limit');
                LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
                return ['campaign' => [], 'status' => self::CODE_STATUS_INVALID, 'message' => $msg];
            }
        }
        return ['campaign' => $campaign];
    }

    /**
     * check the member experice for every campaign
     * @return array
     * @param $campaign, object, campaign info
     * @param $member, object, member object
     */
    public static function checkMemberExperice2Campaign($campaign, $member)
    {
        if (isset($campaign->promotion['products'])) {
            $promotionProducts = $campaign->promotion['products'];
            $msg = Yii::t('product', 'campaign_experience_limit');
            switch ($promotionProducts) {
                case Campaign::TYPE_GIFT_PRODUCT_UNLIMITED:
                    // do nothing
                    break;
                case Campaign::TYPE_GIFT_PRODUCT_FIRST:
                    $campaignLogCount = CampaignLog::count([
                        'member.id' => $member->_id
                    ]);
                    if ($campaignLogCount > 0) {
                        LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
                        return ['campaign' => [], 'status' => self::CODE_STATUS_INVALID, 'message' => $msg];
                    }
                    break;
                default:
                    if (is_array($promotionProducts) && !empty($promotionProducts)) {
                        // take part in the campaign before
                        $where = [
                            'productId' => ['$in' => $promotionProducts],
                            'member.id' => $member->_id
                        ];
                        $memberRecord = CampaignLog::findOne($where);
                        if (empty($memberRecord)) {
                            LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
                            return ['campaign' => [], 'status' => self::CODE_STATUS_INVALID, 'message' => $msg];
                        }
                    } else {
                        // unknown limit
                        $msg = 'unknown limit';
                        LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
                        return ['campaign' => [], 'status' => self::CODE_STATUS_INVALID, 'message' => $message];
                    }
                    break;
            }
        }
        return ['campaign' => $campaign];
    }

    /**
     * check the campaign limitTimes
     */
    public static function checkCampaignLimitTimes($campaign, $member, $num)
    {
        $msg = Yii::t('product', 'exchange_code_limit');
        if (!empty($campaign->limitTimes) && $num >= $campaign->limitTimes) {
            $msg = str_replace('#number#', $campaign->limitTimes, $msg);
            LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
            return ['campaign' => [], 'status' => self::CODE_STATUS_EXCEEDED, 'message' => $msg];
        }
        return ['campaign' => $campaign];
    }

    /**
     * check campaign limit times in advance
     */
    public static function preCheckCampaignLimitTimes($campaign, $member, $num)
    {
        //record data in redis when member check code vaild in offline
        $redis = Yii::$app->cache->redis;
        $key = $member->_id . $campaign->_id;
        $redisNum = $redis->HGET(self::MEMBER_EXCHANGE_CODE, $key);
        $num += $redisNum;
        if (!empty($campaign->limitTimes) && $num > $campaign->limitTimes) {
            $msg = Yii::t('product', 'exchange_code_limit');
            $msg = str_replace('#number#', $campaign->limitTimes, $msg);
            LogUtil::info(['msg' => $msg, 'situation' => 'offline', 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
            return ['campaign' => [], 'status' => self::CODE_STATUS_EXCEEDED, 'message' => $msg];
        }
        return ['campaign' => $campaign];
    }

    public static function checkCampaignParticipantCount($campaign, $member, $memberIds)
    {
        $total = count($memberIds);
        if (!in_array($member->_id, $memberIds) && !empty($campaign->participantCount) && $campaign->participantCount <= $total) {
            $msg = Yii::t('product', 'participant_count_limit');
            LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
            return ['campaign' => [], 'status' => self::CODE_STATUS_EXCEEDED, 'message' => $msg];
        }
        return ['campaign' => $campaign];
    }

    public static function checkCampaignChannel($campaign, $member, $params)
    {
        if (!empty($campaign->promotion['channels'])) {
            $origin = PromotionCode::PORTAL;
            if (!empty($params['channelId'])) {
                $origin = $params['channelId'];
            }
            //all
            if (!in_array(self::CHANNEL_NO_LIMIT, $campaign->promotion['channels']) && !in_array($origin, $campaign->promotion['channels'])) {
                $msg = Yii::t('product', 'campaign_channel_limit');
                LogUtil::info(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id], self::PROMOTION_LOG);
                return ['campaign' => [], 'status' => self::CODE_STATUS_INVALID, 'message' => $msg];
            }
        }
        return ['campaign' => $campaign];
    }

    /**
     * check member whether to fit with the condition of campaign,
     * if the member fit with the condition,then this function will return the campaign
     * otherwise it will return a empty array
     * @param $campaign, array, campaign info
     * @param $member , array, member info
     * @param $createReMemberCampaign, boolean, if the value is true, this function will create a record
     * in the table called ReMemberCampaign.when you check the condition for the campaign and you do not want to
     * exchange code,this value you can pass false
     */
    public static function selectCampaign($campaign, $member, $params, $createReMemberCampaign = true)
    {
        //record fail message
        $msg = '';
        $accountId = $member->accountId;
        //check the total member to take part in campaign
        $memberIds = CampaignLog::getCollection()->distinct("member.id", ['campaignId' => $campaign->_id]);

        if ($createReMemberCampaign) {
            self::createReMemberCampaign($campaign, $member, $accountId);
        } else {
            //check the limiter and participantCount
            $num = CampaignLog::count([
                'campaignId' => $campaign->_id,
                'member.id' => $member->_id
            ]);
            //check campaign limit times
            $result = self::checkCampaignLimitTimes($campaign, $member, $num);
            if (empty($result['campaign'])) {
                return $result;
            }
            //check the limit time in advance whnen check the limit in offline
            $result = self::preCheckCampaignLimitTimes($campaign, $member, $num);
            if (empty($result['campaign'])) {
                return $result;
            }
            //check the campaign participant count
            $result = self::checkCampaignParticipantCount($campaign, $member, $memberIds);
            if (empty($result['campaign'])) {
                return $result;
            }
        }

        // check the tags of member
        $result = self::checkCampaignMemberTag($campaign, $member);
        if (empty($result['campaign'])) {
            return $result;
        }

        // check the channels of member
        $result = self::checkCampaignChannel($campaign, $member, $params);
        if (empty($result['campaign'])) {
            return $result;
        }

        // check the experice
        $result = self::checkMemberExperice2Campaign($campaign, $member);
        if (empty($result['campaign'])) {
            return $result;
        }

        //when user redeem the code.we need operate the participate and limit for per-person
        if ($createReMemberCampaign) {
            $result = self::recordCampaignLimit($campaign, $member, $accountId, $memberIds);
            if (!empty($result['campaign'])) {
                return $result;
            }
        }

        return ['campaign' => $campaign, 'status' => self::CODE_STATUS_VALID, 'message' => 'code is vaild'];
    }


    public static function createReMemberCampaign($campaign, $member, $accountId)
    {
        // check and add the relation for member and campaign
        $condition = ['campaignId' => $campaign->_id, 'memberId' => $member->_id, 'accountId' => $accountId];
        $reMemberCampaign = ReMemberCampaign::findOne($condition);
        if (empty($reMemberCampaign)) {
            $reMemberCampaign = new ReMemberCampaign();
            $reMemberCampaign->memberId = $member->_id;
            $reMemberCampaign->campaignId = $campaign->_id;
            $reMemberCampaign->usedTimes = 0;//empty($campaign->limitTimes) ? Campaign::MAX_COUNT : $campaign->limitTimes
            $reMemberCampaign->accountId = $accountId;
            $reMemberCampaign->save();
        }
    }

    /**
     * when user redeem the code.we need operate the participate and limit for per-person
     * @return array
     */
    public static function recordCampaignLimit($campaign, $member, $accountId, $memberIds)
    {
        $limitTimes = empty($campaign->limitTimes) ? Campaign::MAX_COUNT : $campaign->limitTimes;
        $reAttributes = ['$inc' => ['usedTimes' => 1]];
        $reCondition = ['campaignId' => $campaign->_id, 'memberId' => $member->_id, 'usedTimes' => ['$lt' => $limitTimes], 'accountId' => $accountId];
        $reUpdateResult = ReMemberCampaign::updateAll($reAttributes, $reCondition);
        if ($reUpdateResult === 0) {
            $msg = Yii::t('product', 'campaign_participate_freq_limit');
            LogUtil::error(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id, 'reAttributes' => $reAttributes, 'reCondition' => $reCondition], self::PROMOTION_LOG);
            return ['campaign' => [], 'status' => self::CODE_STATUS_EXCEEDED, 'message' => $msg];
        }
        //when the member take part in this campaign how many times,just only to count to 1
        if (!in_array($member->_id, $memberIds)) {
            $participantCount = empty($campaign->participantCount) ? Campaign::MAX_COUNT : $campaign->participantCount;
            $attributes = ['$inc' => ['usedCount' => 1]];
            $conditions = ['_id' => $campaign->_id, 'usedCount' => ['$lt' => $participantCount], 'accountId' => $accountId];
            $updateResult = Campaign::updateAll($attributes, $conditions);
            if ($updateResult === 0) {
                // rollback the reMemberCampaign remaining times
                $reAttributes = ['$inc' => ['usedTimes' => -1]];
                $reCondition = ['campaignId' => $campaign->_id, 'memberId' => $member->_id, 'accountId' => $accountId];
                ReMemberCampaign::updateAll($reAttributes, $reCondition);

                $msg = Yii::t('product', 'campaign_participate_limit');
                LogUtil::error(['msg' => $msg, 'memberId' => (string)$member->_id, 'campaignId' => (string)$campaign->_id, 'reAttributes' => $reAttributes, 'reCondition' => $reCondition], self::PROMOTION_LOG);
                return ['campaign' => [], 'status' => self::CODE_STATUS_EXCEEDED, 'message' => $msg];
            }
        }
        return ['campaign' => $campaign];
    }

    /**
     * @param $scoreAdd, int
     * @param $memberId, mongoId
     */
    public static function addMemberScore($scoreAdded, $memberId)
    {
        $scoreAdded = intval($scoreAdded);
        $data = [
            '$inc' => [
                'score' => $scoreAdded,
                'totalScore' => $scoreAdded,
                'totalScoreAfterZeroed' => $scoreAdded
            ]
        ];

        Member::updateAll($data, ['_id' => $memberId]);
    }

    /**
     * record log for exchange promotion code in a campaign
     * @param $accountid objectId
     * @param $params arrray
     * @param $member object
     * @param $campaign object
     * @param $scoreAdded int
     */
    public static function recordSelectedCampaign($params, $member, $campaign, $scoreAdded)
    {
        //add score for member
        self::addMemberScore($scoreAdded, $params['memberId']);

        // add tags to member
        self::addMemberTags($campaign, $params['memberId']);

        $productInfo = Product::findByPk($params['productId']);
        $scoreHistoryDescription = $productInfo->name . ' ' . $params['code'];

        $channelInfo = self::getChannelInfo($params);
        //record campaign log
        $campaignLog = self::setCampaignLog($scoreAdded, $params, $member, $campaign, $productInfo, $channelInfo);

        //recode the scoreHistory
        if ($scoreAdded > 0) {
            self::setScoreHistory($scoreAdded, $member, $scoreHistoryDescription, $channelInfo['scoreHistoryChannel'], $params);
        }
        //record member log
        MemberLogs::record($member->_id, $member->accountId, MemberLogs::OPERATION_REDEEM, $campaignLog->redeemTime);
        unset($params, $productInfo, $member, $campaignLog);
    }

    public static function getChannelInfo($params)
    {
        $scoreHistoryChannel = [];
        if (!empty($params['channelId'])) {
            $channelInfo = Channel::getByChannelId($params['channelId']);
            $scoreHistoryChannel = [
                'id' => $channelInfo->channelId,
                'name' => $channelInfo->name,
                'origin' => $channelInfo->origin
            ];
            $channelType = $channelInfo->origin;
            $channelName = $channelInfo->name;
        } else {
            $scoreHistoryChannel = ['origin' => PromotionCode::PORTAL];
            $channelName = '';
            $channelType = PromotionCode::PROMOTION_CODE_EXCHANGE;
        }
        return [
            'scoreHistoryChannel' => $scoreHistoryChannel,
            'channelName' => $channelName,
            'channelType' => $channelType,
        ];
    }

    /**
     * record campaign log
     * @return object, campaign log object
     */
    public static function setCampaignLog($scoreAdded, $params, $member, $campaign, $productInfo, $channelInfo)
    {
        //record a log
        $data = [
            'accountId' => $member->accountId,
            'code' => $params['code'],
            'productId' => $params['productId'],
            'productName' => $productInfo->name,
            'campaignName' => $campaign->name,
            'sku' => $productInfo->sku,
            'operaterEmail' => !empty($params['operaterEmail']) ? $params['operaterEmail'] : '',
        ];

        $properties = $member->properties;
        $name = $phone = '';
        foreach ($properties as $property) {
            if ('name' == $property['name']) {
                $name = $property['value'];
            } else if ('tel' == $property['name']) {
                $phone = $property['value'];
            }
        }

        //get the type of promocode and the name of prize
        $type = $prize = '';
        if (!empty($campaign->promotion['gift'])) {
            $gift = $campaign->promotion['gift'];
            $type = $gift['type'];
            //get the name of prize TODO
        }

        $data['member'] = [
            'id' =>  $params['memberId'],
            'cardNumber' => $member->cardNumber,
            'scoreAdded' => intval($scoreAdded),
            'score' => $member->score,
            'name' => $name,
            'phone' => $phone,
            'type' => $type,
            'prize' => $prize,
        ];

        //add a field to show the member when to exchage code
        $data['redeemTime'] = new MongoDate();
        if (!empty($params['exchangeTime'])) {
            $data['redeemTime'] = new MongoDate($params['exchangeTime']);
        }

        $data['usedFrom'] = [
            'id' => isset($params['channelId']) ? $params['channelId'] : '',
            'name' => $channelInfo['channelName'],
            'type' => $channelInfo['channelType']
        ];

        $data['campaignId'] = $campaign->_id;

        $campaignLog = new CampaignLog();
        $campaignLog->load($data, '');
        if (false === $campaignLog->save()) {
            LogUtil::error(['message'=> 'fail to record log', 'params' => $params, 'member' => $member, 'campaign' => $campaign], self::PROMOTION_LOG);
        }
        return $campaignLog;
    }

    /**
     * add tags for member who joined the party
     * @param $memberId mongoId
     * @param $campaign object
     */
    private static function addMemberTags($campaign, $memberId)
    {
        if ($campaign->isAddTags === false || count($campaign->userTags) == 0) {
            return false;
        }
        $conditions = [
            '_id' => $memberId,
            'accountId' => $campaign->accountId
        ];
        $updateTags['$addToSet'] = ['tags' => ['$each' => $campaign->userTags]];
        $updateMemberResult = Member::updateAll($updateTags, $conditions);

        if (!$updateMemberResult) {
            LogUtil::error(['message' => 'Failed to add member`s tags', 'data' => $updateTags]);
        }
    }

    public static function setScoreHistory($scoreAdded, $member, $scoreHistoryDescription, $channel, $params)
    {
        $history = [
            'assigner' => ScoreHistory::ASSIGNER_EXCHANGE_PROMOTION_CODE,
            'increment' => intval($scoreAdded),
            'memberId' => $member->_id,
            'accountId' => $member->accountId,
            'description' => $scoreHistoryDescription,
            'brief' => ScoreHistory::ASSIGNER_EXCHANGE_PROMOTION_CODE,
            'channel' => $channel,
            'user' => $params['userInfo']
        ];
        ScoreHistory::recordScore($history);
    }

    public static function getByProductId($productId)
    {
        return self::findOne(['productId' => $productId]);
    }

    /**
     * Generate codes
     * @param int $count
     * @param array $codes
     * @return array
     */
    public static function generateCodes($count, $productId)
    {
        $prefix = self::_generateCodePrefix($productId);
        $biggestPromotionCode = self::find()->where(['productId' => $productId, 'code' => new \MongoRegex("/^$prefix/i")])->orderBy(['code' => SORT_DESC])->one();
        if (!empty($biggestPromotionCode)) {
            $biggestCode = $biggestPromotionCode->code;
            $code36 = substr($biggestCode, -4);
            $code10 = base_convert($code36, 36, 10);
        } else {
            $code10 = 0;
        }

        $codes = [];
        $code = $code10;
        $i = 1;
        while ($i <= $count) {
            $codeAdd = rand(1, 5);//skip a rand number to make the code looks like random number.
            $code += $codeAdd;
            $code36 = self::_zerofill(4, $code);
            //skip if code's last char is a number
            if (base_convert($code36[3], 36, 10) >= 10) {
                $tempCode = self::_encryCode($prefix, $code36);
                //skip if code include char 'O'
                if (stripos($code36, 'o') === false && stripos($code36, 'e') === false) {
                    $codes[] = $tempCode;
                    $i++;
                }
            }
        }

        return $codes;
    }

    private static function _encryCode($prefix, $codeIndex)
    {
        $code = $prefix . $codeIndex[0] . $codeIndex[1] .$codeIndex[3] . $codeIndex[2];
        return strtoupper($code);
    }

    public static function _generateCodePrefix($productId)
    {
        $year = date('y');
        $year = $year-10;//start from 2015
        $yearPrefix = self::_getPrefix(1, $year);
        $product = Product::findByPk($productId);
        $productIndex = Product::find()->where(['createdAt' => ['$lte' => $product->createdAt], 'accountId' => $product->accountId])->orderBy(['createdAt' => SORT_ASC])->count();
        $productPrefix = self::_getPrefix(2, $productIndex);
        $batchPrefix = self::_getPrefix(1, $product->batchCode);

        return $yearPrefix . $productPrefix . $batchPrefix;
    }

    private static function _zerofill($length, $number)
    {
        $numberString = base_convert($number, 10, 36);

        $numberLen = strlen($numberString);
        if ($length >= $numberLen) {
            $count = $length - $numberLen;
            for ($i = 0; $i < $count; $i++) {
                $numberString = '0' . $numberString;
            }
        }
        return $numberString;
    }

    private static function _getPrefix($length, $number)
    {
        $map = '0123456789abcdfghijklmnpqrstuvwxyz';
        $mapLen = strlen($map);

        $firstNum = (int) floor($number / $mapLen);
        $lastNum = $number % $mapLen;

        if ($length == 1) {
            return $map[$lastNum];
        } else {
            // product count greater than 35*35 = 1225
            if ($firstNum >= 36) {
                return 'zz';
            }
            return $map[$firstNum] . $map[$lastNum];
        }
    }

    public static function countByProductIds($productIds, $isUsed = null)
    {
        $condition = ['productId' => ['$in' => $productIds]];
        if ($isUsed !== null) {
            $condition = array_merge($condition, ['isUsed' => $isUsed]);
        }

        return self::count($condition);
    }

    public static function countByProductIdAndCreatedAt($productId, $createdAt, $isUsed = null)
    {
        $condition = ['productId' => $productId, 'createdAt' => $createdAt];
        if ($isUsed !== null) {
            $condition = array_merge($condition, ['isUsed' => $isUsed]);
        }

        return self::count($condition);
    }

    public static function getHistoryByProduct($productId)
    {
        $raws = self::getCollection()->aggregate(
            [
                ['$match' => ['productId' => $productId]],
                ['$group' => ['_id' => ['createdAt' => '$createdAt', 'isUsed' => '$isUsed'], 'count' => ['$sum' => 1]]],
                ['$sort' => ['createdAt' => -1]]
            ]
        );

        $raws = empty($raws) ? [] : $raws;

        //check th campaign
        $enable = true;
        $campaignWhere = [
            'promotion.data' => ['$all' => [$productId]],
            'isActivated' => true
        ];
        $campaign = Campaign::findOne($campaignWhere);
        if (!empty($campaign)) {
            $enable = false;
        }

        $result = [];
        foreach ($raws as $raw) {
            $createdAt = $raw['_id']['createdAt'];
            $timestampCreatedAt = MongodbUtil::MongoDate2TimeStamp($createdAt);
            $strCreatedAt = MongodbUtil::MongoDate2String($createdAt, 'Y-m-d H:i:s');
            $result[$timestampCreatedAt]['createdAt'] = $strCreatedAt;
            $result[$timestampCreatedAt]['all'] = empty($result[$timestampCreatedAt]['all']) ? 0 : $result[$timestampCreatedAt]['all'];
            $result[$timestampCreatedAt]['timestamp'] = $timestampCreatedAt;
            $result[$timestampCreatedAt]['used'] = empty($result[$timestampCreatedAt]['used']) ? 0 : $result[$timestampCreatedAt]['used'];
            $result[$timestampCreatedAt]['rest'] = empty($result[$timestampCreatedAt]['rest']) ? 0 : $result[$timestampCreatedAt]['rest'];
            if ($raw['_id']['isUsed']) {
                $result[$timestampCreatedAt]['used'] = $raw['count'];
            } else {
                $result[$timestampCreatedAt]['rest'] = $raw['count'];
            }
            $result[$timestampCreatedAt]['all'] += $raw['count'];
            $result[$timestampCreatedAt]['enable'] = $enable;
        }
        $result = array_values($result);
        ArrayHelper::multisort($result, 'createdAt', SORT_DESC);
        return $result;
    }

    /**
     * add some data into the source data
     * @param $promotionCodes,array
     * @param $product,array
     */
    public static function preProcessCodeData($promotionCode, $product)
    {
        $vaild = $product['status']['vaild'];
        $unvaild = $product['status']['unvaild'];
        $printData = [];
        $printData[] = [
            'code' => $promotionCode['code'],
            'sku' => $product['sku'],
            'isUsed' => $promotionCode['isUsed'] ? $unvaild : $vaild
        ];
        unset($promotionCode, $product, $vaild, $unvaild);
        return $printData;
    }

    /**
     * get redeem code and redeem member id
     * @return array ['code' => x, 'memberIds' => x]
     * @param
     */
    public static function getRedeemCodeAndRedeemMemberId($redeemedCodes)
    {
        $redeemed = $redeemedMemberId = [];
        if (!empty($redeemedCodes)) {
            foreach ($redeemedCodes as $redeemedCode) {
                $redeemed[] = $redeemedCode['code'];
                if (!empty($redeemedCode['usedBy']['memberId'])) {
                    $redeemedMemberId[] = $redeemedCode['usedBy']['memberId'];
                }
            }
        }
        return ['code' => $redeemed, 'memberIds' => $redeemedMemberId];
    }

    /**
     * get the codes which are not been redeemed
     *
     */
    public static function getNotRedeemCode($unRedeemedCodes, $accountId)
    {
        $condition = ['code' => ['$in' => array_values($unRedeemedCodes)], 'accountId' => $accountId, 'isUsed' => false];
        return PromotionCode::findAll($condition);
    }

    public static function getInvalidCodeBetweenValidCodeAndCampaign($campaigns, $validCode)
    {
        $campaignCode = [];
        foreach ($campaigns as $campaign) {
            foreach ($validCode as $code => $productId) {
                if (in_array($productId, $campaign->promotion['data'])) {
                    $campaignCode[] = $code;
                    //this code->productId you can find in campaign
                    $campaignCodeProductId[$code] = $productId;
                }
            }
        }
        $invalid = array_merge($checkInvalidCode, array_diff(array_keys($validCode), array_values($campaignCode)));

    }

    public static function getValidCodeInfoFromPromotionCode($promocodes)
    {
        $validCodeProductId = [];
        foreach ($promocodes as $promocode) {
            $validCodeProductId[$promocode->code] = $promocode->productId;
        }
        return $validCodeProductId;
    }

    public static function getNotExitsCode($unRedeemedCodes, $validCodeProductId)
    {
        return array_values(array_diff($unRedeemedCodes, $validCodeProductId));
    }

    public static function getCodeInfoFitWithCampaign($campaigns, $validCodeProductId)
    {
        $campaignCodeProductId = [];
        foreach ($campaigns as $campaign) {
            foreach ($validCodeProductId as $code => $productId) {
                if (in_array($productId, $campaign->promotion['data'])) {
                    $campaignCodeProductId[$code] = $productId;
                }
            }
        }
        return $campaignCodeProductId;
    }


    /**
     * check the status for code in offline
     * @param $codes,array
     * @param $accountId,objectId
     * @param $member,object
     * @param $exchangeTime,mongoDate
     */
    public static function checkCodeStatus($codes, $accountId, $member, $exchangeTime, $params)
    {
        $invalid = $redeemed = $validCampaignCode = $redeemedMemberId = [];
        //check code redeem
        $condition = ['code' => ['$in' => $codes], 'isUsed' => true, 'accountId' => $accountId];
        //get member info when code is redeemed
        $redeemedCodes = PromotionCode::findAll($condition);
        $redeemInfo = self::getRedeemCodeAndRedeemMemberId($redeemedCodes);
        $redeemed = $redeemInfo['code'];
        $redeemedMemberId = $redeemInfo['memberIds'];

        if (count($codes) != count($redeemed)) {
            //if the code are not all redeem, we need select campaign
            //1.get promotioncode which is vaild
            $unRedeemedCodes = array_diff($codes, $redeemed);
            //1.code is valid,2.code is not exists in collection
            $promocodes = self::getNotRedeemCode($unRedeemedCodes, $accountId);
            if (empty($promocodes)) {
                //if the promocodes is empty,the code all are invalid
                $invalid = $unRedeemedCodes;
            } else {
                //validCodeInfo struct is a array,key is code value is productId
                $validCodeProductId = self::getValidCodeInfoFromPromotionCode($promocodes);
                $validCode = array_keys($validCodeProductId);
                $validProductId = array_values($validCodeProductId);

                $notExistsCode = self::getNotExitsCode($unRedeemedCodes, $validCode);

                //get all campaign
                $condition = ['promotion.data' => ['$in' => $validProductId], 'accountId' => $accountId];
                $campaigns = Campaign::findAll($condition);
                if (empty($campaigns)) {
                    $invalid = $validCode;
                } else {
                    //campaignCodeProductId is array.key is code value is productId
                    $campaignCodeProductId = self::getCodeInfoFitWithCampaign($campaigns, $validCodeProductId);
                    //merge not exists code and code whitch can not find any campaigns
                    $invalid = array_merge($notExistsCode, array_diff($validCode, array_keys($campaignCodeProductId)));

                    if (!empty($campaignCodeProductId)) {
                        //order the code with source code,first-in first-out
                        $campaignCodeProductId = self::orderCode($codes, $campaignCodeProductId);
                        $validCampaignCode = self::checkCode2CampainStatus($campaignCodeProductId, $member, $exchangeTime, $params);
                    }
                }
            }
        }
        $codes = [];
        if (!empty($invalid)) {
            foreach ($invalid as $invalidCode) {
                $codes[$invalidCode] = [
                    'code' => $invalidCode,
                    'status' => self::CODE_STATUS_INVALID,
                    'score' => 0,
                    'description' => Yii::t('product', 'system_not_exists'),
                ];
            }
        }
        if (!empty($redeemed)) {
            $codes = array_merge($codes, self::setMemberName($redeemedMemberId, $redeemedCodes));
        }
        return array_merge($codes, $validCampaignCode);
    }

    /**
     * add member name for redeem code
     * @param $redeemedMemberId, array
     * @param $redeemedCodes, array
     */
    public static function setMemberName($redeemedMemberId, $redeemedCodes)
    {
        $codes = [];
        $condition = ['_id' => ['$in' => $redeemedMemberId]];
        $members = Member::getAllMember($condition);
        foreach ($redeemedCodes as $redeemedCode) {
            $memberId = empty($redeemedCode['usedBy']['memberId']) ? '' : (string)$redeemedCode['usedBy']['memberId'];
            $memberName = '';
            foreach ($members as $member) {
                if ((string)$member->_id == $memberId) {
                    if (!empty($member->properties)) {
                        foreach ($member->properties as $propertie) {
                            if ($propertie['name'] == Member::DEFAULT_PROPERTIES_NAME) {
                                $memberName = $propertie['value'];
                            }
                        }
                    }
                    //if the member is deleted and set the memberId is ''
                    if ($member->isDeleted == true) {
                        $memberId = '';
                    }
                }
            }
            $codes[$redeemedCode['code']] = [
                'code' => $redeemedCode['code'],
                'status' => self::CODE_STATUS_REDEEMED,
                'score' => 0,
                'description' => Yii::t('product', 'code_redeemed'),
                'memberId' => $memberId,
                'memberName' => $memberName,
            ];
        }
        return $codes;
    }

    public static function getCodeIsVaild($campaign, $exchangeTime)
    {
        $currentTime = new MongoDate(time());
        if ($campaign->endTime < $exchangeTime) {
            return [self::CODE_STATUS_EXPIRED, Yii::t('product', 'code_expired')];

        } else if ($campaign->startTime > $exchangeTime) {
            return [self::CODE_STATUS_INVALID, Yii::t('product', 'campaign_not_start')];

        } else if ($campaign->startTime <= $exchangeTime
            && $campaign->endTime >= $exchangeTime
            && $campaign->isActivated === false
            && $campaign->endTime >= $currentTime) {
            //the exchangeTime is between start time and end time,
            //but the isActivated is false,if the endTime time $gt current time,member does not exchange code whitch campaign is forbidden
            return [self::CODE_STATUS_INVALID, Yii::t('product', 'campaign_not_start')];

        } else {
            return [self::CODE_STATUS_VALID, 'code is vaild'];
        }
    }

    /**
     * check the status of codes in campaign
     */
    public static function checkCode2CampainStatus($campaignCodeProductId, $member, $exchangeTime, $params)
    {
        if (empty($campaignCodeProductId)) {
            return [];
        }
        $invalid = $over = $vaild = $expired = [];
        $accountId = $member->accountId;

        foreach ($campaignCodeProductId as $code => $productId) {
            $campaigns = Campaign::findAll(['promotion.data' => ['$all' => [$productId]], 'accountId' => $accountId]);
            foreach ($campaigns as $campaign) {
                list($status, $message) = self::getCodeIsVaild($campaign, $exchangeTime);
                switch ($status) {
                    case self::CODE_STATUS_EXPIRED:
                        //campaign is expired
                        $expired[$code] = [
                            'code' => $code,
                            'score' => 0,
                            'status' => self::CODE_STATUS_EXPIRED,
                            'description' => $message,
                        ];
                        break;

                    case self::CODE_STATUS_INVALID:
                        $expired[$code] = [
                            'code' => $code,
                            'score' => 0,
                            'status' => self::CODE_STATUS_INVALID,
                            'description' => $message,
                        ];
                        break;

                    case self::CODE_STATUS_VALID:
                        //put the code in cache, we need check the limit times and participate count in the selectcampaign
                        self::setCache($member, $campaign, $code);
                        //get response from campaign
                        $response = self::selectCampaign($campaign, $member, $params, false);
                        if (self::CODE_STATUS_EXCEEDED == $response['status']) {
                            //campaign is over
                            $over[$code] = [
                                'code' => $code,
                                'score' => 0,
                                'status' => self::CODE_STATUS_EXCEEDED,
                                'description' => $response['message'],
                            ];
                        } else if (self::CODE_STATUS_VALID == $response['status']) {
                            $score = self::getCampaignScore($campaign);
                            if (isset($vaild[$code])) {
                                $vaild[$code]['score'] += $score;
                            } else {
                                $vaild[$code] = [
                                    'code' => (string)$code,
                                    'score' => $score,
                                    'status' => self::CODE_STATUS_VALID,
                                    'description' => '',
                                ];
                            }
                        } else {
                            //check campain is over or code is can not fit with campaign
                            $invalid[$code] = [
                                'code' => $code,
                                'score' => 0,
                                'status' => self::CODE_STATUS_INVALID,
                                'description' => $response['message'],
                            ];
                        }
                        break;
                }
            }
        }

        //check code in vaild,clear other same code in other array
        foreach ($vaild as $code => $value) {
            //check invalid
            unset($invalid[$code]);
            //check expired
            unset($expired[$code]);
            //check over
            unset($over[$code]);
        }
        return array_merge($vaild, $invalid, $expired, $over);
    }

    /**
     * set cache
     * 1.set a key:$memberId+'code' to store member check how many code
     * 2.set a key:$memberId+$code to store every code belong to which campaign
     * 3.set a key:$memberId+$campaignId to store evey campaign how many times member take part in
     * @param $member,object
     * @param $campaign.object
     * @param $code,string
     */
    public static function setCache($member, $campaign, $code)
    {
        $redis = Yii::$app->cache->redis;
        $memberExchangeCodeCache = self::MEMBER_EXCHANGE_CODE;

        //set cache for code which member exchange
        $memberExchangeCodeKey = $member->_id . 'code';
        $existsCode = $redis->HGET($memberExchangeCodeCache, $memberExchangeCodeKey);

        if (!empty($existsCode)) {
            $existsCode = $existsCode . ',' . $code;
        } else {
            $existsCode = $code;
        }
        $redis->HSET($memberExchangeCodeCache, $memberExchangeCodeKey, $existsCode);
        //set cache code => campaignId
        $codeKey = $member->_id . $code;
        $redis->HSET($memberExchangeCodeCache, $codeKey, (string)$campaign->_id);
        //set cache memberId => campaignId how many times
        $campaignKey = $member->_id . $campaign->_id;
        $limit = $redis->HGET($memberExchangeCodeCache, $campaignKey);
        $redis->HSET($memberExchangeCodeCache, $campaignKey, $limit + 1);
    }

    /**
     * clear redis cache for exchange code
     * @param $memberId,string
     * @param $code,string
     */
    public static function clearExchangeRecord($memberId, $code = '')
    {
        $redis = Yii::$app->cache->redis;
        $redisHash = self::MEMBER_EXCHANGE_CODE;
        //get all code
        $key = $memberId . 'code';
        $codes = $redis->HGET($redisHash, $key);
        $codes = explode(',', $codes);
        //if code is empty,i will clear all cache about member exchange code in offline
        //otherwise i only clear cache about code what you typing
        if (empty($code)) {
            if (!empty($codes)) {
                foreach ($codes as $code) {
                    $clearKey = $memberId . $code;
                    $campaignId = $redis->HGET($redisHash, $clearKey);
                    $redis->HDEL($redisHash, $memberId . $campaignId);
                    $redis->HDEL($redisHash, $clearKey);
                }
                $redis->HDEL($redisHash, $key);
            }
        } else {
            $clearKey = $memberId . $code;
            $campaignId = $redis->HGET($redisHash, $clearKey);

            $limit = $redis->HGET($redisHash, $memberId . $campaignId);
            if ($limit > 0) {
                $redis->HSET($redisHash, $memberId . $campaignId, $limit - 1);
            } else {
                $redis->HDEL($redisHash, $memberId . $campaignId);
            }
            $redis->HDEL($redisHash, $clearKey);
            $redis->HSET($redisHash, $key, implode(',', array_diff($codes, [$code])));
        }
    }

    /**
     * set order for code when member typing in offline,$campaignCodeProductId order by code
     * @param $codes,array,
     * @param $campaignCodeProductId,array:(struct code=>productId)
     */
    public static function orderCode($codes, $campaignCodeProductId)
    {
        $data = [];
        foreach ($codes as $code) {
            foreach ($campaignCodeProductId as $campaignCode => $productId) {
                if ($code == $campaignCode) {
                    $data[$code] = $productId;
                }
            }
        }
        return $data;
    }
}
