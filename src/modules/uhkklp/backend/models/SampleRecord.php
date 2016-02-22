<?php

namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;
use yii\mongodb\Query;

class SampleRecord extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpSamplerecord';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['_id', 'deviceId', 'mobile', 'cookbookId', 'cookbookTitle', 'sampleId', 'sampleName', 'username', 'city', 'address', 'createdDate', 'sent', 'quantity', 'restaurantName', 'userAppellation', 'placeNumber']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['mobile', 'cookbookId'], 'required'],
            ]
        );
    }

    public static function getSampleRecordExcelDate($cookbookId ,$accountId)
    {
        $query = new Query();
        $query->from('uhkklpSamplerecord')
            ->where(['cookbookId' => $cookbookId])
            ->andWhere(['accountId' => $accountId]);
        $list = $query->all();
        return $list;
    }

    public static function getAllSampleRecordExcelDate($accountId)
    {
        $query = new Query();
        $query->from('uhkklpSamplerecord')
            ->where(['accountId' => $accountId])
            ->orderBy('sampleName DESC, updatedAt DESC');
        $list = $query->all();
        for ($i=0; $i < count($list); $i++) {
            $list[$i]['createdTime'] = date('Y-m-d H:i:s', $list[$i]['createdAt']->sec);
        }
        return $list;
    }
}
