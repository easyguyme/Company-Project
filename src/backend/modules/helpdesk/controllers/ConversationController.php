<?php

namespace backend\modules\helpdesk\controllers;

use Yii;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use MongoDate;
use backend\modules\helpdesk\models\Statistics;
use backend\components\Controller;
use backend\modules\helpdesk\models\ChatSession;

class ConversationController extends Controller
{
    public function actionIndex()
    {
        $params = $this->getQuery();
        $unlimited = $this->getQuery('unlimited', false);
        $accountId = $this->getAccountId();

        return ChatSession::search($accountId, $params, $unlimited);
    }

    public function actionStatistics()
    {
        $startTime = $this->getQuery('startTime');
        $endTime = $this->getQuery('endTime');
        $accountId = $this->getAccountId();
        $condition = ['accountId' => $accountId];

        if (!empty($startTime) && !empty($endTime)) {
            $condition['createdAt'] = ['$gt' => new \MongoDate(TimeUtil::ms2sTime($startTime)), '$lt' => new \MongoDate(TimeUtil::ms2sTime($endTime))];
        }
        //return json_encode($condition);
        $total = Statistics::getStatsCount($condition);
        $client = ['totalUser' => 0, 'totalConversation' => 0, 'totalMessage' => 0];
        if (count($total) > 0) {
            $client = $total[0];
        }

        //get the original statistics from db.(raw data)
        $clientCount = $client['totalUser'];
        $conversationCount = $client['totalConversation'];
        $clientMessageCount = $client['totalMessage'];
        $conversationDailyStatistics = Statistics::find()->where($condition)->orderBy(['createdAt' => SORT_ASC])->all();

        //format the statistics for chart
        $categories = [];
        $messageCountSeries = [];
        $clientCountSeries = [];
        $conversationCountSeries = [];
        foreach ($conversationDailyStatistics as $conversationDay) {
            $categories[] = MongodbUtil::MongoDate2String($conversationDay['createdAt'], 'Y-m-d');
            $messageCountSeries[] = $conversationDay['totalMessage'];
            $clientCountSeries[] = $conversationDay['totalUser'];
            $conversationCountSeries[] = $conversationDay['totalConversation'];
        }

        $statistics = [
            'categories' => $categories,
            'series' => [
                ['name' => 'helpdesk_users_count', 'data' => $clientCountSeries],
                ['name' => 'helpdesk_sessions_count', 'data' => $conversationCountSeries],
                ['name' => 'helpdesk_sent_message_count', 'data' => $messageCountSeries]
            ]
        ];

        return ['clientCount' => $clientCount, 'conversationCount' => $conversationCount, 'clientMessageCount' => $clientMessageCount, 'statistics' => $statistics];
    }
}
