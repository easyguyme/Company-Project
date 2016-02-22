<?php
namespace backend\modules\uhkklp\utils;

use Yii;
use Yii\base\Exception;
use backend\modules\uhkklp\models\KlpAccountSetting;
use backend\modules\uhkklp\models\PushUser;
use backend\modules\uhkklp\models\PushLog;

require_once Yii::getAlias('@app') . '/modules/uhkklp/config/config.php';

class GCMUtil
{
    public function pushMessageByToken($token, $message, $accountId) {
        set_time_limit(60000);
        if (is_string($token)) {
            $token = array($token);
        }
        if (is_array($token)) {
            return $this->push($token, $message, $accountId);
        }
    }

    private function push($registrationList, $data, $accountId) {
        $setting = KlpAccountSetting::findOne(['accountId' => new \MongoId($accountId)]);
        if (empty($setting) || !isset($setting->gcmKey) || empty($setting->gcmKey)) {
            return null;
        }
        $apiKey = $setting->gcmKey;
        if (!is_array($data)) {
            $data = [
                'message' => $data
            ];
        }
        $body = [
            'registration_ids' => $registrationList,
            'data' => $data,
        ];
        $response = null;
        try {
            $response = Yii::$app->curl->setHeaders(['Content-Type: application/json', 'Authorization: key=' . $apiKey])
                ->setOption(CURLOPT_PROXY, "sgsgprxs000.unileverservices.com")
                ->setOption(CURLOPT_PROXYPORT, 3128)
                ->post('https://gcm-http.googleapis.com/gcm/send', json_encode($body));
        } catch (Exception $e) {
            $response = $e->getMessage();
        }
        $this->saveLog($body, $response, $accountId);
        return $response;
    }

    private function getRegistrationIdListByMobile($mobile, $accountId) {
        $list = PushUser::getListByMobile($mobile, PushUser::DEVICE_ANDROID, $accountId);
        $registrationList = [];
        if (!empty($list)) {
            foreach ($list as $item) {
                array_push($registrationList, $item->token);
            }
        }
        return $registrationList;
    }

    private function saveLog($request, $response, $accountId)
    {
        $log = new PushLog();
        $log->request = $request;
        $log->response = $response;
        $log->deviceType = PushUser::DEVICE_ANDROID;
        $log->accountId = $accountId;
        $log->save();
    }
}
