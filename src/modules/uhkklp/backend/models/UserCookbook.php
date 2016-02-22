<?php

namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class UserCookbook extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpUserCookbook';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['_id', 'mobile', 'cookbookId', 'collection', 'score']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'mobile',
                        'cookbookId'
                    ],
                    'required'
                ],
            ]
        );
    }
}
