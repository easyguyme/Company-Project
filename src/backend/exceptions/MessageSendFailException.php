<?php
namespace backend\exceptions;

use backend\utils\LogUtil;

/**
 * Message send fail exception represents the exceptions for WeConnect send message error
 **/
class MessageSendFailException extends ApiException
{
    /**
     * Constructor.
     * @param $url, String, the url for the api.
     * @param $response, array, the response from the api
     * @param $params, array, the params sent for the api
     * @param $category, string, the log category
     * @author Rex Chen
     */
    public function __construct(\Exception $previous = null)
    {
        parent::__construct(500, \Yii::t('channel', 'message_send_fail'), $previous);
    }
}
