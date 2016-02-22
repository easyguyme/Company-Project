<?php

namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;
use backend\utils\LogUtil;

class Message extends ActiveRecord
{
    public static function collectionName()
    {
        return 'uhkklpMessage';
    }

    public function attributes()
    {
        return [
            '_id',
            'content',
            'pushMethod',
            'pushDevices',
            'pushTime',
            'linkType',
            'newsId',
            'accountId',
            'isDeleted',
            'isPushed',
            'accountId',
        ];
    }

    public function rules()
    {
        return [
            [
                [
                    'content',
                    'pushMethod',
                    'pushDevices',
                    'pushTime',
                    'linkType',
                    'newsId',
                    'accountId',
                    'isDeleted',
                    'isPushed',
                    'accountId'
                ],
                'safe'
            ],
        ];
    }

    public function getCount($condition = [])
    {
        $condition['isDeleted'] = false;
        return Message::find()->where($condition)->count();
    }

    public function getList($currentPage = 1, $pageSize = 10, $sort, $condition = [])
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
