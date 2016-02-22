<?php

namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberPropTradeCodeAvgQuarterly.
 *
 * The followings are the available columns in collection 'statsMemberPropTradeCodeAvgQuarterly':
 * @property MongoId $_id
 * @property String $propId
 * @property String $propValue
 * @property String $productId
 * @property String $productName
 * @property int $avg
 * @property String $year
 * @property String $quarter
 * @property ObjectId $accountId
 *
 **/

class StatsMemberPropTradeCodeAvgQuarterly extends PlainModel
{
    const NOT_SELECTED = '未选';
    /**
     * Declares the name of the Mongo collection associated with statsMemberPropTradeCodeAvgQuarterly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberPropTradeCodeAvgQuarterly';
    }

    /**
     * Returns the list of all attribute names of statsMemberPropAvgTradeQuaterly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'productId', 'productName', 'avg', 'year', 'quarter']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'productId', 'productName', 'avg', 'year', 'quarter']
        );
    }

    /**
     * Returns the list of all rules of statsMemberPropAvgTradeQuaterly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['propId', 'productId', 'avg', 'year', 'quarter'], 'required'],
                ['avg', 'double'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberPropAvgTradeQuaterly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'propName', 'propValue', 'productId', 'productName', 'avg', 'year', 'quarter'
            ]
        );
    }

    public function preProcessData($condition)
    {
        $stats = self::findAll($condition);
        $quarter = $condition['quarter'];
        $productMap = [];
        $propMap = [];
        foreach ($stats as $stat) {
            if (empty($stat->propValue)) {
                $stat->propValue = self::NOT_SELECTED;
            }
            $productId = (string) $stat->productId;
            if (empty($productMap[$productId])) {
                $productMap[$productId] = [];
            }
            $productMap[$productId][$stat->propValue] = $stat->avg;
            $productMap[$productId]['sku'] = $stat->productName;
            $propMap[$stat->propValue] = true;
        }

        $data = [];
        foreach ($productMap as $productId => $product) {
            foreach ($propMap as $prop => $nil) {
                $data[] = [
                    'quarter' => 'Q' . $quarter,
                    'sku' => empty($product['sku']) ? "" : $product['sku'],
                    'operate' => $prop,
                    'number' => empty($product[$prop]) ? 0 : $product[$prop]
                ];
            }
        }

        return $data;
    }
}
