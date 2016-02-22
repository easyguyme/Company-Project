<?php
namespace backend\components\dingding;

use Yii;
use yii\base\Component;
use backend\exceptions\ApiDataException;
use backend\utils\StringUtil;
use yii\helpers\Json;
use backend\utils\LogUtil;

class Connect extends Component
{
    //const for method
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_GET = 'get';
    const METHOD_DELETE = 'delete';

    public $domain;

    public function getJsTicket($accesstoken)
    {
        $url = $this->domain . '/get_jsapi_ticket';
        $params = [
            'access_token' => $accesstoken,
            'type' => 'jsapi'
        ];
        return $this->curl(self::METHOD_GET, $url, $params);
    }

    public function getUserByCode($accesstoken, $code)
    {
        $url = $this->domain . '/user/getuserinfo';
        $params = [
            'access_token' => $accesstoken,
            'code' => $code
        ];
        return $this->curl(self::METHOD_GET, $url, $params);
    }

    public function getUserById($accesstoken, $id)
    {
        $url = $this->domain . '/user/get';
        $params = [
            'access_token' => $accesstoken,
            'userid' => $id
        ];
        return $this->curl(self::METHOD_GET, $url, $params);
    }

    public function getDepartment($accesstoken)
    {
        $url = $this->domain . '/department/list';
        $params = [
            'access_token' => $accesstoken
        ];
        return $this->curl(self::METHOD_GET, $url, $params);
    }

    public function getUsersByDepartment($accesstoken, $departmentId)
    {
        $url = $this->domain . '/user/list';
        $params = [
            'access_token' => $accesstoken,
            'department_id' => $departmentId,
        ];
        return $this->curl(self::METHOD_GET, $url, $params);
    }

    private function curl($method, $url, $params = [], $logTarget = 'dingding')
    {
        //format header and params for post and put
        if (in_array($method, [self::METHOD_POST, self::METHOD_PUT])) {
            $method = $method . 'Json';
            $params = Json::encode($params);
        }
        $resultJson = Yii::$app->curl->$method($url, $params);

        $logUrl = strtoupper($method) . ' ' . $url;
        if (StringUtil::isJson($resultJson)) {
            $result = Json::decode($resultJson, true);
        } else {
            throw new ApiDataException($logUrl, $resultJson, $params, $logTarget);
        }
        LogUtil::info(['url' => $logUrl, 'response' => $resultJson, 'params' => $params], $logTarget);

        if ($result && isset($result['errcode']) && $result['errcode'] == 0) {
            return $result;
        } else {
            throw new ApiDataException($logUrl, $resultJson, $params, $logTarget);
        }
    }
}
