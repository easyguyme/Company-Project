<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\modules\uhkklp\utils\PushUtil;
use backend\utils\LogUtil;

class PushMessage
{
    public function perform()
    {
        LogUtil::error('uhkklp-push-message:  ' . date('Y-m-d H:i:s', time()) . ' Execute job, enter method perform');
        $args = $this->args;
        PushUtil::pushMessage($args['messageId'], $args['time']);
    }
}
