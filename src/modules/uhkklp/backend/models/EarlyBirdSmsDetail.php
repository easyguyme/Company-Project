<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for SMS detail of early bird. (早鳥兌點抽獎活動)
 *
 * The followings are the available columns in collection 'uhkklpEarlyBirdSmsDetail':
 * @property MongoId   $_id
 * @property string    $mobile
 * @property string    $status
 * @property string    $smsContent
 * @property MongoId   $smsRecordId
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class EarlyBirdSmsDetail extends ActiveRecord
{
  public static function collectionName()
  {
      return 'uhkklpEarlyBirdSmsDetail';
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

  public static function createSmsDetail($mobile, $smsContent, $status, $smsRecordId, $accountId)
  {
      $detail = new EarlyBirdSmsDetail();
      $detail->accountId = $accountId;
      $detail->mobile = $mobile;
      $detail->smsContent = $smsContent;
      $detail->status = $status;
      $detail->smsRecordId = $smsRecordId;
      $detail->createdAt = new \MongoDate();
      $detail->save();
      unset($detail);
  }

  public static function getCountBySmsRecordId($smsRecordId)
  {
      $accountId = Token::getAccountId();
      $successful = EarlyBirdSmsDetail::find()->where(['smsRecordId'=>$smsRecordId, 'status'=>true, 'accountId'=>$accountId])->count();
      $failed = EarlyBirdSmsDetail::find()->where(['smsRecordId'=>$smsRecordId, 'status'=>false, 'accountId'=>$accountId])->count();
      unset($accountId);
      return ['successful'=>$successful, 'failed'=>$failed];
  }

  public static function preProcessEarlyBirdSmsDetails($smsName, $accountId)
  {
      $query = new Query();
      $query = $query->from('uhkklpEarlyBirdSmsRecord')->select(['_id']);
      $record = $query->where(['smsName'=>$smsName, 'accountId'=>$accountId])->orderBy(['createdAt' => SORT_DESC])->one();
      $rows = array();

      $details = self::find()->where(['smsRecordId'=>$record['_id']])->orderBy(['createdAt' => SORT_ASC])->all();
      foreach ($details as $detail) {
          $createdAt = MongodbUtil::MongoDate2String($detail->createdAt, 'Y-m-d H:i:s', null);
          $row = [
                    'createdAt' => $createdAt,
                    'mobile' => "'" . $detail->mobile,
                    'smsContent' => $detail->smsContent,
                    'status' => $detail->status == false ? '失敗' : '成功'
                 ];
          $rows[] = $row;
          unset($createdAt, $row);
      }
      return $rows;
  }

}