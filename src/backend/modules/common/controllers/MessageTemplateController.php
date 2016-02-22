<?php
namespace backend\modules\common\controllers;

use backend\models\MessageTemplate;

class MessageTemplateController extends BaseController
{
    /**
     * get the template list only with the information for webhook usage
     */
    public function actionWebhooks()
    {
        $accountId = $this->getAccountId();
        $result = [];
        $templates = MessageTemplate::findAll(['accountId' => $accountId]);
        foreach ($templates as $template) {
            $result[] = [
                'name'=> $template->name,
                'useWebhook'=> $template->useWebhook
            ];
        }
        return $result;
    }
}
