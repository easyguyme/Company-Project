<?php
namespace backend\modules\member\models;

use backend\components\BaseModel;
use backend\exceptions\InvalidParameterException;
use backend\utils\StringUtil;
use yii\web\BadRequestHttpException;
use mongoId;
use backend\modules\member\models\Member;
use backend\models\Follower;

/**
 * Model class for MemberProperty.
 *
 * The followings are the available columns in collection 'memberShipCard':
 * @property MongoId    $_id
 * @property int        $order
 * @property string     $name
 * @property string     $propertyId
 * @property string     $type
 * @property string     $options
 * @property mixed      $defaultValue
 * @property boolean    $isRequired
 * @property boolean    $isUnique
 * @property boolean    $isVisible
 * @property boolean    $isDefault
 * @proeerty boolean    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 **/
class MemberProperty extends BaseModel
{
    //constants for type
    const TYPE_INPUT = "input";
    const TYPE_TEXTAREA = "textarea";
    const TYPE_DATE = "date";
    const TYPE_RADIO = "radio";
    const TYPE_CHECKBOX = "checkbox";
    const TYPE_EMAIL = "email";

    const PROPERTY_ID_PATTERN = '/^[A-Za-z0-9_]+$/';

    //constants for max count
    const MAX_COUNT = 100;

    /**
     * Declares the name of the Mongo collection associated with Member.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'memberProperty';
    }

    /**
     * Returns the list of all attribute names of member.
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
            ['order', 'name', 'type', 'options', 'defaultValue', 'isRequired', 'isUnique', 'isVisible',
             'isDefault', 'propertyId']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['order', 'name', 'type', 'options', 'defaultValue', 'isRequired', 'isUnique', 'isVisible',
             'isDefault', 'propertyId']
        );
    }

    /**
     * Returns the list of all rules of ChatMessage.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['order', 'name'], 'required'],
                ['order', 'integer'],
                ['type', 'in', 'range' => [
                    self::TYPE_INPUT,
                    self::TYPE_TEXTAREA,
                    self::TYPE_DATE,
                    self::TYPE_RADIO,
                    self::TYPE_CHECKBOX,
                    self::TYPE_EMAIL
                    ]
                ],
                ['isRequired', 'boolean'],
                ['isUnique', 'boolean'],
                ['isVisible', 'boolean'],
                ['isDefault', 'boolean'],
                ['defaultValue', 'default', 'value' => ''],
                ['name', 'validateName'],
                ['propertyId', 'validatePropertyId']
            ]
        );
    }

    public function validateName($attribute)
    {
        //only validate the field "article"
        if ($attribute !== 'name') {
            return true;
        }

        $name = $this->$attribute;
        $existName = ['Name', 'Phone', 'Gender', 'Birthday', 'Email',
                      '姓名', '手机', '性别', '生日', '邮箱'];
        if (in_array($name, $existName) && !$this->isDefault) {
            throw new InvalidParameterException(['memberPropertyName' => \Yii::t('member', 'unique_filed')]);
        }

        $memberProperty = self::getByName($this->accountId, $name);
        if (!empty($memberProperty) && $memberProperty->_id . '' != $this->_id . '') {
            throw new InvalidParameterException(['memberPropertyName' => \Yii::t('member', 'unique_filed')]);
        }
    }

    public function validatePropertyId($attribute)
    {
        if ($attribute !== 'propertyId') {
            return true;
        }

        $propertyId = $this->$attribute;
        if (!preg_match(self::PROPERTY_ID_PATTERN, $propertyId)) {
            throw new InvalidParameterException(['memberPropertyId' => \Yii::t('member', 'format_error')]);
        }

        $propertyIdReg = StringUtil::regStrFormat($propertyId);
        $propertyIdReg = new \MongoRegex("/^$propertyId$/i");
        $memberProperty = self::getByPropertyId($this->accountId, $propertyIdReg);
        if (in_array($propertyId, Member::$defaultProperties) || (!empty($memberProperty) && $memberProperty->_id . '' != $this->_id . '')) {
            throw new InvalidParameterException(['memberPropertyId' => \Yii::t('member', 'unique_filed')]);
        }
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into Member.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['order', 'name', 'options', 'type', 'defaultValue', 'isRequired', 'isUnique', 'isVisible',
                 'isDefault', 'propertyId']
        );
    }

    public static function getByAccount($accountId, $isDefault = null)
    {
        $condition = ['accountId' => $accountId, 'isVisible' => true, 'isDeleted' => self::NOT_DELETED];
        if ($isDefault !== null) {
            $condition['isDefault'] = $isDefault;
        }
        return self::find()->where($condition)->orderBy(['order'=> SORT_ASC])->all();
    }

    public static function getByName($accountId, $name)
    {
        $name = StringUtil::regStrFormat($name);
        $name = new \MongoRegex("/^$name$/i");
        return self::findOne(['accountId' => $accountId, 'name' => $name]);
    }

    public static function getByPropertyId($accountId, $propertyId)
    {
        return self::findOne(['accountId' => $accountId, 'propertyId' => $propertyId]);
    }

    /**
     * get member property id to conver to param for showing data in template
     * @param $accountId, mongoId
     * @param $memberProperties, array, member property,the struct should be fit with properties of member
     */
    public static function getMemberProperty($accountId, $memberProperties)
    {
        $propertyParams = [];

        $properties = self::getByAccount($accountId);
        if (!empty($properties) && !empty($memberProperties)) {
            foreach ($properties as $property) {
                $index = $property->propertyId;
                $propertyValue = '';
                foreach ($memberProperties as $memberProperty) {
                    if ($memberProperty['id'] == $property['_id']) {
                        if (is_array($memberProperty['value'])) {
                            $propertyValue = implode(',', $memberProperty['value']);
                        } else {
                            $propertyValue = $memberProperty['value'];
                        }
                    }
                }
                $propertyParams[$index] = $propertyValue;
            }
        }
        return $propertyParams;
    }

