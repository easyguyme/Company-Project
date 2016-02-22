<?php
namespace backend\modules\uhkklp;

use Yii;
use backend\components\BaseInstall;
use backend\modules\uhkklp\utils\EarlybirdSmsUtil;

class Install extends BaseInstall
{
    public function run($accountId)
    {
        // parent::run($accountId);

        //create early bird sms template
        // EarlybirdSmsUtil::createEarlyBirdSmsTemplate($accountId);
    }
}
