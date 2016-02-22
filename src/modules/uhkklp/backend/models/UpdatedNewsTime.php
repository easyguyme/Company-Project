<?php

namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class UpdatedNewsTime extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpUpdatedNewsTime';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['_id', 'updateTime']
        );
    }
}
