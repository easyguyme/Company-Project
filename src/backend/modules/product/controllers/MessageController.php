<?php
namespace backend\modules\product\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use backend\models\Account;
use backend\modules\member\models\Member;
use backend\models\MessageTemplate;
use backend\utils\MessageUtil;
use backend\components\Controller;
use backend\utils\LogUtil;

class MessageController extends Controller
{
    public function actionGenerateEmailTemplate()
    {
        $params = $this->getParams();
        list($view, $emailParams) = MessageTemplate::getEmailVar($params);
        //check the account have template
        $accountId = $this->getAccountId();
        $name = MessageTemplate::getTemplateName($params['type']);
        $template = MessageTemplate::findOne(['accountId' => $accountId, 'name' => $name]);
        if (!empty($template->email['content'])) {
            $body = MessageTemplate::getEmailTemplate($name, $accountId, $emailParams);
        } else {
            $body = $this->renderPartial($view, $emailParams);
        }
        return ['template' => $body];
    }

    public function actionSendRedemptionEmail()
    {
        $params = $this->getParams();
        list($view, $emailParams) = MessageTemplate::getEmailVar($params);
        //check the account have template
        $accountId = $this->getAccountId();

        $type = $params['type'];
        if (!in_array($type, [MessageTemplate::REDEMPTION_TYPE, MessageTemplate::PROMOCODE_TYPE])) {
            throw new BadRequestHttpException('type is invaild');
        }

        $name = MessageTemplate::getTemplateName($type);
        $template = MessageTemplate::findOne(['accountId' => $accountId, 'name' => $name]);
        if (!empty($template->email['content']) && !empty($template->email['title'])) {
            $subject = $template->email['title'];
            $name = $type == MessageTemplate::REDEMPTION_TYPE ? MessageTemplate::REDEMPTION_TITLE : MessageTemplate::PROMOTIONCODE_TITLE;
            $body = MessageTemplate::getEmailTemplate($name, $accountId, $emailParams);
        } else {
            $subject = $type == MessageTemplate::REDEMPTION_TYPE ? MessageTemplate::REDEMPTION_SUBJECT : MessageTemplate::PROMOCODE_SUBJECT;
            $body = $this->renderPartial($view, $emailParams);
        }

        $mail = Yii::$app->mail;
        $member = Member::getMemberInfo($params['memberId'], 'email');
        if (!empty($member['email']) && !empty($body)) {
            return $mail->sendMail($member['email'], $subject, $accountId, $body);
        } else {
            return false;
        }
    }

    public function actionGenerateMobileTemplate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        $name = MessageTemplate::getTemplateName($params['type']);
        $mobileParams = MessageTemplate::getMobileVar($params);
        $specialParams = MessageTemplate::getProductList($params);
        $template = MessageTemplate::getMobileTemplate($name, $accountId, $mobileParams, $specialParams);

        return ['template' => $template];
    }

    public function actionSendRedemptionMessage()
    {
        $params = $this->getParams();
        $name = MessageTemplate::getTemplateName($params['type']);
        $mobileParams = MessageTemplate::getMobileVar($params);
        $accountId = $this->getAccountId();

        $specialParams = MessageTemplate::getProductList($params);
        $template = MessageTemplate::getMobileTemplate($name, $accountId, $mobileParams, $specialParams);

        $params['memberId'] = new \MongoId($params['memberId']);
        $member = Member::getMemberInfo($params['memberId'], 'tel');

        if (!empty($member['tel']) && !empty($template)) {
            return MessageUtil::sendMobileMessage($member['tel'], $template, $accountId);
        } else {
            return false;
        }
    }

    /**
     * send wechat message
     */
    public function actionSendWechatMessage()
    {
        return true;
    }
}
