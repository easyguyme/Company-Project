<?php
namespace backend\models;

use backend\components\BaseModel;
use backend\components\Query;

/**
 * This is the sensitiveOperation model
 *
 * The followings are the available columns in collection 'sensitiveOperation':
 * @property MongoId    $_id
 * @property string     $name
 * @property array      $users  [userId]
 * @property array      $actions  [actionName]
 * @property boolean    $isActivated
 * @property MongoId    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @author Harry Sun
 **/
class SensitiveOperation extends BaseModel
{
    /**
     * Declares the name of the Mongo collection associated with sensitiveOperation.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'sensitiveOperation';
    }

    /**
     * Returns the list of all attribute names of sensitiveOperation.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'users', 'states', 'actions', 'isActivated', 'accountId']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['isActivated']
        );
    }

    /**
     * Returns the list of all rules of applications.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                // the name and privateKey attributes are required
                [['name'], 'required'],
                ['isActivated', 'default', 'value' => false]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name', 'states', 'actions', 'isActivated',
                'users' => function() {
                    $users = [];
                    if (!empty($this->users)) {
                        $query = new Query;
                        // compose the query
                        $query->select(['name'])
                            ->from('user')
                            ->where(['in', '_id', $this->users]);
                        // execute the query
                        $data = $query->all();
                        foreach ($data as $user) {
                            $users[] = $user['name'];
                        }
                    }
                    return $users;
                }
            ]
        );
    }

    /**
     * Get user forbidden states in frontend
     * @param  MongoId $userId
     * @param  MongoId $accountId
     */
    public static function getForbiddenStates($userId, $accountId)
    {
        $condition = ['accountId' => $accountId, 'users' => $userId, 'isActivated' => true];
        $sensitives = static::find()->select(['_id'])->where($condition)->all();
        $sensitiveIds = [];
        foreach ($sensitives as $option) {
            array_push($sensitiveIds, $option->_id);
        }

        $forbiddenCondition = ['accountId' => $accountId, '_id' => ['$nin' => $sensitiveIds], 'isActivated' => true];
        $forbiddenSensitives = static::findAll($forbiddenCondition);
        $states = [];
        foreach ($forbiddenSensitives as $option) {
            $states = array_merge($states, (array) $option->states);
        }

        return $states;
    }

    /**
     * Init account sensitive options
     * @param  string  $name
     * @param  array   $options
     * ~~~
     * [
     *     'states' => [
     *         'product-edit-product',
     *         'product-edit-product-{id}'
     *     ],
     *     'actions' => [
     *         'product/product/create',
     *         'product/product/update',
     *         'product/product/delete'
     *     ]
     * ]
     * ~~~
     * @param  MongoId $accountId
     * @return boolean
     */
    public static function initOptions($name, $options, $accountId)
    {
        $sensitiveOperation = self::findOne(['name' => $name, 'accountId' => $accountId]);

        if (empty($sensitiveOperation)) {
            $sensitiveOperation = new self();
            $sensitiveOperation->name = $name;
            $sensitiveOperation->users = [];
            $sensitiveOperation->isActivated = false;
            $sensitiveOperation->accountId = $accountId;
        }

        $sensitiveOperation->states = $options['states'];
        $sensitiveOperation->actions = $options['actions'];
        return $sensitiveOperation->save();
    }

    /**
     * Find instance by name
     * @param  string $name      operation name
     * @param  string $accountId account UUID
     * @return array operation instances
     */
    public static function findByName($name, $accountId)
    {
        return self::findByCondition([
            'name' => $name,
            'accountId' => $accountId
        ], false);
    }
}
