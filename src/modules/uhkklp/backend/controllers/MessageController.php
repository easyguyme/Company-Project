<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\web\Controller;
use backend\models\Token;
use backend\modules\uhkklp\models\Message;
use backend\modules\uhkklp\models\PushMessage;
use backend\modules\uhkklp\models\PushUser;
use backend\modules\uhkklp\utils\MessageUtil;
use yii\mongodb\Query;
use backend\utils\LogUtil;

class MessageController extends BaseController
{
    public $enableCsrfValidation = false;

    private function _uniquePushUsers($pushUsers)
    {
        $arr = [];
        foreach ($pushUsers as $pushUser) {
            $isUnique = true;
            foreach ($arr as $key => $a) {
                if ($pushUser['token'] == $a['token']) {
                    if (!empty($pushUser['mobile'])) {
                        if (empty($arr[$key]['mobile'])) {
                            $arr[$key]['mobile'] = $pushUser['mobile'];
                        } else {
                            $arr[$key]['mobile'] = $arr[$key]['mobile']  . ', ' . $pushUser['mobile'];
                        }
                    }
                    $isUnique = false;
                    break;
                }
            }
            if ($isUnique) {
                $arr[] = $pushUser;
            }
        }
        return $arr;
    }

    // private function _updatePushMessages($phoneNums, $condition)
    // {
    //     $pushUsers = [];
    //     foreach ($phoneNums as $phone) {
    //         $query = new Query();
    //         $result = $query->from('uhkklpPushUser')->select(['token'])->where(['mobile' => $phone])->all();
    //         $pushUsers = array_merge($pushUsers, $result);
    //     }
    //     $pushUsers = $this->_uniquePushUsers($pushUsers);
    //     foreach ($pushUsers as $pushUser) {
    //         $pushMessage = PushMessage::findOne(['token' => $pushUser['token']]);
    //         if (!empty($pushMessage)) {
    //             $pushMessage->attributes = $condition;
    //             $pushMessage->update();
    //         }
    //     }
    // }

    private function _deletePushMessages($phoneNums)
    {

        $pushUsers = [];
        foreach ($phoneNums as $phone) {
            $query = new Query();
            $result = $query->from(PushUser::collectionName())->select(['token'])->where(['mobile' => $phone, 'accountId' => $this->getAccountId()])->all();
            $pushUsers = array_merge($pushUsers, $result);
        }
        $pushUsers = $this->_uniquePushUsers($pushUsers);
        foreach ($pushUsers as $pushUser) {
            $pushMessage = PushMessage::findOne(['token' => $pushUser['token'], 'accountId' => $this->getAccountId()]);
            if (!empty($pushMessage)) {
                $pushMessage->delete();
            }
        }
    }

    private function _savePushMessages($messageId, $phoneNums)
    {
        $pushUsers = [];
        if (empty($phoneNums)) {
            $query = new Query();
            $result = $query->from(PushUser::collectionName())->select(['token', 'deviceType', 'mobile'])->where(['accountId' => $this->getAccountId()])->all();
            $pushUsers = array_merge($pushUsers, $result);
        } else {
            foreach ($phoneNums as $phone) {
                $query = new Query();
                $result = $query->from(PushUser::collectionName())->select(['token', 'deviceType', 'mobile'])->where(['mobile' => $phone, 'accountId' => $this->getAccountId()])->all();
                $pushUsers = array_merge($pushUsers, $result);
            }
        }
        $pushUsers = $this->_uniquePushUsers($pushUsers);
        foreach ($pushUsers as $pushUser) {
            $pushMessage = new PushMessage();
            if (!empty($pushUser['mobile'])) {
                $pushMessage->mobile = $pushUser['mobile'];
            }
            $pushMessage->token = $pushUser['token'];
            $pushMessage->deviceType = $pushUser['deviceType'];
            $pushMessage->messageId = $messageId;
            if (!empty($this->getAccountId())) {
                $pushMessage->accountId = $this->getAccountId();
            }
            $pushMessage->insert();
        }
    }

