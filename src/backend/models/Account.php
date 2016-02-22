<?php
namespace backend\models;

use backend\models\SensitiveOperation;
use backend\models\MessageTemplate;
use backend\components\BaseModel;
use backend\exceptions\InvalidParameterException;
use backend\behaviors\ChannelBehavior;
use backend\utils\StringUtil;
use backend\utils\MongodbUtil;
use yii\web\ServerErrorHttpException;
use Yii;

/**
 * Model class for account.
 *
 * The followings are the available columns in collection 'account':
 * @property MongoId $_id
 * @property string $priceType
 * @property Array $enabledMods, the enabled modules.
 * @property Array $channels
 * @property Array $tags
 * @property string $company
 * @property string $helpdeskPhone
 * @property string $phone
 * @property string $name
 * @property string $menus
 * @property string $mods
 * @property string $status
 * @property string $accessKey
 * @property string $secretKey
 * @property MongoDate $serviceStartAt
 * @property MongoDate $keyCreatedAt
 * @property array  $syncWechat
 * @property boolean $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @author Devin.Jin
 **/
class Account extends BaseModel
{
    /**
     * Constants for priceType
     **/
    const PRICE_TYPE_FREE = "free";

    //const for WeConnect channel
    const WECONNECT_CHANNEL_WEIXIN = 'WEIXIN';
    const WECONNECT_CHANNEL_WEIBO = 'WEIBO';
    const WECONNECT_CHANNEL_ALIPAY = 'ALIPAY';

    const STATUS_INITIAL = 'initial';
    const STATUS_ACTIVATED = 'activated';

    /**
     * Declares the name of the Mongo collection associated with user.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'account';
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
            [
                'priceType', 'enabledMods', 'availableExtMods',
                'channels', 'tags', 'company', 'phone', 'name',
                'status', 'syncWechat', 'accessKey', 'secretKey',
                'serviceStartAt', 'keyCreatedAt', 'menus', 'mods',
                'helpdeskPhone'
            ]
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['register'] = ['priceType', 'enabledMods', 'availableExtMods', 'tags'];
        return $scenarios;
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'priceType', 'enabledMods', 'availableExtMods',
                'channels', 'tags', 'company', 'phone', 'name', 'helpdeskPhone'
            ]
        );
    }

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
                ['priceType', 'default', 'value' => self::PRICE_TYPE_FREE],
                ['enabledMods', 'default', 'value' => Yii::$app->params['coreMods']],
                ['availableExtMods', 'default', 'value' => []],
                [['tags', 'syncWechat'], 'default', 'value' => []],
                [['menus', 'mods'], 'default', 'value' => []],
                ['status', 'default', 'value' => self::STATUS_INITIAL],
                ['phone', 'registUnique'],
                ['company', 'registUnique']
            ]
        );
    }

    public function registUnique($attribute)
    {
        $condition = [$attribute => $this->$attribute];
        $model = self::findOne($condition);

        if (!empty($model) && ($model->_id . '' !== $this->_id . '')) {
            throw new InvalidParameterException([$attribute => \Yii::t('common', 'unique_feild_' . $attribute)]);
        }
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['priceType', 'enabledMods', 'availableExtMods', 'tags', 'company', 'phone', 'name', 'helpdeskPhone']
        );
    }

    /**
     * Create a new account and initialize it
     * @param string $company
     * @param string $mobile
     * @param string $priceType
     * @throws ServerErrorHttpException
     * @return MongoId
     */
    public static function create($company, $phone, $name, $priceType = self::PRICE_TYPE_FREE)
    {
        $account = new Account;

        $account->priceType = $priceType;
        $account->company = $company;
        $account->phone = $phone;
        $account->name = $name;
        // add 4 basic mods into extensions #3191, extensions changed in #4107
        $account->availableExtMods = [];
        $account->generateKey();

        $enabledMods = Yii::$app->params['coreMods'];
        $result = Yii::$app->extModule->getMergedConfig($enabledMods);
        $account->menus = $result['menus'];
        $account->mods = $result['mods'];

        if ($account->save()) {
            self::afterCreateAccount($account);
            return $account->_id;
        }

        throw new ServerErrorHttpException("Save account failed");
    }

    /**
     * do something after creating account
     * @param  Account $account
     */
    public static function afterCreateAccount($account)
    {
        $options = Yii::$app->params['sensitive_options'];
        foreach ($options as $name => $options) {
            SensitiveOperation::initOptions($name, $options, $account->_id);
        }

        //init account data
        Yii::$app->job->create('backend\modules\management\job\InitNewAccount', ['account' => serialize($account)]);
    }

    /**
     * Get account by phone
     * @param string $phone
     */
    public static function getByPhone($phone)
    {
        return self::findOne(['phone' => $phone]);
    }

    /**
     * Generate account access key and sercet key
     */
    public function generateKey()
    {
        $this->accessKey = StringUtil::rndString(10);
        $this->secretKey = StringUtil::rndString(40);
        $this->keyCreatedAt = new \MongoDate();
    }

    /**
     * Get account access key and sercet key
     */
    public function getKey()
    {
        return [
            'accessKey' => $this->accessKey,
            'secretKey' => $this->secretKey,
            'keyCreatedAt' => MongodbUtil::MongoDate2TimeStamp($this->keyCreatedAt)
        ];
    }

    public static function getAllTags($accountId)
    {
        $tags = [];
        $tagAll = self::findAll(['_id' => $accountId]);
        if (!empty($tagAll)) {
            foreach ($tagAll as $tag) {
                foreach ($tag['tags'] as $item) {
                    $tags[] = $item['name'];
                }
            }
        }
        return $tags;
    }

    /**
     * @return array account id list
     */
    public static function getActivatedAccountIdList()
    {
        $accounts = Account::findAll(['status' => Account::STATUS_ACTIVATED]);
        $datas = [];
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $datas[] = (string)$account->_id;
            }
        }
        return $datas;
    }
}
