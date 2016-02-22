<?php
namespace backend\components;

use backend\utils\LogUtil;

/**
 * This is the base event class for aug-marketing
 * @author Vincent Hou
 *
 **/
class BaseEvent
{
    public function handle($data)
    {
        LogUtil::info(['class' => get_called_class(), 'data' => $data], 'event');
    }
}
