<?php

namespace backend\components;

use yii\mongodb\ActiveRecord;
use yii\helpers\ArrayHelper;
use backend\models\Token;
use backend\utils\StringUtil;
use yii\helpers\Json;
use backend\utils\TimeUtil;

/**
 * This is the base model class for aug-marketing
 * @author Devin Jin
 *
 **/
class BaseModel extends ActiveRecord
{
    const DELETED = true;
    const NOT_DELETED = false;

    const WECHAT = 'wechat';
    const WEIBO = 'weibo';
    const ALIPAY = 'alipay';
    const PORTAL = 'portal';
    const APP_ANDROID = 'app:android';
    const APP_IOS = 'app:ios';
    const APP_WEB = 'app:web';
    const APP_WEBVIEW = 'app:webview';
    const OTHERS = 'others';

    const MAX_COUNT = 1000000000;

    /**
     * The name of the create scenario.
     */
    const SCENARIO_CREATE = 'create';

    /**
     * The name of the update scenario.
     */
    const SCENARIO_UPDATE = 'update';

    public static $origins = [
        self::WECHAT,
        self::WEIBO,
        self::ALIPAY,
        self::PORTAL,
        self::APP_ANDROID,
        self::APP_IOS,
        self::APP_WEB,
        self::APP_WEBVIEW,
        self::OTHERS,
    ];

    /**
     * Returns a list of scenarios and the corresponding active attributes.
     * An active attribute is one that is subject to validation in the current scenario.
     * The returned array should be in the following format:
     *
     * ~~~
     * [
     *     'scenario1' => ['attribute11', 'attribute12', ...],
     *     'scenario2' => ['attribute21', 'attribute22', ...],
     *     ...
     * ]
     * ~~~
     *
     * By default, an active attribute is considered safe and can be massively assigned.
     * If an attribute should NOT be massively assigned (thus considered unsafe),
     * please prefix the attribute with an exclamation character (e.g. '!rank').
     *
     * The default implementation of this method will return all scenarios found in the [[rules()]]
     * declaration. Three special scenarios named [[SCENARIO_DEFAULT]], [[SCENARIO_CREATE]]
     * and [[SCENARIO_UPDATE]] will contain all attributes found in the [[rules()]].
     * Each scenario will be associated with the attributes that
     * are being validated by the validation rules that apply to the scenario.
     *
     * @return array a list of scenarios and the corresponding active attributes.
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_DEFAULT];
        return $scenarios;
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return ['_id', 'createdAt', 'updatedAt', 'isDeleted', 'accountId'];
    }

    public function safeAttributes()
    {
        return ['createdAt', 'updatedAt', 'isDeleted', 'accountId'];
    }

    public function fields()
    {
        return [
            'id' => function ($model) {
                return (string) $model->_id;
            }
        ];
    }

    /**
     * validateUnique validates that the attribute value is unique with isDeleted in the specified database table.
     *
     * The following is an example of validation rules using this validator:
     *
     * ```php
     * // a1 needs to be unique
     * ['a1', 'validateUnique']
     * ```
     * @author Harry Sun
     */
    public function validateUnique($attribute)
    {
        $token = Token::getToken();
        $condition = [$attribute => $this->$attribute];

        if (!empty($token->accountId)) {
            $condition['accountId'] = $token->accountId;
        } else if (!empty($this->accountId)) {
            $condition['accountId'] = $this->accountId;
        }

        $model = self::findOne($condition);

        if (!empty($model) && ($model->_id . '' !== $this->_id . '')) {
            $this->addError($attribute, $this->$attribute . " has been used.");
        }
    }

    /**
     * Finds ActiveRecord instance(s) by the given condition.
     * This method is internally called by [[findOne()]] and [[findAll()]].
     * @param mixed $condition please refer to [[findOne()]] for the explanation of this parameter
     * @param boolean $one whether this method is called by [[findOne()]] or [[findAll()]]
     * @return static|static[]
     * @throws InvalidConfigException if there is no primary key defined
     * @internal
     * @author Harry Sun
     */
    protected static function findByCondition($condition, $one)
    {
        $query = static::find();

        if (!empty($condition) && !ArrayHelper::isAssociative($condition)) {
            // query by primary key
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $condition = [$primaryKey[0] => $condition];
            } else {
                throw new InvalidConfigException(get_called_class() . ' must have a primary key.');
            }
        }

