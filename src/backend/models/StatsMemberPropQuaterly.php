<?php

namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberPropQuaterly.
 *
 * The followings are the available columns in collection 'statsMemberPropQuaterly':
 * @property MongoId $_id
 * @property String $propName
 * @property String $propValue
 * @property int $total
 * @property String $year
 * @property String $quater
 * @property ObjectId $accountId
 *
 **/

class StatsMemberPropQuaterly extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsMemberPropQuaterly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberPropQuaterly';
    }

    /**
     * Returns the list of all attribute names of statsMemberPropQuaterly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['propName', 'propValue', 'total', 'year', 'quater']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['propName', 'propValue', 'total', 'year', 'quater']
        );
    }

    /**
     * Returns the list of all rules of statsMemberPropQuaterly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['propName', 'propValue', 'total', 'year', 'quater'], 'required'],
                ['total', 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberPropQuaterly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'propName', 'propValue', 'total', 'year', 'quater'
            ]
        );
    }
}
