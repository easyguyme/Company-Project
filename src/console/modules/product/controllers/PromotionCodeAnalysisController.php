<?php
namespace console\modules\product\controllers;

use yii\console\Controller;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\utils\TimeUtil;
use Yii;

/**
 * Analysic how many codes to be redeemed
 */
class PromotionCodeAnalysisController extends Controller
{
    /**
     * update the promotioncode analysis
     */
    public function actionUpdateData($beginTime = '', $endTime = '', $type = '')
    {
        if (empty($beginTime) || empty($endTime) || empty($type)) {
            echo '1)第一个参数是开始时间' . PHP_EOL;
            echo '2)第二个参数是结束时间' . PHP_EOL;
            echo '3)第三个参数是更新类型' . PHP_EOL;
            echo '1.表示更新参与人数;2.表示更新兑换总数;3.表示每天兑换数量,4.表示没每天参加的总人数' . PHP_EOL;
            exit();
        }

        if (empty($beginTime) || empty($endTime)) {
            echo '时间参数错误!' . PHP_EOL;
            exit();
        }

        $types = [
            PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_EVERYDAY_PRIZE,
            PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_PARTICIPATE,
            PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL,
            PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE,
        ];

        if (!in_array($type, $types)) {
            echo '类型参数错误!' . PHP_EOL;
            exit();
        }
        $args = ['beginTime' => $beginTime, 'endTime' => $endTime, 'type' => $type];
        $jobId = Yii::$app->job->create('backend\modules\product\job\PromotionCodeAnalysisUpdate', $args, null, null, false);
        echo $jobId . PHP_EOL;
    }
}
