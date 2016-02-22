<?php
namespace backend\behaviors;

use yii\base\Behavior;
use backend\models\Goods;
use backend\models\StoreGoods;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\modules\reservation\models\ReservationGoods;
use MongoId;
use Yii;

class ProductBehavior extends Behavior
{
    public function update($product)
    {
        $productId = $product->_id;
        $name = $product->name;
        $categoryId = isset($product->category['id']) ? $product->category['id'] : '';

        Goods::updateAll(
            ['$set' => ['productName' => $name, 'categoryId' => $categoryId, 'sku' => $product->sku]],
            ['productId' => $productId]
        );

        ReservationGoods::updateAll(
            ['$set' => ['productName' => $name, 'categoryId' => $categoryId, 'pictures' => $product->pictures, 'sku' => $product->sku]],
            ['productId' => $productId]
        );

        StoreGoods::updateAll(
            ['$set' => ['productName' => $name, 'categoryId' => $categoryId, 'sku' => $product->sku]],
            ['productId' => $productId]
        );

        PromotionCodeAnalysis::updateAll(['$set' => ['productName' => $name]], ['productId' => $productId]);

        //update product log
        $args = [
            'productId' => (string) $product->_id,
            'description' => 'Direct: update product log info'
        ];
        Yii::$app->job->create('backend\modules\product\job\UpdateProductLog', $args);
    }

    public function delete($productIds)
    {
        Goods::deleteAll(['productId' => ['$in' => $productIds]]);
        StoreGoods::deleteAll(['productId' => ['$in' => $productIds]]);
    }

    public function updateSpecificationPrice($product, $specifications)
    {
        if (count($product->specifications) != count($specifications)) {
            //update reservation goods specification price
            $this->clearReservationGoodsPrice($product->_id);
        } else {
            //check the specification id whether change
            $change = $this->checkIsChangeSpecification($product->specifications, $specifications);
            if ($change) {
                $this->clearReservationGoodsPrice($product->_id);
            } else {
                //get new specification map
                //get a map of product specifications properties by property index
                $productSpecificationPropertyIds = $this->getAllSpecificationPropertyIdWithIndex($specifications);
                //get how many propeties in ervery specification and how many groups
                $specificationTotal = $this->getStatisticsWithGroup($productSpecificationPropertyIds);
                //create a map base on the property index and total group number
                $map = $this->createMapBaseOnIndex($specificationTotal['propertyIndexData'], $specificationTotal['total']);

                //get map value => md5(propertyid,proprty id)(sort by property id and connenct each proprty id with , then md5 the value)
                $mapIds = $this->converIndexToPropertyIdWithMd5($map, $productSpecificationPropertyIds);
                //get all reservation goods price
                $result = $this->getAllReservationPriceAndStatus($product->_id);
                //update every reservation goods
                $this->updateReservationGoodsPriceAndStatus($result, $mapIds);
            }
        }
    }

    public function checkIsChangeSpecification($oldSpecifications, $specifications)
    {
        if (count($oldSpecifications) != count($specifications)) {
            return true;
        }
        return false;
    }

    public function updateReservationGoodsPriceAndStatus($result, $mapIds)
    {
        foreach ($result['prices'] as $reservationGoodId => $prices) {
            $updatePrice = $updateStatus = [];
            $id = array_keys($prices);
            $price = array_values($prices);
            $status = array_values($result['status'][$reservationGoodId]);
            foreach ($mapIds as $mapId) {
                foreach ($id as $k => $v) {
                    if ($mapId == $v) {
                        $updatePrice[$mapId] = $price[$k];
                        $updateStatus[$mapId] = $status[$k];
                    }
                }
                if (!isset($updatePrice[$mapId])) {
                    $updatePrice[$mapId] = $updateStatus[$mapId] = 0;
                }
            }

            $reservationGood = ReservationGoods::findByPk(new MongoId($reservationGoodId));
            if (!empty($reservationGood)) {
                $reservationShelf = [
                    'id' => $reservationGood->reservationShelf['id'],
                    'price' => $updatePrice,
                    'status' => $updateStatus,
                ];

                $reservationGood->price = $this->getMinPriceFromOnSale($updatePrice, $updateStatus);
                $reservationGood->reservationShelf = $reservationShelf;
                $reservationGood->save();
            }
        }
    }

