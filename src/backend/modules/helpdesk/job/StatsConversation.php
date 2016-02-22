<?php
namespace backend\modules\helpdesk\job;

use backend\utils\LogUtil;
use backend\components\resque\SchedulerJob;
use backend\modules\helpdesk\models\Statistics;
use backend\models\Account;

/**
 * This class is main to create a helpdesk conversation statistics,
 * For update totalUser, totalConversation and totalMessage every day.
 *
 * @author Mike Wang
 */
class StatsConversation extends SchedulerJob
{
    public function perform()
    {
        $accounts = Account::findAll([]);
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                Statistics::createRecord($account->_id);
            }
        }
    }
}
