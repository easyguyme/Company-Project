<?php
namespace backend\modules\product\models;

use Yii;
use backend\components\BaseModel;
use backend\utils\MongodbUtil;
use yii\web\BadRequestHttpException;

/**
 * Model class for ReceiveAddress.
 * The followings are the available columns in collection 'receiveAddress':
 * @property MongoId      $_id
 * @property string       $address
 * @property Object       $location:{province,city,district,detail}
 * @property string       $phone
 * @property boolean      $isDeleted
 * @property MongoDate    $createdAt
 * @property MongoDate    $updatedAt
 * @property MongoId      $accountId
 **/
class ReceiveAddress extends BaseModel
{

    /**
    * Declares the name of the Mongo collection associated with ReceiveAddress.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'receiveAddress';
    }

    /**
    * Returns the list of all attribute names of ReceiveAddress.
    * This method must be overridden by child classes to define available attributes.
    * The parent's attributes function is:
    *
    * ```php
    * public function attributes()
    * {
    *     return ['_id', 'createdAt', 'updatedAt', 'isDeleted'];
    * }
    * ```
    *
    * @return array list of attribute names.
    */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['address', 'location', 'phone']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['address', 'location', 'phone']
        );
    }

    /**
    * Returns the list of all rules of ReceiveAddress.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['address', 'location', 'phone'], 'required']
            ]
        );
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into ReceiveAddress.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'address', 'location', 'phone',
            ]
        );
    }
}
