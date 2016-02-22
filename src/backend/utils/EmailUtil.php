<?php

namespace backend\utils;

use backend\models\Validation;
use Yii;

/**
 * This is class file for email utils
 * @author Sara Zhang
 **/
class EmailUtil
{
    public static function sendInviteEmail($user, $accountName, $link, $subject, $template = 'invitation')
    {
        $validation = new Validation();
        $validation->userId = $user->_id;
        $validation->code = StringUtil::uuid();
        $validation->expire = new \MongoDate(strtotime('+7 day'));

        if ($validation->save()) {
            $mail = Yii::$app->mail;
            $vars = [
                'name' => $accountName,
                'email' => $user->email,
                'host' => UrlUtil::getDomain(),
                'link' => str_replace('code', $validation->code, $link),
            ];
            $mail->setView('//mail/' . $template, $vars, '//layouts/email');
            $mail->sendMail($user->email, $subject, $user->accountId);

            return true;
        }
        return false;
    }
}
