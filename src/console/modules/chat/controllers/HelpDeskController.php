<?php
namespace console\modules\chat\controllers;

use Yii;
use yii\console\Controller;
use backend\modules\helpdesk\models\HelpDesk;

/**
 * HelpDesk relative operations
 */
class HelpDeskController extends Controller
{
    /**
     * Clean all helpdesks online of one account
     */
    public function actionCleanAll($accountId)
    {
        Yii::$app->cache->set('conversations' . $accountId, []);
    }

    /**
     * Clean target helpdesks
     */
    public function actionCleanOffline(array $ids)
    {
        $cache = Yii::$app->cache;
        foreach($ids as $helpDeskId)
        {
            $helpDesk = HelpDesk::findByPk(new \MongoId($helpDeskId));
            $accountId = $helpDesk->accountId;
            $conversations = $cache->get('conversations' . $accountId);
            unset($conversations[$helpDeskId]);
            $cache->set('conversations' . $accountId, $conversations);
        }
    }

    /**
     * Set helpdesk notification type
     */
    public function actionSetNotificationType()
    {
        $helpdesks = HelpDesk::findAll([]);
        if (!empty($helpdesks)) {
            foreach($helpdesks as $helpdesk) {
                if(!isset($helpdesk->notificationType)) {
                    $helpdesk->notificationType = HelpDesk::NOTIFICATION_TYPE_DESKTOP_MARK;
                    if ($helpdesk->save()) {
                        echo "Success to set notification type to helpdesk " . $helpdesk->_id . "\n";
                    } else {
                        echo "Fail to set notification type to helpdesk " . $helpdesk->_id . " successfully" . "\n";
                    }
                }
            }
        }
    }
}
