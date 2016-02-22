<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use backend\components\PlainModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for failed SMS.
 *
 * The followings are the available columns in collection 'uhkklpBulkSmsFailed'
 * @property MongoId   $_id
 * @property string    $mobile
 * @property string    $smsContent
 * @property MongoId   $smsRecordId
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class BulkSmsFailed extends PlainModel
{
  public static function collectionName()
  {
      return 'uhkklpBulkSmsFailed';
  }

  public function attributes()
  {
      return ['_id', 'mobile', 'smsContent', 'smsRecordId', 'accountId', 'createdAt'];
  }

  public function safeAttributes()
  {
      return ['_id', 'mobile', 'smsContent', 'smsRecordId', 'accountId', 'createdAt'];
  }

  public function rules()
  {
      return [
          [['smsRecordId'], 'required'],
      ];
  }

  public static function createSmsFailed($mobile, $smsContent, $smsRecordId, $accountId)
  {
      $smsFailed = new BulkSmsFailed();
      $smsFailed->mobile = $mobile;
      $smsFailed->smsContent = $smsContent;
      $smsFailed->smsRecordId = $smsRecordId;
      $smsFailed->accountId = $accountId;
      if (!$smsFailed->save()) {
          return false;
      } else {
          return true;
      }
  }

}