<?php
namespace backend\models;

use yii\mongodb\Query;
use backend\components\BaseModel;
use backend\models\Account;
use yii\web\BadRequestHttpException;

/**
 * This is the admin model class for aug-marketing
 *
 * The followings are the available columns in collection 'user':
 * @property MongoId    $_id
 * @property string     $name
 * @property string     $email
 * @property string     $password
 * @property string     $salt
 * @property string     $avatar
 * @property string     $language
 * @property array      $accounts
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @author Harry Sun
 **/
class Admin extends BaseModel
{
    /**
     * Declares the name of the Mongo collection associated with user.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'admin';
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
            ['name', 'email', 'password', 'salt', 'avatar', 'language', 'accounts']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'email', 'password', 'salt', 'language', 'avatar', 'accounts']
        );
    }

    /**
     * Returns the list of all rules of admin.
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
                // the email attributes are validated by validateUnique()
                ['email', 'validateUnique'],
                ['name', 'validateUnique']
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['name', 'email', 'avatar', 'language', 'accounts']
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
     * Find helpDesk by email
     * @param string, $email
     * @return Array, HelpDesk info
     */
    public static function getByEmail($email)
    {
        return Admin::findOne(['email' => $email]);
    }
}
