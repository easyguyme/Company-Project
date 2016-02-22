<?php
namespace backend\modules\store\job;

use MongoDate;
use backend\components\resque\SchedulerJob;
use backend\models\StoreGoods;

class StoreGoodsOnSale extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Store goods on sale every minute"}
     */
    public function perform()
    {
        StoreGoods::updateAll(
            ['$set' => ['status' => StoreGoods::STATUS_ON]],
            [
                'status' => StoreGoods::STATUS_OFF,
                'onSaleTime' => ['$lte' => new MongoDate(strtotime('+1 minute'))]
            ]
        );
    }
}
