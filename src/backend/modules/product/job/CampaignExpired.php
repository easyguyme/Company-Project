<?php
namespace backend\modules\product\job;

use MongoDate;
use backend\components\resque\SchedulerJob;
use backend\modules\product\models\Campaign;

class CampaignExpired extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Campaign expired every minute"}
     */
    public function perform()
    {
        Campaign::expiredByTime(new MongoDate(strtotime('+1 minute')));
    }
}
