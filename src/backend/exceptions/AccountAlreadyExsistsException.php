<?php
namespace backend\exceptions;

/**
 * AccountAlreadyExsistsException represents the exception when the wechat account has been created before.
 * @author Devin Jin
 **/
class AccountAlreadyExsistsException extends FailedResponseApiException
{
    public function __construct($message = null)
    {
        if(empty($message)) {
          $message = \Yii::t('channel', 'wechat_account_already_exsisted');
        }

        parent::__construct($message);
    }
}
