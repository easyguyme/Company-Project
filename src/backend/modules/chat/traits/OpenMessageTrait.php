<?php

namespace backend\modules\chat\traits;

use backend\components\BaseControllerTrait;
use yii\web\ServerErrorHttpException;
use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\chat\controllers\ConversationController;
use backend\utils\LogUtil;

/**
 * This is a trait for push message
 */
trait OpenMessageTrait
{
    /**
     * This method is used to push message.
     *  If mobile help-desk client is on backend, push message.
     */
    public function pushMessage($deskId, $eventType, $extra, $message = null)
    {
        $cache = \Yii::$app->cache;
        $deskStrId = $deskId . '';
        //Update the helpdesk unread message count
        $count = $cache->get(ConversationController::UNREAD_COUNT_PREFIX . $deskStrId);
        empty($count) && ($count = 0);

        $desk = HelpDesk::findByPk($deskId);
        if (empty($desk->deviceToken) || empty($desk->environment)) {
            LogUtil::info(['push' => 'missing device token', 'desk' => $deskId, 'eventType' => $eventType, 'extra' => $extra]);
        } else {
            if ($eventType == ConversationController::EVENT_CHAT_MESSAGE && $this->isPushMessage($deskId, $desk->accountId)) {
                $cache->set(ConversationController::UNREAD_COUNT_PREFIX . $deskStrId, ++$count);
            } else {
                //no code here, push state like 'sessionConnected', no need to add $count
            }
            $extra['type'] = $eventType;
            $target = [$desk->environment => [$desk->deviceToken]];
            \Yii::$app->tuisongbao->pushMessage($target, $count, $extra, $message);
        }
    }

    public function isPushMessage($deskId, $accountId)
    {
        $deskStrId = $deskId . '';
        $conversations = ChatConversation::getConversationMap($accountId);
        $onlineDesks =  \Yii::$app->$cache->get('wm-online-desks' . $accountId . '');
        $conversations = empty($conversations) ? [] : $conversations;
        $onlineDesks = empty($onlineDesks) ? [] : $onlineDesks;
        //Check if the helpdesk is not left and not online, push message
        if (array_key_exists($deskStrId, $conversations) && !array_key_exists($deskStrId, $onlineDesks)) {
            return true;
        } else {
            return false;
        }
    }
}
