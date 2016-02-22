<?php
namespace backend\modules\uhkklp\job;

use backend\modules\resque\components\ResqueUtil;
use backend\models\StatsMemberCampaignLogDaily as ModelStatsMemberCampaignLogDaily;
use backend\models\StatsMemberPropAvgTradeQuarterly as ModelStatsMemberPropAvgTradeQuarterly;
use backend\modules\member\models\MemberProperty;
use backend\utils\TimeUtil;
use MongoId;

/**
 * Job for StatsMemberPropAvgTradeQuarterly
 */
class StatsMemberPropAvgTradeQuarterly
{
    public function setUp()
    {
        # Set up environment for this job
    }

    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['properties'])) {
            ResqueUtil::log('Missing required arguments accountId or properties!');
            return false;
        }

        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $year = date('Y', $datetime);
        $quarter = TimeUtil::getQuarter($datetime);
        $propertyOperate = $args['properties'][0];

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }

        foreach ($accountIds as $accountId) {
            $accountId = new MongoId($accountId);
            $memberPropertyId = self::getMemberPropertyId($accountId, $args);
            if (!empty($memberPropertyId)) {
                self::generateData($accountId, $memberPropertyId, $year, $quarter, $propertyOperate);
            }
        }
        return true;
    }

    public static function getMemberPropertyId($accountId, $args)
    {
        $memberPropertyId = '';
        $propertyOperate = $args['properties'][0];
        $memberProperty = MemberProperty::getByPropertyId($accountId, $propertyOperate);
        if (!empty($memberProperty)) {
            $memberPropertyId = $memberProperty->_id;
        } else {
            ResqueUtil::log($accountId . ':Property ' . $propertyOperate . ' not found');
        }
        return $memberPropertyId;
    }

    public static function generateData($accountId, $memberPropertyId, $year, $quarter, $propertyOperate)
    {
        $statsQuarterly = ModelStatsMemberCampaignLogDaily::getPropAvgTradeQuarterly($accountId, (string) $memberPropertyId, $year, $quarter);
        $statsAvg = [];
        foreach ($statsQuarterly as $stats) {
            $propValueKey = 'memProperty.' .  $memberPropertyId;
            if (empty($stats[$propValueKey])) {
                continue;
            }
            $propId = $propertyOperate;
            $propValue = $stats[$propValueKey];
            $avg = $stats['avg'];
            $statsAvg = ModelStatsMemberPropAvgTradeQuarterly::getByPropAndDate($accountId, $propId, $propValue, $year, $quarter);

            if (!empty($statsAvg)) {
                $statsAvg->avg = $avg;
            } else {
                $statsAvg = new ModelStatsMemberPropAvgTradeQuarterly;
                $statsAvg->propId = $propId;
                $statsAvg->propValue = $propValue;
                $statsAvg->avg = $avg;
                $statsAvg->year = $year;
                $statsAvg->quarter = $quarter;
                $statsAvg->accountId = $accountId;
            }

            try {
                $statsAvg->save();
            } catch (Exception $e) {
                ResqueUtil::log(['Update StatsMemberPropAvgTradeQuarterly error' => $e->getMessage(), 'StatsMemberPropAvgTradeQuarterly' => $statsAvg]);
                continue;
            }
        }
    }
}
