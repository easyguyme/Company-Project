<?php
namespace backend\modules\uhkklp\job;

use Yii;
use MongoId;
use MongoDate;
use backend\models\StatsMemberCampaignLogDaily as ModelStatsMemberCampaignLogDaily;
use backend\modules\member\models\Member;
use backend\modules\product\models\CampaignLog;
use backend\modules\member\models\MemberProperty;
use backend\modules\resque\components\ResqueUtil;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;

/**
* Job for StatsMemberCampaignLog
* Base job for other campaign related statistics
*/
class StatsMemberCampaignLogDaily
{
    public function perform()
    {
        //accountId, properties are required fileds
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['properties'])) {
            ResqueUtil::log('Missing required arguments accountId or properties!');
            return false;
        }
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }
        foreach ($accountIds as $accountId) {
            $accountId = new MongoId($accountId);
            self::createCampaignLogDailyWithAccountId($accountId, $datetime, $args);
        }
        return true;
    }

    public static function createCampaignLogDailyWithAccountId($accountId, $datetime, $args)
    {
        $dateStr = date('Y-m-d', $datetime);
        $start = new MongoDate($datetime);
        $end = new MongoDate(strtotime('+1 day', $datetime));

        $condition = [
            'accountId' => $accountId,
            'createdAt' => [
                '$gte' => $start,
                '$lt' => $end
            ]
        ];
        $campaignLogs = self::getCampaignLog($condition);

        if (empty($campaignLogs)) {
            LogUtil::info(['date' => date('Y-m-d H:i:s'), 'message' => $dateStr .': campaignLogs is empty,no need to store data'], 'resque');
            return true;
        }
        //get all member info
        $memberInfos = self::getMemberInfo($condition);
        //Get all the property mongo id for comparison
        $condition = [
            'propertyId' => ['$in' => $args['properties']],
            'accountId' => $accountId
        ];
        $propertyIdStrs = self::getPropertyIds($condition);

        //Generate the meta data for inserting
        $statsRows = [];
        foreach ($campaignLogs as $campaignLog) {
            $campaignLog = $campaignLog['_id'];

            $redeemTime = self::getRedeemTime($campaignLog);

            //check the redeem time whether exists
            $condition = [
                'code' => $campaignLog['code'],
                'productId' => $campaignLog['productId'],
                'month' => date('Y-m', $redeemTime),
                'accountId' => $accountId
            ];
            $memberCampaignLogDaily = ModelStatsMemberCampaignLogDaily::findOne($condition);

            if (empty($memberCampaignLogDaily)) {
                $memProperty = self::getProperty((string)$campaignLog['member']['id'], $memberInfos, $propertyIdStrs);
                $statsRows[] = [
                    'memberId' => $campaignLog['member']['id'],
                    'memProperty' => $memProperty,
                    'productId' => $campaignLog['productId'],
                    'code' => $campaignLog['code'],
                    'year' => date('Y', $redeemTime),
                    'month' => date('Y-m', $redeemTime),
                    'quarter' => TimeUtil::getQuarter($redeemTime),
                    'accountId' => $accountId,
                    'createdAt' => new MongoDate(strtotime('+1 day', $datetime) - 1),
                ];
            }
        }
        ModelStatsMemberCampaignLogDaily::batchInsert($statsRows);
        unset($statsRows, $memberCampaignLogDaily, $condition, $memProperty, $memberInfos);
    }

    public function tearDown()
    {
        //TODO: Add other statistics based on the collection created by the job
        $args = $this->args;
        if (!empty($args['runNextJob'])) {
            unset($args['runNextJob']);
            $args['description'] = 'Direct: Stats of StatsMemberPropTradeQuarterly';
            Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberPropTradeQuarterly', $args);
            $args['description'] = 'Direct: Stats of StatsMemberPropTradeCodeQuarterly';
            Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberPropTradeCodeQuarterly', $args);
            $args['description'] = 'Direct: Stats of StatsCampaignProductCodeQuarterly';
            Yii::$app->job->create('backend\modules\uhkklp\job\StatsCampaignProductCodeQuarterly', $args);
            $args['description'] = 'Direct: Stats of StatsMemberPropAvgTradeQuarterly';
            Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberPropAvgTradeQuarterly', $args);
            $args['description'] = 'Direct: Stats of StatsMemberPropertyTradeCodeAvgQuarterly';
            Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberPropertyTradeCodeAvgQuarterly', $args);
        }
    }

    public static function getProperty($memberId, $memberInfos, $propertyIdStrs)
    {
        $memProperty = [];
        if (isset($memberInfos[$memberId])) {
            $member = $memberInfos[$memberId];
            foreach ($member->properties as $property) {
                $propertyId = (string)$property['id'];
                if (in_array($propertyId, $propertyIdStrs)) {
                    $memProperty[$propertyId] = $property['value'];
                }
            }
        }
        return $memProperty;
    }

    private static function getCampaignLog($condition)
    {
        $campaignLogs = CampaignLog::getCollection()->aggregate(
            [
                '$match' => $condition,
            ],
            [
                '$group' => [
                    '_id' => [
                        'redeemTime' => '$redeemTime',
                        'member' => '$member',
                        'code' => '$code',
                        'productId' => '$productId',
                        'createdAt' => '$createdAt'
                    ],
                ]
            ]
        );
        return $campaignLogs;
    }

    /**
     * @return int
     */
    public static function getRedeemTime($campaignLog)
    {
        if ($campaignLog['redeemTime'] == $campaignLog['createdAt']) {
            $redeemTime = $campaignLog['createdAt'];
        } else {
            $redeemTime = $campaignLog['redeemTime'];
        }

        return MongodbUtil::MongoDate2TimeStamp($redeemTime);
    }

    public static function getPropertyIds($condition)
    {
        $propertyIds = MemberProperty::find()->select(['_id'])->where($condition)->all();
        $propertyIdStrs = [];
        foreach ($propertyIds as $propertyId) {
            $propertyIdStrs[] = (string) $propertyId['_id'];
        }
        return $propertyIdStrs;
    }

    public static function getMemberInfo($condition)
    {
        $memberIds = CampaignLog::distinct('member.id', $condition);

        $memberInfos = [];
        if (!empty($memberIds)) {
            $members = Member::findAll(['_id' => ['$in' => $memberIds]]);
            if (!empty($members)) {
                foreach ($members as $member) {
                    $memberInfos[(string)$member->_id] = $member;
                }
            }
            unset($member, $members);
        }
        return $memberInfos;
    }
}
