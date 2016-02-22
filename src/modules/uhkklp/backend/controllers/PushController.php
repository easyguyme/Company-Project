<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use backend\modules\uhkklp\models\KlpAccountSetting;
use backend\modules\uhkklp\models\PushUser;
use backend\modules\uhkklp\utils\GCMUtil;
use backend\modules\uhkklp\utils\APMUtil;
use backend\utils\LogUtil;

class PushController extends BaseController
{
    public function actionSave()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return [
                'code' => 400,
                'msg' => 'missing accountId'
            ];
        }

        $body = Yii::$app->request->getRawBody();
        $query = json_decode($body);
        if (empty($query)) {
            return [
                'code' => 400,
                'msg' => 'bad request'
            ];
        }
        if (!isset($query->token)) {
            return [
                'code' => 400,
                'msg' => 'missing token'
            ];
        }

        $query->accountId = new \MongoId($accountId);
        $user = PushUser::saveToken($query);
        return [
            'code' => 200,
            'msg' => 'ok'
        ];
    }

    public function actionTest()
    {
        // $user = new PushUser();
        // $user->mobile = '0911111119';
        // $user->token = 'APA91bE64-zQyhsdSdm7RGJgiY8hiOI3UDa_x08s3A60Of8If9jw9-XVvXCdsLCY8L1NSL4MrKKbpvEBO-EaIoW01r0dFaqEThKbG48LX3cFO66qdHrYo3H9QW50az7-Nxd6s6w0Tx5Pj9Q9FySOnSnYJT6a2xcZbg';
        // $user->deviceType = 'Android';
        // $user->save();
        // var_dump($user);

        // $GCM = new GCMUtil();
        // $GCM->pushMessage(['0944444444', '0911111111', '0922222222', '0912345678'], 'push message', '55d6c463c9d1228e3b8b4567');
        // $GCM->boardcast('boardcast', '55d6c463c9d1228e3b8b4567');
        // return $GCM->pushMessageByToken('APA91bE64-zQyhsdSdm7RGJgiY8hiOI3UDa_x08s3A60Of8If9jw9-XVvXCdsLCY8L1NSL4MrKKbpvEBO-EaIoW01r0dFaqEThKbG48LX3cFO66qdHrYo3H9QW50az7-Nxd6s6w0Tx5Pj9Q9FySOnSnYJT6a2xcZbg',
        //     'push message to single', $this->getAccountId());

        // $setting = new KlpAccountSetting();
        // $setting->accountId = new \MongoId($this->getAccountId());
        // $setting->gcmKey = 'AIzaSyAvEydx1z1mOUTFctiNji5kpKqiLZuUI0U';
        // $setting->save();
        // var_dump($setting);
    }

    public function actionPushApple()
    {
        $APS = new APMUtil();
        //$content = Yii::$app->request->get('content');
        //$deviceToken = Yii::$app->request->get('deviceToken');

        for ($i = 0; $i < 10; $i++) {
            $msg = array(
                'content' => '每週大廚上菜：珍品八寶魚頭鍋',
                'linkType' => 'app',
                'newsId' => '',
            );
            $deviceToken = 'b22cfd968abd6a2e151194f633633706c60aa75431160dbc0759844470eeddc'.$i;
            $res = $APS->pushMsg($deviceToken, $msg, $this->getAccountId(), $i);
            LogUtil::error('Push result: ' . $deviceToken . ' + ' . $res);
            if ($res != 200) {
                unset($APS);
                $APS = new APMUtil();
            }
        }
        $APS->closeFp();

        return 200;
    }
}
