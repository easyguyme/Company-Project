<?php
namespace backend\exceptions;

use yii\web\HttpException;

/**
 * Api exception represents the common or unknown exceptions
 * for the 3rd-part webservice apis for aug-marketing.
 *
 * Define your own sub-class for the specific case while intergrate
 * with the 3rd-part apis. e.g. ApiDataException for incorrect data
 * in the requests, ApiAuthException for the authentic issues
 *
 * @author Devin Jin
 **/
class ApiException extends HttpException
{
    /**
     * @var the user-friendly name of this exception
     */
    public $name;

    /**
     * Constructor.
     * @param integer $status HTTP status code, such as 404, 500, etc.
     * @param string $message error message
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($status = 503, $message = null, \Exception $previous = null)
    {
        $this->statusCode = $status;
        parent::__construct($status, $message, 0, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return \Yii::t('channel', 'channel_service_error');
    }
}
