<?php
namespace backend\modules\store;

use Yii;
use backend\components\BaseInstall;
use backend\models\MessageTemplate;

class Install extends BaseInstall
{
    public function run($accountId)
    {
        parent::run($accountId);

        //create default template
        $where = ['accountId' => $accountId, 'name' => MessageTemplate::STAFF_TITLE];
        $result = MessageTemplate::findOne($where);
        if (empty($result)) {
            MessageTemplate::createStaffTemplate($accountId);
        }
    }
}
