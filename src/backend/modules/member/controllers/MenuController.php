<?php
namespace backend\modules\member\controllers;

use Yii;
use MongoId;
use yii\helpers\Json;
use backend\utils\TimeUtil;
use backend\models\Token;
use yii\web\BadRequestHttpException;
use yii\helpers\ArrayHelper;
use backend\components\Controller;
use backend\modules\member\models\Member;

class MenuController extends Controller
{
    public function actionStatsMenusHits()
    {
        $openId = $this->getQuery('openId');
        $channelId = $this->getQuery('channelId');
        $count = 0;
        $lastTime = '';
        $results = [];

        if (empty($openId) || empty($channelId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $resultItem = Yii::$app->weConnect->statsMenusHits($openId, $channelId);

        if (!empty($resultItem)) {
            if (!empty($resultItem['profiles']) && !empty($resultItem['profiles']['menus']) && count($resultItem['profiles']['menus']) > 0) {
                $raw = $resultItem['profiles']['menus'];
                $count = !isset($raw['hitCount']) ? 0 : $raw['hitCount'];
                $lastTime = empty($raw['lastHitTime']) ? '' : TimeUtil::msTime2String($raw['lastHitTime'], 'Y-m-d H:i:s');
            }
        }
        $item = [
            'hitCount' => $count,
            'lastHitTime' => $lastTime
        ];
        return $item;
    }

    private function _transformMenus($menus)
    {
        $items = [];
        if (count($menus) > 0) {
            foreach ($menus as $menu) {
                if (array_key_exists('parentId', $menu)) {
                    $menuType = 'subMenu';
                } else {
                    $menuType = 'mainMenu';
                }

                if (array_key_exists('deleteTime', $menu) && isset($menu['deleteTime'])) {
                    $isDeleted = true;
                } else {
                    $isDeleted = false;
                }

                $item = [
                    'id' => $menu['id'],
                    'content' => $menu['name'],
                    'type' => $menuType,
                    'isDeleted' => $isDeleted
                ];
                $items[] = $item;
            }
        }
        return $items;
    }

    private function _transformStatsMenuHits($channelId, $result, $menuItems)
    {
        $items = [];
        if (!empty($result) && count($menuItems) > 0) {
            if (!empty($result['profiles']) && !empty($result['profiles']['menus']) && !empty($result['profiles']['menus']['items']) && count($result['profiles']['menus']['items']) > 0) {
                $menu = $result['profiles']['menus']['items'];
                for ($i = 0; $i < count($menu); $i++) {
                    for ($j = 0; $j < count($menuItems); $j++) {
                        if ($menuItems[$j]['id'] == $menu[$i]['menuId']) {
                            $menus = array_merge(["channelId" => $channelId], $menu[$i]);
                            $items[] = array_merge($menuItems[$j], $menus);
                        }
                    }
                }
            }
        }
        return $items;
    }

    private function _transformStatsMenu($result, $menuId)
    {
        $items = [];
        if (!empty($result)) {
            if (!empty($result['profiles']) && !empty($result['profiles']['menus']) && !empty($result['profiles']['menus']['items']) && count($result['profiles']['menus']['items']) > 0) {
                $menuItems = $result['profiles']['menus']['items'];
                foreach ($menuItems as $menu) {
                    if ($menu['menuId'] == $menuId) {
                        $items[] = $menu['daily'];
                    }
                }
            }
        }
        return $items;
    }

    public function actionStatsMenuHits()
    {
        $query = $this->getQuery();
        $openId = $query['openId'];
        $channelId = $query['channelId'];
        $page = $query['page'];
        $perPage = $query['per-page'];
        $orderBy = $this->getQuery('orderby');

        $orderKey = $orderValue = '';
        $accountId = Token::getAccountId();

        if (empty($openId) || empty($channelId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        if (!empty($orderBy)) {
            $orderBy = Json::decode($orderBy, true);
            foreach ($orderBy as $key => $value) {
                $orderKey = $key;
                if ('asc' === strtolower($value)) {
                    $orderValue = SORT_ASC;
                } else {
                    $orderValue = SORT_DESC;
                }
            }
        }

        // Get all menus.
        $menus = Yii::$app->weConnect->getMenus($channelId);
        $menuItems = $this->_transformMenus($menus);

        // Get every menus count.
        $result = Yii::$app->weConnect->statsMenusHits($openId, $channelId);
        $items = $this->_transformStatsMenuHits($channelId, $result, $menuItems);

        // Paging
        if (!empty($orderKey) && !empty($orderValue)) {
            ArrayHelper::multisort($items, $orderKey, $orderValue);
        }
        $offset = ($page -1) * $perPage;
        $message = array_slice($items, $offset, $perPage);

        return [
                    'items' => empty($message) ? [] : $message,
                    '_meta' => [
                        'totalCount' => count($items),
                        'pageCount' => ceil(count($items) / $perPage),
                        'currentPage' => $page,
                        'perPage' => $perPage
                    ]
               ];
    }

    public function actionStatsMenu()
    {
        $query = $this->getQuery();
        $openId = $query['openId'];
        $channelId = $query['channelId'];
        $menuId = $query['menuId'];
        $page = $query['page'];
        $perPage = $query['per-page'];
        $orderBy = $this->getQuery('orderby');

        $items = [];
        $orderKey = $orderValue = '';

        if (empty($menuId) || empty($openId) || empty($channelId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        if (!empty($orderBy)) {
            $orderBy = Json::decode($orderBy, true);
            foreach ($orderBy as $key => $value) {
                $orderKey = $key;
                if ('asc' === strtolower($value)) {
                    $orderValue = SORT_ASC;
                } else {
                    $orderValue = SORT_DESC;
                }
            }
        }

        // Get every menus count.
        $result = Yii::$app->weConnect->statsMenusHits($openId, $channelId);
        $options = $this->_transformStatsMenu($result, $menuId);

        if (!empty($options) && count($options) > 0) {
            foreach ($options as $option) {
                $items = array_merge($option, $items);
            }
        }
        // Paging
        if (!empty($orderKey) && !empty($orderValue)) {
            ArrayHelper::multisort($items, $orderKey, $orderValue);
        }
        $offset = ($page -1) * $perPage;
        $message = array_slice($items, $offset, $perPage);

        return [
                    'items' => empty($message) ? [] : $message,
                    '_meta' => [
                        'totalCount' => count($items),
                        'pageCount' => ceil(count($items) / $perPage),
                        'currentPage' => $page,
                        'perPage' => $perPage
                    ]
               ];
    }

    public function actionChannels()
    {
        $memberId = $this->getQuery("memberId");
        if (empty($memberId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        // Get member.
        $member = Member::findOne(['_id' => new MongoId($memberId)]);
        $channels = $member->getChannels($memberId);
        return $channels;
    }
}
