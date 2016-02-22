<?php
namespace backend\modules\product\models;

use backend\components\BaseModel;

/**
 * Model class for productInfo.
 * The followings are the available columns in collection 'productInfo':
 * @property MongoId    $_id
 * @property string     $intro
 * @property Date       $updatedAt
 * @property Date       $createdAt
 * @property boolean    $isDeleted
 **/
class ProductInfo extends BaseModel
{
    /**
    * Declares the name of the Mongo collection associated with Product.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'productInfo';
    }

    /**
    * @return array list of attribute names.
    */
    public function attributes()
    {
        return array_merge(
        parent::attributes(),
            ['intro']
        );
    }

    public function safeAttributes()
    {
        return ['_id', 'intro'];
    }
    public function fields()
    {
        return ['intro'];
    }
}
