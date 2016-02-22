<?php
namespace backend\modules\member\controllers;

use backend\modules\member\models\StatsMemberMonthly;
use backend\modules\member\models\StatsMemberGrowthQuarterly;
use backend\modules\member\models\StatsMemberGrowthMonthly;
use Yii;
use backend\components\Controller;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;
use backend\models\Channel;

class StatsController extends Controller
{
    public function actionSignupSummary()
    {
        $params = $this->getQuery();

        if (!isset($params['start']) || !isset($params['end'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if ($params['start'] > $params['end']) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        $accountId = $this->getAccountId();
        $startDate = TimeUtil::msTime2String($params['start'], 'Y-m');
        $endDate = TimeUtil::msTime2String($params['end'], 'Y-m');

        $statsData = StatsMemberMonthly::getByDate($startDate, $endDate, $accountId);
        $data = [];
        foreach ($statsData as $item) {
            $date = $item->month;
            $data[$item->origin][$date] = empty($data[$item->origin][$date]) ? 0 : $data[$item->origin][$date];
            $data[$item->origin][$date] += $item->total;
        }

        //ensure origins
        $channelOrigins = Channel::getOriginsByAccount($accountId);
        $origins = array_merge(StatsMemberMonthly::$originWithoutChannels, $channelOrigins);
        foreach ($origins as $origin) {
            if (empty($data[$origin])) {
                $data[$origin] = [];
            }
        }

        $endTime = strtotime($endDate);
        $dates = [];
        $totals = [];
        $dateTime = strtotime($startDate);
        while ($dateTime <= $endTime) {
            $date = date('Y-m', $dateTime);
            $dates[] = $date;
            foreach ($data as $origin => $dateTotal) {
                $totals[$origin][] = empty($dateTotal[$date]) ? 0 : $dateTotal[$date];
            }
            $dateTime = strtotime('+1 month', $dateTime);
        }

        return ['date' => $dates, 'data' => $totals];
    }

    public function actionActiveTracking()
    {
        $params = $this->getQuery();
        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if ($params['quarter'] > 4 || $params['quarter'] < 1) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        $accountId = $this->getAccountId();

        return StatsMemberGrowthQuarterly::getByQuarterAndAccount($accountId, $params['year'], (int) $params['quarter']);
    }

    public function actionEngagement()
    {
        $params = $this->getQuery();
        if (empty($params['year'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();

        $startDate = $params['year'] . '-01';
        $endDate = $params['year'] . '-12';
        $statsGrowth = StatsMemberGrowthMonthly::getByMonth($accountId, $startDate, $endDate);
        $monthActiveMap = [];
        foreach ($statsGrowth as $growth) {
            $monthActiveMap[$growth->month] = $growth->totalActive;
        }

        $dates = [];
        $data = [];
        $dateTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        while ($dateTime <= $endTime) {
            $date = date('Y-m', $dateTime);
            $dates[] = date('M', $dateTime);
            $data[] = empty($monthActiveMap[$date]) ? 0 : $monthActiveMap[$date];
            $dateTime = strtotime('+1 month', $dateTime);
        }

        return ['date' => $dates, 'data' => $data];
    }

    public function actionExportSignupSummary()
    {
        $params = $this->getQuery();
        if (!isset($params['start']) || !isset($params['end'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if ($params['start'] > $params['end']) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        $accountId = $this->getAccountId();
        $startDate = TimeUtil::msTime2String($params['start'], 'Y-m');
        $endDate = TimeUtil::msTime2String($params['end'], 'Y-m');

        $condition = ['accountId' => $accountId, 'month' => ['$lte' => $endDate, '$gte' => $startDate]];

        $key = Yii::t('member', 'signup_summary') . '_' . date('Ymd');
        $headerTitle = Yii::t('member', 'signup_summary_export_title');
        $headerValue = explode(',', $headerTitle);
        $header = [
            'month',
            'channel',
            'number'
        ];

        $showHeader = array_combine($header, $headerValue);
        $exportArgs = [
            'key' => $key,
            'header' => $showHeader,
            'condition' => serialize($condition),
            'classFunction' => '\backend\modules\member\models\StatsMemberMonthly::preProcessData',
            'description' => 'Direct: Export Signup Summary',
        ];
        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportStats', $exportArgs);

        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionExportActiveTracking()
    {
        $params = $this->getQuery();
        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if ($params['quarter'] > 4 || $params['quarter'] < 1) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        $accountId = $this->getAccountId();

        $condition = ['accountId' => $accountId, 'year' => $params['year'], 'quarter' => (int) $params['quarter']];

        $key = Yii::t('member', 'acct_tracking') . '_' . date('Ymd');
        $headerTitle = Yii::t('member', 'member_active_rate_export_title');
        $headerValue = explode(',', $headerTitle);
        $header = [
            'active',
            'inactive',
            'new'
        ];

        $showHeader = array_combine($header, $headerValue);
        $exportArgs = [
            'key' => $key,
            'header' => $showHeader,
            'condition' => serialize($condition),
            'classFunction' => '\backend\modules\member\models\StatsMemberGrowthQuarterly::preProcessData',
            'description' => 'Direct: Export Active Tracking',
        ];
        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportStats', $exportArgs);

        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionExportEngagement()
    {
        $params = $this->getQuery();
        if (empty($params['year'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();

        $startDate = $params['year'] . '-01';
        $endDate = $params['year'] . '-12';

        $condition = ['accountId' => $accountId, 'month' => ['$lte' => $endDate, '$gte' => $startDate]];
        $key = Yii::t('member', 'member_ship_engagement') . '_' . date('Ymd');
        $headerTitle = Yii::t('member', 'member_ship_engagement_export_title');
        $headerValue = explode(',', $headerTitle);
        $header = [
            'month',
            'number'
        ];

        $showHeader = array_combine($header, $headerValue);
        $exportArgs = [
            'key' => $key,
            'header' => $showHeader,
            'condition' => serialize($condition),
            'classFunction' => '\backend\modules\member\models\StatsMemberGrowthMonthly::preProcessData',
            'description' => 'Direct: Export Member Ship Engagement',
        ];
        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportStats', $exportArgs);

        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }
}
