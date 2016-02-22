<?php
namespace backend\components\extservice\models;

use backend\modules\reservation\models\ReservationOrder as ModelReservationOrder;

class ReservationOrder extends BaseComponent
{

    /**
     * Search uncomplete reservation by productIds
     * @param  array $ids productIds
     */
    public function getByProductIds($ids)
    {
        if (is_string($ids)) {
            $ids = [new MongoId($ids)];
        } else if (is_object($ids)) {
            $ids = [$ids];
        }
        $condition = [
            'status' => [
                '$nin' => [
                    ModelReservationOrder::ORDER_COMPLETED,
                    ModelReservationOrder::ORDER_CANCELED,
                    ModelReservationOrder::ORDER_REJECTED,
                    ModelReservationOrder::ORDER_TOREFUND
                ]
            ],
            'reservation.productId' => [
                '$in' => $ids
            ]
        ];
        return ModelReservationOrder::findAll($condition);
    }
}
