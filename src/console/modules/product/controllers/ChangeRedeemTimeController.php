<?php
namespace console\modules\product\controllers;

use yii\console\Controller;
use backend\modules\product\models\CampaignLog;

/**
 * Add redeemTime to campaignLog
 */
class ChangeRedeemTimeController extends controller
{
    //how many records to deal
    const BATCH_ROWS = 1000;

    public function actionIndex()
    {
        $offset = 0;
        $query = CampaignLog::find();
        $order = ['_id' => SORT_ASC];
        $campaignLogs = $query->orderBy($order)->offset($offset)->limit(static::BATCH_ROWS)->all();

        while (!empty($campaignLogs)) {
            foreach ($campaignLogs as $campaignLog) {
                if (empty($campaignLog->redeemTime)) {
                    if (!empty($campaignLog->usedTime)) {
                        $campaignLog->redeemTime = $campaignLog->usedTime;
                    } else {
                        $campaignLog->redeemTime = $campaignLog->createdAt;
                    }
                    $campaignLog->save();
                }
            }
            unset($campaignLog, $campaignLogs);
            $offset += static::BATCH_ROWS;
            $campaignLogs = $query->offset($offset)->limit(static::BATCH_ROWS)->all();
        }
        echo 'update data successful' . PHP_EOL;
    }
}

