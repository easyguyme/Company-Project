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
use backend\modules\member\models\Member;
use backend\modules\uhkklp\models\EarlyBirdSmsDetail;

/**
 * Model class for SMS record of early bird. (早鳥兌點抽獎活動)
 *
 * The followings are the available columns in collection 'uhkklpEarlyBirdSmsRecord':
 * @property MongoId   $_id
 * @property string    $smsName
 * @property int       $total
 * @property int       $successful
 * @property int       $failed
 * @property int       $process     (0：準備發送, 1：正在發送， 2：發送完成, 3:發送故障)
 * @property string    $smsTemplate (簡訊模板內容)
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property boolean   $isDeleted
 **/
class EarlyBirdSmsRecord extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpEarlyBirdSmsRecord';
    }

    public function attributes()
    {
        return ['_id', 'smsName', 'total', 'successful', 'failed', 'process',
              'smsTemplate', 'accountId', 'operator', 'createdAt', 'updatedAt', 'isDeleted'];
    }

    public function safeAttributes()
    {
        return ['_id', 'smsName', 'total', 'successful', 'failed', 'process',
              'smsTemplate', 'accountId', 'operator', 'createdAt', 'updatedAt', 'isDeleted'];
    }

    public function rules()
    {
        return [
            [['smsName'], 'required'],
        ];
    }

    public static function createSmsRecord($operator, $smsName, $smsTemplate, $total = 0, $process = 0)
    {
        $properties = [
            'smsName' => $smsName,
            'total' => $total,
            'successful' => 0,
            'failed' => 0,
            'process' => $process, // 0：準備發送, 1：正在發送， 2：發送完成
            'smsTemplate' => $smsTemplate,
            'accountId' => Token::getAccountId(),
            'operator' => $operator
        ];
        $smsRecord = new EarlyBirdSmsRecord();
        $smsRecord->attributes = $properties;
        if (!$smsRecord->save()) {
            return false;
        }
        return $smsRecord->_id;
    }

    public static function updateProcessById($id, $process)
    {
        $smsRecord = EarlyBirdSmsRecord::findOne($id);
        $smsRecord->process = $process;
        if (!$smsRecord->save()) {
            throw new ServerErrorHttpException("update sending process failed");
        }
    }

    public static function updateSmsRecordById($id)
    {
        $smsCount = EarlyBirdSmsDetail::getCountBySmsRecordId($id);
        $smsRecord = EarlyBirdSmsRecord::findOne($id);
        $smsRecord->successful = $smsCount['successful'];
        $smsRecord->failed = $smsCount['failed'];
        $smsRecord->save();
        return $smsRecord;
    }

    public static function getLastSmsRecord()
    {
        $accountId = Token::getAccountId();
        $query = new Query();
        $query = $query->from('uhkklpEarlyBirdSmsRecord')->select(['_id','total','successful','failed','process']);

        $recordOne = $query->where(['smsName'=>'sms_one', 'accountId'=>$accountId])->orderBy(['createdAt' => SORT_DESC])->one();
        $recordTwo = $query->where(['smsName'=>'sms_two', 'accountId'=>$accountId])->orderBy(['createdAt' => SORT_DESC])->one();
        $recordThree = $query->where(['smsName'=>'sms_three', 'accountId'=>$accountId])->orderBy(['createdAt' => SORT_DESC])->one();
        $recordFour = $query->where(['smsName'=>'sms_four', 'accountId'=>$accountId])->orderBy(['createdAt' => SORT_DESC])->one();
        unset($accountId, $query);

        return [
            'sms_one' => $recordOne,
            'sms_two' => $recordTwo,
            'sms_three' => $recordThree,
            'sms_four' => $recordFour
        ];
    }

}