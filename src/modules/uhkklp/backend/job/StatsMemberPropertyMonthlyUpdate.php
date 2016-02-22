<?php
namespace backend\modules\uhkklp\job;

use MongoId;
use MongoDate;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\models\Account;
use backend\models\StatsMemberPropMonthly;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberProperty;

/**
* Job for StatsMemberDaily
*/
class StatsMemberPropertyMonthlyUpdate
{
    public function setUp()
    {
    }

    public function perform()
    {
        //accountId, properties are required fileds
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['properties'][0])
            || empty($args['startDate']) || empty($args['endDate'])) {
            LogUtil::error(['Missing params in update StatsMemberPropertyMonthly', 'params' => $args], 'update_job');
            return false;
        }

        $startTime = strtotime($args['startDate']);
        $endTime = strtotime($args['endDate']);
        $today = strtotime(date('Y-m-d'));
        if ($endTime >= $today) {
            $endTime = $today - 3600 * 24;
        }

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }

        foreach ($accountIds as $accountId) {
            $accountId = new MongoId($accountId);
            //delete data with account and time
            StatsMemberPropMonthly::deleteAll(['accountId' => $accountId, 'createdAt' => ['$gte' => new MongoDate($startTime), '$lt' => new MongoDate($endTime)]]);
            self::createStatsMemberPropMonthly($accountId, $args, $startTime, $endTime);
        }
    }

    public static function createStatsMemberPropMonthly($accountId, $args, $startTime, $endTime)
    {
        $property = $args['properties'][0];
        $memberProperty = MemberProperty::findOne(['propertyId' => $property, 'accountId' => $accountId]);

        if (empty($memberProperty)) {
            LogUtil::error(['message' => $accountId . ':Can not find member property with propertyId:' . $property], 'update_job');
            return false;
        }

        for ($t = $startTime; $t < $endTime; $t = strtotime('+1 month', $t)) {
            LogUtil::info(['message' => $accountId . ':update member property ' . date('Y-m', $t)], 'update_job');
            $month = date('Y-m', $t);
            $startCreatedAt = strtotime($month);
            $endCreatedAt = strtotime(date('Y-m', strtotime('+1 month', $startCreatedAt)));

            $raw = Member::getCollection()->aggregate([
                ['$unwind' => '$properties'],
                [
                    '$match' => [
                        'createdAt' => [
                            '$gte' => new MongoDate($startCreatedAt),
                            '$lt' => new MongoDate($endCreatedAt),
                        ],
                        'properties.id' => $memberProperty->_id,
                        'accountId' => $accountId,
                        'isDeleted' => Member::NOT_DELETED
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$properties.value',
                        'total' => [
                            '$sum' => 1
                        ]
                    ]
                ]
            ]);
            foreach ($raw as $item) {
                $total = $item['total'];
                $propValue = $item['_id'];
                // save the stats member property monthly
                $statsMemberPropMonthly = StatsMemberPropMonthly::findOne([
                    'propId' => $property,
                    'propValue' => $propValue,
                    'month' => $month,
                    'accountId' => $accountId
                ]);
                if (empty($statsMemberPropMonthly)) {
                    $statsMemberPropMonthly = new StatsMemberPropMonthly();
                    $statsMemberPropMonthly->propId = $property;
                    $statsMemberPropMonthly->propValue = $propValue;
                    $statsMemberPropMonthly->month = $month;
                    $statsMemberPropMonthly->accountId = $accountId;
                }

                $statsMemberPropMonthly->total = $total;
                $statsMemberPropMonthly->save();
            }
        }
    }

    public function tearDown()
    {
    }
}
