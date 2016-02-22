<?php
namespace backend\modules\mall;

use Yii;
use backend\components\BaseInstall;
use backend\models\MessageTemplate;

class Install extends BaseInstall
{
    public function run($accountId)
    {
        parent::run($accountId);

        //create default template
        $where = ['accountId' => $accountId, 'name' => ['$in' => [MessageTemplate::REDEMPTION_TITLE, MessageTemplate::PROMOTIONCODE_TITLE]]];
        $result = MessageTemplate::findOne($where);
        if (empty($result)) {
            MessageTemplate::createDefaultTemplate($accountId);
        }
    }
}
