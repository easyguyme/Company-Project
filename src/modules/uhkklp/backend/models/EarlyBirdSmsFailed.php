<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use backend\components\PlainModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for failed SMS of early bird. (早鳥兌點抽獎活動)
 *
 * The followings are the available columns in collection 'uhkklpEarlyBirdSmsFailed':
 * @property MongoId   $_id
 * @property string    $mobile
 * @property string    $smsContent
 * @property MongoId   $smsRecordId
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class EarlyBirdSmsFailed extends PlainModel
{
  public static function collectionName()
  {
      return 'uhkklpEarlyBirdSmsFailed';
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
      $smsFailed = new EarlyBirdSmsFailed();
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

  public static function preProcessSendFailedData($smsName, $accountId)
  {
      $query = new Query();
      $query = $query->from('uhkklpEarlyBirdSmsRecord')->select(['_id','failed']);
      $record = $query->where(['smsName'=>$smsName, 'accountId'=>$accountId])->orderBy(['createdAt' => SORT_DESC])->one();
      $rows = array();

      if ($record['failed'] > 0) {
          $failedAll = EarlyBirdSmsFailed::find()->where(['smsRecordId'=>$record['_id']])->orderBy(['createdAt' => SORT_ASC])->all();
          foreach ($failedAll as $smsFailed) {
              $createdAt = MongodbUtil::MongoDate2String($smsFailed->createdAt, 'Y-m-d H:i:s', null);
              $row = [
                        'createdAt' => $createdAt,
                        'mobile' => "'" . $smsFailed->mobile,
                        'smsContent' => $smsFailed->smsContent,
                        'status' => '失敗'
                     ];
              $rows[] = $row;
              unset($createdAt, $row);
          }
      }
      return $rows;
  }

}