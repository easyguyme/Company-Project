<?php
namespace backend\models;

use backend\components\PlainModel;

/**
 * Model class for captcha.
 *
 * The followings are the available columns in collection 'captcha':
 * @property MongoId $_id
 * @property string ip
 * @property string mobile
 * @property string code
 * @property boolean isExpired
 * @property MongoId accountId
 * @property MongoDate $createdAt
 **/
class Captcha extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with captcha.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'captcha';
    }

    /**
     * Returns the list of all attribute names of captcha.
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
            ['ip', 'mobile', 'code', 'isExpired']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['ip', 'mobile', 'code', 'isExpired']
        );
    }

    /**
     * Returns the list of all rules of captcha.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['ip', 'mobile', 'code', 'isExpired'], 'required'],
                ['isExpired', 'default', 'value'=> false]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into captcha.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['ip', 'mobile', 'code', 'isExpired']
        );
    }

    /**
     * Get available captcha by ip
     * @param string $ip
     * @return array captcha info
     */
    public static function getByIP($ip)
    {
        return self::findOne(['ip' => $ip, 'isExpired' => false]);
    }

    /**
     * Get available captcha by mobile
     * @param string $mobile
     * @return array captcha info
     */
    public static function getByMobile($mobile)
    {
        return self::find()->andWhere(['mobile' => $mobile, 'isExpired' => false])->orderBy(['createdAt' => SORT_DESC])->one();
    }
}
