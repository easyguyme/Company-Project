<?php
namespace backend\modules\management\job;

use Yii;
use MongoId;
use backend\models\SensitiveOperation;
use backend\utils\LogUtil;

/**
* Job for init a new account, install core modules
*/
class InitNewAccount
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['account'])) {
            LogUtil::error(['message' => 'Missing param to init account data'], 'member');
            return;
        }
        $account = unserialize($args['account']);

        foreach ($account->enabledMods as $moduleName) {
            $installFilePath = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Install.php';
            if (file_exists($installFilePath)) {
                require_once($installFilePath);
                $className = 'backend\modules\\' . $moduleName . '\Install';
                if (class_exists($className)) {
                    $installObj = Yii::createObject($className);
                    $installObj->run($account->_id);
                }
            }
        }
    }
}
