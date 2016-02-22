<?php
/**
 * FailedResponseApiException represents the exceptions when getting error message from 3d-rd-part apis
 * @author Devin Jin
 **/
namespace backend\exceptions;

use yii\helpers\Json;

class FailedResponseApiException extends ApiException
{
    /**
     * Constructor.
     * @param integer $status HTTP status code, such as 404, 500, etc.
     * @param string $message error message
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($message = null)
    {
        if (is_array($message)) {
            $message = Json::encode($message);
        }

        parent::__construct(503, $message, null);
    }
}
