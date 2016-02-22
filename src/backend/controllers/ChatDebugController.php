<?php
namespace backend\controllers;

use Yii;
use MongoId;
use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\helpdesk\models\ChatConversation;
use backend\components\Controller;

class ChatDebugController extends Controller
{
    /**
     * Get online helpdesk status
     * @return array the information for helpdesks
     */
    public function actionHelpdeskList()
    {
        $data = [];
        $accountId = $this->getAccountId();
        $conversations = ChatConversation::getConversationMap($accountId);
        foreach ($conversations as $helpdeskId => $clients) {
            $helpdesk = HelpDesk::findByPk(new MongoId($helpdeskId));
            $data[] = [
                'id' => $helpdeskId,
                'clients' => $clients,
                'count' => $helpdesk->clientCount,
                'info' => $helpdesk
            ];
        }
        return $data;
    }

    /**
     * Get cache data
     * @return array conversations and activities in redis cache
     */
    public function actionCache()
    {
        $accountId = $this->getAccountId();
        return [
            'conversations' => ChatConversation::getConversationMap($accountId),
            'activities' => ChatConversation::getActivityMap($accountId)
        ];
    }

    /**
     * Clear all the helpdesks in cache
     * @return array conversations and activities in redis cache
     */
    public function actionClearHelpdeskList()
    {
        $accountId = $this->getAccountId();
        ChatConversation::setConversationMap($accountId, []);
        ChatConversation::setActivityMap($accountId, []);
        return [
            'conversations' => ChatConversation::getConversationMap($accountId),
            'activities' => ChatConversation::getActivityMap($accountId)
        ];
    }

    /**
     * Clear all the clients for helpdesks
     * @return array conversations and activities in redis cache
     */
    public function actionClearClientList()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $conversations = ChatConversation::getConversationMap($accountId);
        $activities = ChatConversation::getActivityMap($accountId);
        $data = [];
        if (empty($params['helpdesk'])) {
            foreach ($conversations as $helpdeskId => $clients) {
                if (!empty($activities[$helpdeskId])) {
                    unset($activities[$helpdeskId]);
                }
                HelpDesk::flushClientCount(new MongoId($helpdeskId));
                $data[$helpdeskId] = [];
            }
        } else {
            foreach ($conversations as $helpdeskId => $clients) {
                if ($params['helpdesk'] == $helpdeskId) {
                    if (!empty($activities[$helpdeskId])) {
                        unset($activities[$helpdeskId]);
                    }
                    HelpDesk::flushClientCount(new MongoId($helpdeskId));
                    $data[$helpdeskId] = [];
                    break;
                }
            }
        }

        ChatConversation::setActivityMap($accountId, $activities);
        ChatConversation::setConversationMap($accountId, $data);
        return [
            'conversations' => ChatConversation::getConversationMap($accountId),
            'activities' => ChatConversation::getActivityMap($accountId)
        ];
    }

    public function actionConversations($userId)
    {
        return Yii::$app->tuisongbao->getConversations($userId);
    }

    public function actionInactiveConversations($userId)
    {
        return Yii::$app->tuisongbao->getConversations($userId, '', '', '', false);
    }
}
