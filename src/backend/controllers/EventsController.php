<?php
namespace backend\controllers;

use Yii;
use yii\helpers\Json;
use yii\base\InvalidParamException;
use backend\components\Controller;
use backend\utils\LogUtil;

class EventsController extends Controller
{
    public function actionWeconnect()
    {
        $data = $this->getParams();
        LogUtil::info(['weconnect data' => $data], 'weconnect');
        if (!empty($data['modules'])) {
            $moduleRealPath = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
            $data['modules'] = array_unique($data['modules']);
            foreach ($data['modules'] as $module) {
                if (is_file($classFile = $moduleRealPath . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR . 'WeconnectEvent.php')) {
                    $class = 'backend\modules\\' . $module . '\events\WeconnectEvent';
                    $object = new $class();
                    unset($data['modules']);
                    call_user_func([$object, 'handle'], $data);
                }
            }
        } else {
            throw new InvalidParamException('Module name is required');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action) && $this->validateSignature()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * validate go api signature
     * @return boolean
     */
    private function validateSignature()
    {
        $request = Yii::$app->request;
        $bodyStr = $request->getRawBody();
        $headers = $request->getHeaders();
        //for golang
        $signature = hash_hmac('sha256', $bodyStr, 'Zc6smtltqrAToO44awoutxdS7LNsA81k');

        if (!empty($headers['X-Event-Signature']) && $signature === $headers['X-Event-Signature']) {
            return true;
        } else {
            LogUtil::error([
                'message' => 'event signature error',
                'X-API-Signature' => empty($headers['X-API-Signature']) ? '' : $headers['X-API-Signature'],
                'bodyStr' => $bodyStr,
                'signature' => $signature
            ], 'event');
            return false;
        }
    }
}