    private function _changePushMessages($messageId, $newPhoneNums, $oldPhoneNums)
    {
        if ($newPhoneNums == $oldPhoneNums) {
            return;
        }

        if (empty($newPhoneNums)) {
            $this->_deletePushMessages($oldPhoneNums);
            $this->_savePushMessages($messageId, $newPhoneNums);
            return;
        }
        if (empty($oldPhoneNums)) {
            PushMessage::deleteAll(['messageId' => $messageId]);
            $this->_savePushMessages($messageId, $newPhoneNums);
            return;
        }

        $PhoneNums2BInset = array_values(array_diff($newPhoneNums, $oldPhoneNums));
        $PhoneNums2BDelete = array_values(array_diff($oldPhoneNums ,$newPhoneNums));

        if (!empty($PhoneNums2BDelete)) {
            $this->_deletePushMessages($PhoneNums2BDelete);
        }

        if (!empty($PhoneNums2BInset)) {
            $this->_savePushMessages($messageId, $PhoneNums2BInset);
        }

    }

    public function actionSave()
    {
        $data = file_get_contents('php://input', true);
        $data = json_decode($data, true);
        $message = new Message();

        if (!empty($this->getAccountId())) {
            $data['accountId'] = $this->getAccountId();
        }
        $data['isPushed'] = false;
        $data['isDeleted'] = false;

        $time = $data['pushTime'] / 1000;
        $data['pushTime'] = new \MongoDate($time);
        $data['accountId'] = $this->getAccountId();

        $message->attributes = $data;
        $message->save();
        $this->_savePushMessages($message->_id, $message->pushDevices);

        LogUtil::error('uhkklp-push-message:  ' . date('Y-m-d H:i:s', time()) . ' Create scheduler job');
        Yii::$app->resque->enqueueJobAt((int)$time, 'global', 'backend\modules\uhkklp\job\PushMessage', ['messageId' => $message->_id->{'$id'}, 'time' => $time]);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => '1'];
    }

    public function actionGet()
    {
        $request = Yii::$app->request;
        $id = $request->get('$id');
        $message = Message::findOne([$id]);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $message->attributes;
    }

    public function actionGetList()
    {
        $data = file_get_contents('php://input', true);
        $data = json_decode($data, true);

        $message = new Message();
        $dataCount = $message->getCount(['accountId' => $this->getAccountId()]);
        $messages = $message->getList($data['currentPage'], $data['pageSize'], $data['sort'], ['accountId' => $this->getAccountId()]);

        $resData = ['dataCount'=>$dataCount, 'messages'=>$messages];
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return $resData;
    }

    public function actionUpdateOne()
    {
        $data = file_get_contents('php://input', true);
        $data = json_decode($data, true);

        // $data['startTime'] = new \MongoDate($data['startTime'] / 1000);
        // $data['endTime'] = new \MongoDate($data['endTime'] / 1000);
        $time = $data['pushTime'] / 1000;
        $data['pushTime'] = new \MongoDate($time);

        $id = $data['_id']['$id'];
        $message = Message::findOne([$id]);
        $oldPhoneNums = $message->pushDevices;

        $repush = true;
        if ($data['pushTime'] == $message->pushTime) {
            $repush = false;
        }

        unset($data['accountId']);
        $message->attributes = $data;
        $message->update();
        $this->_changePushMessages($message->_id, $message->pushDevices, $oldPhoneNums);

        if ($repush) {
            LogUtil::error('uhkklp-push-message:  ' . date('Y-m-d H:i:s', time()) . ' Create scheduler job');
            Yii::$app->resque->enqueueJobAt((int)$time, 'global', 'backend\modules\uhkklp\job\PushMessage', ['messageId' => $message->_id->{'$id'}, 'time' => $time]);
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => '2'];
    }

    // public function actionUpdateStatus()
    // {
    //     $request = Yii::$app->request;
    //     $id = $request->post('$id');
    //     $status = $request->post('status');
    //     $message = Message::findOne([$id]);
    //     $message->status = $status;
    //     $message->update();
    //     Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    //     return ['code' => '3'];
    // }

    public function actionDelete()
    {
        $request = Yii::$app->request;
        $id = $request->post('$id');
        $message = Message::findOne([$id]);
        $message->isDeleted = true;
        $message->update();
        PushMessage::deleteAll(['messageId' => $message->_id]);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => '-1'];
    }

    public function actionTest()
    {

    }

}
