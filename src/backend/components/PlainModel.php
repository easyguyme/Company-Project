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
 * @author Harry Sun
 *
 **/
class PlainModel extends ActiveRecord
{
    const WECHAT = 'wechat';
    const WEIBO = 'weibo';
    const ALIPAY = 'alipay';
    const PORTAL = 'portal';
    const APP_ANDROID = 'app:android';
    const APP_IOS = 'app:ios';
    const APP_WEB = 'app:web';
    const APP_WEBVIEW = 'app:webview';
    const OTHERS = 'others';

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
        return ['_id', 'createdAt', 'accountId'];
    }

    public function safeAttributes()
    {
        return ['createdAt'];
    }

    public function fields()
    {
        return [
            'id' => function ($model) {
                return $model->_id . '';
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

        return $one ? $query->andWhere($condition)->one() : $query->andWhere($condition)->all();
    }

    public function insert($runValidation = true, $attributeNames = null)
    {
        $this->createdAt = new \MongoDate();
        return parent::insert($runValidation, $attributeNames);
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
        return self::getCollection()->distinct($column, $condition);
    }

    public static function count($condition)
    {
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
    public static function batchInsert(&$rows, $options = [])
    {
        if (!empty($rows)) {
            foreach ($rows as &$row) {
                if (!array_key_exists('createdAt', $row)) {
                    $row['createdAt'] = new \MongoDate();
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
