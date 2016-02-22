<?php

namespace backend\modules\uhkklp\controllers;

use backend\components\Controller;
use Yii;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;
use backend\models\StatsMemberPropAvgTradeQuarterly;
use backend\models\StatsMemberPropTradeCodeQuarterly;
use backend\models\StatsMemberPropTradeQuarterly;
use backend\models\StatsMemberPropMonthly;
use backend\models\StatsMemberPropTradeCodeAvgQuarterly;

class StatsPropertyController extends BaseController
{
    public function actionProductOperatorAvg()
    {
        $params = $this->getQuery();
        if (empty($params['year'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();
        $statsQuater = StatsMemberPropAvgTradeQuarterly::getByYearAndAccount($accountId, $params['year']);

        $avgData = [];
        foreach ($statsQuater as $stats) {
            if (empty($stats->propValue)) {
                $stats->propValue = self::NOT_SELECTED;
            } else {
                $stats->propValue = 'KLP ' . $stats->propValue;
            }
            $avgData[$stats->propValue][$stats->quarter] = $stats->avg;
        }

        $querters = [1, 2, 3, 4];
        $date = [];
        $data = [];
        foreach ($querters as $querter) {
            $date[] = 'Q' . $querter;
            foreach ($avgData as $propValue => $avgs) {
                $data[$propValue][] = empty($avgs[$querter]) ? 0 : (float) sprintf("%.2f", $avgs[$querter]);
            }
        }

        return ['date' => $date, 'data' => $data];
    }

    public function actionExportProductOperatorAvg()
    {
        $params = $this->getQuery();
        if (empty($params['year'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();

        $key = 'SKU_per_Operator_' . date('Ymd');
        $condition = ['accountId' => $accountId, 'year' => $params['year']];
        $header = ['quarter' => 'Quarter', 'operate' => 'Operate', 'number' => 'Number'];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'condition' => serialize($condition),
            'classFunction' => '\backend\models\StatsMemberPropAvgTradeQuarterly::preProcessData',
            'description' => 'Direct: Export SKU per Operator',
        ];
        $jobId = \Yii::$app->job->create('backend\modules\common\job\ExportStats', $exportArgs);

        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionProductCode()
    {
        $params = $this->getQuery();
        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();
        $raw = StatsMemberPropTradeCodeQuarterly::getByYearAndQuarter($params['year'], $params['quarter'], $accountId);
        $data = [];
        foreach ($raw as $item) {
            $propValue = $item['propValue'];
            //Skip the invalid property value (empty array, empty string)
            if (!empty($propValue)) {
                $data[$propValue] = $item['total'];
            }
        }
        return ['data' => $data];
    }

    public function actionExportProductCode()
    {
        $params = $this->getQuery();

        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $header = ['quarter' => 'Quarter', 'propValue' => 'Channel', 'total' => 'Number'];
        $key = 'KLP_Channel_Penetration_in_Volume' . '_' . date('Ymd');

        $condition = [
            'accountId' => $this->getAccountId(),
            'year' => $params['year'],
            'quarter' => new \MongoInt32($params['quarter']),
        ];

        $args = [
            'key' => $key,
            'header' => $header,
            'condition' => serialize($condition),
            'classFunction' => '\backend\models\StatsMemberPropTradeCodeQuarterly::preProcessData',
            'description' => 'Direct: Export product code',
        ];

        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportStats', $args);
        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionMemberParticipant()
    {
        $params = $this->getQuery();
        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();
        $raw = StatsMemberPropTradeQuarterly::getByYearAndQuarter($params['year'], $params['quarter'], $accountId);
        $data = [];
        foreach ($raw as $item) {
            $propValue = $item['propValue'];
            //Skip the invalid property value (empty array, empty string)
            if (!empty($propValue)) {
                $data[$propValue] = $item['total'];
            }
        }
        return ['data' => $data];
    }

    public function actionExportMemberParticipant()
    {
        $params = $this->getQuery();

        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $header = ['quarter' => 'Quarter', 'propValue' => 'Channel', 'total' => 'Number'];
        $key = 'KLP_Channel_Penetration_in_Acct' . '_' . date('Ymd');

        $condition = [
            'accountId' => $this->getAccountId(),
            'year' => $params['year'],
            'quarter' => new \MongoInt32($params['quarter'])
        ];

        $args = [
            'key' => $key,
            'header' => $header,
            'condition' => serialize($condition),
            'classFunction' => '\backend\models\StatsMemberPropTradeQuarterly::preProcessData',
            'description' => 'Direct: Export member participant',
        ];

        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportStats', $args);
        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionMemberMonthly()
    {
        $startDate = $this->getQuery('start');
        $endDate = $this->getQuery('end');

        if (empty($startDate) || empty($endDate)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $startDate = TimeUtil::msTime2String($startDate, 'Y-m');
        $endDate = TimeUtil::msTime2String($endDate, 'Y-m');

        $accountId = $this->getAccountId();
        $condition = [
            'accountId' => $accountId,
            'month' => [
                '$gte' => $startDate,
                '$lte' => $endDate
            ]
        ];

        $data = [];
        $stats = StatsMemberPropMonthly::findAll($condition);
        $dateCondition = [
            'dateFormat' => 'Y-m',
            'dateDiff' => '+1 month',
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        $result = $this->_formatStatsWithDate($stats, 'month', 'total', $dateCondition);
        return $result;
    }

    public function actionExportMemberMonthly()
    {
        $startDate = $this->getQuery('start');
        $endDate = $this->getQuery('end');

        if (empty($startDate) || empty($endDate)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $startDate = TimeUtil::msTime2String($startDate, 'Y-m');
        $endDate = TimeUtil::msTime2String($endDate, 'Y-m');

        $accountId = $this->getAccountId();
        $condition = [
            'accountId' => $accountId,
            'month' => [
                '$gte' => $startDate,
                '$lte' => $endDate
            ]
        ];
        $key = 'FT_and_Pull_Participant_Summary_' . date('Ymd');
        $header = ['month' => 'Month', 'operate' => 'Operate', 'number' => 'Number'];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'condition' => serialize($condition),
            'classFunction' => '\backend\models\StatsMemberPropMonthly::preProcessData',
            'description' => 'Direct: Export FT and Pull Participant Summary',
        ];
        $jobId = \Yii::$app->job->create('backend\modules\common\job\ExportStats', $exportArgs);

        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];

    }

    public function actionCodeAvgQuarterly()
    {
        $year = $this->getQuery('year');
        $quarter = $this->getQuery('quarter');

        if (empty($year) || empty($quarter)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $accountId = $this->getAccountId();
        $condition = [
            'accountId' => $accountId,
            'year' => $year,
            'quarter' => (int) $quarter
        ];

        $data = [];
        $stats = StatsMemberPropTradeCodeAvgQuarterly::findAll($condition);
        $result = $this->_formatStatsWithCategory($stats, 'productId', 'productName', 'avg');
        return $result;
    }

    public function actionExportCodeAvgQuarterly()
    {
        $year = $this->getQuery('year');
        $quarter = $this->getQuery('quarter');

        if (empty($year) || empty($quarter)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $accountId = $this->getAccountId();
        $condition = [
            'accountId' => $accountId,
            'year' => $year,
            'quarter' => (int) $quarter
        ];
        $key = 'SKU_Summary_Pull_vs_FT_' . date('Ymd');
        $header = ['quarter' => 'Quarter', 'sku' => 'SKU', 'operate' => 'Operate', 'number' => 'Number'];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'condition' => serialize($condition),
            'classFunction' => '\backend\models\StatsMemberPropTradeCodeAvgQuarterly::preProcessData',
            'description' => 'Direct: Export SKU Summary Pull vs FT',
        ];
        $jobId = \Yii::$app->job->create('backend\modules\common\job\ExportStats', $exportArgs);

        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }
}
