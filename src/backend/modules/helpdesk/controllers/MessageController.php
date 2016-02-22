<?php
namespace backend\modules\helpdesk\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use MongoId;
use backend\modules\helpdesk\models\ChatMessage;
use backend\components\ActiveDataProvider;
use backend\utils\StringUtil;
use yii\helpers\Json;
use backend\components\Controller;
use backend\models\Graphic;
use backend\modules\helpdesk\models\ChatSession;

class MessageController extends Controller
{

    const MESSAGE_TYPE_ARTICLE = 'article';
    const MESSAGE_TYPE_NEWS    = 'NEWS';
    const MESSAGE_TYPE_TEXT    = 'TEXT';

    public function actionIndex()
    {
        $sessionId = $this->getQuery('sessionId');
        $page = $this->getQuery('page', 1);
        $page = $page < 1 ? 1 : $page;
        $perPage = intval($this->getQuery('per-page', 10));

        $chatSession = ChatSession::findByPk(new MongoId($sessionId));
        if (empty($chatSession)) {
            throw new BadRequestHttpException("Session Id not exists");
        }

        $meta = [
            'currentPage' => $page,
            'perPage'     => $perPage
        ];

        $meta['totalCount'] = $chatSession->endMessageId - $chatSession->startMessageId + 1;
        $meta['pageCount'] = intval($meta['totalCount'] % $perPage > 0 ? $meta['totalCount'] / $perPage + 1 : $meta['totalCount'] / $perPage);

        $startMessageId = $chatSession->startMessageId + $page * $perPage - 1;
        $startMessageId = $startMessageId < 2 ? 2 :$startMessageId;

        $endMessageId = $startMessageId - $perPage + 1;
        $endMessageId = $endMessageId < $chatSession->startMessageId ? $chatSession->startMessageId : $endMessageId;


        $messages = Yii::$app->tuisongbao->getMessages($chatSession->conversationId, $startMessageId, $endMessageId, $perPage);

        $items = [];

        if (!empty($messages) && empty($messages['conversation'])) {
            foreach ($messages as $message) {
                $messageContent = $message['content'];
                if (!empty($messageContent['extra']['type']) && empty($messageContent['extra']['type']) == self::MESSAGE_TYPE_ARTICLE) {
                    // Message with article
                    $articleId = $messageContent['text'];
                    // Get graphic
                    $article = Graphic::findOne(['_id' => $articleId]);
                    $body = [
                        'id'       => $articleId,
                        'articles' => []
                    ];
                    if (!empty($article)) {
                        $body['articles'] = $article->articles;
                    }

                    $item = [
                        'content'  => [
                            'msgType' => self::MESSAGE_TYPE_NEWS,
                            'body'    => $body
                        ]
                    ];
                } else {
                    // Message with text
                    $item = [
                        'content'  => [
                            'msgType' => self::MESSAGE_TYPE_TEXT,
                            'body'    => $messageContent['text']
                        ]
                    ];
                }

                $item['isReply'] = $messageContent['extra']['isHelpdesk'];
                $item['sentTime'] = strtotime($message['createdAt']) * 1000;

                $items[] = $item;
            }
        }

        return ['items' => array_reverse($items), '_meta' => $meta];
    }
}
