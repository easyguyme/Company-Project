<?php
namespace backend\modules\uhkklp\models;

use Yii;
use backend\models\Token;
use yii\mongodb\ActiveRecord;
use backend\utils\MongodbUtil;

/**
 * Model class for mass texting log.
 *
 * The followings are the available columns in collection 'uhkklpSmsLog'
 * @property MongoId   $_id
 * @property string    $mobile
 * @property string    $status
 * @property string    $smsContent
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class SmsLog extends ActiveRecord
{
  public static function collectionName()
  {
      return 'uhkklpSmsLog';
  }

  public function attributes()
  {
      return ['_id', 'mobile', 'status', 'smsContent', 'smsName', 'accountId', 'createdAt'];
  }

  public function safeAttributes()
  {
      return ['_id', 'mobile', 'status', 'smsContent', 'smsName', 'accountId', 'createdAt'];
  }

  public function rules()
  {
      return [
          [['mobile'], 'required'],
      ];
  }

  public static function createSmsLog($mobile, $smsContent, $smsName, $status, $accountId)
  {
      $log = new SmsLog();
      $log->accountId = $accountId;
      $log->mobile = $mobile;
      $log->smsContent = $smsContent;
      $log->status = $status;
      $log->smsName = $smsName;
      $log->createdAt = new \MongoDate();
      $log->save();
      unset($log);
  }

}