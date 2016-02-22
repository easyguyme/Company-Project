<?php
namespace backend\modules\uhkklp\job;

use MongoId;
use backend\models\StatsMemberPropTradeQuarterly as ModelStatsMemberPropTradeQuarterly;
use backend\modules\member\models\MemberProperty;
use backend\utils\TimeUtil;
use backend\modules\resque\components\ResqueUtil;

/**
* Job for StatsMemberPropTradeCodeQuaterly
*/
class StatsMemberPropTradeQuarterly
{
    public function setUp()
    {
    }

    public function perform()
    {
        $args = $this->args;
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }

        foreach ($accountIds as $accountId) {
            //Assume that the subChannel is the secode element in properties
            self::createStatsMemberPropTradeQuarterly($accountId, $args, $datetime);
        }
        return true;
    }

    public static function createStatsMemberPropTradeQuarterly($accountId, $args, $datetime)
    {
        $propertyKey = $args['properties'][1];
        $memberProperty = MemberProperty::findOne([
                'propertyId' => $propertyKey,
                'accountId' => new MongoId($accountId)
            ]
        );
        if (!empty($memberProperty)) {
            return ModelStatsMemberPropTradeQuarterly::generateByYearAndQuarter((string)$memberProperty['_id'], $accountId, $datetime);
        } else {
            ResqueUtil::log($accountId . ":Fail to get memberProperty with propertyId $propertyKey");
        }
    }

    public function tearDown()
    {
    }
}