    /**
     * Get all properties by account
     * @param $accountId
     */
    public static function getAllByAccount($accountId)
    {
        return self::findAll(['accountId' => $accountId]);
    }

    /**
     * Validate all properties.
     * @param $params array
     * @param $accountId mongoId
     * @param $flag string
     *
     * @return boolean|array false|[]
     */
    public static function checkProperties($params, $accountId)
    {
        $propertiesValue = '';
        $properties = $params['properties'];
        $openId = $params['openId'];

        if (count($properties) > 0) {
            foreach ($properties as $property) {
                $memberProperty = MemberProperty::findOne(['_id' => new MongoId($property['id']), 'accountId' => $accountId]);
                # Verify required.
                Member::validateRequiredAndUnique($property['value'], $openId, $memberProperty, $accountId);

                switch ($memberProperty->type) {
                    case self::TYPE_EMAIL:
                        if (!empty($property['value']) && StringUtil::isEmail($property['value']) === false) {
                            throw new InvalidParameterException(\Yii::t('member', 'email_format_error'));
                        }
                        $propertiesValue = [
                            'id' => new MongoId($memberProperty->_id),
                            'name' => $property['name'],
                            'value' => empty($property['value']) ? '' : $property['value']
                        ];
                        break;
                    case self::TYPE_INPUT:
                        if (!empty($property['value']) && $property['name'] == 'name') {
                            $valueLen = mb_strlen($property['value'], 'utf-8');
                            if ($valueLen < 2 || $valueLen > 30) {
                                throw new InvalidParameterException(\Yii::t('member', 'name_format_error'));
                            }
                        }

                        if (!empty($property['value']) && $property['name'] == 'tel') {
                            if (StringUtil::isMobile($property['value']) === false) {
                                throw new InvalidParameterException(\Yii::t('member', 'mobile_format_error'));
                            }
                        }
                        $propertiesValue = [
                            'id' => new MongoId($memberProperty->_id),
                            'name' => $property['name'],
                            'value' => empty($property['value']) ? '' : $property['value']
                        ];
                        break;
                    case self::TYPE_DATE:
                        $propertiesValue = [
                            'id' => new MongoId($memberProperty->_id),
                            'name' => $property['name'],
                            'value' => empty($property['value']) ? '' : (int)$property['value']
                        ];
                        break;
                    default:
                        $propertiesValue = [
                            'id' => new MongoId($memberProperty->_id),
                            'name' => $property['name'],
                            'value' => empty($property['value']) ? '' : $property['value']
                        ];
                        break;
                }
            }
        }
        return $propertiesValue;
    }

    public static function getDefaultByName($accountId, $name)
    {
        return self::findOne(['accountId' => $accountId, 'name' => $name, 'isDefault' => true]);
    }

    /**
     * Get properties which the type is radio.
     * @param $accountId mongoId
     *
     * @return array [{'id', 'name', 'value'},...]
     */
    public static function getRadioProperty($accountId)
    {
        $properties = [];
        $condition = [
            'type' => self::TYPE_RADIO,
            'accountId' => $accountId
        ];
        $memberProperty = self::findAll($condition);

        foreach ($memberProperty as $property) {
            $itemProperty = [
                'id' => $property['_id'],
                'name' => $property['name'],
                'value' => empty($property['options'][0]) ? '' : $property['options'][0]
            ];
            $properties[] = $itemProperty;
        }
        return $properties;
    }

    /**
     * merge properties.
     * @param $accountId MongoId
     * @param $dbProperties array
     * @param $properties array
     *
     * @return array [{'id', 'name', 'value'},...]
     */
    public static function mergeProperties($dbProperties, $properties, $accountId)
    {
        $propertyId = [];
        $propertyMap = self::propertyMap($dbProperties);

        // get all the properties.
        $memberProperty = self::findAll(['accountId' => $accountId]);

        foreach ($memberProperty as $item) {
            $propertyId[] = (string)$item['_id'];
        }

        foreach ($properties as $property) {
            if (in_array((string)$property['id'], $propertyId)) {
                $propertyMap[(string)$property['id']] = $property;
            }
        }
        return array_values($propertyMap);
    }

    /**
     * map proprties.
     * @param $properties array
     *
     * @return array
     *  the result is:
     *  [
     *      'id' => [
     *          'id' => '123',
     *          'name' => 'a1',
     *          'value' => 'aa'
     *      ],
     *      'id' => [
     *          'id' => '124',
     *          'name' => 'b1',
     *          'value' => 'bb'
     *      ],
     *      ...
     *  ]
     */
    private static function propertyMap($properties)
    {
        $memberPropertiesMap = [];

        foreach ($properties as $property) {
            $memberPropertiesMap[(string)$property['id']] = $property;
        }
        return $memberPropertiesMap;
    }
}
