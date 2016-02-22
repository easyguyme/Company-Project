<?php
namespace backend\models;

use backend\components\BaseModel;
use backend\models\Account;
use backend\utils\MongodbUtil;
use yii\mongodb\Query;
use yii\web\BadRequestHttpException;

/**
 * This is the applications model class for aug-marketing
 *
 * The followings are the available columns in collection 'user':
 * @property MongoId    $_id
 * @property string     $name
 * @property string     $privateKey
 * @property string     $content
 * @property MongoId    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @author Harry Sun
 **/
class Applications extends BaseModel
{
    /**
     * The signing algorithm. default algorithm is 'HS256'
     */
    const DEFAULT_ALG = 'HS256';

    /**
     * Declares the name of the Mongo collection associated with applications.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'applications';
    }

    /**
     * Returns the list of all attribute names of applications.
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
            ['name', 'privateKey', 'icon', 'content']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'content', 'icon']
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
                [['name', 'privateKey'], 'required'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into applications.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name', 'privateKey', 'icon', 'content',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2TimeStamp($this->createdAt);
                }
            ]
        );
    }

    /**
     * Generate app private key
     */
    public function generateKey()
    {
        $account = Account::findByPk($this->accountId);
        $token = [
            'uid' => (string)$this->accountId,
            'scopes' => [],
            'app' => (string)$this->_id
        ];
        $this->privateKey = \JWT::encode($token, $account->secretKey, self::DEFAULT_ALG, $account->accessKey);
    }
}
