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

/**
 * Model class for winner of early bird. (早鳥兌點抽獎活動)
 *
 * The followings are the available columns in collection 'uhkklpEarlyBirdWinner':
 * @property MongoId   $_id
 * @property string    $prizeLevel
 * @property MongoId   $memberId
 * @property string    $mobile
 * @property string    $name
 * @property string    $exchangeGoodsScore
 * @property string    $prizeName
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 * @property MongoId   $drawRecordId
 **/
class EarlyBirdWinner extends ActiveRecord
{
  public static function collectionName()
  {
      return 'uhkklpEarlyBirdWinner';
  }

  public function attributes()
  {
      return ['_id', 'prizeLevel', 'memberId', 'mobile', 'name', 'exchangeGoodsScore', 'prizeName', 'drawRecordId', 'accountId', 'createdAt'];
  }

  public function safeAttributes()
  {
      return ['_id', 'prizeLevel', 'memberId', 'mobile', 'name', 'exchangeGoodsScore', 'prizeName', 'drawRecordId', 'accountId', 'createdAt'];
  }

  public function rules()
  {
      return [
          [['mobile'], 'required'],
      ];
  }

  //$winnerArr array('id'=>'56039cac475df4210c8b81ac', 'name'=>'用戶名', 'mobile'=>"0912345678", 'exchangeGoodsScore'=>3000)
  public static function creatWinner($winnerArr, $recordId, $prizeName)
  {
      $winner = new EarlyBirdWinner();
      $winner->memberId = new \MongoId($winnerArr['id']);
      $winner->prizeLevel = $winnerArr['prizeLevel'];
      $winner->mobile = $winnerArr['mobile'];
      $winner->name = $winnerArr['name'];
      $winner->exchangeGoodsScore = $winnerArr['exchangeGoodsScore'];
      $winner->accountId = Token::getAccountId();
      $winner->createdAt = new \MongoDate();
      $winner->drawRecordId = $recordId;
      $winner->prizeName = $prizeName;
      $winner->save();
      unset($winner);
  }

  public static function preProcessEarlyBirdWinnerData($condition)
  {
      $winners = self::find()->where($condition)->orderBy(['exchangeGoodsScore' => SORT_DESC])->all();
      $rows = [];
      foreach ($winners as $winner) {
          $createdAt = MongodbUtil::MongoDate2String($winner->createdAt, 'Y-m-d H:i', null);
          switch ($winner->prizeLevel) {
            case 'one': $prizeLevel = '一等獎'; break;
            case 'two': $prizeLevel = '二等獎'; break;
            case 'three': $prizeLevel = '三等獎'; break;
            default: break;
          }
          $row = [
              'prizeLevel' => $prizeLevel,
              'mobile' => "'" . $winner->mobile,
              'name' => $winner->name,
              'prizeName' => $winner->prizeName,
              'exchangeGoodsScore' => $winner->exchangeGoodsScore,
              'createdAt' => $createdAt
          ];
          $rows[] = $row;
          unset($createdAt, $prizeLevel, $row);
      }
      return $rows;
  }

}