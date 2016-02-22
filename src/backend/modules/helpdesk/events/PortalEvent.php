<?php
namespace backend\modules\helpdesk\events;

use backend\components\BaseEvent;
use backend\utils\LogUtil;

class PortalEvent extends BaseEvent
{
    public function handle($data)
    {
        parent::handle($data);
        LogUtil::info(['data'=>$data], 'test');
    }
}
