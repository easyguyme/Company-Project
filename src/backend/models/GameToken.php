<?php
namespace backend\models;

use Yii;
use backend\components\PlainModel;

/**
 * This model is just for ensureIndex
 * @author Rex Chen
 */
class GameToken extends PlainModel
{
    public static function collectionName()
    {
        return 'gameToken';
    }

    public function attributes()
    {
        return array_merge(parent::attributes(), ['memberId', 'gameId', 'token', 'isUsed', 'validFrom', 'validTo']);
    }

    public function safeAttributes()
    {
        return array_merge(parent::safeAttributes(), ['memberId', 'gameId', 'token', 'isUsed', 'validFrom', 'validTo']);
    }

    public function fields()
    {
        return array_merge(parent::fields(), ['memberId', 'gameId', 'token', 'isUsed', 'validFrom', 'validTo']);
    }
}
