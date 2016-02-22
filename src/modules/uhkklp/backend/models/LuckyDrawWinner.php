<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use yii\mongodb\ActiveRecord;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;
use backend\modules\uhkklp\utils\BulkSmsUtil;
use backend\modules\member\models\Member;

/**
 * Model class for Lucky Draw winner
 *
 * The followings are the available columns in collection 'uhkklpLuckyDrawWinner':
 * @property MongoId   $_id
 * @property string    $mobile
 * @property string    $name
 * @property string    $awardName
 * @property Array     $winInfo
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 * @property MongoId   $drawRecordId
 **/
class LuckyDrawWinner extends ActiveRecord
{
  public static function collectionName()
  {
      return 'uhkklpLuckyDrawWinner';
  }

  public function attributes()
  {
      return ['_id', 'mobile', 'name', 'awardName', 'winInfo', 'drawRecordId', 'accountId', 'createdAt'];
  }

  public function safeAttributes()
  {
      return ['_id', 'mobile', 'name', 'awardName', 'winInfo', 'drawRecordId', 'accountId', 'createdAt'];
  }

  public function rules()
  {
      return [
          [['mobile'], 'required'],
          ['winInfo', 'default', 'value' => []]
      ];
  }

  public static function createWinner($params)
  {
      $winner = new LuckyDrawWinner();
      if (!array_key_exists('accountId', $params)) {
          $winner->accountId = Token::getAccountId();
      }
      $winner->attributes = $params;
      $winner->createdAt = new \MongoDate();
      $winner->save();
      unset($winner);
  }

  public static function preProcessCnyWinnerData($condition)
  {
      // TODO refine smsContent
      $smsTemplate = BulkSmsUtil::CNY_WINNERS_SMS_TEMPLATE;
      $winners = LuckyDrawWinner::find()->where($condition)->all();
      $rows = array();
      foreach ($winners as $winner) {
          $smsContent = str_replace("%username%", $winner->name, $smsTemplate);
          $smsContent = str_replace("%awardName%", $winner->awardName, $smsContent);
          $member = Member::getByMobile($winner->mobile, $condition['accountId']);
          
          if ($member != null) {
              $city = null;
              $site = null;
              $rname = null;
              foreach ($member->properties as $property) {
                  if ($property['name'] == '縣市') {
                      $city = $property['value'];
                  }
                  if ($property['name'] == '地址') {
                      $site = $property['value'];
                  }
                  if ($property['name'] == '餐廳名稱') {
                      $rname = $property['value'];
                  }
              }

              $row = [
                  'mobile' => " " . $winner->mobile,
                  'name' => $winner->name,
                  'city' => $city,
                  'site' => $site,
                  'rname' => $rname,
                  'awardName' => $winner->awardName,
                  'scoreAdded' => $winner->winInfo['scoreAdded'],
                  'createdAt' => MongodbUtil::MongoDate2String($winner->createdAt, 'Y-m-d H:i', null),
                  'remark' => $winner->winInfo['boughtAllProducts'] ? '已包含全部3支品項' : '',
                  'smsContent' => $smsContent
              ];
              $rows[] = $row;
              unset($row, $smsContent, $member, $city, $site);
          }
      }
      return $rows;
  }

  public static function preProcessCnyWinnerSmsData($condition)
  {
      // TODO refine smsContent
      $winners = LuckyDrawWinner::find()->where($condition)->all();
      $smsTemplate = BulkSmsUtil::CNY_WINNERS_SMS_TEMPLATE;
      $rows = array();
      foreach ($winners as $winner) {
          $smsContent = str_replace("%username%", $winner->name, $smsTemplate);
          $smsContent = str_replace("%awardName%", $winner->awardName, $smsContent);
          $row = [
              'mobile' => $winner->mobile,
              'smsContent' => $smsContent
          ];
          $rows[] = $row;
          unset($row, $smsContent);
      }
      return $rows;
  }

}
