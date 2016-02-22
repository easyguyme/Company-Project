<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\components\BaseModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for activity-bar. (拉霸活动)
 *
 * The followings are the available columns in collection 'uhkklpActivityBar':
 * @property MongoId   $_id
 * @property string    $name
 * @property string    $mainImgUrl
 * @property int       $probability
 * @property string    $rule
 * @property MongoDate $startDate
 * @property MongoDate $endDate
 * @property string    $status
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property string    $operator
 * @property boolean   $isDeleted
 **/
class ActivityBar extends BaseModel
{
  public static function collectionName()
  {
      return 'uhkklpActivityBar';
  }

  public function attributes()
  {
      return ['_id', 'name', 'mainImgUrl', 'probability', 'rule', 'startDate',
            'endDate', 'status', 'accountId', 'createdAt', 'updatedAt', 'operator', 'isDeleted'];
  }

  public function safeAttributes()
  {
      return ['_id', 'name', 'mainImgUrl', 'probability', 'rule', 'startDate',
            'endDate', 'status', 'accountId', 'createdAt', 'updatedAt', 'operator', 'isDeleted'];
  }

  public function rules()
  {
      return [
          [['name', 'accountId', 'isDeleted'], 'required'],
      ];
  }

  public static function createBar($params)
  {
      $params['accountId'] = Token::getAccountId();
      if (empty($params['accountId'])) {
          throw new ServerErrorHttpException("Fail to get account's id");
      }
      unset($params['_id']);

      $bar = new ActivityBar();
      $params['startDate'] = MongodbUtil::msTimetamp2MongoDate($params['startDate']);
      $params['endDate'] = MongodbUtil::msTimetamp2MongoDate($params['endDate']);
      $params['probability'] = (int)$params['probability'];
      $bar->attributes = $params;

      if (!$bar->save()) {
        LogUtil::error(['message' => 'save activity-bar failed', 'error' => $bar->errors], 'activityBar');
        throw new ServerErrorHttpException('save activityBar failed');
      }

      return $bar['_id'];
  }

  public static function updateBar($params)
  {
      $params['_id'] = new \MongoId($params['_id']);
      $bar = ActivityBar::findOne($params['_id']);
      $params['startDate'] = MongodbUtil::msTimetamp2MongoDate($params['startDate']);
      $params['endDate'] = MongodbUtil::msTimetamp2MongoDate($params['endDate']);

      $bar['name'] = $params['name'];
      $bar['mainImgUrl'] = $params['mainImgUrl'];
      $bar['probability'] = (int)$params['probability'];
      $bar['rule'] = $params['rule'];
      $bar['startDate'] = $params['startDate'];
      $bar['endDate'] = $params['endDate'];
      $bar['status'] = $params['status'];
      $bar['operator'] = $params['operator'];

      if (!$bar->save()) {
        LogUtil::error(['message' => 'save activity-bar failed', 'error' => $bar->errors], 'activityBar');
        throw new ServerErrorHttpException('save activityBar failed');
      }

      return $bar['_id'];
  }

  public static function getById($id)
  {
      $bar = self::findOne(new \MongoId($id));
      $bar['_id'] = $id;
      $bar['startDate'] = MongodbUtil::MongoDate2msTimeStamp($bar['startDate']);
      $bar['endDate'] = MongodbUtil::MongoDate2msTimeStamp($bar['endDate']);
      return $bar;
  }

  public static function getCountByCondition($condition = [])
  {
      return ActivityBar::find()->where($condition)->count();
  }

  public static function findList($currentPage = 1, $pageSize = 10, $condition = [])
  {
      $offset = ($currentPage - 1) * $pageSize;

      $query = new Query();
      $bars = $query->from('uhkklpActivityBar')
                    ->select(['_id','name','startDate','endDate','status','updatedAt'])
                    ->where($condition)
                    ->orderBy(['updatedAt' => SORT_DESC])
                    ->offset($offset)
                    ->limit($pageSize)
                    ->all();

      for ($i=0;$i<count($bars);$i++) {
          $bars[$i]['startDate'] = MongodbUtil::MongoDate2String($bars[$i]['startDate'], 'Y-m-d', null);
          $bars[$i]['endDate'] = MongodbUtil::MongoDate2String($bars[$i]['endDate'], 'Y-m-d', null);
          $bars[$i]['updatedAt'] = MongodbUtil::MongoDate2String($bars[$i]['updatedAt'], 'Y-m-d H:i:s', null);
      }
      return $bars;
  }

}