<?php
namespace backend\modules\uhkklp\models;

use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\components\BaseModel;
use backend\models\Token;
use backend\utils\LogUtil;
use backend\utils\TimeUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for activity prize.
 *
 * The followings are the available columns in collection 'uhkklpActivityPrize':
 * @property MongoId   $_id
 * @property string    $name
 * @property string    $prizeImgUrl
 * @property string    $type       (littlePrize, topPrize)
 * @property string    $isPoint    (Y, N)
 * @property int       $points
 * @property int       $quantity
 * @property MongoDate $startDate
 * @property MongoDate $endDate
 * @property MongoId   $activityId
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property boolean   $isDeleted
 **/
class ActivityPrize extends BaseModel
{
    public static function collectionName()
    {
        return 'uhkklpActivityPrize';
    }

    public function attributes()
    {
        return ['_id', 'name', 'prizeImgUrl', 'type', 'isPoint', 'points', 'quantity',
              'startDate', 'endDate', 'activityId', 'accountId', 'createdAt', 'updatedAt', 'isDeleted'];
    }

    public function safeAttributes()
    {
        return ['_id', 'name', 'prizeImgUrl', 'type', 'isPoint', 'points', 'quantity',
              'startDate', 'endDate', 'activityId', 'accountId', 'createdAt', 'updatedAt', 'isDeleted'];
    }

    public function rules()
    {
        return [
          [['name', 'quantity', 'isDeleted'], 'required'],
        ];
    }

    /* params: Array of prize, prize is also an array */
    public static function createPrize($params, $activityId)
    {
        $accountId = Token::getAccountId();
        if (empty($accountId)) {
            throw new ServerErrorHttpException("Fail to get account's id");
        }

        for ($i=0; $i<count($params); $i++) {
            unset($params[$i]['_id']);      //remove default _id, create MongoId
            $params[$i]['activityId'] = $activityId;
            $params[$i]['accountId'] = $accountId;
            $params[$i]['points'] = (int)$params[$i]['points'];
            $params[$i]['quantity'] = (int)$params[$i]['quantity'];
            $prize = new ActivityPrize();
            if (!empty($params[$i]['startDate'])) {
                $params[$i]['startDate'] = MongodbUtil::msTimetamp2MongoDate($params[$i]['startDate']);
                $params[$i]['endDate'] = MongodbUtil::msTimetamp2MongoDate($params[$i]['endDate']);
            }
            $prize->attributes = $params[$i];
            if (!$prize->save()) {
                LogUtil::error(['message' => 'save activity-prize failed', 'error' => $prize->errors], 'activityPrize');
                // throw new ServerErrorHttpException('save activityPrize failed');
                return false;
            }
            unset($prize);
        }
        return true;
    }

    /* params: Array of prize, prize is also an array */
    public static function updatePrize($params, $activityId)
    {
        for ($i=0; $i<count($params); $i++) {
            $prize = null;
            if ($params[$i]['_id'] == "") {
                $prize = new ActivityPrize();
            } else {
                $prize = ActivityPrize::findOne(new \MongoId($params[$i]['_id']));
            }

            if (!empty($params[$i]['startDate'])) {
                $prize['startDate'] = MongodbUtil::msTimetamp2MongoDate($params[$i]['startDate']);
                $prize['endDate'] = MongodbUtil::msTimetamp2MongoDate($params[$i]['endDate']);
            }

            if ($params[$i]['isPoint'] == 'N') {
                $params[$i]['points'] = null;
            }

            $prize->name = $params[$i]['name'];
            $prize->prizeImgUrl = $params[$i]['prizeImgUrl'];
            $prize->type = $params[$i]['type'];
            $prize->isPoint = $params[$i]['isPoint'];
            $prize->points = (int)$params[$i]['points'];
            $prize->quantity = (int)$params[$i]['quantity'];
            $prize->activityId = $activityId;

            if (!$prize->save()) {
                LogUtil::error(['message' => 'save activity-prize failed', 'error' => $prize->errors], 'activityPrize');
                // throw new ServerErrorHttpException('save activityPrize failed');
                return false;
            }
            unset($prize);
        }
        return true;
    }

    //$activityId : String
    public static function getByActivityId($activityId)
    {
        $activityId = new \MongoId($activityId);
        $list = ActivityPrize::findAll(['activityId'=>$activityId, 'isDeleted'=>false]);
        for ($i=0; $i<count($list); $i++) {
            if (!empty($list[$i]['startDate'])) {
                $list[$i]['startDate'] = MongodbUtil::MongoDate2msTimeStamp($list[$i]['startDate']);
                $list[$i]['endDate'] = MongodbUtil::MongoDate2msTimeStamp($list[$i]['endDate']);
            }
            $list[$i] = $list[$i]->attributes;
        }
        return $list;
    }

    //$activityId : MongoId
    public static function getValidPrizesByActivityId($activityId)
    {
        $list = ActivityPrize::findAll(['activityId'=>$activityId, 'isDeleted'=>false, 'quantity'=>['$gt'=>0]]);
        for ($i=0; $i<count($list); $i++) {
            if (!empty($list[$i]['startDate'])) {
                $list[$i]['startDate'] = MongodbUtil::MongoDate2msTimeStamp($list[$i]['startDate']);
                $list[$i]['endDate'] = MongodbUtil::MongoDate2msTimeStamp($list[$i]['endDate']);
            }
            $list[$i] = $list[$i]->attributes;
        }
        return $list;
    }

}