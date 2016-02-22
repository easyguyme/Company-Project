<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\components\BaseModel;
use backend\utils\LogUtil;
use backend\utils\TimeUtil;
use backend\utils\MongodbUtil;
use backend\models\Token;
use backend\modules\uhkklp\models\ActivityPrize;

/**
 * Model class for activity prize.
 *
 * The followings are the available columns in collection 'uhkklpActivityUser':
 * @property MongoId   $_id
 * @property MongoId   $activityId
 * @property MongoId   $prizeId
 * @property string    $prizeContent
 * @property string    $deviceId
 * @property string    $mobile
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property boolean   $isDeleted
 * @property MongoId   $accountId
 **/
class ActivityUser extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpActivityUser';
    }

    public function attributes()
    {
        return ['_id', 'activityId', 'prizeId', 'prizeContent', 'deviceId',
              'mobile', 'createdAt', 'updatedAt', 'isDeleted', 'accountId'];
    }

    public function safeAttributes()
    {
        return ['_id', 'activityId', 'prizeId', 'prizeContent', 'deviceId',
              'mobile', 'createdAt', 'updatedAt', 'isDeleted', 'accountId'];
    }

    //獎品爲 謝謝惠顧 prizeId 存 'thanks'
    public function rules()
    {
        return [
          [['mobile', 'activityId', 'prizeId', 'isDeleted'], 'required'],
        ];
    }

    //element in $params: activityId prizeId prizeContent deviceId mobile
    public static function createUser($params)
    {
        try{
            $result = true;
            $accountId = Token::getAccountId();
            if (empty($accountId)) {
                throw new ServerErrorHttpException("Fail to get account's id");
            }

            $params['accountId'] = $accountId;
            $user = new ActivityUser();
            $user->attributes = $params;

            if (!$user->save()) {
              LogUtil::error(['message' => 'save activity-user failed', 'error' => $user->errors], 'activityUser');
              $result = false;
            }
            return $result;

        } catch (\Exception $ex) {
            LogUtil::error(['message' => 'save activity-user failed', 'error' => $ex->getMessage()], 'activityUser');
            return false;
        }
    }

    public static function getCountByCondition($condition = [])
    {
        return ActivityUser::find()->where($condition)->count();
    }

    public static function findList($currentPage = 1, $pageSize = 10, $condition = [])
    {
        $offset = ($currentPage - 1) * $pageSize;

        $query = new Query();
        $users = $query->from('uhkklpActivityUser')
                      ->select(['_id','deviceId','prizeContent','mobile','createdAt'])
                      ->where($condition)
                      ->orderBy(['createdAt' => SORT_DESC])
                      ->offset($offset)
                      ->limit($pageSize)
                      ->all();

        for ($i=0;$i<count($users);$i++) {
            $users[$i]['createdAt'] = MongodbUtil::MongoDate2String($users[$i]['createdAt'], 'Y-m-d H:i:s', null);
        }
        return $users;
    }

    /**
     * deal with the data before export
     * @param $condition,array. (activityId:MongoId, accountId:MongoId)
     */
    public static function preProcessPrizeStatisticData($condition)
    {
        $activityId = $condition['activityId'];
        // $accountId = $condition['accountId'];

        $prizes = ActivityPrize::find()->where(['activityId'=>$activityId])->orderBy(['createdAt' => SORT_ASC])->all();
        $rows = [];
        if (!empty($prizes)) {
            foreach ($prizes as $prize) {
                $count = self::getCountByCondition(['prizeId'=>$prize->_id, 'activityId'=>$activityId]);
                $createdAt = MongodbUtil::MongoDate2String($prize->createdAt, 'Y-m-d H:i:s', null);
                $row = [
                    'id' => (string)($prize->_id),
                    'prizeName' => $prize->name,
                    'count' => $count,
                    'createdAt' => $createdAt,
                    'isDeleted' => $prize->isDeleted ? '是' : '否'
                ];
                $rows[] = $row;
                unset($row, $count);
            }
            $count = self::getCountByCondition(['prizeId'=>'thanks', 'activityId'=>$activityId]);
            $row = [
                'id' => 'thanks',
                'prizeName' => '銘謝惠顧',
                'count' => $count,
                'createdAt' => '',
                'isDeleted' => ''
            ];
            $rows[] = $row;
            unset($row, $count, $createdAt);
        }
        return $rows;
    }

    /**
     * deal with the data before export
     * @param $condition,array. (activityId:MongoId, accountId:MongoId)
     */
    public static function preProcessUserPlayCountData($condition)
    {
        $activityId = $condition['activityId'];
        $users = ActivityUser::find()->where(['activityId'=>$activityId])->orderBy(['createdAt' => SORT_ASC])->all();

        $userCountArr = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                if (!empty($userCountArr[$user->mobile])) {
                    $userCountArr[$user->mobile] += 1;
                } else {
                    $userCountArr[$user->mobile] = 1;
                }
            }
        }

        $rows = array();
        if (!empty($userCountArr) and is_array($userCountArr)) {
            foreach ($userCountArr as $key => $value) {
                $row = [
                    'mobile' => "'" . $key,
                    'count' => $value
                ];
                $rows[] = $row;
                unset($row);
            }
            unset($userCountArr);
        }
        return $rows;
    }

    /**
     * deal with the data before export
     * @param $users,object
     * @param $args,array. (activityId:MongoId, accountId:MongoId)
     */
    public static function preProcessBarUseRecordData($users ,$args)
    {
        $rows = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                $createdAt = MongodbUtil::MongoDate2String($user->createdAt, 'Y-m-d H:i:s', null);
                $row = [
                    'createdAt' => $createdAt,
                    'mobile' => "'" . $user->mobile,
                    'prizeContent' => $user->prizeContent,
                    'deviceId' => "'" . $user->deviceId
                ];
                $rows[] = $row;
                unset($row, $createdAt);
            }
        }
        return $rows;
    }

}