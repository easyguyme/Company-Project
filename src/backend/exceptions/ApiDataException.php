<?php
namespace backend\exceptions;

use backend\utils\LogUtil;
use yii\helpers\Json;
use backend\utils\StringUtil;
use Yii;

/**
 * Api data exception represents the exceptions for the 3rd-part
 * webservice apis for aug-marketing due to the incorrect data..
 * @author Devin Jin
 **/
class ApiDataException extends ApiException
{
    //errorCode => message key(defined in common.php[i18n])
    public $weconnectExceptionCodeMap = [
        500 => 'internal_server_error',
        100001 => 'save_fail',
        100002 => 'update_fail',
        100003 => 'method_not_allowed',
        100004 => 'data_error',
        100005 => 'data_error',
        100006 => 'request_wechat_error',
        100007 => 'request_weibo_error',
        100008 => 'parameters_missing',
        100009 => 'data_exists',
        100010 => 'data_error',
        100011 => 'data_error',
        100012 => 'data_error',
        100013 => 'request_alipay_error',
        100014 => 'request_third_part_error',
        100015 => 'data_error',
        100016 => 'request_wechat_trade_error',
        100017 => 'request_channel_time_error',
        200001 => 'parameters_missing',
        200002 => 'parameters_missing',
        200003 => 'parameters_missing',
        200004 => 'parameters_missing',
        200005 => 'wechat_start_time_error',
        200006 => 'user_interaction_time_timeout',
        200007 => 'parameters_missing',
        200008 => 'parameters_missing',
        200009 => 'file_too_large',
        200010 => 'delete_fail'
    ];

    /**
     * Constructor.
     * @param $url, String, the url for the api.
     * @param $response, array, the response from the api
     * @param $params, array, the params sent for the api
     * @param $category, string, the log category
     * @param $code , int, the error number
     * @author Devin Jin
     */
    public function __construct($url, $response = null, $params = null, $category = 'channel', \Exception $previous = null)
    {
        LogUtil::error(['url' => $url, 'response' => $response, 'params' => $params, 'code' => LogUtil::API_SERVER_ERROR], $category);
        $message = $this->getExceptionMessage($response);
        parent::__construct(500, $message, $previous);
    }

    public function getExceptionMessage($response)
    {
        if (StringUtil::isJson($response)) {
            $result = Json::decode($response);
            $weconnectExceptionCodeMap = $this->weconnectExceptionCodeMap;
            if (isset($result['data']['errorCode'])) {
                $code = $result['data']['errorCode'];
                if (isset($weconnectExceptionCodeMap[$code])) {
                    return Yii::t('common', $weconnectExceptionCodeMap[$code]);
                }
            }
        }
        return Yii::t('channel', 'api_data_exception');
    }
}
