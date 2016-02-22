<?php
namespace console\modules\product\controllers;

use MongoDate;
use yii\console\Controller;
use backend\modules\product\models\CampaignLog;
use backend\models\Account;
use yii\helpers\ArrayHelper;
use backend\modules\member\models\ScoreHistory;
use backend\modules\member\models\Member;

class CampaignLogController extends controller
{
    public function actionFixData($startData, $endData)
    {
        $accounts = Account::findAll(['enabledMods' => 'product']);
        foreach ($accounts as $account) {
            $accountId = $account->_id;
            $condition = ['accountId' => $accountId, 'createdAt' => ['$gte' => new MongoDate(strtotime($startData)), '$lt' => new Mongodate(strtotime($endData))]];
            $pipeline = [
                ['$match' => $condition],
                [
                    '$group' => [
                        '_id' => ['campaignId' => '$campaignId', 'code' => '$code'],
                        'count' => ['$sum' => 1],
                    ]
                ],
                [
                    '$match' => ['count' => ['$gt' => 1]]
                ]
            ];
            $stats = CampaignLog::getCollection()->aggregate($pipeline);
            if (!empty($stats)) {
                foreach ($stats as $stat) {
                    $code = $stat['_id']['code'];
                    $logCondition = array_merge($condition, $stat['_id']);
                    $logs = CampaignLog::find()->where($logCondition)->orderBy(['createdAt' => 1])->all();
                    $memberId = $logs[0]['member']['id'];
                    $productName = $logs[0]['productName'];
                    $description = $productName . ' ' . $code;
                    $scoreHistoryCondition = ['memberId' => $memberId, 'brief' => ScoreHistory::ASSIGNER_EXCHANGE_PROMOTION_CODE, 'description' => $description];
                    $scoreHistorys = ScoreHistory::find()->where($scoreHistoryCondition)->orderBy(['createdAt' => 1])->all();
                    $keepScoreHistory = $scoreHistorys[0];
                    unset($scoreHistorys[0]);
                    $removeScoreHistoryIds = [];
                    $deduct = 0;
                    foreach ($scoreHistorys as $scoreHistory) {
                        $removeScoreHistoryIds[] = $scoreHistory->_id;
                        $deduct += $scoreHistory->increment;
                    }
                    $member = Member::findByPk($memberId);
                    if ($member->score <= $deduct || $member->totalScore <= $deduct || $member->totalScoreAfterZeroed <= $deduct) {
                        echo 'Failed : Member' . $memberId .' score not enough ' . 'score: ' .$member->score;
                        echo ' totalScore: ' . $member->totalScore;
                        echo ' totalScoreAfterZeroed: ' . $member->totalScoreAfterZeroed . PHP_EOL;
                        continue;
                    }
                    $deductScore = 0 - $deduct;
                    Member::updateAll(['$inc' => ['score' => $deductScore, 'totalScore' => $deductScore, 'totalScoreAfterZeroed' => $deductScore]], ['_id' => $memberId]);
                    ScoreHistory::deleteAll(['_id' => ['$in' => $removeScoreHistoryIds]]);
                    $logIds = ArrayHelper::getColumn($logs, '_id');
                    $keepLogId = $logIds[0];
                    unset($logIds[0]);
                    CampaignLog::deleteAll(['_id' => ['$in' => array_values($logIds)]]);
                    echo 'Success: ' . $productName . ' ' . $code . ' ' . $stat['count'];
                    echo ' Deduct member ' . $memberId . ' score ' . $deduct . PHP_EOL;
                }
            }
        }

        echo 'Success' . PHP_EOL;
    }
}
