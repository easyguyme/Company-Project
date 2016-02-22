<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\components\PlainModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for lucky draw record
 *
 * The followings are the available columns in collection 'uhkklpLuckyDrawRecord'
 * @property MongoId     $_id
 * @property String      $activityName
 * @property MongoDate   $activityStartDate
 * @property MongoDate   $activityEndDate
 * @property Array       $condition
 * @property Array       $awards
 * @property Array       $remark
 * @property MongoId     $accountId
 * @property MongoDate   $createdAt
 **/
class LuckyDrawRecord extends PlainModel
{
    public static function collectionName()
    {
        return 'uhkklpLuckyDrawRecord';
    }

    public function attributes()
    {
        return ['_id', 'activityName', 'activityStartDate', 'activityEndDate', 'condition', 'awards', 'remark', 'accountId', 'createdAt'];
    }

    public function safeAttributes()
    {
        return ['_id', 'activityName', 'activityStartDate', 'activityEndDate', 'condition', 'awards', 'remark', 'accountId', 'createdAt'];
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
              ['condition', 'default', 'value' => []],
              ['awards', 'default', 'value' => []],
              ['remark', 'default', 'value' => []]
            ]
        );
    }

    public static function createDrawRecord($params)
    {
        $record = new LuckyDrawRecord();
        if (!array_key_exists('accountId', $params)) {
            $record->accountId = Token::getAccountId();
        }
        $record->attributes = $params;
        if (!$record->save()) {
            throw new ServerErrorHttpException('save lucky draw record failed');
        }
        return $record->_id;
    }

    /**
     * @param $id MongoId
     * @param $remark array
     */
    public static function addRemark($id, $remark)
    {
        $record = LuckyDrawRecord::findOne($id);
        if (!empty($record)) {
            $record->remark = array_merge($record->remark, $remark);
            $record->save();
        }
        unset($record);
    }

    public static function getDrawCount()
    {
        return self::find(['accountId'=>Token::getAccountId()])->count();
    }

    public static function findList($currentPage = 1, $pageSize = 10, $condition = [])
    {
        $offset = ($currentPage - 1) * $pageSize;

        $query = new Query();
        $records = $query->from('uhkklpLuckyDrawRecord')
                      ->select(['_id','createdAt', 'remark'])
                      ->where($condition)
                      ->orderBy(['createdAt' => SORT_DESC])
                      ->offset($offset)
                      ->limit($pageSize)
                      ->all();

        for ($i=0;$i<count($records);$i++) {
            $records[$i]['createdAt'] = MongodbUtil::MongoDate2String($records[$i]['createdAt'], 'Y-m-d H:i:s', null);
            $records[$i]['_id'] = (string)$records[$i]['_id'];
        }
        return $records;
    }
}