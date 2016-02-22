<?php
namespace backend\modules\member\controllers;

use Yii;
use MongoId;
use backend\utils\TimeUtil;
use backend\components\Controller;
use backend\modules\member\models\Member;
use yii\web\BadRequestHttpException;

/**
 * Controller class for member`s interact hisotry message
 **/
class InteractMessageController extends Controller
{
    public function actionStatsMessages()
    {
        $openId = $this->getQuery('openId');
        $channelId = $this->getQuery('channelId');
        $messageCount = 0;
        $keyCount = 0;
        $lastInteractTime = '';

        if (empty($openId) || empty($channelId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $result = Yii::$app->weConnect->statsMenusHits($openId, $channelId);
        if (!empty($result) && !empty($result['profiles']) && !empty($result['profiles']['messages'])) {
            $messages = $result['profiles']['messages'];
            $messageCount = !isset($messages['messageCount']) ? 0 : $messages['messageCount'];
            $keyCount = !isset($messages['hitKeywordCount']) ? 0 : $messages['hitKeywordCount'];
            $lastInteractTime = empty($messages['lastMessageTime']) ? '' : TimeUtil::msTime2String($messages['lastMessageTime'], 'Y-m-d H:i:s');
        }
        $item = [
            'messageCount' => $messageCount,
            'keyCount' => $keyCount,
            'lastInteractTime' => $lastInteractTime
        ];
        return $item;
    }

    private function _transformMessages($result, $channelId)
    {
        $items = $item = [];
        if (!empty($result) && !empty($result['results']) && count($result['results']) > 0) {
            foreach ($result['results'] as $message) {
                $messages = $message['message'];
                if (!empty($messages)) {
                    $item = [
                        'id' => empty($messages['messageId']) ? '' : $messages['messageId'],
                        'channelId' => $channelId,
                        'message' => !isset($messages['content']) ? '' : $messages['content'],
                        'msgType' => $messages['msgType'],
                        'keycode' => !isset($message['keycode']) ? '' : $message['keycode'],
                        'interactTime' => TimeUtil::msTime2String($messages['createTime'], 'Y-m-d H:i:s')
                    ];
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

    public function actionStatsMessage()
    {
        $query = $this->getQuery();
        $openId = $query['openId'];
        $channelId = $query['channelId'];
        $page = $query['page'];
        $perPage = $query['per-page'];
        $orderBy = $query['ordering'];

        $items = [];
        $pageCount = $totalCount = $currentPage = $perPage = 0;

        if (empty($openId) || empty($channelId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $query = array_merge(['ignoreReplyMessage' => true], $query);
        $result = Yii::$app->weConnect->statsMessage($channelId, $openId, $query);
        $messages = $this->_transformMessages($result, $channelId);

        if (!empty($result)) {
             $pageCount = ceil($result['totalAmount'] / $result['pageSize']);
             $totalCount = $result['totalAmount'];
             $currentPage = $result['pageNum'];
             $perPage = $result['pageSize'];
        }
        $items = [
                    'items' => empty($messages) ? [] : $messages,
                    '_meta' => [
                        'totalCount' => $totalCount,
                        'pageCount' => $pageCount,
                        'currentPage' => $currentPage,
                        'perPage' => $perPage
                    ]
                ];
        return $items;
    }

    public function actionMessages()
    {
        $query = $this->getQuery();
        $openId = $query['openId'];
        $channelId = $query['channelId'];
        $page = $query['page'];
        $perPage = $query['per-page'];
        $totalCount = $pageCount = $perPage = 0;
        $currentPage = 0;

        if (empty($openId) || empty($channelId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $result = Yii::$app->weConnect->statsMessage($channelId, $openId, $query);

        if (!empty($result)) {
            $totalCount = $result['totalAmount'];
            $perPage = $result['pageSize'];
            $pageCount = ceil($totalCount / $perPage);
            $currentPage = $result['pageNum'];
        }
        $items = [
                    'items' => empty($result['results']) ? [] : $result['results'],
                    '_meta' => [
                        'totalCount' => $totalCount,
                        'pageCount' => $pageCount,
                        'currentPage' => $currentPage,
                        'perPage' => $perPage
                    ]
                ];
        return $items;
    }
}
