<?php
namespace backend\modules\member\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for memberLogs.
 *
 * The followings are the available columns in collection 'memberLogs':
 * @property MongoId    $_id
 * @property MongoId    $memberId
 * @property string     $operation
 * @property MongoDate  $operationAt
 * @property MongoDate  $createdAt
 * @property ObjectId   $accountId
 *
 **/

class MemberLogs extends PlainModel
{
    const OPERATION_VIEWED = 'viewed';
    const OPERATION_REDEEM = 'redeem';

    /**
     * Declares the name of the Mongo collection associated with memberLogs.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'memberLogs';
    }

    /**
     * Returns the list of all attribute names of memberLogs.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['memberId', 'operation', 'operationAt', 'createdAt']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['memberId', 'operation', 'operationAt', 'createdAt']
        );
    }

    /**
     * Returns the list of all rules of memberLogs.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['operation', 'in', 'range' => [self::OPERATION_REDEEM, self::OPERATION_VIEWED]],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into memberLogs.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'memberId', 'operation',
                'operationAt' => function($model) {
                    return MongodbUtil::MongoDate2String($model->operationAt);
                },
                'createdAt' => function($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt);
                }
            ]
        );
    }

    public static function record($memberId, $accountId, $operation, $operationAt = null)
    {
        $memberLogs = new MemberLogs;
        $memberLogs->memberId = $memberId;
        $memberLogs->accountId = $accountId;
        $memberLogs->operation = $operation;
        $memberLogs->operationAt = ($operationAt == null) ? new \MongoDate() : $operationAt;

        return $memberLogs->save();
    }

    public static function getTotalActiveByAccount($accountId, $startTime, $endTime)
    {
        $condition = ['accountId' => $accountId, 'operationAt' => ['$gte' => $startTime, '$lt' => $endTime]];
        return count(self::getCollection()->distinct('memberId', $condition));
    }

    public static function getTotalNewByAccount($accountId, $newMemberIds)
    {
        $condition = ['accountId' => $accountId, 'memberId' => ['$in' => $newMemberIds]];
        return count(self::getCollection()->distinct('memberId', $condition));
    }
}