    public function getMinPriceFromOnSale($price, $status)
    {
        $validPrice = array_values($price);
        sort($validPrice);
        return isset($validPrice[0]) ? $validPrice[0] : 0;
    }

    public function converIndexToPropertyIdWithMd5($map, $productSpecificationPropertyIds)
    {
        $count = count($map);
        $mapIds = [];
        for ($i = 0; $i < $count; ++$i) {
            $temp = [];
            $value = (string)$map[$i];
            $len = strlen($value);
            for ($k = 0; $k < $len; ++$k) {
                $temp[] = $productSpecificationPropertyIds[$k][$value[$k]];
            }
            //sort the value
            asort($temp);
            $mapIds[] = md5(implode(',', $temp));
        }
        return $mapIds;
    }

    public function getAllReservationPriceAndStatus($productId)
    {
        $reservationGoods = ReservationGoods::find()
                            ->select(['reservationShelf'])
                            ->where(['productId' => $productId])
                            ->all();
        $prices = $status = [];
        if (!empty($reservationGoods)) {
            foreach ($reservationGoods as $reservationGood) {
                if (empty($reservationGood->reservationShelf['price']) || empty($reservationGood->reservationShelf['status'])) {
                    continue;
                }
                $prices[(string)$reservationGood->_id] = $reservationGood->reservationShelf['price'];
                $status[(string)$reservationGood->_id] = $reservationGood->reservationShelf['status'];
            }
        }
        return ['prices' => $prices, 'status' => $status];
    }

    public function createMapBaseOnIndex($datas, $total)
    {
        $map = [];
        $lastKey = count($datas) - 1;
        foreach ($datas as $key => $value) {
            $m = 0;
            if ($key == 0) {
                $first = 1;
                $last = $total/$value;
            } else if ($lastKey != $key) {
                $first = $this->getBeforeValue($datas, $key);
                $last = $total/($first*$value);
            } else {
                $first = $total/$datas[$lastKey];
                $last = 1;
            }

            for ($i = 0; $i < $first; ++$i) {
                for ($v = 0; $v < $value; ++$v) {
                    for ($j = 0; $j < $last; ++$j) {
                        if (isset($map[$m])) {
                            $map[$m] .= $v;
                        } else {
                            $map[$m] = $v;
                        }
                        $m ++;
                    }
                }
            }
        }
        return $map;
    }

    private function getBeforeValue($datas, $key)
    {
        $total = 1;
        foreach ($datas as $index => $value) {
            if ($index < $key) {
                $total *= $value;
            } else {
                break;
            }
        }
        return $total;
    }

    public function getStatisticsWithGroup($productSpecificationPropertyIds)
    {
        $number = 1;
        $propertyNumber = [];
        foreach ($productSpecificationPropertyIds as $key => $productSpecificationPropertyId) {
            $total = count($productSpecificationPropertyId) ? count($productSpecificationPropertyId) : 1;
            $propertyNumber[$key] = $total;
            $number *= $total;
        }
        return ['total' => $number, 'propertyIndexData' => $propertyNumber];
    }

    private function getAllSpecificationPropertyIdWithIndex($specifications)
    {
        $productSpecificationPropertyIds = [];
        foreach ($specifications as $index => $sourceSpecifications) {
            if (!empty($sourceSpecifications['properties'])) {
                foreach ($sourceSpecifications['properties'] as $key => $sourceSpecification) {
                    $productSpecificationPropertyIds[$index][$key] = $sourceSpecification['id'];
                }
            }
        }
        return $productSpecificationPropertyIds;
    }

    private function clearReservationGoodsPrice($productId)
    {
        ReservationGoods::updateAll(
            ['reservationShelf.price' => [], 'reservationShelf.status' => [], 'price' => 0],
            ['productId' => $productId]
        );
    }
}
