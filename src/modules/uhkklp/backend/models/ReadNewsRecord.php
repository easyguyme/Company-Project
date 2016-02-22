<?php
namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;

class ReadNewsRecord extends ActiveRecord
{
    public static function collectionName()
    {
        return 'uhkklpReadNewsRecord';
    }

    public function attributes()
    {
        return [
            '_id',
            'deviceId',
            'readedNewsId',
            'accountId'
        ];
    }

    public function rules()
    {
        return [
        ];
    }

    public function getItem($condition = []) {
        $query = new Query();
        $data = $query->from(self::collectionName())->select($this->attributes())->where($condition)->one();
        return $data;
    }

    public function updateItem($condition = [], $newdata) {
        $record = ReadNewsRecord::findOne($condition);
        $record->readedNewsId = $newdata;
        return $record->update();
    }
}
