<?php
namespace backend\modules\uhkklp\job;

use MongoId;
use yii\helpers\ArrayHelper;
use backend\utils\TimeUtil;
use backend\modules\member\models\Member;
use backend\modules\product\models\Product;
use backend\modules\member\models\MemberProperty;
use backend\models\StatsMemberCampaignLogDaily as ModelStatsMemberCampaignLogDaily;
use backend\models\StatsMemberPropTradeCodeAvgQuarterly as ModelStatsMemberPropTradeCodeAvgQuarterly;
use backend\modules\resque\components\ResqueUtil;

/**
* Job for StatsMemberDaily
*/
class StatsMemberPropertyTradeCodeAvgQuarterly
{
    public function setUp()
    {
    }

    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['properties'][0])) {
            ResqueUtil::log('Missing required arguments accountId or properties!');
            return false;
        }

        $date = empty($args['date']) ? '' : $args['date'];
        $date = TimeUtil::getDatetime($date);

        $year = date('Y', $date);
        $quarter = TimeUtil::getQuarter($date);
        $property = $args['properties'][0];

        if (is_array($args['accountId'])) {
            $accountIds = $args['accountId'];
        } else {
            $accountIds = [$args['accountIds']];
        }

        foreach ($accountIds as $accountId) {
            $accountId = new MongoId($accountId);
            $memberProperty = self::getMemberProperty($accountId, $property);
            if (!empty($memberProperty)) {
                self::generateData($memberProperty, $property, $year, $quarter, $accountId);
            }
        }
        return true;
    }

    public static function getMemberProperty($accountId, $property)
    {
        $memberProperty = MemberProperty::getByPropertyId($accountId, $property);

        if (!empty($memberProperty)) {
            return $memberProperty;
        } else {
            ResqueUtil::log($accountId . ':Can not find member property with propertyId:' . $property);
            return [];
        }
    }

    public static function generateData($memberProperty, $property, $year, $quarter, $accountId)
    {
        $propertyKey = 'memProperty.' . $memberProperty->_id;
        $keys = ['productId' => true, $propertyKey => true];
        $condition = [
            'year' => $year,
            'quarter' => $quarter,
            'accountId' => $accountId,
        ];
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

        $statsItems = ModelStatsMemberCampaignLogDaily::getCollection()->group($keys, $initial, $reduce, [
            'condition' => $condition,
            'finalize' => $finalize
        ]);

        $productIds = ArrayHelper::getColumn($statsItems, 'productId');
        $productNames = self::getProductName($productIds);

        foreach ($statsItems as $statsItem) {
            $condition['propId'] = $property;
            $condition['propValue'] = $statsItem[$propertyKey];
            $productId = $statsItem['productId'];
            $condition['productId'] = $productId;

            $stats = ModelStatsMemberPropTradeCodeAvgQuarterly::findOne($condition);

            if (empty($stats)) {
                $stats = new ModelStatsMemberPropTradeCodeAvgQuarterly();

                $productName = isset($productNames[(string)$productId]) ? $productNames[(string)$productId] : '';

                $stats->propId = $property;
                $stats->propValue = $statsItem[$propertyKey];
                $stats->productId = $productId;
                $stats->productName = $productName;
                $stats->year = $year;
                $stats->quarter = $quarter;
                $stats->accountId = $accountId;
            }

            $stats->avg = $statsItem['avg'];
            $stats->save();
        }
    }

    public function tearDown()
    {
    }

    /**
     * @param $productIds, array
     */
    public static function getProductName($productIds)
    {
        if (empty($productIds) || !is_array($productIds)) {
            return [];
        }

        $products = Product::findAll(['_id' => ['$in' => $productIds]]);

        $datas = [];
        if (!empty($products)) {
            foreach ($products as $product) {
                $datas[(string)$product->_id] = $product->name;
            }
        }
        return $datas;
    }
}
