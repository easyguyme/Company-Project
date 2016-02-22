<?php
namespace backend\modules\microsite\models;

use backend\components\BaseModel;
use backend\utils\MongodbUtil;
use yii\web\ServerErrorHttpException;
use backend\utils\StringUtil;
use backend\components\ActiveDataProvider;
use yii\helpers\Json;
use backend\models\StoreGoods;
use backend\models\User;

/**
 * Model class for Article
 *
 * The followings are the available columns in collection 'ArticleChannel':
 * @property MongoId    $_id
 * @property String     $name
 * @property String     $url
 * @property String     $createdBy
 * @property String     $picUrl
 * @property String     $content
 * @property Array      $fields
 * @property MongoId    $channel
 * @property MongoId    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 */

class Article extends BaseModel
{
    /**
     * Declares the name of the Mongo collection associated with ChatConversation.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'article';
    }

    /**
     * Returns the list of all attribute names of ChatConversation.
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
            ['name', 'url', 'createdBy', 'picUrl', 'content', 'fields', 'channel']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'url', 'createdBy', 'picUrl', 'content', 'fields', 'channel']
        );
    }

    /**
     * Returns the list of all rules of ChatConversation.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        $currentUser = User::findByPk(new \MongoId(CURRENT_USER_ID));

        //get default channel
        if (empty($this->accountId)) {
            throw new ServerErrorHttpException('Account id of article cannot be blank');
        }
        $defaultChannel = ArticleChannel::getDefault($this->accountId);
        if (empty($defaultChannel)) {
            throw new ServerErrorHttpException('Can not found default channel');
        }

        return array_merge(
            parent::rules(),
            [
                [['name', 'content', 'url'], 'required'],
                ['createdBy', 'default', 'value' => $currentUser->name],
                ['fields', 'default', 'value' => []],
                ['fields', 'validateFields'],
                ['channel', 'default', 'value' => $defaultChannel->_id],
                ['channel', 'toMongoId']
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into ChatConversation.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name', 'url', 'createdBy', 'picUrl', 'content', 'fields',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                'channel' => function () {
                    return $this->channel . '';
                }
            ]
        );
    }

    /**
     * get the documents of collection "article" according to a id
     * @param  mongoId $channelId
     * @return array<article>
     */
    public static function findByChannel($channelId)
    {
        return self::find()->where(['channel' => $channelId])->all();
    }

    /**
     * reset the channel to default channel
     * @param  MongoId $channelId.
     * @return integer the number of documents updated.
     * @author Devin Jin
     */
    public static function resetChannel($channelId)
    {
        return self::updateAll(['channel' => '', 'fields' => []], ['channel' => $channelId]);
    }

    /**
     * Get the count of the articles of one channel.
     * @param  MongoId $channelId
     * @return Integer
     * @author Devin Jin
     */
    public static function countByChannel(\MongoId $channelId)
    {
        return self::find()
            ->where(['channel' => $channelId, 'isDeleted' => false])
            ->count();
    }

    /**
     * Validator for attribute "fields"
     * @author Devin Jin
     */
    public function validateFields($attribute)
    {
        //only for attribute "fields"
        if ($attribute !== 'fields') {
            return;
        }

        $fields = $this->fields;

        //fields should be an array
        if (!is_array($fields)) {
            $this->addError($attribute, 'fields should be an array');
        }

        $requiredFields = ['id', 'name', 'type', 'content'];

        //validate each fields.
        foreach ($fields as $field) {
            //validate the required fields
            foreach ($requiredFields as $requiredField) {
                if (!array_key_exists($requiredField, $field)) {
                    $this->addError($attribute, 'fields.' . $requiredField . ' is required.');
                }
            }
        }
    }

    public static function search($accountId, $params)
    {
        $query = self::find();
        $condition = ['accountId' => $accountId, 'isDeleted' => StoreGoods::NOT_DELETED];

        if (!empty($params['channels'])) {
            $channels = Json::decode($params['channels'], true);
            //transform the channel id in string to mongoId
            foreach ($channels as &$channel) {
                $channel = new \MongoId($channel);
            }
            $condition['channel'] = ['$in' => $channels];
        }

        $query->where($condition);
        $query->orderBy(self::normalizeOrderBy($params));
        return new ActiveDataProvider(['query' => $query]);
    }

    public static function searchByNameAndUrl($accountId, $limit, $search = null, $createdAt = null)
    {
        $condition = ['isDeleted' => false, 'accountId' => $accountId];
        if ($search !== null) {
            $search = StringUtil::regStrFormat(trim($search));
            $searchReg = new \MongoRegex("/$search/i");
            $condition['$or'] = [
                ['url' => $searchReg],
                ['name' => $searchReg]
            ];
        }
        if ($createdAt !== null) {
            $condition['createdAt'] = ['$lt' => $createdAt];
        }

        return self::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->limit($limit)->all();
    }

    public static function getByUrl($url)
    {
        return self::findOne(['url' => $url]);
    }
}
