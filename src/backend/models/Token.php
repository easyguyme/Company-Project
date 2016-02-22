<?php
namespace backend\models;

use backend\components\BaseModel;
use backend\utils\StringUtil;
use Yii;
use backend\utils\LogUtil;
use yii\web\ServerErrorHttpException;

/**
 * This is the token model class for aug-marketing.
 * It is used to validate the user's authority.
 * The followings are the available columns in collection 'token':
 * @property MongoId $id
 * @property string $accessToken
 * @property MongoDate $expireTime
 * @property MongoId $userId
 * @property MongoId $accountId
 * @property string $role
 * @property string $avatar
 * @property string $email
 * @property array $enabledMods The the list of enabled modules' name.
 * @property boolean $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property boolen $isOnline The identifier of user's WeChatCP helpdesk App is opened or not.
 * @author Harry Sun
 **/
class Token extends BaseModel
{
    const EXPIRE_TIME = 21600;//6 hours = 6 * 60 * 60
    const DEFAULT_LANGUAGE = 'zh_cn';

    private $defaultLanguage = 'zh_cn';

    /**
     * Declare a token cache for a request
     * @var backend\models\Token
     */
    private static $_token;
    /**
     * Declares the name of the Mongo collection associated with token.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'token';
    }

    /**
     * Returns the list of all attribute names of token.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(parent::attributes(), ['accessToken', 'expireTime', 'userId', 'accountId', 'role', 'language', 'enabledMods', 'isOnline']);
    }

    /**
     * Returns the list of all safeattribute names of token.
     * @return array list of attribute names.
     */
    public function safeAttributes()
    {
        return array_merge(parent::safeAttributes(), ['accessToken', 'expireTime', 'userId', 'accountId', 'role', 'language', 'enabledMods', 'isOnline']);
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(parent::fields(), ['accessToken', 'expireTime', 'userId', 'accountId', 'isOnline']);
    }

    /**
     * Create a new Token
     * @param Object $user, instance of model User
     * @author Devin Jin
     *
     */
    public static function create($user)
    {
        $account = Account::findOne(['_id' => $user->accountId]);

        if (!empty($account)) {
            $token = new Token;
            $token->accessToken = StringUtil::uuid();
            $token->expireTime = new \MongoDate(time() + self::EXPIRE_TIME);
            $token->userId = $user['_id'];
            $token->accountId = $account['_id'];
            $token->language = $user['language'];
            $token->enabledMods = empty($account['enabledMods']) ? [] : $account['enabledMods'];
            $token->role = $user['role'];

            if ($token->save()) {
                LogUtil::info(['message' => 'create new token for wechat', 'accessToken' => $token->accessToken]);
                return $token;
            }
        }

        return false;
    }

    /**
     * Create a new Token through helpdesk
     * @param Object $helpdesk, instance of model HelpDesk
     * @param boolean $isWechatCP, is it login from wechat CP app or not
     * @author Harry Sun
     */
    public static function createByHelpDesk($helpdesk, $isWechatCP = false)
    {
        if (!empty($helpdesk)) {
            $token = new Token;
            $token->accessToken = StringUtil::uuid();
            $token->expireTime = new \MongoDate(time() + self::EXPIRE_TIME);
            $token->userId = $helpdesk['_id'];
            $token->accountId = $helpdesk['accountId'];
            $token->language = $helpdesk['language'];
            if ($isWechatCP) {
                $token->isOnline = true;
            }

            if ($token->save()) {
                return $token;
            }
        }

        return false;
    }

    /**
     * Create access token for mobile end users
     * @param  MongoId $accountId
     * @param  array $options the optional parameters.
     *         now "language", "expire" is supported.
     *         e.g. ['language' => 'zh_cn']
     *         options['expire'] is in seconds.
     * @return object the token object
     * @author Devin Jin
     */
    public static function createForMobile($accountId, $options = [])
    {
        $account = Account::findByPk($accountId);

        if (empty($account)) {
            throw new ServerErrorHttpException("Illegal accountId");
        }

        $expire = isset($options['expire']) ? $options['expire'] : self::EXPIRE_TIME;

        $token = new Token;
        $token->accessToken = StringUtil::uuid();
        $token->expireTime = new \MongoDate(time() + $expire);
        $token->userId ='';
        $token->accountId = $account['_id'];
        $token->language = isset($options['language']) ? $options['language'] : self::DEFAULT_LANGUAGE;
        $token->enabledMods = empty($account['enabledMods']) ? [] : $account['enabledMods'];
        $token->role = User::ROLE_MOBILE_ENDUSER;

        if ($token->save()) {
            return $token;
        }

        throw new Exception("Faile to create token for database problems");
    }

    /**
     * Update language
     * @param string $lauguage
     * @param string $token
     * @return
     */
    public static function channgeLanguage($token, $lauguage)
    {
        return Token::updateAll(['language' => $lauguage], ['accessToken' => $token]);
    }

    public static function createForWechat($accountId)
    {
        $account = Account::findByPk($accountId);

        if (empty($account)) {
            throw new Exception("Illegal accountId");
        }

        $expire = 3600 * 24 * 10000; //never expired

        $token = new Token;
        $token->accessToken = StringUtil::uuid();
        $token->expireTime = new \MongoDate(time() + $expire);
        $token->userId ='';
        $token->accountId = $account['_id'];
        $token->language = self::DEFAULT_LANGUAGE;
        $token->enabledMods = ['chat'];
        $token->role = User::ROLE_WECONNECT;

        if ($token->save()) {
            return $token;
        }

        throw new Exception("Faile to create token for database problems");
    }

    /**
     * According access token get logined user account id
     * @param  string $accessToken, access token
     * @return string user account id
     */
    public static function getAccountId($accessToken = null)
    {
        $token = self::getToken($accessToken);
        return $token['accountId'];
    }

    /**
     * According access token to get token model
     * @param  string $accessToken, access token
     * @return backend\models\Token
     * @author Harry Sun
     */
    public static function getToken($accessToken = null)
    {
        if (!empty($accessToken) && (empty(self::$_token) || $accessToken !== self::$_token['accessToken'])) {
            self::$_token = Token::findOne(['accessToken' => $accessToken]);
        }

        return self::$_token;
    }

    public static function getByAccesstoken($accesstoken)
    {
        return Token::findOne(['accessToken' => $accesstoken]);
    }

    public static function getUnexpiredByUserId($userId)
    {
        return self::findAll(['userId' => $userId, 'expireTime' => ['$gt' => new \MongoDate()]]);
    }

    /**
     * Get the lastest token under the specified userId.
     * @param ObjectId, $userId
     * @return Token
     */
    public static function getLastestByUserId($userId)
    {
        $where = ['userId' => $userId, 'expireTime' => ['$gt' => new \MongoDate()]];
        return self::find()->where($where)->orderBy(['createdAt' => SORT_DESC])->one();
    }
}
