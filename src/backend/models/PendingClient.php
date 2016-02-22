<?php

namespace backend\models;

use MongoId;
use yii\web\ServerErrorHttpException;
use backend\components\PlainModel;
use backend\modules\helpdesk\models\ChatConversation;
use backend\modules\helpdesk\models\HelpDesk;
use backend\models\MongodbUtil;
use backend\utils\LogUtil;
use backend\utils\TimeUtil;

/**
 * Model class for pendingClient.
 *
 * The followings are the available columns in collection 'account':
 * @property MongoId $_id
 * @property String $nick
 * @property String $avatar
 * @property String $openId
 * @property String $source
 * @property String $sourceChannel
 * @property MongoId $accountId
 * @property array $tags
 * @property MongoDate $createdAt
 * @author Devin.Jin
 **/
class PendingClient extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with user.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'pendingClient';
    }

    /**
     * Returns the list of all attribute names of peding client.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['nick', 'avatar', 'openId', 'source', 'channelId', 'channelInfo', 'tags']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['nick', 'avatar', 'openId', 'source', 'channelId', 'channelInfo', 'tags']
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
                ['source', 'in', 'range' => [ChatConversation::TYPE_WEBSITE, ChatConversation::TYPE_WECHAT, ChatConversation::TYPE_WEIBO, ChatConversation::TYPE_ALIPAY]]
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
            [
                'nick', 'avatar', 'openId', 'source', 'channelId', 'channelInfo', 'tags'
            ]
        );
    }

    /**
     * Find pending client with openId
     * @param  string $openId    [description]
     * @param  MongoId $accountId [description]
     * @return array client object
     */
    public static function findByOpenId($openId, $accountId)
    {
        return self::findByCondition([
            'openId' => $openId,
            'accountId' => $accountId
        ], true);
    }

    /**
     * Push the client to the pending client queue
     * @param  array $client
     * @author Devin.Jin
     */
    public static function enQueue($client)
    {
        if (empty(self::findOne(['openId' => $client['openId']]))) {
            $pendingClient = new PendingClient;
            $pendingClient->load($client, '');
            $pendingClient->accountId = new MongoId($client['accountId']);

            if (!$pendingClient->save()) {
                LogUtil::error(['error' => 'Save pending client failed', 'errors' => $pendingClient->getErrors()], 'helpdesk');
                throw new ServerErrorHttpException("failed to save pending client for unknown reason");
            }
        }
    }

    /**
     * Get the latest documents of the pending clients.
     * The clients should ping the server in the threshold or sourced from WeChat.
     * @param Integer $count the count of the elements to be dequeued
     * @return array the client.
     */
    public static function deQueue($deskId, $count = 1)
    {
        $helpdesk = HelpDesk::findOne($deskId);

        $condition = [
            //'source' => ['$in' => [ChatConversation::TYPE_WECHAT, ChatConversation::TYPE_WEIBO, ChatConversation::TYPE_ALIPAY]],
            'accountId' => $helpdesk->accountId
        ];

        if (!empty($helpdesk->tags)) {
            $condition['tags'] = ['$in' => $helpdesk->tags];

            $pendingClients = self::find()
                ->where($condition)
                ->orderBy(['createdAt' => SORT_ASC])
                ->limit($count)
                ->all();

            if (count($pendingClients) < $count) {
                $condition['tags'] = ['$nin' => $helpdesk->tags];
                $excludeTagClients = self::find()
                    ->where($condition)
                    ->orderBy(['createdAt' => SORT_ASC])
                    ->limit($count - count($pendingClients))
                    ->all();
                $pendingClients = array_merge_recursive($pendingClients, $excludeTagClients);
            }

        } else {
            $pendingClients = self::find()
                ->where($condition)
                ->orderBy(['createdAt' => SORT_ASC])
                ->limit($count)
                ->all();
        }

        $clients = [];

        foreach ($pendingClients as $pendingClient) {
            $client = [
                'nick' => $pendingClient->nick,
                'avatar' => $pendingClient->avatar,
                'openId' => $pendingClient->openId,
                'source' => $pendingClient->source,
                'channelId' => $pendingClient->channelId
            ];
            if ($pendingClient->channelInfo) {
                $client['channelInfo'] = $pendingClient->channelInfo;
            }
            $clients[] = $client;
            $pendingClient->delete();
        }

        return $clients;
    }
}
