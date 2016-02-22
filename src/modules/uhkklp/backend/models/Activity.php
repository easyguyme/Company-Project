<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\components\BaseModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for activity.
 *
 * The followings are the available columns in collection 'uhkklpActivity':
 * @property MongoId   $_id
 * @property string    $name
 * @property string    $imageUrl
 * @property string    $rule
 * @property MongoDate $startDate
 * @property MongoDate $endDate
 * @property string    $status
 * @property array     $luckyDrawInfo
 * @property array     $otherInfo
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property string    $operator
 * @property boolean   $isDeleted
 **/
class Activity extends BaseModel
{
  public static function collectionName()
  {
      return 'uhkklpActivity';
  }

  public function attributes()
  {
      return ['_id', 'name', 'imageUrl', 'rule', 'startDate', 'endDate', 'status', 'luckyDrawInfo', 'otherInfo',
              'accountId', 'createdAt', 'updatedAt', 'operator', 'isDeleted'];
  }

  public function safeAttributes()
  {
      return ['_id', 'name', 'imageUrl', 'rule', 'startDate', 'endDate', 'status', 'luckyDrawInfo', 'otherInfo',
              'accountId', 'createdAt', 'updatedAt', 'operator', 'isDeleted'];
  }

  public function rules()
  {
      return [
          [['name', 'accountId', 'isDeleted'], 'required'],
          ['imageUrl', 'default', 'value' => ''],
          ['rule', 'default', 'value' => ''],
          ['luckyDrawInfo', 'default', 'value' => []],
          ['otherInfo', 'default', 'value' => []]
      ];
  }

  public static function createActivity($params)
  {
      $params['accountId'] = Token::getAccountId();
      $params['startDate'] = MongodbUtil::msTimetamp2MongoDate($params['startDate']);
      $params['endDate'] = MongodbUtil::msTimetamp2MongoDate($params['endDate']);
      $activity = new Activity();
      $activity->attributes = $params;
      $activity->save();
      return $activity;
  }

  public static function updateActivityByName($name, $params)
  {
      $accountId = Token::getAccountId();
      $params['startDate'] = MongodbUtil::msTimetamp2MongoDate($params['startDate']);
      $params['endDate'] = MongodbUtil::msTimetamp2MongoDate($params['endDate']);
      $activity = Activity::findOne(['name'=>$name, 'accountId'=>$accountId]);
      if (!empty($activity)) {
          $activity->attributes = $params;
          $activity->save();
      }
      return $activity;
  }

  public static function getActivityByName($name, $accountId=null)
  {
      if ($accountId == null) {
          $accountId = Token::getAccountId();
      }
      $activity = Activity::findOne(['name'=>$name, 'accountId'=>$accountId]);
      return $activity;
  }

}