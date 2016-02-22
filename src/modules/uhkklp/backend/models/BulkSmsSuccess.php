<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use backend\components\PlainModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for success SMS.
 *
 * The followings are the available columns in collection 'uhkklpBulkSmsSuccess'
 * @property MongoId   $_id
 * @property string    $mobile
 * @property string    $smsContent
 * @property MongoId   $smsRecordId
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class BulkSmsSuccess extends PlainModel
{
  public static function collectionName()
  {
      return 'uhkklpBulkSmsSuccess';
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

  public static function createSmsSuccess($mobile, $smsContent, $smsRecordId, $accountId)
  {
      $smsSuccess = new BulkSmsSuccess();
      $smsSuccess->mobile = $mobile;
      $smsSuccess->smsContent = $smsContent;
      $smsSuccess->smsRecordId = $smsRecordId;
      $smsSuccess->accountId = $accountId;
      if (!$smsSuccess->save()) {
          return false;
      } else {
          return true;
      }
  }

}
