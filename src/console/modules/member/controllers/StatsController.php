<?php
namespace console\modules\member\controllers;

use Yii;
use yii\console\Controller;

/**
 * Campaign and member related analysis
 */
class StatsController extends Controller
{
    const DAILY = '+1 day';
    const MONTHLY = '+1 month';
    const QUARTERLY = '+3 month';
    const ACCOUND_ID = "559b7615fd604ab7768b4567";

    private $properties = ['operate', 'subChannel'];

    /**
     * Create a stats job to get datas from $startDate to $endDate and save it.
     * @param string $collection @example StatsMemberDaily
     * @param string $startDate @example 2015-06-07
     * @param string $endDate @example 2015-06-28
     */
    public function actionRun($collection, $startDate, $endDate = '')
    {
        $collections = [
            'StatsMemberDaily' => ['span' => self::DAILY, 'namespace' => 'backend\modules\member\job\\'],
            'StatsMemberMonthly' => ['span' => self::MONTHLY, 'namespace' => 'backend\modules\member\job\\'],
            'StatsMemberCampaignLogDaily' => ['span' => self::DAILY, 'namespace' => 'backend\modules\uhkklp\job\\'],
            'StatsMemberGrowthMonthly' => ['span' => self::MONTHLY, 'namespace' => 'backend\modules\member\job\\'],
            'StatsMemberGrowthQuarterly' => ['span' => self::QUARTERLY, 'namespace' => 'backend\modules\member\job\\'],
            'StatsMemberPropAvgTradeQuarterly' => ['span' => self::QUARTERLY, 'namespace' => 'backend\modules\uhkklp\job\\'],
            'StatsMemberPropTradeQuarterly' => ['span' => self::DAILY, 'namespace' => 'backend\modules\uhkklp\job\\'],
            'StatsMemberPropTradeCodeQuarterly' => ['span' => self::DAILY, 'namespace' => 'backend\modules\uhkklp\job\\'],
            'StatsCampaignProductCodeQuarterly' => ['span' => self::DAILY, 'namespace' => 'backend\modules\uhkklp\job\\'],
            'StatsMemberPropertyMonthly' => ['span' => self::MONTHLY, 'namespace' => 'backend\modules\uhkklp\job\\'],
            'StatsMemberPropertyTradeCodeAvgQuarterly' => ['span' => self::DAILY, 'namespace' => 'backend\modules\uhkklp\job\\'],
            'StatsQuestionnaireDaily' => ['span' => self::DAILY, 'namespace' => 'backend\modules\content\job\\'],
            'StatsQuestionnaireAnswerDaily' => ['span' => self::DAILY, 'namespace' => 'backend\modules\content\job\\'],
            'StatsCouponLogDaily' => ['span' => self::DAILY, 'namespace' => 'backend\modules\product\job\\']
        ];
        if (empty($collections[$collection])) {
            echo 'Error collection' . PHP_EOL;
            return;
        }

        $dateTime = strtotime($startDate);
        if (empty($endDate)) {
            $endDate = date('Y-m-d');
        }
        $endTime = strtotime($endDate);
        while ($dateTime <= $endTime) {
            $date = date('Y-m-d', $dateTime);
            $args = [
                'description' => 'Direct: Stats of ' . $collection,
                'date' => $date,
                'runNextJob' => false,
                'accountId' => self::ACCOUND_ID,
                'properties' => $this->properties,
            ];
            Yii::$app->job->create($collections[$collection]['namespace'] . $collection, $args, null, null, false);
            $dateTime = strtotime($collections[$collection]['span'], $dateTime);
        }
        echo 'Success' . PHP_EOL;
        return;
    }
}
