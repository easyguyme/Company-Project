<?php
namespace backend\components;

use backend\utils\LogUtil;

class BaseInstall
{
    public function run($accountId)
    {
        LogUtil::info(['class' => get_called_class(), 'accountId' => $accountId], 'module');
    }
}
