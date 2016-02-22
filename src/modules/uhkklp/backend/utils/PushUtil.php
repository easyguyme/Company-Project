<?php
namespace backend\modules\uhkklp\utils;

use Yii;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\Message;
use backend\utils\LogUtil;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\PushUser;

class PushUtil
{
    public static function pushMessage($messageId, $time)
    {

        $time = (int)$time;

        $logFileName = 'pushmessage';

        $logMsg = 'Message (' . $messageId . ') started to push at ' .  date('Y-m-d H:i:s', time());
        LogUtil::error('uhkklp-push-message:  ' . $logMsg);

        $queryMessage = new Query();

        $logMsg = '---Find message by id...';
        LogUtil::error('uhkklp-push-message:  ' . $logMsg);

        $message = $queryMessage->from('uhkklpMessage')
            ->select(['content', 'linkType', 'newsId', 'pushTime'])
            ->where(['_id' => new \MongoId($messageId)])
            ->all();

        if (empty($message)) {
            $logMsg = 'Push canseled, message was deleted in database';
            LogUtil::error('uhkklp-push-message:  ' . $logMsg);
            return;
        }

        $message = $message[0];

        if ($message['pushTime']->sec != $time) {
            $logMsg = 'Push canseled, pushTime was changed in database';
            LogUtil::error('uhkklp-push-message:  ' . $logMsg);
            return;
        }

        $logMsg = '---done';
        LogUtil::error('uhkklp-push-message:  ' . $logMsg);

        $logMsg = '---Find tokens...';
        LogUtil::error('uhkklp-push-message:  ' . $logMsg);

        $queryUser = new Query();
        $users1 = $queryUser->from('uhkklpPushMessage')
            ->select(['mobile', 'token', 'deviceType', 'accountId'])
            ->where(['messageId' => new \MongoId($messageId)])
            ->all();
        $users = array_reverse($users1);

        $logMsg = '---done';
        LogUtil::error('uhkklp-push-message:  ' . $logMsg);

        $gcmUtil = new GCMUtil();
        $apmUtil = new APMUtil();

        $logMsg = '---Pushing...';
        LogUtil::error('uhkklp-push-message:  ' . $logMsg);


        $msg['content'] = $message['content'];
        $msg['linkType'] = $message['linkType'];
        $msg['newsId'] = $message['newsId'];

        $log = new PushMessageLog();
        $log->messageId = $messageId;
        $log->startTime = date('Y-m-d H:i:s' ,time());
        $log->save();

        $results = [];
        $rowId = 1;
        foreach ($users as $user) {
            if ($user['deviceType'] == PushUser::DEVICE_ANDROID) {
                $response = $gcmUtil->pushMessageByToken($user['token'], $msg, $user['accountId']);
                LogUtil::error('GCMUtil:  ' . $user['token'] . '  ' . $response);
            } else {
                $response = $apmUtil->pushMsg($user['token'], $msg, $user['accountId'], $rowId);
                LogUtil::error('APMUtil:  ' . $user['token'] . '  ' . $response);
                if ($response != 200) {
                    unset($apmUtil);
                    $apmUtil = new APMUtil();
                }
                usleep(500000);
            }
            $result['type'] = $user['deviceType'];
            $result['token'] = $user['token'];
            if (!empty($user['mobile'])) {
                $result['mobile'] = $user['mobile'];
            }
            $result['res'] = $response;
            $results[] = $result;
            $log->results = $results;
            $log->save();
            unset($result);
            $rowId++;
        }

        $apmUtil->closeFp();
        $logMsg = 'Push finished at ' . date('Y-m-d H:i:s' ,time());
        LogUtil::error('uhkklp-push-message:  ' . $logMsg);

        $log->endTime = date('Y-m-d H:i:s' ,time());
        $log->accountId = $user['accountId'];
        $log->save();

        $uhkklpMessage = Message::findOne([$messageId]);
        $uhkklpMessage->isPushed = true;
        $uhkklpMessage->update();

        return $results;
    }
}
