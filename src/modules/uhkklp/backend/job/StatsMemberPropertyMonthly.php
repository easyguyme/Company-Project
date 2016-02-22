<?php
namespace backend\modules\uhkklp\job;

use MongoId;
use MongoDate;
use backend\utils\TimeUtil;
use backend\models\Account;
use backend\models\StatsMemberPropMonthly as ModelStatsMemberPropMonthly;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberProperty;
use backend\modules\resque\components\ResqueUtil;

/**
* Job for StatsMemberDaily
*/
class StatsMemberPropertyMonthly
{
    public function setUp()
    {
    }

    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['properties'][0])) {
            ResqueUtil::log('Missing required arguments accountId or properties!');
            return false;
        }

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }

        foreach ($accountIds as $accountId) {
            $accountId = new MongoId($accountId);
            self::createStatsMemberPropMonthly($accountId, $args);
        }
        return true;
    }

    public static function createStatsMemberPropMonthly($accountId, $args)
    {
        $property = $args['properties'][0];
        $memberProperty = MemberProperty::findOne(['propertyId' => $property, 'accountId' => $accountId]);

        if (empty($memberProperty)) {
            ResqueUtil::log($accountId . ':Can not find member property with propertyId:' . $property);
            return false;
        }

        $date = empty($args['date']) ? '' : $args['date'];
        $date = TimeUtil::getDatetime($date);

        $month = date('Y-m', $date);
        $startDate = strtotime($month);
        $endDate = strtotime('+1 days', $date);
        $raw = Member::getCollection()->aggregate([
            ['$unwind' => '$properties'],
            [
                '$match' => [
                    'createdAt' => [
                        '$gte' => new MongoDate($startDate),
                        '$lt' => new MongoDate($endDate),
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
            $statsMemberPropMonthly = ModelStatsMemberPropMonthly::findOne([
                'propId' => $property,
                'propValue' => $propValue,
                'month' => $month,
                'accountId' => $accountId
            ]);
            if (empty($statsMemberPropMonthly)) {
                $statsMemberPropMonthly = new ModelStatsMemberPropMonthly();
                $statsMemberPropMonthly->propId = $property;
                $statsMemberPropMonthly->propValue = $propValue;
                $statsMemberPropMonthly->month = $month;
                $statsMemberPropMonthly->accountId = $accountId;
            }

            $statsMemberPropMonthly->total = $total;
            $statsMemberPropMonthly->save();
        }
    }

    public function tearDown()
    {
    }
}
