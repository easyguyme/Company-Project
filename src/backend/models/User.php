<?php
namespace backend\models;

use yii\mongodb\Query;
use backend\components\BaseModel;
use backend\models\Account;
use yii\web\BadRequestHttpException;
use backend\behaviors\UserBehavior;

/**
 * This is the user model class for aug-marketing
 *
 * The followings are the available columns in collection 'user':
 * @property MongoId $_id
 * @property string $name
 * @property string $email
 * @property string $position
 * @property string $password
 * @property string $salt
 * @property string $role The user role, it is one of' guest', 'operator', 'billing Account'
 * @property string $avatar
 * @property string $language
 * @property string $accountId
 * @property string $consultManager
 * @property MongoId $accountId
 * @property boolean $isActivated
 * @property boolean $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @author Harry Sun
 **/
class User extends BaseModel
{
    /**
     * Define the role level
     */
    const ROLE_GUEST = 'guest';
    const ROLE_CUSTOMER_SERVICE = 'customer_service';
    const ROLE_OPERATOR = 'operator';
    const ROLE_ADMIN = 'admin';
    const ROLE_MOBILE_ENDUSER = 'mobile_end_user';
    const ROLE_WECONNECT = 'weconnect';

    /**
     * Define user active status
     */
    const ACTIVATED = true;
    const NOT_ACTIVATED = false;

    /**
     * Declares the name of the Mongo collection associated with user.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'user';
    }

    /**
     * Returns the list of all attribute names of user.
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
            ['name', 'email', 'position', 'password', 'salt', 'role', 'nickname', 'avatar', 'language', 'isActivated', 'userList', 'consultManager', 'accountId']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'email', 'position', 'password', 'salt', 'language', 'avatar', 'role']
        );
    }

    /*public function scenarios()
    {
        return [
            'add' => ['email', 'role'],
            'register' => ['username', 'email', 'password'],
        ];
    }*/

    /**
     * Returns the list of all rules of user.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                // the name, email, password and salt attributes are required
                //[['name', 'email', 'password', 'salt'], 'required'],
                ['email', 'required'],
                // the email attribute should be a valid email address
                ['email', 'email'],
                ['email', 'validateEmail'],
                ['name', 'validateUnique'],
                ['accountId', 'required']
            ]
        );
    }

    public function validateEmail($attribute)
    {
        if ($attribute !== 'email') {
            return true;
        }

        $condition = ['email' => $this->$attribute];
        $model = self::findOne($condition);

        if (!empty($model) && ($model->_id . '' !== $this->_id . '')) {
            $this->addError($attribute, $this->$attribute . " has been used.");
        }
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['name', 'email', 'role', 'nickname', 'avatar', 'language', 'isActivated', 'userList']
        );
    }

    public function validatePassword($password)
    {
        $password = self::encryptPassword($password, $this->salt);
        return $password == $this->password;
    }

    /**
     * Encrypt the password when save user.
     *
     * Using md5 to encrypt the password when save user.
     *
     * @param string $password The origin password that user input
     * @param string $salt The generate salt
     * @author Devin Jin
     * @return string The password that after md5
     */
    public static function encryptPassword($password, $salt = 'abcdef')
    {
        return md5(md5(trim($password)) . $salt);
    }

    /**
     * Get the count of the channels that the user(must be an admin)
     * @param $userId, MongoId.
     * @return int, the count of the channels that the users has binded.
     **/
    public static function getChannelCount($userId)
    {
        $user = User::findOne(['_id' => $userId]);
        $count = 0;

        if (empty($user)) {
            throw new BadRequestHttpException('No such user');
        }

        if (empty($user['account'])) {
            throw new BadRequestHttpException('Not an admin account');
        }

        if (!empty($user['account']['channel']['wechat'])) {
            $count += count($user['account']['channel']['wechat']);
        }

        if (!empty($user['account']['channel']['weibo'])) {
            $count += count($user['account']['channel']['weibo']);
        }

        return $count;
    }

    /**
     * Find user by email
     * @param string $email
     * @return array user info
     */
    public static function getByEmail($email)
    {
        return User::findOne(['email' => $email]);
    }

    /**
     * get user list by accountId
     * @param MongoId $accountId
     * @return array
     **/
    public static function getByAccount($accountId)
    {
        return User::findAll(['accountId' => $accountId]);
    }

    /**
     * get user by name
     * @param String $name
     * @return array
     **/
    public static function getByName($accountId, $name)
    {
        return User::findOne(['accountId' => $accountId, 'name' => $name]);
    }

    /**
     * Update creator when update user name
     * @see \yii\db\BaseActiveRecord::afterSave($insert, $changedAttributes)
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!$insert && array_key_exists('name', $changedAttributes)) {
            $this->attachBehavior('UserBehavior', new UserBehavior);
            $this->updateCreator($this->_id, $this->name);
        }
    }
}
