<?php

namespace backend\components;

use yii\base\Component;
use yii\helpers\Json;
use Yii;
use backend\utils\LogUtil;
use backend\utils\StringUtil;

class Webhook extends Component
{
    public $domain;

    //const for method
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_GET = 'get';
    const METHOD_DELETE = 'delete';

    const EVENT_MEMBER_CREATED = 'membercreated';
    const EVENT_PROMOTION_CODE_REDEEMED = 'promotion_code_redeemed';
    const EVENT_PRODUCT_REDEEMED = 'product_redeemed';
    const EVENT_STAFF_CREATED = 'staff_created';

    public function triggerEvent($data)
    {
        $params = [
            'type' => 'event',
            'data' => $data,
        ];
        $url = $this->domain . '/webhooks/portal';

        return $this->_requestService($url, self::METHOD_POST, $params);
    }

    /**
     * Request the service with basic auth
     * @param string $url requested url
     * @param string $method requested method
     * @param string $params requested parameters
     */
    private function _requestService($url, $method, $params = NULL)
    {
        //format header and params for post and put
        if (in_array($method, [self::METHOD_POST, self::METHOD_PUT])) {
            $method = $method . 'Json';
            $params = Json::encode($params);
        }
        $resultJson = Yii::$app->curl->$method($url, $params);

        $logUrl = strtoupper($method) . ' ' . $url;
        $logTarget = 'webhook';
        LogUtil::info(['url' => $logUrl, 'response' => $resultJson, 'params' => $params], $logTarget);
        if (StringUtil::isJson($resultJson)) {
            return Json::decode($resultJson, true);
        } else {
            LogUtil::error(['url' => $logUrl, 'response' => $resultJson, 'params' => $params], $logTarget);
        }
    }
}
