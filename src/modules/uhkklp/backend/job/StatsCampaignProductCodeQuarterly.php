<?php
namespace backend\modules\uhkklp\job;

use backend\models\StatsCampaignProductCodeQuarterly as ModelStatsCampaignProductCodeQuarterly;
use backend\utils\TimeUtil;

/**
* Job for StatsMemberPropTradeCodeQuaterly
*/
class StatsCampaignProductCodeQuarterly
{
    public function perform()
    {
        $args = $this->args;
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }

        foreach ($accountIds as $accountId) {
            ModelStatsCampaignProductCodeQuarterly::generateByYearAndQuarter($accountId, $datetime);
        }
        return true;
    }
}
