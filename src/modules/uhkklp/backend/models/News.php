<?php
namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;

class News extends ActiveRecord
{
    public static function collectionName()
    {
        return 'uhkklpNews';
    }

    public function attributes()
    {
        return [
            '_id',
            'begin',
            'icon',
            'title',
            'thumbnail',
            'isTop',
            'content',
            'imgUrl',
            'isVideo',
            'isLatest',
            'youtubeUrl',
            'shareUrl',
            'moreInfo',
            'accountId',
            'shareBtnTxt',
            'isDeleted'
        ];
    }

    public function rules()
    {
        return [
        ];
    }

    public function getList($condition = [])
    {
        $sort = ['begin' => SORT_DESC];

        $query = new Query();
        $data = $query->from(self::collectionName())->select($this->attributes())->where($condition)->orderBy($sort)->all();
        return $data;
    }

    public function getItem($condition = [])
    {
        $condition['isDeleted'] = false;
        $data = News::findOne($condition);
        return $data;
    }

    public function deleteItem($condition = [])
    {
        $data = News::findOne($condition);
        $data->isDeleted = true;
        $data->save();
    }
}