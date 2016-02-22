<?php

namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class Sample extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpSample';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['_id', 'name', 'operator', 'quantity', 'imgUrl', 'usedNumber']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'name'
                    ],
                    'required'
                ],
            ]
        );
    }

    public static function lockSample($sample)
    {
        $sample = self::find()->where(['_id'=>$sample['id']])->one();
        if ($sample == null) {
            return ['code' => 500,'msg' => 'no sample', 'data' => $sample];
        }
        if (!isset($sample->usedNumber)) {
            $sample->usedNumber = 0;
        }
        $sample->usedNumber = $sample->usedNumber + 1;
        $sample->save();
        return ['code' => 200,'msg' => 'OK'];
    }

    public static function unlockSample($sample)
    {
        $sample = self::find()->where(['_id'=>$sample['id']])->one();
        if ($sample == null) {
            return ['code' => 500,'msg' => 'no sample', 'data' => $sample];
        }
        if (!isset($sample->usedNumber)) {
            $sample->usedNumber = 0;
        }
        $sample->usedNumber = $sample->usedNumber - 1;
        if ($sample->usedNumber < 0) {
           $sample->usedNumber = 0;
        }
        $sample->save();
        return ['code' => 200,'msg' => 'OK'];
    }
}
