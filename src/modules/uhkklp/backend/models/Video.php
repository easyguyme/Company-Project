<?php

namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;
use backend\utils\LogUtil;

class Video extends ActiveRecord
{

    const POSITION_HORIZONTAL = 'horizontal';
    const POSITION_VERTICAL = 'vertical';

    public static function collectionName()
    {
        return 'uhkklpVideo';
    }

    public function attributes()
    {
        return [
            '_id',
            'title',
            'url',
            'imgUrl',
            'creator',
            'createdAt',
            'updatedAt',
            'isDeleted',
            'accountId',
            'position'
        ];
    }

    public function rules()
    {
        return [
            [
                [
                    'title',
                    'url',
                    'imgUrl',
                    'creator',
                    'createdAt',
                    'updatedAt',
                    'isDeleted',
                    'accountId',
                    'position'
                ],
                'safe'
            ]
        ];
    }

    public function getCount($condition = [])
    {
        $condition['isDeleted'] = false;
        return self::find()->where($condition)->count();
    }

    public function getList($currentPage = 1, $pageSize = 10, $condition = [], $sort = [])
    {
        if (empty($sort)) {
            $sort = ['_id' => SORT_DESC];
        } else {
            foreach ($sort as $key => $value) {
                if ($value) {
                    $sort = [$key => SORT_DESC];
                }
                else {
                    $sort = [$key => SORT_ASC];
                }
            }
        }

        $offset = ($currentPage - 1) * $pageSize;
        $condition['isDeleted'] = false;
        $query = new Query();
        $datas = $query->from(self::collectionName())->select($this->attributes())->where($condition)->orderBy($sort)->offset($offset)->limit($pageSize)->all();
        return $datas;
    }
}
