<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\mongodb\Query;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\components\PlainModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for lucky draw record of early bird. (早鳥兌點抽獎活動)
 *
 * The followings are the available columns in collection 'uhkklpEarlyBirdDrawRecord':
 * @property MongoId     $_id
 * @property MongoDate   $activityStartDate
 * @property MongoDate   $activityEndDate
 * @property int         $order
 * @property int         $pointsOne
 * @property int         $pointsTwo
 * @property int         $pointsThree
 * @property int         $quantityOne
 * @property int         $quantityTwo
 * @property int         $quantityThree
 * @property string      $prizeNameOne
 * @property string      $prizeNameTwo
 * @property string      $prizeNameThree
 * @property MongoId     $accountId
 * @property MongoDate   $createdAt
 **/
class EarlyBirdDrawRecord extends PlainModel
{
    public static function collectionName()
    {
        return 'uhkklpEarlyBirdDrawRecord';
    }

    public function attributes()
    {
        return ['_id', 'activityStartDate', 'activityEndDate', 'order', 'pointsOne', 'pointsTwo', 'pointsThree',
        'quantityOne', 'quantityTwo', 'quantityThree', 'prizeNameOne', 'prizeNameTwo', 'prizeNameThree', 'createdAt', 'accountId'];
    }

    public function safeAttributes()
    {
        return ['_id', 'activityStartDate', 'activityEndDate', 'order', 'pointsOne', 'pointsTwo', 'pointsThree',
        'quantityOne', 'quantityTwo', 'quantityThree', 'prizeNameOne', 'prizeNameTwo', 'prizeNameThree', 'createdAt', 'accountId'];
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            []
        );
    }

    public static function createDrawRecord($params)
    {
        try {
            $order = self::getDrawCount() + 1;
            $record = new EarlyBirdDrawRecord();
            $params['activityStartDate'] = MongodbUtil::msTimetamp2MongoDate($params['startDate']);
            $params['activityEndDate'] = MongodbUtil::msTimetamp2MongoDate($params['endDate']);
            unset($params['startDate'], $params['endDate']);
            $record->attributes = $params;
            $record->order = $order;
            $record->accountId = Token::getAccountId();
            if (!$record->save()) {
                throw new ServerErrorHttpException('save lucky draw record failed');
            }
            return $record->_id;

        } catch (\Exception $ex) {
            LogUtil::error(['message' => 'save lucky draw record failed', 'error' => $ex->getMessage()], 'earlybird');
            throw new ServerErrorHttpException('save lucky draw record failed');
        }
    }

    public static function getDrawCount()
    {
        return self::find(['accountId'=>Token::getAccountId()])->count();
    }

    public static function findList($currentPage = 1, $pageSize = 10, $condition = [])
    {
        $offset = ($currentPage - 1) * $pageSize;

        $query = new Query();
        $records = $query->from('uhkklpEarlyBirdDrawRecord')
                      ->select(['_id','order','createdAt'])
                      ->where($condition)
                      ->orderBy(['createdAt' => SORT_DESC])
                      ->offset($offset)
                      ->limit($pageSize)
                      ->all();

        for ($i=0;$i<count($records);$i++) {
            $records[$i]['createdAt'] = MongodbUtil::MongoDate2String($records[$i]['createdAt'], 'Y-m-d H:i:s', null);
            $records[$i]['_id'] = (string)$records[$i]['_id'];
        }
        return $records;
    }

}