<?php
namespace backend\modules\product\controllers;

use Yii;
use MongoId;
use MongoDate;
use backend\exceptions\InvalidParameterException;
use backend\modules\product\models\PromotionCode;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\modules\product\models\StatsPromotionCodeAnalysis;
use backend\utils\LogUtil;
use backend\utils\TimeUtil;

class PromotionCodeAnalysisController extends BaseController
{
    const EXPORT_ALL = 'all';

    public $modelClass = 'backend\modules\product\models\PromotionCodeAnalysis';

    public static $types = [
        PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_PARTICIPATE,
        PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL,
        PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_EVERYDAY_PRIZE,
        PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE,
    ];

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();

        if (empty($params['startDate']) || empty($params['endDate']) || empty($params['campaignId'])) {
            throw new InvalidParameterException('missing params');
        }
        $params['startDate'] = new MongoDate(TimeUtil::ms2sTime($params['startDate']));
        $params['endDate'] = new MongoDate(TimeUtil::ms2sTime($params['endDate']) + 3600 * 24);
        $accountId = $this->getAccountId();

        $data = [];
        if (!empty($params['type'])) {
            if (!is_array($params['type'])) {
                $types = [$params['type']];
            } else {
                $types = $params['type'];
            }
        } else {
            $types = self::$types;
        }

        //to suport to get all campaign data
        if (MongoId::isValid($params['campaignId'])) {
            $params['campaignId'] = new MongoId($params['campaignId']);
            $data = PromotionCodeAnalysis::getAnalysisData($types, $accountId, $params);
        } else {
            $data = StatsPromotionCodeAnalysis::getAnalysisData($types, $accountId, $params);
        }

        return $data;
    }

    public function actionExport()
    {
        $params = $this->getQuery();

        if (empty($params['campaignName']) || empty($params['campaignId']) || empty($params['type']) || empty($params['startDate']) || empty($params['endDate'])) {
            throw new InvalidParameterException('missing params');
        }
        //add a param called all for export all campaigns
        $types = self::$types;

        if (!in_array($params['type'], $types)) {
            throw new InvalidParameterException('invaild type');
        }

        $startDate = TimeUtil::ms2sTime($params['startDate']);
        $endDate = TimeUtil::ms2sTime($params['endDate']) + 3600 * 24;

        $header = self::getExportFileHeader($startDate, $endDate);

        $key = $params['campaignName'] . '(' . Yii::t('product', 'analysis_' . $params['type']) . ')';

        $params['type'] = intval($params['type']);
        $sort = ['productName' => 1, 'createdAt' => 1];

        $condition = [
            'campaignId' => new MongoId($params['campaignId']),
            'type' => $params['type'],
            'createdAt' => [
                '$gte' => new MongoDate($startDate),
                '$lt' => new MongoDate($endDate),
            ],
        ];

        $key .= '_' . date('Y-m-d', $startDate) . '_' . date('Y-m-d', $endDate);
        $fields = 'createdAt,productName,total,type';

        $args = [
            'accountId' => (string)$this->getAccountId(),
            'key' => $key,
            'header' => $header,
            'fields' => $fields,
            'sort' => $sort,
            'params' => ['header' => $header],
            'collection' => 'promotionCodeAnalysis',
            'condition' => serialize($condition),
            'classFunction' => '\backend\modules\product\models\PromotionCodeAnalysis::preProcessData',
            'description' => 'Direct: Export PromotionCodeAnalysis data',
        ];
        $jobId = Yii::$app->job->create('backend\modules\product\job\ExportPromotionCodeAnalysis', $args);
        unset($params, $args);

        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionExportCampaignAnalysis()
    {
        $params = $this->getQuery();

        if (empty($params['type']) || empty($params['startDate']) || empty($params['endDate'])) {
            throw new InvalidParameterException('missing params');
        }
        //add a param called all for export all campaigns
        $types = self::$types;

        if (!in_array($params['type'], $types)) {
            throw new InvalidParameterException('invaild type');
        }

        $startDate = TimeUtil::ms2sTime($params['startDate']);
        $endDate = TimeUtil::ms2sTime($params['endDate']) + 3600 * 24;

        $header = self::getExportFileHeader($startDate, $endDate);

        $params['type'] = intval($params['type']);
        $sort = ['productName' => 1, 'createdAt' => 1];

        $accountId = $this->getAccountId();
        $condition = [
            'type' => $params['type'],
            'createdAt' => [
                '$gte' => new MongoDate($startDate),
                '$lt' => new MongoDate($endDate),
            ],
            'accountId' => $accountId,
        ];

        $key = Yii::t('product', 'active_name') . '_' . date('Y-m-d', $startDate) . '_' . date('Y-m-d', $endDate);
        $fields = 'createdAt,productName,total,type';

        $args = [
            'accountId' => (string)$accountId,
            'key' => $key,
            'header' => $header,
            'fields' => $fields,
            'sort' => $sort,
            'params' => ['header' => $header],
            'collection' => 'statsPromotionCodeAnalysis',
            'condition' => serialize($condition),
            'classFunction' => '\backend\modules\product\models\StatsPromotionCodeAnalysis::preProcessData',
            'description' => 'Direct: Export stats PromotionCodeAnalysis data',
        ];
        $jobId = Yii::$app->job->create('backend\modules\product\job\ExportPromotionCodeAnalysis', $args);
        unset($params, $args);

        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    private function getExportFileHeader($startDate, $endDate)
    {
        $header = [];

        $header['productName'] = '';
        for ($t = $startDate; $t < $endDate; $t += 3600 * 24) {
            $header[date('Y-m-d', $t)] = date('Y-m-d', $t);
        }
        return $header;
    }
}
