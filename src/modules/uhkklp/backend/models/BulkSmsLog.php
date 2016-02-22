<?php
namespace backend\modules\uhkklp\models;

use Yii;
use backend\models\Token;
use yii\mongodb\ActiveRecord;
use backend\utils\MongodbUtil;

/**
 * Model class for mass texting log.
 *
 * The followings are the available columns in collection 'uhkklpBulkSmsLog'
 * @property MongoId   $_id
 * @property string    $mobile
 * @property string    $status
 * @property string    $smsContent
 * @property MongoId   $smsRecordId
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class BulkSmsLog extends ActiveRecord
{
  public static function collectionName()
  {
      return 'uhkklpBulkSmsLog';
  }

  public function attributes()
  {
      return ['_id', 'mobile', 'status', 'smsContent', 'smsRecordId', 'accountId', 'createdAt'];
  }

  public function safeAttributes()
  {
      return ['_id', 'mobile', 'status', 'smsContent', 'smsRecordId', 'accountId', 'createdAt'];
  }

  public function rules()
  {
      return [
          [['smsRecordId'], 'required'],
      ];
  }

  public static function createSmsLog($mobile, $smsContent, $status, $smsRecordId, $accountId)
  {
      $log = new BulkSmsLog();
      $log->accountId = $accountId;
      $log->mobile = $mobile;
      $log->smsContent = $smsContent;
      $log->status = $status;
      $log->smsRecordId = $smsRecordId;
      $log->createdAt = new \MongoDate();
      $log->save();
      unset($log);
  }

  public static function getCountBySmsRecordId($smsRecordId)
  {
      $accountId = Token::getAccountId();
      $successful = BulkSmsLog::find()->where(['smsRecordId'=>$smsRecordId, 'status'=>true, 'accountId'=>$accountId])->count();
      $failed = BulkSmsLog::find()->where(['smsRecordId'=>$smsRecordId, 'status'=>false, 'accountId'=>$accountId])->count();
      unset($accountId);
      return ['successful'=>$successful, 'failed'=>$failed];
  }

  public static function preProcessBulkSmsRecordData($smsRecordId, $accountId)
  {
      $logs = BulkSmsLog::find()
                          ->where(['smsRecordId'=>$smsRecordId, 'accountId'=>$accountId])
                          ->orderBy(['createdAt' => SORT_ASC])
                          ->all();
      $rows = array();
      if (!empty($logs)) {
          foreach ($logs as $log) {
              $row = [
                  'mobile' => "'" . $log->mobile,
                  'smsContent' => $log->smsContent,
                  'status' => $log->status ? '成功' : '失敗',
                  'createdAt' => MongodbUtil::MongoDate2String($log->createdAt, 'Y-m-d H:i:s', null)
              ];
              $rows[] = $row;
              unset($row);
          }
      }
      return $rows;
  }

}