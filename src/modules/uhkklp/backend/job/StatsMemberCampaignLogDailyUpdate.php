<?php
namespace backend\modules\uhkklp\job;

use Yii;
use MongoId;
use MongoDate;
use backend\models\StatsMemberCampaignLogDaily;
use backend\modules\member\models\Member;
use backend\modules\product\models\CampaignLog;
use backend\modules\member\models\MemberProperty;
use backend\modules\uhkklp\job\StatsMemberPropAvgTradeQuarterly;
use backend\models\StatsMemberPropAvgTradeQuarterly as ModelStatsMemberPropAvgTradeQuarterly;
use backend\models\StatsMemberPropTradeQuarterly;
use backend\models\StatsCampaignProductCodeQuarterly;
use backend\models\StatsMemberPropTradeCodeQuarterly;
use backend\models\StatsMemberPropTradeCodeAvgQuarterly;
use backend\modules\product\models\Product;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;

/**
* Job for update StatsMemberCampaignLog
* Base job for other campaign related statistics
*/
class StatsMemberCampaignLogDailyUpdate
{
    const ROWS = 1000;

    public function perform()
    {
        //accountId, properties are required fileds
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['properties'])
            || empty($args['startDate']) || empty($args['endDate'])) {
            LogUtil::error(['Missing params in update StatsMemberCampaignLog', 'params' => $args], 'update_job');
            return false;
        }

        $startTime = strtotime($args['startDate']);
        $endTime = strtotime($args['endDate']);
        $today = strtotime(date('Y-m-d'));
        if ($endTime >= $today) {
            $endTime = $today - 3600 * 24;
        }
        $deleteLogEndTime = $endTime + 3600 * 24;

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }

        foreach ($accountIds as $accountId) {
            $args['accountId'] = new MongoId($accountId);
            //delete data with account and time
            StatsMemberCampaignLogDaily::deleteAll(['accountId' => $args['accountId'], 'createdAt' => ['$gte' => new MongoDate($startTime), '$lt' => new MongoDate($deleteLogEndTime)]]);
            self::createStatsMemberCampaignLogDailyWithTime($startTime, $endTime, $args);
        }
        return true;
    }

    public static function createStatsMemberCampaignLogDailyWithTime($startTime, $endTime, $args)
    {
        $accountId = $args['accountId'];
        for ($t = $startTime; $t <= $endTime; $t += 3600 * 24) {
            $start = new MongoDate($t);
            $endHours = $t + 3600 * 24;
            $end = new MongoDate($endHours);
            $formatTime = date('Y-m-d', $t);

            //Get all the property mongo id for comparison
            $condition = [
                'propertyId' => ['$in' => $args['properties']],
                'accountId' => $accountId
            ];
            $propertyIdStrs = self::_getPropertyId($condition);

            //get campaign log begin
            $condition = [
                'accountId' => $accountId,
                'createdAt' => [
                    '$gte' => $start,
                    '$lt' => $end
                ]
            ];

            $count = self::getToadyCampaignLog($condition);
            //get today member info
            $members = self::getMemberInfo($condition);

            if ($count > self::ROWS) {
                for ($beginHour = 0; $beginHour < 24; ++$beginHour) {
                    $nextHour = $beginHour + 1;
                    $condition['createdAt'] = [
                        '$gte' => new MongoDate($t + 3600 * $beginHour),
                        '$lt' => new MongoDate($t + 3600 * $nextHour),
                    ];
                    unset($nextHour);
                    $campaignLogs = self::_getCampaignLog($condition);
                    $formatTime = date('Y-m-d H:i:s', $t + 3600 * $beginHour);
                    self::recordProcessAndCreate($members, $campaignLogs, $propertyIdStrs, $accountId, $formatTime);
                }
            } else {
                $campaignLogs = self::_getCampaignLog($condition);
                self::recordProcessAndCreate($members, $campaignLogs, $propertyIdStrs, $accountId, $formatTime);
            }
        }
    }

    public static function getMemberInfo($condition)
    {
        $memberIds = CampaignLog::distinct('member.id', $condition);
        $members = Member::findAll(['_id' => ['$in' => $memberIds]]);

        $data = [];
        if (!empty($members)) {
            foreach ($members as $member) {
                $data[(string)$member->_id] = $member;
            }
        }
        return $data;
    }

    public static function recordProcessAndCreate($members, $campaignLogs, $propertyIdStrs, $accountId, $formatTime)
    {
        if (empty($campaignLogs)) {
            LogUtil::info(['message' => $formatTime .': campaignLogs is empty,no need to store data in StatsMemberCampaignLog', 'accountId' => (string)$accountId], 'update_job');
        } else {
            LogUtil::info(['message' => $formatTime .': Begin to store data in StatsMemberCampaignLog', 'number' => count($campaignLogs), 'accountId' => (string)$accountId], 'update_job');
            //Generate the meta data for inserting
            self::createDailyData($members, $campaignLogs, $propertyIdStrs, $accountId);
        }
        unset($campaignLogs);
    }

    public static function getToadyCampaignLog($condition)
    {
        return CampaignLog::find()->where($condition)->count();
    }

    public function tearDown()
    {
        $args = $this->args;

        $startTime = strtotime($args['startDate']);
        $endTime = strtotime($args['endDate']);
        $today = strtotime(date('Y-m-d'));
        if ($endTime >= $today) {
            $endTime = $today - 3600 * 24;
        }

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountId']];
        }
        foreach ($accountIds as $accountId) {
            $args['accountId'] = new MongoId($accountId);
            //StatsMemberPropAvgTradeQuarterly
            self::_setStatsMemberPropAvgTradeQuarterly($startTime, $endTime, $args);
            //StatsMemberPropertyTradeCodeAvgQuarterly
            self::_setStatsMemberPropertyTradeCodeAvgQuarterly($startTime, $endTime, $args);
            //StatsMemberPropTradeQuarterly
            self::_setStatsMemberPropTradeQuarterly($startTime, $endTime, $args);
            //StatsMemberPropTradeCodeQuarterly
            self::_setStatsMemberPropTradeCodeQuarterly($startTime, $endTime, $args);
            //StatsCampaignProductCodeQuarterly
            self::_setStatsCampaignProductCodeQuarterly($startTime, $endTime, $args);
        }

        LogUtil::info(['message' => 'All script run over'], 'update_job');
    }

    public static function _setStatsMemberPropertyTradeCodeAvgQuarterly($startTime, $endTime, $args)
    {
        $property = $args['properties'][0];
        $memberProperty = MemberProperty::getByPropertyId($args['accountId'], $property);
        if (empty($memberProperty)) {
            LogUtil::error(['message' => $args['accountId'] . ':Can not find member property with propertyId:' . $property, 'accountId' => (string)$args['accountId']], 'update_job');
        } else {
            $startQuarter = TimeUtil::getQuarter($startTime);
            $endQuarter = TimeUtil::getQuarter($endTime);
            for ($quarter = $startQuarter; $quarter <= $endQuarter; ++$quarter) {
                $year = date('Y', $startTime);
                $condition = [
                    'year' => $year,
                    'quarter' => $quarter,
                    'accountId' => $args['accountId'],
                ];
                StatsMemberPropTradeCodeAvgQuarterly::deleteAll($condition);
                self::generateStatsMemberPropertyTradeCodeAvgQuarterlyData($memberProperty, $property, $condition);
                LogUtil::info(['message' => $quarter . ' :Run StatsMemberPropertyTradeCodeAvgQuarterly', 'accountId' => (string)$args['accountId']], 'update_job');
            }
        }
    }

    public static function _setStatsMemberPropAvgTradeQuarterly($startTime, $endTime, $args)
    {
        $propertyOperate = $args['properties'][0];
        $memberProperty = MemberProperty::getByPropertyId($args['accountId'], $propertyOperate);
        if (empty($memberProperty)) {
            LogUtil::error(['message' => 'Can not find the property:' . $propertyOperate, 'accountId' => (string)$args['accountId']], 'update_job');
        } else {
            $memberPropertyId = $memberProperty->_id;

            $startQuarter = TimeUtil::getQuarter($startTime);
            $endQuarter = TimeUtil::getQuarter($endTime);

            for ($quarter = $startQuarter; $quarter <= $endQuarter; ++$quarter) {
                $year = date('Y', $startTime);
                $condition = [
                    'accountId' => $args['accountId'],
                    'year' => $year,
                    'quarter' => $quarter
                ];
                ModelStatsMemberPropAvgTradeQuarterly::deleteAll($condition);
                StatsMemberPropAvgTradeQuarterly::generateData($args['accountId'], $memberPropertyId, $year, $quarter, $propertyOperate);
                LogUtil::info(['message' => $quarter . 'Run StatsMemberPropAvgTradeQuarterly', 'accountId' => (string)$args['accountId']], 'update_job');
            }
        }
    }

    private static function _setStatsCampaignProductCodeQuarterly($startTime, $endTime, $args)
    {
        $startQuarter = TimeUtil::getQuarter($startTime);
        $endQuarter = TimeUtil::getQuarter($endTime);
        for ($quarter = $startQuarter; $quarter <= $endQuarter; ++$quarter) {
            $year = date('Y', $startTime);
            $condition = [
                'accountId' => $args['accountId'],
                'year' => $year,
                'quarter' => $quarter
            ];
            StatsCampaignProductCodeQuarterly::deleteAll($condition);
            self::generateStatsCampaignProductCodeQuarterlyData($condition);
            LogUtil::info(['message' => $quarter . ' :Run StatsCampaignProductCodeQuarterly', 'accountId' => (string)$args['accountId']], 'update_job');
        }
    }

    private static function _setStatsMemberPropTradeCodeQuarterly($startTime, $endTime, $args)
    {
        //Assume that the subChannel is the secode element in properties
        $propertyKey = $args['properties'][1];
        $memberProperty = MemberProperty::getByPropertyId($args['accountId'], $propertyKey);
        if (!empty($memberProperty)) {
            $startQuarter = TimeUtil::getQuarter($startTime);
            $endQuarter = TimeUtil::getQuarter($endTime);
            for ($quarter = $startQuarter; $quarter <= $endQuarter; ++$quarter) {
                $year = date('Y', $startTime);
                $condition = [
                    'accountId' => $args['accountId'],
                    'year' => $year,
                    'quarter' => $quarter
                ];
                StatsMemberPropTradeCodeQuarterly::deleteAll($condition);
                self::generateStatsMemberPropTradeCodeQuarterlyData((string)$memberProperty['_id'], $condition);
                LogUtil::info(['message' => $quarter . ' :Run StatsMemberPropTradeCodeQuarterly', 'accountId' => (string)$args['accountId']], 'update_job');
            }
        } else {
            LogUtil::info(['message' => 'Fail to get memberProperty with propertyId' . $propertyKey, 'accountId' => (string)$args['accountId']], 'update_job');
        }
    }

    private static function _setStatsMemberPropTradeQuarterly($startTime, $endTime, $args)
    {
        //Assume that the subChannel is the secode element in properties
        $propertyKey = $args['properties'][1];
        $memberProperty = MemberProperty::getByPropertyId($args['accountId'], $propertyKey);
        if (!empty($memberProperty)) {
            $startQuarter = TimeUtil::getQuarter($startTime);
            $endQuarter = TimeUtil::getQuarter($endTime);
            for ($quarter = $startQuarter; $quarter <= $endQuarter; ++$quarter) {
                $year = date('Y', $startTime);
                $condition = [
                    'accountId' => $args['accountId'],
                    'year' => $year,
                    'quarter' => $quarter
                ];
                StatsMemberPropTradeQuarterly::deleteAll($condition);
                self::generateStatsMemberPropTradeQuarterlyData((string)$memberProperty['_id'], $condition);
                LogUtil::info(['message' => $quarter . ' :Run StatsMemberPropTradeQuarterly', 'accountId' => (string)$args['accountId']], 'update_job');
            }
        } else {
            LogUtil::info(['message' => 'Can not find this propertyId:' . $propertyKey, 'accountId' => (string)$args['accountId']], 'update_job');
        }
    }

    private static function _getCampaignLog($condition)
    {
        $campaignLogs = CampaignLog::getCollection()->aggregate(
            [
                '$match' => $condition,
            ],
            [
                '$group' => [
                    '_id' => [
                        'productId' => '$productId',
                        'createdAt' => '$createdAt',
                        'member' => '$member',
                        'code' => '$code',
                        'redeemTime' => '$redeemTime',
                    ],
                ]
            ]
        );
        return $campaignLogs;
    }

    private static function _getPropertyId($condition)
    {
        $propertyIds = MemberProperty::find()->select(['_id'])->where($condition)->all();
        $propertyIdStrs = [];
        if (empty($propertyIds)) {
            LogUtil::error(['message' => 'Run update StatsMemberCampaignLog fail, because the propertyIds is empty', 'condition' => $condition], 'resque');
        } else {
            foreach ($propertyIds as $propertyId) {
                $propertyIdStrs[] = (string) $propertyId['_id'];
            }
        }
        return $propertyIdStrs;
    }

    public static function createDailyData($members, $campaignLogs, $propertyIdStrs, $accountId)
    {
        $statsRows = [];
        foreach ($campaignLogs as $campaignLog) {
            $campaignLog = $campaignLog['_id'];

            if ($campaignLog['redeemTime'] == $campaignLog['createdAt']) {
                $createdAt = $campaignLog['createdAt'];
            } else {
                $createdAt = $campaignLog['redeemTime'];
            }

            //check the redeem time whether exists
            $createdAt = MongodbUtil::MongoDate2TimeStamp($createdAt);
            $month = date('Y-m', $createdAt);

            $memberId = $campaignLog['member']['id'];
            $memProperty = [];
            if (isset($members[(string)$memberId])) {
                $member = $members[(string)$memberId];
                foreach ($member->properties as $property) {
                    $propertyId = (string)$property['id'];
                    if (in_array($propertyId, $propertyIdStrs)) {
                        $memProperty[$propertyId] = $property['value'];
                    }
                }
            }

            $statsRows[] = [
                'memberId' => $memberId,
                'memProperty' => $memProperty,
                'productId' => $campaignLog['productId'],
                'code' => $campaignLog['code'],
                'year' => date('Y', $createdAt),
                'month' => $month,
                'quarter' => TimeUtil::getQuarter($createdAt),
                'accountId' => $accountId,
                'createdAt' => $campaignLog['createdAt'],
            ];
        }
        StatsMemberCampaignLogDaily::batchInsert($statsRows);
        unset($statsRows, $members, $campaignLog, $campaignLogs);
    }

    public static function generateStatsMemberPropertyTradeCodeAvgQuarterlyData($memberProperty, $property, $condition)
    {
        $propertyKey = 'memProperty.' . $memberProperty->_id;
        $keys = ['productId' => true, $propertyKey => true];

        $initial = [
            'avg' => 0,
            'codes' => ['count' => 0],
            'members' => ['count' => 0]
        ];
        $reduce = 'function(doc, prev) {
            if (!prev.members[doc.memberId]) {
                prev.members[doc.memberId] = true;
                prev.members["count"]++;
            }
            if (!prev.codes[doc.code]) {
                prev.codes[doc.code] = true;
                prev.codes["count"]++;
            }
        }';
        $finalize = 'function(prev) {
            prev.avg = prev.codes["count"] / prev.members["count"];
            delete prev.codes;
            delete prev.members;
        }';

        $statsItems = StatsMemberCampaignLogDaily::getCollection()->group($keys, $initial, $reduce, [
            'condition' => $condition,
            'finalize' => $finalize
        ]);

        $productNames = self::getProductNames($condition);

        $rows = [];
        foreach ($statsItems as $statsItem) {
            $condition['propId'] = $property;
            $condition['propValue'] = $statsItem[$propertyKey];
            $productId = $statsItem['productId'];
            $condition['productId'] = $productId;

            $productName = isset($productNames[(string)$productId]) ? $productNames[(string)$productId] : '';
            $rows[] = [
                'propId' => $property,
                'propValue' => $statsItem[$propertyKey],
                'productId' => $productId,
                'productName' => $productName,
                'year' => $condition['year'],
                'quarter' => $condition['quarter'],
                'accountId' => $condition['accountId'],
                'avg' => $statsItem['avg'],
            ];
        }
        StatsMemberPropTradeCodeAvgQuarterly::batchInsert($rows);
        unset($rows);
    }

    public static function getProductNames($condition)
    {
        $productIds = StatsMemberCampaignLogDaily::distinct('productId', $condition);
        $products = Product::findAll(['_id' => ['$in' => $productIds]]);

        $datas = [];
        if (!empty($products)) {
            foreach ($products as $product) {
                $datas[(string)$product->_id] = $product->name;
            }
        }
        return $datas;
    }

    public static function generateStatsMemberPropTradeCodeQuarterlyData($propertyId, $condition)
    {
        $campaignLogDailys = StatsMemberCampaignLogDaily::getCollection()->aggregate(
            [
                '$match' => $condition,
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$memProperty.' . $propertyId,
                        'code' => '$code',
                    ]
                ],
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$_id.propValue',
                    ],
                    'total' => [
                        '$sum' => 1
                    ]
                ]
            ]
        );
        if (!empty($campaignLogDailys)) {
            foreach ($campaignLogDailys as $item) {
                $rows[] = [
                    'propId' => $propertyId,
                    'propValue' => $item['_id']['propValue'],
                    'total' => $item['total'],
                    'year' => (string)$condition['year'],
                    'quarter' => $condition['quarter'],
                    'accountId' => $condition['accountId'],
                ];
            }
            StatsMemberPropTradeCodeQuarterly::batchInsert($rows);
            unset($rows);
        }
    }

    public static function generateStatsCampaignProductCodeQuarterlyData($condition)
    {
        $campaignLogDailys = StatsMemberCampaignLogDaily::getCollection()->aggregate([
            [
                '$match' => $condition,
            ],
            [
                '$group' => [
                    '_id' => [
                        'productId' => '$productId',
                    ],
                    'total' => [
                        '$sum' => 1
                    ]
                ]
            ]
        ]);

        if (!empty($campaignLogDailys)) {
            $productIds = StatsMemberCampaignLogDaily::distinct('productId', $condition);
            $products = Product::findAll(['_id' => ['$in' => $productIds]]);
            $productNames = [];
            foreach ($products as $product) {
                $productNames[(string)$product->_id] = $product->name;
            }
            foreach ($campaignLogDailys as $campaignLogDaily) {
                $productId = $campaignLogDaily['_id']['productId'];
                $rows[] = [
                    'productId' => $productId,
                    'productName' => isset($productNames[(string)$productId]) ? $productNames[(string)$productId] : '',
                    'total' => $campaignLogDaily['total'],
                    'year' => $condition['year'],
                    'quarter' => $condition['quarter'],
                    'accountId' => $condition['accountId'],
                ];
            }
            StatsCampaignProductCodeQuarterly::batchInsert($rows);
        }
    }

    public static function generateStatsMemberPropTradeQuarterlyData($propertyId, $condition)
    {
        $campaignLogDailys = StatsMemberCampaignLogDaily::getCollection()->aggregate(
            [
                '$match' => $condition,
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$memProperty.' . $propertyId,
                        'memberId' => '$memberId',
                    ]
                ],
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$_id.propValue',
                    ],
                    'total' => [
                        '$sum' => 1
                    ]
                ]
            ]
        );

        $rows = [];
        if (!empty($campaignLogDailys)) {
            foreach ($campaignLogDailys as $item) {
                $rows[] = [
                    'propId' => $propertyId,
                    'propValue' => $item['_id']['propValue'],
                    'total' => $item['total'],
                    'year' => $condition['year'],
                    'quarter' => $condition['quarter'],
                    'accountId' => $condition['accountId'],
                ];
            }
            StatsMemberPropTradeQuarterly::batchInsert($rows);
            unset($rows);
        }
    }
}