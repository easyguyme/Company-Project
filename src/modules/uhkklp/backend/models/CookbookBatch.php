<?php

namespace backend\modules\uhkklp\models;

use backend\models\Token;
use yii\mongodb\ActiveRecord;
use backend\models\User;
use yii\mongodb\Query;
use backend\utils\LogUtil;

class CookbookBatch extends ActiveRecord
{

    public static function collectionName()
    {
        return 'uhkklpCookbookBatch';
    }

    public function attributes()
    {
        return ['_id', 'cookbooks', 'hasImages', 'createdTime', 'accountId', 'operator'];
    }

    // public function rules()
    // {
    //     return [
    //         ['accountId', 'checkcreate', 'on' => 'create']
    //     ];

    // }

    // public function checkcreate($attribute, $params)
    // {
    //     LogUtil::error('here');
    //     $this->hasImages = false;
    //     if (empty($this->createdTime)) {
    //         $this->createdTime = new \MongoDate();
    //     }
    //     if (empty($this->accountId)) {
    //         $this->accountId = Token::getAccountId();
    //     }
    //     if (empty($this->operator)) {
    //         $accessToken = Token::getToken();
    //         $user = User::findOne(['_id' => $accessToken->userId]);
    //         $this->operator = $user->name;
    //     }
    // }

    public static function getCount($condition = [])
    {
        return self::find()->where(['accountId' => Token::getAccountId()])
            // ->andWhere(['like', 'cookBooks.name', ''])
            ->andWhere($condition)
            ->count();
    }

    public static function getList($currentPage = 1, $pageSize = 10, $sort = [], $condition = [])
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
        $query = new Query();
        $datas = $query->from(self::collectionName())
            ->select(self::attributes())
            ->where(['accountId' => Token::getAccountId()])
            ->andWhere($condition)
            ->orderBy($sort)
            ->offset($offset)
            ->limit($pageSize)
            ->all();
        return $datas;
    }

}
