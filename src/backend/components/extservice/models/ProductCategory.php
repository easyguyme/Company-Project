<?php
namespace backend\components\extservice\models;

use backend\modules\product\models\ProductCategory as ModelProductCategory;
use backend\utils\StringUtil;

class ProductCategory extends BaseComponent
{
    /**
     * create a default category for reservation
     * @return array
     * @param $name, string
     * @param $propertyName, string
     */
    public function createDefaultReservationCategory($name, $propertyName = 'price')
    {
        $condition = [
            'name' => $name,
            'accountId' => $this->accountId,
            'type' => ModelProductCategory::RESERVATION,
        ];
        $category = ModelProductCategory::findOne($condition);

        if (empty($category)) {
            $category = new ModelProductCategory();
            $data = [
                'name' => $name,
                'type' => ModelProductCategory::RESERVATION,
                'accountId' => $this->accountId,
                'properties' => [
                    [
                        'name' => $propertyName,
                        'order' => 1,
                        'propertyId' => 'wm' . $propertyName,
                        'id' => StringUtil::uuid(),
                    ]
                ],
            ];
            $category->load($data, '');
            $category->save();
        }
        return $category;
    }

    /**
     * get default reservation category id
     * @return string
     */
    public function getDefaultReservationCategoryId()
    {
        $condition = [
            'accountId' => $this->accountId,
            'type' => ModelProductCategory::RESERVATION,
        ];
        $categorys = ModelProductCategory::findAll($condition);
        $categoryId = '';
        if (!empty($categorys)) {
            foreach ($categorys as $category) {
                $categoryId .= (string)$category->_id . ',';
            }
        }
        return rtrim($categoryId, ',');
    }
}
