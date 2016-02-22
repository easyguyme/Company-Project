<?php
namespace backend\modules\management\controllers;

use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use yii\helpers\ArrayHelper;
use backend\models\Account;
use backend\models\Token;

class ModuleController extends BaseController
{
    /**
     * Activate a module
     *
     * <b>Request Type </b>:PUT
     * <b>Request Endpoints </b>: http://{server-domain}/api/management/module/activate-module
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for the user to activate a extension module.
     *
     * <b>Request Example </b>:
     * <pre>
     *  http://{server-domain}/api/management/module/activate-module
     * </pre>
     * <pre>
     * {
     *     "name" : "customer",
     * }
     * </pre>
     *
     **/
    public function actionActivateModule()
    {
        $moduleName = $this->getParams('name');
        $accountId = $this->getAccountId();

        $moduleNames = Yii::$app->extModule->getDependencyModules($moduleName);

        $account = Account::findByPk($accountId);
        if (in_array($moduleName, $account->enabledMods)) {
            throw new BadRequestHttpException(\Yii::t('common', 'function_has_been_activated'));
        }
        $updateAccountResult = Account::updateAll(['$addToSet' => ['enabledMods' => ['$each' => $moduleNames]]], ['_id' => $accountId]);
        if ($updateAccountResult) {
            $updateTokenResult = Token::updateAll(['$addToSet' => ['enabledMods' => ['$each' => $moduleNames]]], ['accountId' => $accountId]);
            if ($updateTokenResult) {
                $installFilePath = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Install.php';
                if (file_exists($installFilePath)) {
                    require_once($installFilePath);
                    $className = 'backend\modules\\' . $moduleName . '\Install';
                    if (class_exists($className)) {
                        $installObj = Yii::createObject($className);
                        $installObj->run($accountId);
                    }
                }

                list($menus, $mods) = Yii::$app->extModule->getMenuAndExt($moduleName);
                $dbMenus = $account->menus;
                $dbMods = $account->mods;

                foreach ($menus as $moduleName => $menu) {
                    if (!isset($dbMenus[$moduleName])) {
                        $dbMenus[$moduleName] = [];
                    }
                    $dbMenus[$moduleName] = ArrayHelper::merge($dbMenus[$moduleName], $menu);
                }
                $account->menus = $dbMenus;

                foreach ($mods as $mod) {
                    $isInDB = false;
                    foreach ($dbMods as $dbMod) {
                        if (!empty($dbMod['name']) && !empty($mod['name']) && $dbMod['name'] == $mod['name']) {
                            $isInDB = true;
                            break;
                        }
                    }
                    if (!$isInDB) {
                        $dbMods[] = $mod;
                    }
                }
                $account->mods = $dbMods;

                $account->save(true, ['menus', 'mods']);

            } else {
                throw new ServerErrorHttpException('Activate fail');
            }
        } else {
            throw new ServerErrorHttpException('Activate fail');
        }
    }

    /**
     * Get extension modules
     *
     * <b>Request Type </b>:GET
     * <b>Request Endpoints </b>: http://{server-domain}/api/management/module/extension-module
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for the user to get extension modules.
     *
     * <b>Request Example </b>:
     * <pre>
     *  http://{server-domain}/api/management/module/extension-module
     * </pre>
     *
     **/
    public function actionExtensionModule()
    {
        $accountId = $this->getAccountId();

        $account = Account::findByPk($accountId);
        $availableExtMods = $account->availableExtMods;

        return $availableExtMods;
    }
}
