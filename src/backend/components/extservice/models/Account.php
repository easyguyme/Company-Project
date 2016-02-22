<?php
namespace backend\components\extservice\models;

use backend\models\Account as ModelAccount;

class Account extends BaseComponent
{
    /**
     * Get account helpdeskPhone
     * @return string
     */
    public function getHelpdeskPhone()
    {
        $account = ModelAccount::findOne(['_id' => $this->accountId]);
        if (!empty($account)) {
            return $account->helpdeskPhone;
        }
    }
}
