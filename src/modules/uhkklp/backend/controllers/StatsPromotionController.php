<?php

namespace backend\modules\uhkklp\controllers;

use backend\components\Controller;
use yii\web\BadRequestHttpException;
use backend\models\StatsCampaignProductCodeQuarterly;
use Yii;

class StatsPromotionController extends BaseController
{
    public function actionProduct()
    {
        $params = $this->getQuery();
        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();
        $raw = StatsCampaignProductCodeQuarterly::getByYearAndQuarter($params['year'], $params['quarter'], $accountId);
        $products = [];
        $data = [];
        foreach ($raw as $item) {
            $products[] = $item['productName'];
            $data[] = $item['total'];
        }
        return ['products' => $products, 'data' => $data];
    }

    public function actionExportProduct()
    {
        $params = $this->getQuery();
        if (empty($params['year']) || empty($params['quarter'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $header = ['quarter' => 'Quarter', 'productName' => 'SKU', 'total' => 'Number'];
        $key = 'Promotion_SKU_Summary' . '_' . date('Ymd');

        $condition = [
            'accountId' => $this->getAccountId(),
            'year' => $params['year'],
            'quarter' => new \MongoInt32($params['quarter']),
        ];

        $args = [
            'key' => $key,
            'header' => $header,
            'condition' => serialize($condition),
            'classFunction' => '\backend\models\StatsCampaignProductCodeQuarterly::preProcessData',
            'description' => 'Direct: Export product',
        ];
        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportStats', $args);
        return ['result' => 'success', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }
}
