<?php
namespace backend\modules\member\models;

use Yii;
use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\models\Channel;

/**
 * Model class for statsMemberMonthly.
 *
 * The followings are the available columns in collection 'statsMemberMonthly':
 * @property MongoId   $_id
 * @property String    $month
 * @property String    $origin
 * @property String    $originName
 * @property int       $total
 * @property MongoDate $createdAt
 * @property ObjectId  $accountId
 *
 **/

class StatsMemberMonthly extends PlainModel
{
    public static $originWithoutChannels = [
        self::PORTAL,
        self::APP_ANDROID,
        self::APP_IOS,
        self::APP_WEB,
        self::APP_WEBVIEW,
        self::OTHERS,
    ];

    /**
     * Declares the name of the Mongo collection associated with statsMemberMonthly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberMonthly';
    }

    /**
     * Returns the list of all attribute names of statsMemberMonthly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['month', 'origin', 'originName', 'total']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['month', 'origin', 'originName', 'total']
        );
    }

    /**
     * Returns the list of all rules of statsMemberMonthly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['month', 'total'], 'required'],
                ['origin', 'in', 'range' => self::$origins],
                ['total', 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberMonthly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'month', 'origin', 'originName', 'total',
                'createdAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt);
                }
            ]
        );
    }

    public static function getByDateAndOriginInfo($date, $origin, $originName, $accountId)
    {
        $condition = [
            'accountId' => $accountId,
            'month' => $date,
            'origin' => $origin,
            'originName' => $originName
        ];
        return self::findOne($condition);
    }

    public static function getByDate($startDate, $endDate, $accountId)
    {
        $condition = [
            'accountId' => $accountId,
            'month' => ['$gte' => $startDate, '$lte' => $endDate]
        ];
        return self::findAll($condition);
    }

    public static function preProcessData($condition)
    {
        $stats = self::findAll($condition);
        $statsData = [];
        foreach ($stats as $item) {
            $date = $item->month;
            $statsData[$item->origin][$date] = empty($statsData[$item->origin][$date]) ? 0 : $statsData[$item->origin][$date];
            $statsData[$item->origin][$date] += $item->total;
        }

        //ensure origins
        $channelOrigins = Channel::getOriginsByAccount($condition['accountId']);
        $origins = array_merge(StatsMemberMonthly::$originWithoutChannels, $channelOrigins);
        foreach ($origins as $origin) {
            if (empty($statsData[$origin])) {
                $statsData[$origin] = [];
            }
        }

        $endDate = $condition['month']['$lte'];
        $startDate = $condition['month']['$gte'];
        $endTime = strtotime($endDate);
        $dateTime = strtotime($startDate);
        $data = [];
        while ($dateTime <= $endTime) {
            $date = date('Y-m', $dateTime);
            foreach ($statsData as $origin => $dateTotal) {
                $data[] = [
                    'month' => $date,
                    'channel' => Yii::t('common', $origin),
                    'number' => empty($dateTotal[$date]) ? 0 : $dateTotal[$date],
                ];
            }
            $dateTime = strtotime('+1 month', $dateTime);
        }

        return $data;
    }
}
