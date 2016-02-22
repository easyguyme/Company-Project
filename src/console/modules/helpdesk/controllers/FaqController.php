<?php
namespace console\modules\helpdesk\controllers;

use yii\console\Controller;
use backend\modules\helpdesk\models\FaqCategory;
use backend\models\Account;
use \MongoId;

/**
 * Provide FAQ-related initialization operations.
 **/
class FaqController extends Controller
{

    /**
     * create default category base on the account id(create-default-category)
     * @param $accountId, string; if this value is all,it will support all accounts,otherwise it only support this account
     */
    public function actionCreateDefaultCategory($accountId)
    {
        $where = ['enabledMods' => ['$all' => ['helpdesk']]];
        if (empty($accountId)) {
            echo 'AccountId can not be empty' . PHP_EOL;
            exit();
        } elseif ($accountId == 'all') {
            $accounts = Account::findAll($where);
            if (!empty($accounts)) {
                foreach ($accounts as $account) {
                    $this->_createDefaultCategory($account->_id);
                }
            }
        } else {
            $accountId = new MongoId($accountId);
            $account = Account::findOne(array_merge(['_id' => $accountId], $where));
            if (empty($account)) {
                echo 'Can not find the account by ' . $accountId . PHP_EOL;
                exit();
            }
            $this->_createDefaultCategory($accountId);
        }
        echo 'Create default value successfully' . PHP_EOL;
    }

     /**
     * add default category for account.
     */
    private function _createDefaultCategory($accountId)
    {
        $defaultCategory = FaqCategory::getDefault($accountId);
        if (empty($defaultCategory)) {
            $category = new FaqCategory;
            $category->name = 'default_category';
            $category->isDefault = true;
            $category->accountId = $accountId;

            if (!$category->save()) {
                echo $category->getErrors() . PHP_EOL;
                exit();
            }
        } else {
            echo $accountId . 'data is exists' . PHP_EOL;
        }
    }
}
