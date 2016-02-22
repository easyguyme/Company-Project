<?php
namespace backend\modules\chat\job;

use Yii;
use MongoId;
use backend\components\resque\SchedulerJob;
use backend\models\Account;
use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\modules\helpdesk\models\ChatConversation;
use backend\utils\LogUtil;
use backend\utils\TimeUtil;
use backend\models\PendingClient;
use backend\modules\helpdesk\models\HelpDesk;

class ClearOffline extends SchedulerJob
{
    public function perform()
    {
        $accounts = Account::findAll(['enabledMods' => 'helpdesk']);
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $this->removeUserBasedOnActivities($account->_id);
            }
        }
    }

    private function removeUserBasedOnActivities($accountId)
    {
        $accountIdStr = (string) $accountId;
        $helpDeskSetting = HelpDeskSetting::findOne(['accountId' => $accountId]);
        $timeStep = $helpDeskSetting->maxWaitTime * TimeUtil::SECONDS_OF_MINUTE;
        $activities = ChatConversation::getActivityMap($accountIdStr);
        $activities = empty($activities) ? [] : $activities;
        foreach ($activities as $userId => $activeAt) {
            // The user ID of helpdesk is mongo ID
            $isHelpdesk = MongoId::isValid($userId);
            // Client is silent for a long time
            if (!$isHelpdesk && (time() - TimeUtil::ms2sTime($activeAt)) > $timeStep) {
                LogUtil::info(['timeStep' => $timeStep, 'userId' => $userId, 'accountId' => $accountIdStr], 'schedule-clear-users');
                $pending = PendingClient::findByOpenId($userId, $accountId);
                if (empty($pending)) {
                    $tuisongbao = Yii::$app->tuisongbao;
                    // Get client conversation
                    $conversations = $tuisongbao->getConversations($userId);
                    if (!empty($conversations)) {
                        //client user only have one conversation
                        $conversation = $conversations[0];
                        $helpdesk = $conversation['extra']['helpdesk'];
                        $client = $conversation['extra']['client'];
                        $accountId = $accountIdStr;

                        // Only handle PC user
                        if (empty($client['channelId'])) {
                            //send the desk left message to client for wechat, weibo or alipay
                            LogUtil::info(['client' => $client, 'accountId' => $accountId, 'helpdesk' => $helpdesk], 'schedule-clear-users');
                            $tuisongbao->notifyClientLeft($client, $helpdesk['id'], $conversation['conversationId'], $accountId);
                        }
                    }
                } else {
                    // Remove pending client
                    $pending->delete();
                    $activities = self::getActivityMap($accountIdStr);
                    unset($activities[$userId]);
                    self::setActivityMap($accountIdStr, $activities);
                }
            } else if ($isHelpdesk && 0 === $activeAt) {
                // Remove the helpdesk in case that his socket is closed
                ChatConversation::removeUser($userId, $accountIdStr);
            }
        }
    }
}
