<?php
namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class Goods extends BaseModel {

    public static function collectionName()
    {
        return 'uhkklpGoods';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'image', 'description', 'href']
        );
    }
}
