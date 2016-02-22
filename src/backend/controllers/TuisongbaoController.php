<?php
namespace backend\controllers;

use backend\components\Controller;
use backend\utils\LogUtil;

class TuisongbaoController extends Controller
{
    public function actionAuth()
    {
        $secret = TUISONGBAO_SECRET;
        $socketId = $this->getParams('socketId');
        $channelName = $this->getParams('channelName');
        $authData = $this->getParams('authData');
        $parts = explode(':', $authData);
        $clientId = $parts[1];
        $userData = ['userId' => $clientId, 'userInfo' => []];

        $userDataJsonStr = json_encode($userData);
        $strToSign = $socketId . ':' . $channelName . ':' . $userDataJsonStr;
        LogUtil::info(['strToSign' => $strToSign, 'secret' => $secret], 'signature');
        $signature = hash_hmac('sha256', $strToSign, $secret);
        LogUtil::info(['signature' => $signature, 'channelData' => $userDataJsonStr], 'signature');
        $result = ['signature' => $signature, 'channelData' => $userDataJsonStr];
        header("Content-Type:application/json");
        echo json_encode($result);
    }
}
