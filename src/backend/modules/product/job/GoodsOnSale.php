<?php
namespace backend\modules\product\job;

use backend\modules\resque\components\ResqueUtil;
use backend\components\resque\SchedulerJob;
use backend\models\Goods;
use MongoDate;

class GoodsOnSale extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Goods on sale every minute"}
     * @see \backend\components\resque\SchedulerJob::perform()
     */
    public function perform()
    {
        Goods::updateAll(
            ['$set' => ['status' => Goods::STATUS_ON]],
            [
                'status' => Goods::STATUS_OFF,
                'onSaleTime' => ['$lte' => new MongoDate(strtotime('+1 minute'))]
            ]
        );
    }
}
