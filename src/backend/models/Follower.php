<?php
namespace backend\models;

use Yii;
use MongoDate;
use MongoId;
use backend\components\PlainModel;
use backend\modules\member\models\MemberProperty;
use backend\modules\member\models\Member;
use yii\helpers\ArrayHelper;
use backend\exceptions\InvalidParameterException;

/**
 * Model class for staff.
 * The followings are the available columns in collection 'follower':
 * @property MongoId $_id
 * @property string $channelId
 * @property string $openId
 * @property string $properties
 * @property MongoDate $updatedAt
 * @property MongoId $accountId
 **/

class Follower extends PlainModel
{
    /**
    * Declares the name of the Mongo collection associated with follower.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'follower';
    }

    /**
    * Returns the list of all attribute names of follower.
    * This method must be overridden by child classes to define available attributes.
    * @return array list of attribute names.
    */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['avatar', 'channelId', 'openId', 'phone', 'properties']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['avatar', 'channelId', 'openId', 'phone', 'properties']
        );
    }

    /**
    * Returns the list of all rules of follower.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['properties', 'default', 'value' => []],
                ['properties', 'ensureProperties']
            ]
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'avatar', 'channelId', 'openId', 'phone',
                'properties' => function () {
                    $properties = $this->properties;
                    foreach ($properties as &$property) {
                        $property['id'] = (string) $property['id'];
                    }
                    return $properties;
                }
            ]
        );
    }

    /**
     * PHP getter magic method.
     * This method is overridden so that attributes and related objects can be accessed like properties.
     *
     * @param string $name property name
     * @throws \yii\base\InvalidParamException if relation name is wrong
     * @return mixed property value
     * @see getAttribute()
     */
    public function __get($name)
    {
        $value = parent::__get($name);
        //append property 'tel' into follower properties
        if ($name === 'properties') {
            $propertyNames = ArrayHelper::getColumn($value, 'name');
            if (!in_array(Member::DEFAULT_PROPERTIES_MOBILE, $propertyNames)) {
                $propertyMobile = MemberProperty::getDefaultByName($this->accountId, Member::DEFAULT_PROPERTIES_MOBILE);
                if (!empty($propertyMobile)) {
                    $value[] = [
                        'id' => $propertyMobile->_id,
                        'name' => Member::DEFAULT_PROPERTIES_MOBILE,
                        'value' => $this->phone
                    ];
                }
            }
        }
        return $value;
    }

    public function ensureProperties($attribute)
    {
        //only validate the field "properties"
        if ($attribute !== 'properties') {
            return true;
        }

        $properties = $this->$attribute;
        if (!is_array($properties)) {
            throw new InvalidParameterException(Yii::t('common', 'data_error'));
        }

        $followerProperties = [];
        //validate each field in properties
        foreach ($properties as $property) {
            //validate the required fields in properties
            if (empty($property['id']) || empty($property['name'])) {
                throw new InvalidParameterException(Yii::t('common', 'data_error'));
            }

            // formate property id string to mongoId
            if (!empty($property['id']) && $property['name'] !== Member::DEFAULT_PROPERTIES_MOBILE) {
                $property['id'] = new MongoId($property['id']);
                $followerProperties[] = $property;
            }

            if ($property['name'] == Member::DEFAULT_PROPERTIES_MOBILE) {
                $this->phone = $property['value'];
            }
        }

        $this->$attribute = $followerProperties;
    }

    /**
     * Follower create
     * @param MongoId $accountId
     * @param array $follower, weconnect user
     * @return number
     */
    public static function upsert($accountId, $follower)
    {
        $channelId = $follower['accountId'];
        $openId = $follower['originId'];

        $propertiesInfo = self::formatPropertis($accountId, $follower);
        $properties = $propertiesInfo['properties'];
        $attributes = [
            'avatar' => !empty($follower['headerImgUrl']) ? $follower['headerImgUrl'] : Yii::$app->params['defaultAvatar'],
            'channelId' => $channelId,
            'openId' => $openId,
            'properties' => $properties,
            'accountId' => $accountId,
        ];
        if ($propertiesInfo['isNew']) {
            $attributes['createdAt'] = new MongoDate();
        }
        $condition = ['accountId' => $accountId, 'channelId' => $channelId, 'openId' => $openId];
        return self::updateAll($attributes, $condition, ['upsert' => true]);
    }

    /**
     * Method to format follower properties
     * @param MongoId $accountId
     * @param array $follower, weconnect user
     * @param string $phone, if $phone not empty, replace property phone with $phone
     * @return array
     */
    public static function formatPropertis($accountId, $follower)
    {
        $channelId = $follower['accountId'];
        $openId = $follower['originId'];
        $nickname = $follower['nickname'];
        $gender = $follower['gender'];

        $follower = self::findOne(['accountId' => $accountId, 'channelId' => $channelId, 'openId' => $openId]);
        $isNew = empty($follower);
        $followerProperties = empty($follower['properties']) ? [] : $follower['properties'];
        $mapProperties = [];
        foreach ($followerProperties as $followerProperty) {
            $mapProperties[(string) $followerProperty['id']] = $followerProperty;
        }

        $properties = MemberProperty::getByAccount($accountId, true);
        foreach ($properties as $property) {
            $propertyIdStr = (string) $property->_id;
            if (empty($mapProperties[$propertyIdStr])) {
                if ($property->name === Member::DEFAULT_PROPERTIES_NAME) {
                    $mapProperties[$propertyIdStr] = [
                        'id' => $property->_id,
                        'name' => $property->name,
                        'value' => $nickname
                    ];
                }
                if ($property->name === Member::DEFAULT_PROPERTIES_GENDER) {
                    $mapProperties[$propertyIdStr] = [
                        'id' => $property->_id,
                        'name' => $property->name,
                        'value' => strtolower($gender)
                    ];
                }
            }
            //remove prperty tel from properties
            if ($property->name === Member::DEFAULT_PROPERTIES_MOBILE) {
                unset($mapProperties[$propertyIdStr]);
            }
        }

        return ['properties' => array_values($mapProperties), 'isNew' => $isNew];
    }

    public static function getByOpenId($accountId, $openId)
    {
        return self::findOne(['accountId' => $accountId, 'openId' => $openId]);
    }

    public static function removeByOpenId($accountId, $openId)
    {
        return self::deleteAll(['accountId' => $accountId, 'openId' => $openId]);
    }
}