        $condition['isDeleted'] = self::NOT_DELETED;
        return $one ? $query->andWhere($condition)->one() : $query->andWhere($condition)->all();
    }

    /**
     * Updates all documents in the collection using the provided attribute values and conditions.
     * For example, to change the status to be 1 for all customers whose status is 2:
     *
     * ~~~
     * Customer::updateAll(['status' => 1], ['status' => 2]);
     * ~~~
     *
     * @param array $attributes attribute values (name-value pairs) to be saved into the collection
     * @param array $condition description of the objects to update.
     * Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array $options list of options in format: optionName => optionValue.
     * @return integer the number of documents updated.
     */
    public static function updateAll($attributes, $condition = [], $options = [])
    {
        $keys = array_keys($attributes);

        if (!empty($keys) && strncmp('$', $keys[0], 1) !== 0) {
            $attributes = ['$set' => $attributes];
        }

        $attributes['$set']['updatedAt'] = new \MongoDate();
        $condition['isDeleted'] = self::NOT_DELETED;
        return static::getCollection()->update($condition, $attributes, $options);
    }

    public function insert($runValidation = true, $attributeNames = null)
    {
        $this->createdAt = $this->updatedAt = new \MongoDate();
        $this->isDeleted = self::NOT_DELETED;
        return parent::insert($runValidation, $attributeNames);
    }

    public function update($runValidation = true, $attributeNames = null)
    {
        $this->updatedAt = new \MongoDate();
        return parent::update($runValidation, $attributeNames);
    }

    /**
     * Deletes the document corresponding to this active record from the collection.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeDelete()]]. If the method returns false, it will skip the
     *    rest of the steps;
     * 2. delete the document from the collection;
     * 3. call [[afterDelete()]].
     *
     * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
     * will be raised by the corresponding methods.
     *
     * @return integer|boolean the number of documents deleted, or false if the deletion is unsuccessful for some reason.
     * Note that it is possible the number of documents deleted is 0, even though the deletion execution is successful.
     * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
     * being deleted is outdated.
     * @throws \Exception in case delete failed.
     * @author Harry Sun
     */
    public function delete()
    {
        $result = false;
        if ($this->beforeDelete()) {
            if ($this->hasProperty('isDeleted', true, false)) {
                $this->isDeleted = self::DELETED;
                $result = $this->update();
            } else {
                $result = $this->deleteInternal();
            }

            $this->afterDelete();
        }

        return $result;
    }
    /**
     * Deletes documents in the collection using the provided conditions.
     * WARNING: If you do not specify any condition, this method will delete documents rows in the collection.
     *
     * For example, to delete all customers whose status is 3:
     *
     * ~~~
     * Customer::deleteAll(['status' => 3]);
     * ~~~
     *
     * @param array $condition description of the objects to delete.
     * Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array $options list of options in format: optionName => optionValue.
     * @return integer the number of documents deleted.
     * @author Harry Sun
     */
    public static function deleteAll($condition = [], $options = [])
    {
        $attributes = ['isDeleted' => self::DELETED];
        return static::getCollection()->update($condition, $attributes, $options);
    }

    public static function findByPk($id, $condition = [])
    {
        $condition = array_merge(['_id' => $id], $condition);
        return static::findOne($condition);
    }

    public static function deleteByPk($id, $condition = [])
    {
        $condition = array_merge(['_id' => $id], $condition);
        return static::deleteAll($condition);
    }

    /**
     * Transfer the model list to the id list
     * @param  array, $models the list of the models
     * @return array, the id list
     */
    public static function getIdList($models)
    {
        $idList = [];

        foreach ($models as $model) {
            if ($model instanceof self) {
                $idList[] = $model->_id;
            }
        }

        return $idList;
    }

    /**
     * Returns a list of distinct values for the given column across a collection.
     * @param string $column column to use.
     * @param array $condition query parameters.
     * @return array|boolean array of distinct values, or "false" on failure.
     * @throws Exception on failure.
     */
    public static function distinct($column, $condition = [])
    {
        $condition = array_merge($condition, ['isDeleted' => self::NOT_DELETED]);
        return self::getCollection()->distinct($column, $condition);
    }

    public static function count($condition)
    {
        $condition = array_merge($condition, ['isDeleted' => self::NOT_DELETED]);
        return self::find()->where($condition)->count();
    }

    /**
     * Normalize formate orderBy
     * @param array $params
     * $params['orderBy'] can be specified in either a string (e.g. {"id":"asc"}, {"name":"desc"}) or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     * @return array
     * if $params['orderBy'] is empty, return ['createdAt' => SORT_DESC]
     */
    public static function normalizeOrderBy($params)
    {
        if (!empty($params['orderBy'])) {
            $orderBy = $params['orderBy'];
            if (StringUtil::isJson($orderBy)) {
                $orderBy = Json::decode($orderBy, true);

                foreach ($orderBy as $key => $value) {
                    if ($value === 'asc' || $value === 'ASC') {
                        $orderBy[$key] = SORT_ASC;
                    } else {
                        $orderBy[$key] = SORT_DESC;
                    }
                }
            } else {
                $orderBy = [$orderBy => SORT_DESC];
            }
        } else {
            $orderBy = ['createdAt' => SORT_DESC];
        }

        return $orderBy;
    }

    /**
     * validator to transfer string to mongoId
     */
    public function toMongoId($attribute)
    {
        if (empty($this->$attribute)) {
            return;
        }

        $this->$attribute = new \MongoId($this->$attribute);
        return;
    }

    /**
     * Inserts several new rows into collection.
     * @param array $rows array of arrays or objects to be inserted.
     * @param array $options list of options in format: optionName => optionValue.
     * @return boolean
     */
    public static function batchInsert($rows, $options = [])
    {
        if (!empty($rows)) {
            foreach ($rows as &$row) {
                $row['isDeleted'] = false;
                if (!array_key_exists('createdAt', $row)) {
                    $row['createdAt'] = $row['updatedAt'] = new \MongoDate();
                } else {
                    $row['updatedAt'] = $row['createdAt'];
                }
            }
            $result = self::getCollection()->mongoCollection->batchInsert($rows, $options);
            if (!empty($result['ok']) && $result['ok'] == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Validator to transfer timestamp to mongoDate
     * @param unknown $attribute
     */
    public function toMongodate($attribute)
    {
        if (empty($this->$attribute)) {
            return;
        }

        $this->$attribute = new \MongoDate(TimeUtil::ms2sTime($this->$attribute));
        return;
    }

    public function validateSave($result = null)
    {
        if ($this->validate()) {
            // all inputs are valid
            if ($this->save()) {
                return empty($result) ? $this : $result;
            } else {
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        } else {
            // valid fail, return errors
            return $this->errors;
        }
    }
}
