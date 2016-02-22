<?php
namespace backend\components;

use Yii;
use yii\base\Component;
use backend\exceptions\ApiDataException;
use yii\web\ServerErrorHttpException;
use backend\utils\LogUtil;
use backend\utils\StringUtil;
use yii\helpers\Json;
use backend\exceptions\WechatUnauthException;
use backend\exceptions\FailedResponseApiException;
use backend\exceptions\AccountAlreadyExsistsException;
use backend\exceptions\MessageSendFailException;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;
use backend\models\Store;
use backend\models\WebhookEvent;

class WeConnect extends Component
{
    public $weconnectDomain;
    public $wechatDomain;

    //const for message type
    const MESSAGE_TEXT = 'TEXT';
    const MESSAGE_NEWS = 'NEWS';
    const MESSAGE_URL = 'URL';
    const MESSAGE_MPNEWS = 'MPNEWS';
    const MESSAGE_EVENT = 'EVENT';
    const MESSAGE_EXTEND = 'EXT';
    const MESSAGE_MIXED = 'MIXED';

    //const for menu type
    const MENU_CUSTOMER_SERVICE = 'CUSTOMER_SERVICE';
    const MENU_USER_CENTER = 'USER_CENTER';
    const MENU_TYPE_CLICK = 'CLICK';
    const MENU_TYPE_VIEW = 'VIEW';
    const MENU_TYPE_EXTEND = 'EXT';
    const MENU_TYPE_WEBHOOK = 'WEBHOOK';

    //const for method
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_GET = 'get';
    const METHOD_DELETE = 'delete';

    //const for member bind
    const TYPE_MALE = 1;
    const TYPE_FEMALE = 2;
    const MALE = 'MALE';
    const FEMALE = 'FEMALE';
    const UNKNOWN = 'UNKNOWN';

    public function init()
    {
        //TODO
    }

    /**
     * This method is provide to build parameter, turn array into string, implode by comma.
     * @param array $data
     * @return string
     */
    private function _a2s($data)
    {
        if (is_array($data)) {
            $data = array_unique($data);
            $result = implode(',', $data);
        } else if (is_string($data)) {
            $result = $data;
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'data_error'));
        }

        return $result;
    }

    /**
     * This method is provide to build parameter, turn array into json.
     * @param array $data
     * @throws WeConnectException
     * @return json
     */
    private function _a2j($data)
    {
        try {
            $result = Json::encode($data);
            return $result;
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(Yii::t('common', 'data_error'));
        }
    }

    public function allAccounts()
    {
        $url = $this->weconnectDomain . '/accounts';
        return $this->_curl(self::METHOD_GET, $url, 'channel');
    }

    /**
     * This method is provide to get account by id.
     * @param array or string $accountIds
     *  example :
     *      ['5473ffe7db7c7c2f0bee5c71', '5473ffe7db7c7c2f0bee5c71'] or '5473ffe7db7c7c2f0bee5c71'
     * @throws WeConnectException
     * @return array, the account info
     */
    public function getAccounts($accountIds)
    {
        $url = $this->weconnectDomain . '/accounts/accountIds/' . $this->_a2s($accountIds);

        return $this->_curl(self::METHOD_GET, $url, 'channel');
    }

    /**
     * This method is provide to get account by id.
     * @param string $channelId
     * @throws WeConnectException
     * @return the account info
     */
    public function getAccount($channelId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $channelId;
        return $this->_curl(self::METHOD_GET, $url, 'channel');
    }

    /**
     * Get the accessToken by $channelId
     * @param  string $channelId
     * @return
     */
    public function getAccessToken($channelId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $channelId . '/refreshToken';
        $params = ['validation' => true];
        return $this->_curl(self::METHOD_POST, $url, 'weixin', $params, true);

    }

    /**
     * This method is provide to get account and user count by id.
     * @param array or string $accountIds
     *  example :
     *      ['5473ffe7db7c7c2f0bee5c71', '5473ffe7db7c7c2f0bee5c71'] or '5473ffe7db7c7c2f0bee5c71'
     * @throws WeConnectException
     * @return array, the account info include userCount
     */
    public function getAccountsAndUserCounts($accountIds)
    {
        $url = $this->weconnectDomain . '/accounts/accountIds/' . $this->_a2s($accountIds) . '/userCount';

        return $this->_curl(self::METHOD_GET, $url, 'channel');
    }

    /**
     * This method is provide to create account.
     * @param array $account
     *  example :
     *      [
     *          "appId": "wxf5696b744f8581a4",
     *          "appSecret": "ab1b7d2b3d5624b099c4c653dfa2aa4d",
     *          "channelAccount": "gh_fdba39256c8e",
     *          "name": "群游汇",
     *          "channel": "WEIXIN",
     *          "accountType": "SUBSCRIPTION_ACCOUNT"
     *      ]
     * @throws WeConnectException
     * @return array, The created account.
     */
    public function createAccount($account)
    {
        $url = $this->weconnectDomain . '/accounts';
        $channel = $account['channel'];
        $account = $this->_a2j($account);
        $result = Yii::$app->curl->setHeaders(['Content-Type: application/json'])->post($url, $account);//REVIEW: common, wrap it in postJson method...

        LogUtil::info(['url' => 'POST ' . $url, 'response' => $result, 'params' => $account], 'channel');

        $result = json_decode($result, true);

        if ($result && isset($result['code']) && 200 == $result['code']) {
            return $result['data'];
        } else if ($result && isset($result['data']['errorMessage'])) {
            $errorMessage = $result['data']['errorMessage'];
            $errorCode = empty($result['data']['errorCode']) ? '' : $result['data']['errorCode'];

            if ($errorCode == 100009) {
                if ($channel == 'WEIXIN') {
                    $message = Yii::t('channel', 'wechat_account_already_exsisted');
                } else {
                    $message = Yii::t('channel', strtolower($channel) . '_account_already_exsisted');
                }
                throw new AccountAlreadyExsistsException($message);
            } else {
                throw new FailedResponseApiException($result['data']['errorMessage']);
            }
        } else {
            throw new ApiDataException('GET ' . $url, $result, $account);
        }
    }

    /**
     * This method is provide to delete account by id.
     * @param string $accountId
     * @throws WeConnectException
     * @return empty array
     */
    public function deleteAccount($accountId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId;
        return $this->_curl(self::METHOD_DELETE, $url, 'channel');
    }

    /**
     * This method is provide to update account.
     * @param array $account,
     *  example :
     *      [
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "appId": "wxf5696b744f8581a4",
     *          "appSecret": "ab1b7d2b3d5624b099c4c653dfa2aa4d",
     *          "channelAccount": "gh_fdba39256c8e",
     *          "name": "群游汇",
     *          "channel": "WEIXIN",
     *          "accountType": "SUBSCRIPTION_ACCOUNT"
     *      ]
     * @throws WeConnectException
     * @return array, The updated account.
     */
    public function updateAccount($accountId, $account)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId;

        return $this->_curl(self::METHOD_PUT, $url, 'channel', $account);
    }

    /**
     * This function is for get the followers according to the accountId
     * @param $accountId, string.
     * @param $conditions, array.
     * @return array, the response from WeConnect
     * @throws ApiDataException
     * @author Devin Jin
     **/
    public function getFollowers($accountId, $conditions)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/users/search';
        return $this->_curl(self::METHOD_GET, $url, 'channel', $conditions);
    }

    /**
     * This function is for query detail information for a follower
     * @param $userId, String
     * @param $accountId, String
     * @return array
     * @throws ApiDataException
     **/
    public function getFollower($userId, $accountId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/users/' . $userId;
        return $this->_curl(self::METHOD_GET, $url, 'channel');
    }

    /**
     * Get follower by origin id
     * @param string $originId
     * @param string $accountId
     * @throws ApiDataException
     * @return Array user info
     */
    public function getFollowerByOriginId($originId, $accountId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/users/originId/' . $originId;
        return $this->_curl(self::METHOD_GET, $url, 'member');
    }

    /**
     * This function is for add tags to followers.
     * @param $channelId, String
     * @param $followers, array
     * @param $tags, array
     * @return string, the result infomation
     * @author Devin.Jin
     **/
    public function addTagsToFollowers($channelId, $followers, $tags, $idType = 'user')
    {
        $url = $this->weconnectDomain . '/accounts/' . $channelId . '/users/bulkAddTags';
        $params = ['tags' => $tags];
        if ($idType === 'user') {
            $params['userIds'] = $followers;
        } else if ($idType === 'origin') {
            $params['originIds'] = $followers;
        }

        return $this->_curl(self::METHOD_POST, $url, 'channel', $params, false);
    }

    /**
     * Remove tags for followers.
     * @param $channelId, String
     * @param $followers, array
     * @param $tags, array
     * @return string, the result infomation
     * @author Devin.Jin
     **/
    public function removeTags($channelId, $followers, $tags, $idType = 'user')
    {
        $url = $this->weconnectDomain . '/accounts/' . $channelId . '/users/bulkRemoveTags';
        $params = ['tags' => $tags];
        if ($idType === 'user') {
            $params['userIds'] = $followers;
        } else if ($idType === 'origin') {
            $params['originIds'] = $followers;
        }

        return $this->_curl(self::METHOD_POST, $url, 'channel', $params, false);
    }

    /**
     * This function is provide to query keyword messages.
     * @param string $channelId
     * @param array $condition
     * @return array: keyword list
     */
    public function getKeywords($channelId, $condition)
    {
        $condition = $this->_transferPaginationCondition($condition);
        $url = $this->weconnectDomain . '/accounts/' . $channelId . '/keywords';
        $keywords = $this->_curl(self::METHOD_GET, $url, 'channel', $condition);

        if (!array_key_exists('results', $keywords)) {
            $keywords['results'] = [];
        }

        //format replay message
        foreach ($keywords['results'] as &$item) {
            // skip not supported message type
            if (!$item['replyMessage']['msgType']) {
                continue;
            }
            $replyMessage = $this->_formatMsgResponse($item['replyMessage']);
            $item['msgType'] = $replyMessage['msgType'];
            $item['content'] = $replyMessage['content'];
            unset($item['replyMessage']);
        }

        return $keywords;
    }

    /**
     * This function is provide to query keyword by id.
     * @param string $accountId
     * @param string $keywordId
     * @return array: keyword info
     */
    public function getKeyword($accountId, $keywordId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/keywords/' . $keywordId;
        $keyword = $this->_curl(self::METHOD_GET, $url, 'channel');

        $replyMessage = $this->_formatMsgResponse($keyword['replyMessage']);

        // skip not supported message type
        if ($replyMessage['msgType']) {
            unset($keyword['replyMessage']);
            $keyword['msgType'] = $replyMessage['msgType'];
            $keyword['content'] = $replyMessage['content'];
        }

        return $keyword;
    }

    /**
     * This method is provide to create a keyword.
     * @param string, $accountId
     * @param array, $keyword
     * @return array: keyword info
     */
    public function createKeyword($accountId, $keyword)
    {
        //format replay message
        $keyword['replyMessage'] = $this->_formatMsgRequest($keyword['msgType'], $keyword['content']);
        unset($keyword['msgType'], $keyword['content']);//useless property

        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/keywords';
        return $this->_curl(self::METHOD_POST, $url, 'channel', $keyword);
    }

    /**
     * This function is provide to update keyword.
     * @param string, $accountId
     * @param array, $keyword
     * @return array, keyword info
     */
    public function updateKeyword($accountId, $keyword)
    {
        $keywordId = $keyword['id'];
        $keyword['replyMessage'] = $this->_formatMsgRequest($keyword['msgType'], $keyword['content']);
        unset($keyword['id'], $keyword['msgType'], $keyword['content']);

        $url = $this->weconnectDomain . '/accounts/'. $accountId . '/keywords/' . $keywordId;
        return $this->_curl(self::METHOD_PUT, $url, 'channel', $keyword);
    }

    /**
     * This function is provide to delete a keyword.
     * @param string $accountId
     * @param string $keywordId
     * @return boolean, true: delete success
     */
    public function deleteKeyword($accountId, $keywordId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/keywords/' . $keywordId;
        return $this->_curl(self::METHOD_DELETE, $url, 'channel');
    }

    /**
     * This function is provide to disable/enable keyword
     * @param array, $keyword: ['accountId', 'keywordId', 'action']
     * @return boolean, true: disable/enable success
     */
    public function updateKeywordStatus($keyword)
    {
        $accountId = $keyword['channelId'];
        $keywordId = $keyword['keywordId'];
        $action = $keyword['action'];

        $url = $this->weconnectDomain . '/accounts/'. $accountId . '/keywords/' . $keywordId . '/' . $action;

        return $this->_curl(self::METHOD_POST, $url, 'channel', $keyword, false);
    }

    /**
     * This function is provide to get keywords time series
     * @param string $accountId
     * @param string $keywordId
     * @param array $dateCondition
     * @return array, keywords statistics info
     */
    public function getKeywordsTimeSeries($accountId, $keywordId, $dateCondition)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/statistics/keywords/' . $keywordId . '/timeSeries';
        return $this->_curl(self::METHOD_GET, $url, 'channel', $dateCondition);
    }

    /**
     * This function is provide to get all menus.
     * @param string $channelId
     * @return array, menus info
     */
    public function getMenus($channelId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $channelId . '/menus?ignoreDeleted=false';
        return $this->_curl(self::METHOD_GET, $url, 'channel');
    }

    /**
     * This function is provide to get menus.
     * @param string $channelId
     * @return array, menus info
     */
    public function getMenu($channelId, &$keycodes = [])
    {
        $url = $this->weconnectDomain . '/accounts/' . $channelId . '/menus';
        $result = $this->_curl(self::METHOD_GET, $url, 'channel');

        if (!isset($result['menus'])) {
            return [];
        }

        $menus = &$result['menus'];

        //format menu replay message
        if (!empty($menus)) {
            foreach ($menus as &$menu) {
                //no submenu
                if (empty($menu['subMenus'])) {
                    if (!empty($menu['functionKey']) && ($index = array_search($menu['functionKey'], $keycodes)) !== false) {
                        $menu['type'] = static::MENU_TYPE_EXTEND;
                        $menu['keycode'] = $menu['functionKey'];
                        array_splice($keycodes, $index, 1);
                    } else if (!empty($menu['replyMessage'])) {
                        $replyMessage = $this->_formatMsgResponse($menu['replyMessage']);
                        if (!$replyMessage['msgType']) {
                            continue;
                        }
                        $menu['msgType'] = $replyMessage['msgType'];
                        $menu['content'] = $replyMessage['content'];
                    } else {
                        // WEBHOOK: Reply message is null and type is 'CLICK'
                        if (!empty($menu['type']) && $menu['type'] == self::MENU_TYPE_CLICK) {
                            $menu['type'] = self::MENU_TYPE_WEBHOOK;
                        }
                        if (!empty($menu['type']) && $menu['type'] == self::MENU_TYPE_VIEW) {
                            $menu['msgType'] = self::MESSAGE_URL;
                            $menu['content'] = $menu['keycode'];
                        }
                    }

                } else {
                    foreach ($menu['subMenus'] as &$subMenu) {
                        if (!empty($subMenu['functionKey']) && ($index = array_search($subMenu['functionKey'], $keycodes)) !== false) {
                            $subMenu['type'] = static::MENU_TYPE_EXTEND;
                            $subMenu['keycode'] = $subMenu['functionKey'];
                            array_splice($keycodes, $index, 1);
                        } else if (!empty($subMenu['replyMessage'])) {
                            $replyMessage = $this->_formatMsgResponse($subMenu['replyMessage']);
                            if (!$replyMessage['msgType']) {
                                continue;
                            }
                            $subMenu['msgType'] = $replyMessage['msgType'];
                            $subMenu['content'] = $replyMessage['content'];
                        } else {
                            // WEBHOOK: Reply message is null and type is 'CLICK'
                            if (!empty($subMenu['type']) && $subMenu['type'] == self::MENU_TYPE_CLICK) {
                                $subMenu['type'] = self::MENU_TYPE_WEBHOOK;
                            }
                            if (!empty($subMenu['type']) && $subMenu['type'] == self::MENU_TYPE_VIEW) {
                                $subMenu['msgType'] = self::MESSAGE_URL;
                                $subMenu['content'] = $subMenu['keycode'];
                            }
                        }
                        unset($subMenu['index'], $subMenu['accountId'], $subMenu['replyMessage'], $subMenu['functionKey']);
                    }
                }
                unset($menu['index'], $menu['accountId'], $menu['replyMessage'], $menu['functionKey']);//useless property
            }
        }

        return $result;
    }

    /**
     * This function is provide to create a menu.
     * @param string, $accountId
     * @param array, $menu
     * @throws BadRequestHttpException, menu's action can not be null
     * @throws ApiDataException
     * @return boolean, true: create success
     */
    public function createMenu($accountId, $menus, $actions)
    {
        //format replay message
        foreach ($menus as &$menu) {
            if (empty($menu['subMenus'])) {
                $menu = $this->_formatMenuItem($menu, $actions);
            } else {
                // get replay message
                foreach ($menu['subMenus'] as &$subMenu) {
                    //format replay message
                    $subMenu = $this->_formatMenuItem($subMenu, $actions);
                }
            }
        }
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/menus';

        return $this->_curl(self::METHOD_POST, $url, 'channel', $menus, false);
    }

    /**
     * Format menu item from request
     * @param  array $menu
     * @return array
     */
    private function _formatMenuItem($menu, $actions)
    {
        if (isset($menu['type'])) {
            if ($menu['type'] === static::MENU_TYPE_WEBHOOK) {
                $menu['type'] = static::MENU_TYPE_CLICK;
            } else if ($menu['type'] === static::MENU_TYPE_EXTEND) {
                if (!empty($actions[$menu['keycode']])  && ($content = $actions[$menu['keycode']])) {
                    $menu['functionKey'] = $menu['keycode'];
                    $menu['type'] = $content['type'];
                    if (!empty($content['content'])) {
                        if ($content['type'] === self::MENU_TYPE_VIEW) {
                            $menu['keycode'] = $content['content'];
                        } else {
                            $menu['replyMessage'] = $this->_formatMsgRequest($content['msgType'], $content['content']);
                        }
                    }
                } else {
                    unset($menu['type']);
                }
            } else if (isset($menu['content'])) {
                $menu = $this->_formatMenuRequest($menu, $menu['type'], $menu['content']);
                unset($menu['msgType'], $menu['content']);
            }
        } else {
            unset($menu['msgType'], $menu['content']);
        }
        return $menu;
    }

    /**
     * This function is provie to format menu data to request WeConnect
     * @param string, $type
     * @param array/string $content
     * @throws ServerErrorHttpException
     * @return array, message info
     */
    private function _formatMenuRequest($menu, $type, $content)
    {
        switch ($type) {
            case static::MENU_TYPE_CLICK:
                $replyMessage = [];
                if (!empty($content)) {
                    if (is_string($content)) {
                        $replyMessage['msgType'] = static::MESSAGE_TEXT;
                        $replyMessage['content'] = $content;
                    } else {
                        $replyMessage['msgType'] = static::MESSAGE_NEWS;
                        foreach ($content['articles'] as &$article) {
                            $article['url'] = $article['picUrl'];
                            unset($article['picUrl']);
                        }
                        $replyMessage['articles'] = $content['articles'];
                    }
                    $menu['replyMessage'] = $replyMessage;
                }
                break;
            case static::MENU_TYPE_VIEW:
                $menu['keycode'] = $content;
                break;
            default:
                throw new ServerErrorHttpException(Yii::t('common', 'data_error'));
        }

        return $menu;
    }

    /**
     * This function is provide to publish/unpublish menu.
     * @param string, $accountId
     * @param array, $menu: ['channelId', 'action']
     * @return boolean, true: publish/unpublish success
     */
    public function changeMenuPublish($accountId, $menu, $ignoreTypes = [])
    {
        $action = $menu['action'];
        $data = $this->getMenu($accountId);

        if (!isset($data['menus'])) {
            return false;
        }

        $menus = $data['menus'];
        //check replay message
        foreach ($menus as $menu) {
            //no submenu
            if (empty($menu['subMenus'])) {
                if ((!isset($menu['msgType']) || !isset($menu['content']))
                        && isset($menu['type']) && $menu['type'] != self::MENU_TYPE_VIEW
                        && $menu['type'] != self::MENU_TYPE_WEBHOOK
                        && !in_array($menu['keycode'], $ignoreTypes)) {
                    throw new BadRequestHttpException(Yii::t('channel', 'empty_menu_error'));
                }
            } else {
                foreach ($menu['subMenus'] as $subMenu) {
                    //check submenu replay message
                    if ((!isset($subMenu['msgType']) || !isset($subMenu['content']))
                            && isset($subMenu['type']) && $subMenu['type'] != self::MENU_TYPE_VIEW
                            && $subMenu['type'] != self::MENU_TYPE_WEBHOOK
                            && !in_array($subMenu['keycode'], $ignoreTypes)) {
                        throw new BadRequestHttpException(Yii::t('channel', 'empty_menu_error'));
                    }
                }
            }
        }

        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/menus/' . $action;
        return $this->_curl(self::METHOD_POST, $url, 'channel', [], false);
    }

    /**
     * Judge wether set help desk in wechat menu
     * @param  array|string  $argv  wechat menu or account id
     * @return boolean
     * @author Harry Sun
     */
    public function isSetHelpDesk($argv)
    {
        $menus = $argv;

        if (is_string($argv)) {
            $menus = $this->getMenu($argv)['items'];
        }

        if (isset($menus) && !empty($menus)) {
            foreach ($menus as $menu) {
                if (isset($menu['keycode']) && $menu['keycode'] === self::MENU_CUSTOMER_SERVICE) {
                    return true;
                }
                if (isset($menu['subMenus']) && !empty($menu['subMenus'])) {
                    foreach ($menu['subMenus'] as $submenu) {
                        if (isset($submenu['keycode']) && $submenu['keycode'] === self::MENU_CUSTOMER_SERVICE) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * This function is provide to get menu statistics.
     * @param string, $accountId
     * @param string, $menuId
     * @param int, $startDate
     * @param int, $endDate
     * @return array, menu statistics data
     */
    public function menuStatistics($accountId, $menuId, $startDate, $endDate)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/statistics/menus/' . $menuId . '/timeSeries';
        $params = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'id' => $menuId
        ];
        return $this->_curl(self::METHOD_GET, $url, 'channel', $params);
    }

    /**
     * This function is provide to init default rule message.
     * @param string, $accountId
     * @param array, $defaultRule
     * @throws ApiDataException
     * @return array, rule info
     */
    public function initDefaultRule($accountId, $defaultRule)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/defaultRules/' . $defaultRule['type'];

        $defaultRule['replyMessage'] = $this->_formatMsgRequest($defaultRule['msgType'], $defaultRule['content']);
        unset($defaultRule['msgType'], $defaultRule['content'], $defaultRule['type']);//useless property

        return $this->_curl(self::METHOD_POST, $url, 'channel', $defaultRule);
    }

    /**
     * This function is provide to query default rules.
     * @param string $accountId
     * @return array, default rules
     */
    public function getDefaultRules($accountId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/defaultRules';
        $result = $this->_curl(self::METHOD_GET, $url, 'channel');

        $count= count($result);
        foreach ($result as &$item) {
            $replyMessage = $this->_formatMsgResponse($item['replyMessage']);
            if (!$replyMessage['msgType']) {
                continue;
            }
            unset($item['replyMessage']);
            $item['msgType'] = $replyMessage['msgType'];
            $item['content'] = $replyMessage['content'];
        }

        $page = ['totalCount' => $count, 'pageCount' => 1, 'currentPage' => 1, 'perPage' => 20];
        return ['items' => $result, '_meta' => $page];
    }

    /**
     * This function is provide to disable/enbale rules.
     * @param array, $defaultRule: ['channelId', 'type', 'action']
     * @return boolean, true: disable/enable success
     */
    public function updateDefuaultRuleStatus($defaultRule)
    {
        $accountId = $defaultRule['channelId'];
        $type = $defaultRule['type'];
        $action = $defaultRule['action'];

        $url = $this->weconnectDomain . '/accounts/'. $accountId . '/defaultRules/' . $type . '/' . $action;
        return $this->_curl(self::METHOD_POST, $url, 'channel', $defaultRule, false);
    }

    /**
     * This function is provide to query mass messages.
     * @param string $accountId
     * @param array $condition
     * @return array: mass messages
     */
    public function getMassMessages($accountId, $condition)
    {
        $condition = $this->_transferOrderCondition($condition);
        $condition = $this->_transferPaginationCondition($condition);

        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/massMessages';
        $massmessages = $this->_curl(self::METHOD_GET, $url, 'channel', $condition);

        if (!array_key_exists('results', $massmessages)) {
            $massmessages['results'] = [];
        }
        //format mass message
        foreach ($massmessages['results'] as &$item) {
            //format time
            $item['scheduleTime'] = $this->_formatTime($item['scheduleTime']);
            $item['createTime'] = $this->_formatTime($item['createTime']);
            $item['finishTime'] = $this->_formatTime($item['finishTime']);
            $item['submitTime'] = $this->_formatTime($item['submitTime']);

            $totalCount = $item['totalCount'];// total count
            $sentCount = $item['sentCount'];//wechat sent count
            $csSentCount = $item['csSentCount'];// wechat help desk sent count
            //format count
            $item['successCount'] = $sentCount + $csSentCount;
            $item['failedCount'] = $totalCount - $sentCount - $csSentCount;

            //format replay message
            $replyMessage = $this->_formatMsgResponse($item['massMessage']);
            //skip not supported message type
            if (!$replyMessage['msgType']) {
                continue;
            }
            unset($item['massMessage']);//useless property
            $item['msgType'] = $replyMessage['msgType'];
            $item['content'] = $replyMessage['content'];
        }

        return $massmessages;
    }

    /**
     * This function is provide to query mass message by id.
     * @param string $accountId
     * @param string $massMessagelId
     * @return mass message info
     */
    public function getMassMessage($accountId, $massMessagelId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/massMessages/' . $massMessagelId;
        $massmessage = $this->_curl(self::METHOD_GET, $url, 'channel');

        //format time
        $massmessage['scheduleTime'] = $this->_formatTime($massmessage['scheduleTime']);
        $massmessage['createTime'] = $this->_formatTime($massmessage['createTime']);

        $totalCount = $massmessage['totalCount'];// total count
        $sentCount = $massmessage['sentCount'];//wechat sent count
        $csSentCount = $massmessage['csSentCount'];// wechat help desk sent count
        //format count
        $massmessages['successCount'] = $sentCount + $csSentCount;
        $massmessages['failedCount']= $totalCount - $sentCount - $csSentCount;

        //format replay message
        $replyMessage = $this->_formatMsgResponse($massmessage['massMessage']);
        //skip not supported message type
        if (!$replyMessage['msgType']) {
            continue;
        }
        unset($massmessage['massMessage']);//useless property
        $massmessage['msgType'] = $replyMessage['msgType'];
        $massmessage['content'] = $replyMessage['content'];
        return $massmessage;
    }

    /**
     * This function is provide to create mass message.
     * @param string $accountId
     * @param array $massMessage
     * @throws MessageSendFailException, 'message send fail'
     * @return boolean: true: create success
     */
    public function createMassMessage($accountId, $massMessage)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/massMessages';
        if (!empty($massMessage['mixed'])) {
            $massMessage['massiveType'] = self::MESSAGE_MIXED;
        }
        $massMessage['massMessage'] = $this->_formatMsgRequest($massMessage['msgType'], $massMessage['content']);
        unset($massMessage['msgType'], $massMessage['content'], $massMessage['mixed']);//useless property
        return $this->_curl(self::METHOD_POST, $url, 'channel', $massMessage, false, new MessageSendFailException());
    }

    /**
     * This function is provide to delete mass message.
     * @param string $accountId
     * @param string $massMessageId
     * @return boolean, true: delete success
     */
    public function deleteMassMessage($accountId, $massMessageId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/massMessages/' . $massMessageId;
        return $this->_curl(self::METHOD_DELETE, $url, 'channel');
    }

    /**
     * This function is provide to update mass message.
     * @param string, $accountId
     * @param array, $massMessage
     * @throws MessageSendFailException, 'message send fail'
     * @return boolean, true: update suddess
     */
    public function updateMassMessage($accountId, $massMessage)
    {
        $massMessageId = $massMessage['id'];
        if (!empty($massMessage['mixed'])) {
            $massMessage['massiveType'] = self::MESSAGE_MIXED;
        }

        $massMessage['massMessage'] = $this->_formatMsgRequest($massMessage['msgType'], $massMessage['content']);
        unset($massMessage['msgType'], $massMessage['content'], $massMessage['id'], $massMessage['mixed']);//useless property

        $url = $this->weconnectDomain . '/accounts/'. $accountId . '/massMessages/' . $massMessageId;
        return $this->_curl(self::METHOD_PUT, $url, 'channel', $massMessage, false, new MessageSendFailException());
    }

    /**
     * This function is provide to get account message history list.
     * @param string $accountId
     * @param array $condition
     * @return array, account message history list
     */
    public function getAccountMessages($accountId, $condition)
    {
        $condition = $this->_transferPaginationCondition($condition);
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/interactMessageHistories';
        $result = $this->_curl(self::METHOD_GET, $url, 'channel', $condition);
        if (!array_key_exists('results', $result)) {
            $result['results'] = [];
        }

        //format time
        foreach ($result['results'] as &$items) {
            $items['message']['createTime'] = $this->_formatTime($items['message']['createTime']);
            $items['sender']['subscribeTime'] = $this->_formatTime($items['sender']['subscribeTime']);
        }
        return $result;
    }

    /**
     * This function is provide to get account message history info.
     * @param string $accountId
     * @param string $userId
     * @param string $next
     * @return array, account message history information
     */
    public function getInteractMessages($accountId, $userId, $condition = [])
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/interactMessageHistories/user/' . $userId;

        $result =  $this->_curl(self::METHOD_GET, $url, 'channel', $condition);
        return $result;
    }

    /**
     * This function is provide to get property info.
     * @param string, $accountId
     * @param array, $condition: ['property' => 'city', 'parentProperty' => 'province', 'parentPropertyValue' => '上海']
     * @return array, property info
     */
    public function getProperty($accountId, $condition)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/statistics/users/property/' . $condition['property'];
        unset($condition['property']);

        return $this->_curl(self::METHOD_GET, $url, 'common', $condition);
    }

    /**
     * Get location statistics info
     * @param string $accountId
     * @param string $condition, ['locationProperty'=>'city', 'parentCountry'=>'中国'， 'parentProvince'=>'湖北']
     * @throws ApiDataException
     * @return array
     */
    public function getLocation($accountId, $condition)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/statistics/users/property/location';

        return $this->_curl(self::METHOD_GET, $url, 'common', $condition);
    }

    /**
     * Get the component token
     * @return array ['componentAppId'=>'abcabacbvacfas', 'componentToken'=>'fasdfasdfasdfsfsda']
     * @author Devin Jin
     **/
    public function getComponentToken()
    {
        $url = $this->weconnectDomain . '/sa/componentToken';
        $resultJson = Yii::$app->curl->get($url);
        LogUtil::info(['url' => $url, 'response' => $resultJson], 'channel');
        $result = json_decode($resultJson, true);

        if ($result && isset($result['code']) && 200 == $result['code'] && isset($result['data']) && $result['data']['componentToken']['expireDateTime'] > time()) {
            return [
                'componentAppId' => $result['data']['componentAppId'],
                'componentToken' => $result['data']['componentToken']['token']
            ];
        } else {
            throw new ApiDataException('GET ' . $url, $resultJson);
        }
    }

    /**
     * Get the preauthcode for wechat
     * @param $componentAccessToken
     * @param $componentAppId
     * @return String, the preauthtoken
     * @author Devin Jin
     **/
    public function getPreauthcode($componentAccessToken, $componentAppId)
    {
        $url = $this->wechatDomain . "/cgi-bin/component/api_create_preauthcode?component_access_token=$componentAccessToken";
        $params = [
            'component_appid' => $componentAppId
        ];
        $resultJson = Yii::$app->curl->post($url, JSON::encode($params));
        LogUtil::info(['url' => $url, 'params' => $params, 'response' => $resultJson], 'channel');
        $result = JSON::decode($resultJson, true);

        if (empty($result['pre_auth_code'])) {
            throw new ApiDataException('POST ' . $url, $resultJson, $params);
        } else {
            return $result['pre_auth_code'];
        }
    }

    /**
     * Get query auth infomation
     * @param $accessToken
     * @param $componentAppId
     * @param $authCode
     * @return array
     **/
    public function getQueryAuth($accessToken, $authCode, $componentAppId)
    {
        $url = $this->wechatDomain . "/cgi-bin/component/api_query_auth?component_access_token=$accessToken";
        $params = [
            'component_appid' => $componentAppId,
            'authorization_code' => $authCode
        ];
        $resultJson = Yii::$app->curl->post($url, JSON::encode($params));
        LogUtil::info(['url' => $url, 'params' => $params, 'response' => $resultJson], 'channel');
        $result = Json::decode($resultJson, true);

        if (empty($result['authorization_info'])) {
            throw new ApiDataException('POST ' . $url, $result, $params);
        }

        if (empty($result['authorization_info']['authorizer_access_token'])) {
            throw new WechatUnauthException;
        }

        return [
            'authorizerAccessToken' => $result['authorization_info']['authorizer_access_token'],
            'authorizerRefreshToken' => $result['authorization_info']['authorizer_refresh_token'],
            'authorizerAppId' => $result['authorization_info']['authorizer_appid']
        ];
    }

    /**
     * Get the authorizer's information
     * @param $componentAppId, the component appid.
     * @param $authorizerAppId, the authorizer's appid
     * @return array, the authorizer's information
     * @author Devin.Jin
     **/
    public function getAuthorizerInfo($accessToken, $componentAppId, $authorizerAppId)
    {
        $url = $this->wechatDomain . "/cgi-bin/component/api_get_authorizer_info?component_access_token=$accessToken";
        $params = [
            'component_appid' => $componentAppId,
            'authorizer_appid' => $authorizerAppId
        ];
        $resultJson = Yii::$app->curl->post($url, JSON::encode($params));
        LogUtil::info(['url' => $url, 'params' => $params, 'response' => $resultJson], 'channel');
        $result = Json::decode($resultJson, true);


        if (empty($result['authorizer_info'])) {
            throw new ApiDataException('POST ' . $url, $resultJson, $params);
        } else {
            return [
                'nickname' => $result['authorizer_info']['nick_name'],
                'headImg' => empty($result['authorizer_info']['head_img']) ? '' : $result['authorizer_info']['head_img'],
                'type' => $result['authorizer_info']['service_type_info']['id'],
                'appid' => $result['authorization_info']['authorizer_appid'],
                'wechatId' =>  $result['authorizer_info']['user_name'],
                'verified' => $result['authorizer_info']['verify_type_info']['id'] != '-1'
            ];
        }
    }

    /**
     * Get follower's openId by wechat code
     * @param string $code
     * @param string $appId
     * @param boolean $wechatTestAppSecret wechat test account can pass in appSecret directly
     * @throws ApiDataException
     * @return Array, accesstoken info
     * example:
     * {
     *    "access_token":"ACCESS_TOKEN",
     *    "expires_in":7200,
     *    "refresh_token":"REFRESH_TOKEN",
     *    "openid":"OPENID",
     *    "scope":"SCOPE"
     * }
     */
    public function getOpenId($code, $appId, $wechatTestAppSecret = null)
    {
        $params = [
            'appId' => $appId,
            'code' => $code
        ];

        if (empty($wechatTestAppSecret)) {
            $component = self::getComponentToken();
            $componentToken = $component['componentToken'];
            $componentAppId = $component['componentAppId'];

            $params=array_merge($params, [
                'componentToken' => $componentToken,
                'componentAppId' => $componentAppId,
            ]);

            $url = $this->wechatDomain . "/sns/oauth2/component/access_token?appid=$appId&code=$code&grant_type=authorization_code&component_appid=$componentAppId&component_access_token=$componentToken";
        } else {
            $url = $this->wechatDomain . "/sns/oauth2/access_token?appid=$appId&secret=$wechatTestAppSecret&code=$code&grant_type=authorization_code";
        }

        $resultJson = Yii::$app->curl->get($url);

        LogUtil::info(['url' => $url, 'params' => $params, 'response' => $resultJson], 'member');
        $result = Json::decode($resultJson, true);

        if (empty($result['openid']) || empty($result['access_token'])) {
            throw new ApiDataException('get ' . $url, $resultJson, $params);
        } else {
            return $result;
        }
    }

    public function refreshAccesstoken($refreshtoken, $appId)
    {
        $component = self::getComponentToken();
        $componentToken = $component['componentToken'];
        $componentAppId = $component['componentAppId'];

        $url = $this->wechatDomain . "/sns/oauth2/component/refresh_token?appid=$appId&grant_type=refresh_token&component_appid=$componentAppId&component_access_token=$componentToken&refresh_token=$refreshtoken";

        $resultJson = Yii::$app->curl->get($url);

        LogUtil::info(['url' => $url, 'response' => $resultJson], 'member');
        $result = Json::decode($resultJson, true);

        if (empty($result['openid']) || empty($result['refresh_token'])) {
            throw new ApiDataException('get ' . $url, $resultJson, null, 'member');
        } else {
            return $result;
        }
    }

    public function getUserInfo($accesstoken, $openId)
    {
        $url = $this->wechatDomain . "/sns/userinfo?access_token=$accesstoken&openid=$openId&lang=zh_CN";

        $resultJson = Yii::$app->curl->get($url);

        LogUtil::info(['url' => $url, 'response' => $resultJson], 'member');
        $info = Json::decode($resultJson, true);

        if (!empty($info['openid'])) {
            $result = $info;
            $result['headerImgUrl'] = empty($info['headimgurl']) ? '' : $info['headimgurl'];

            return $result;
        } else {
            throw new ApiDataException('get ' . $url, $resultJson, null, 'member');
        }
    }

    private function _transferUserCondition($follower)
    {
        if ($follower['sex'] == self::TYPE_MALE) {
            $sex = self::MALE;
        } else if ($follower['sex'] == self::TYPE_FEMALE) {
            $sex = self::FEMALE;
        } else {
            $sex = self::UNKNOWN;
        }
        $params = [
            'originId' => $follower['openid'],
            'nickname' => $follower['nickname'],
            'gender' => $sex,
            'city' => $follower['city'],
            'province' => $follower['province'],
            'country' => $follower['country'],
            'headerImgUrl' => $follower['headimgurl'],
            'unionId' => $follower['unionid']
        ];
        return $params;
    }

    /**
     * Add user information to weconnect.
     * @param string $accountId
     * @param Object $follower
     * @return Array setting info
     */
    public function setUserInfo($accountId, $follower)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/users";
        $params = $this->_transferUserCondition($follower);
        $resultJson = $this->_curl(self::METHOD_POST, $url, 'member', $params);
        LogUtil::info(['url' => 'POST ' . $url, 'request' => $params, 'response' => $resultJson], 'member');
        return $resultJson;
    }

    /**
     * Update customer service setting
     * @param string $accountId
     * @param string $userId
     * @param string $sessionExpiresIn millisecond
     * @throws ApiDataException
     * @return Array setting info
     */
    public function updateCustomerServiceSetting($accountId, $sessionExpiresIn, $accesstoken)
    {
        $params = [
            'status' => 'ENABLE',
            'sessionExpiresIn' => $sessionExpiresIn,
            'accessToken' => $accesstoken
        ];

        $url = $this->weconnectDomain . "/accounts/$accountId/customerServiceMessages/settings";
        return $this->_curl(self::METHOD_POST, $url, 'helpdesk', $params, false);
    }

    /**
     * Get customer service setting
     * @throws ApiDataException
     * @return Array setting info
     */
    public function getCustomerServiceSetting()
    {
        $url = $this->weconnectDomain . '/customerServiceMessages/settings';
        return $this->_curl(self::METHOD_GET, $url, 'helpdesk');
    }

    /**
     * Send customer service message to end user on WeChat via WeConnect api
     * @param  string $userId    id for the end user.
     * @param  string $accountId the wechat accountId.
     * @param  array $message    see http://git.augmentum.com.cn/scrm/we-connect/blob/develop/docs/customer_service_api.md#weconnect-客服交互消息的格式
     * @return boolean           true for success
     * @throws ApiDataException
     * @author Devin Jin
     */
    public function sendCustomerServiceMessage($userId, $accountId, $message)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/customerServiceMessages/user/$userId";
        $formatedMsg = $this->_formatMsgRequest($message['msgType'], $message['content'], isset($message['createTime']) ? $message['createTime'] : '', $message['customerServiceAccount']);
        return $this->_curl(self::METHOD_POST, $url, 'helpdesk', $formatedMsg, false);
    }

    /**
     * Get qrcode list
     * @param string $accountId
     * @param array $conditions
     * @throws ApiDataException
     * @return Array qrcode list
     */
    public function getQrcodes($accountId, $conditions)
    {
        $conditions = $this->_transferOrderCondition($conditions);
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/qrcodes';
        $result = $this->_curl(self::METHOD_GET, $url, 'channel', $conditions);

        if (!array_key_exists('results', $result)) {
            $result['results'] = [];
        }

        //format result about replayMessage
        foreach ($result['results'] as &$qrcode) {
            if (!empty($qrcode['replyMessage'])) {
                $replyMessage = $this->_formatMsgResponse($qrcode['replyMessage']);
                if ($replyMessage['msgType']) {
                    $qrcode['msgType'] = $replyMessage['msgType'];
                    $qrcode['content'] = $replyMessage['content'];
                    unset($qrcode['replyMessage']);
                }
            }
        }
        return $result;
    }

    /**
     * Get qrcode by id
     * @param string $accountId
     * @param string $qrcodeId
     * @throws ApiDataException
     * @return Array qrcode info
     */
    public function getQrcode($accountId, $qrcodeId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/qrcodes/' . $qrcodeId;
        $qrcode = $this->_curl(self::METHOD_GET, $url, 'channel');

        //format qrcode about replay message
        if (!empty($qrcode['replyMessage'])) {
            $replyMessage = $this->_formatMsgResponse($qrcode['replyMessage']);
            if ($replyMessage['msgType']) {
                $qrcode['msgType'] = $replyMessage['msgType'];
                $qrcode['content'] = $replyMessage['content'];
                unset($qrcode['replyMessage']);
            }
        }

        return $qrcode;
    }

    /**
     * This method is provide to create a qrcode.
     * @param string, $accountId
     * @param array, $qrcode
     * @return array: qrcode info
     */
    public function createQrcode($accountId, $qrcode)
    {
        //format replay message
        if (!empty($qrcode['msgType']) && !empty($qrcode['content'])) {
            $qrcode['replyMessage'] = $this->_formatMsgRequest($qrcode['msgType'], $qrcode['content']);
            //useless property
            unset($qrcode['msgType'], $qrcode['content']);
        }

        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/qrcodes';

        if (!empty($qrcode['optionSceneStr'])) {
            $url = $this->weconnectDomain . '/accounts/' . $accountId . '/qrcodes?optionSceneStr=true';
            unset($qrcode['optionSceneStr']);
        }

        $qrcode = $this->_a2j($qrcode);

        $resultJson = Yii::$app->curl->postJson($url, $qrcode);

        LogUtil::info(['url' => 'POST ' . $url, 'response' => $resultJson, 'param' => $qrcode], 'channel');
        $result = Json::decode($resultJson, true);

        if ($result && isset($result['code']) && 200 == $result['code']  && isset($result['data'])) {
            return $result['data'];
        } else if ($result && isset($result['code']) && 412 == $result['code']) {
            throw new InvalidParameterException(['qrcodeName' => Yii::t('channel', 'qrcode_name_exist')]);
        } else {
            throw new ApiDataException('POST ' . $url, $resultJson, $qrcode);
        }
    }

    /**
     * This method is provide to create a qrcode.
     * @param string, $accountId
     * @param string, $qrcodeId
     * @param array, $qrcode
     * @return array: qrcode info
     */
    public function updateQrcode($accountId, $qrcodeId, $qrcode)
    {
        //format replay message
        if (!empty($qrcode['msgType']) && !empty($qrcode['content'])) {
            $qrcode['replyMessage'] = $this->_formatMsgRequest($qrcode['msgType'], $qrcode['content']);
            unset($qrcode['msgType'], $qrcode['content']);//useless property
        }
        $qrcode = $this->_a2j($qrcode);

        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/qrcodes/' . $qrcodeId;
        $resultJson = Yii::$app->curl->putJson($url, $qrcode);

        LogUtil::info(['url' => 'put ' . $url, 'response' => $resultJson, 'param' => $qrcode], 'channel');
        $result = Json::decode($resultJson, true);

        if ($result && isset($result['code']) && 200 == $result['code']  && isset($result['data'])) {
            return $result['data'];
        } else if ($result && isset($result['code']) && 412 == $result['code']) {
            throw new ApiDataException('POST ' . $url, Yii::t('channel', 'qrcode_name_exist'), $qrcode);
        } else {
            throw new ApiDataException('POST ' . $url, $resultJson, $qrcode);
        }
    }

    /**
     * This method is provide to create a qrcode.
     * @param string, $accountId
     * @param string, $qrcodeId
     * @return boolean
     */
    public function deleteQrcode($accountId, $qrcodeId)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/qrcodes/' . $qrcodeId;
        return $this->_curl(self::METHOD_DELETE, $url, 'channel');
    }

    /**
     * This method is provide to get a qrcode statistics information
     * @param string, $accountId
     * @param string, $qrcodeId
     * @return array: qrcode statistics info
     */
    public function getQrcodeStatistics($accountId, $qrcodeId, $startDate, $endDate)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/statistics/qrcodes/' . $qrcodeId. '/detail';
        $urlParams = [];
        $startDate && array_push($urlParams, 'startDate=' . $startDate);
        $endDate && array_push($urlParams, 'endDate=' . $endDate);
        if (!empty($urlParams)) {
            $url = $url . '?' . implode('&', $urlParams);
        }
        $resultJson = Yii::$app->curl->get($url);
        LogUtil::info(['url' => 'POST' . $url, 'response' => $resultJson, 'qrcodeId' => $qrcodeId], 'channel');
        $result = Json::decode($resultJson, true);
        if ($result && isset($result['code']) && (200 == $result['code'] || 204 == $result['code'])) {
            $statistics = $result['data'];
            return $statistics;
        } else {
            throw new ApiDataException('GET ' . $url, $resultJson, $qrcodeId);
        }
    }

    /**
     * Get api authorizer token
     * @param string    $accountId
     * @return string
     */
    public function getAuthorizerToken($accountId, $validation = false)
    {
        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/refreshToken';
        $resultJson = Yii::$app->curl->setHeaders(['Content-Type: application/json'])->post($url, ['validation' => $validation]);

        LogUtil::info(['url' => 'POST' . $url, 'response' => $resultJson, 'channelId' => $accountId], 'channel');
        $result = Json::decode($resultJson, true);

        if ($result && isset($result['code']) && 200 == $result['code'] && isset($result['data'])) {
            return $result['data'];
        } else {
            throw new ApiDataException('GET ' . $url, $resultJson);
        }
    }

    /**
     * Add stores
     * @param array $stores Store objects array
     */
    public function addStore($accountId, $stores)
    {
        $data = ['location_list' => $stores];
        $url = $this->weconnectDomain . "/accounts/$accountId/wechat/stores";
        $resultJson = $this->_curl(self::METHOD_POST, $url, 'store', $data);
        LogUtil::info(['url' => 'POST ' . $url, 'response' => $resultJson, 'data' => $data], 'store');
        return $resultJson;
    }

    /**
     * get stores
     * @param string $accountId     the wechat channel id
     * @param array  $sizeCondition the offset and count
     */
    public function getStores($accountId, $sizeCondition)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/wechat/stores";
        $resultJson = $this->_curl(self::METHOD_GET, $url, 'store', $sizeCondition);
        LogUtil::info(['url' => 'GET ' . $url, 'response' => $resultJson, 'data' => $sizeCondition], 'store');
        return $resultJson;
    }

    /**
     * Transfer order condition for wechat API
     * @param array the query condition got from request
     * @return array, formatted query condition for requsting we connect
     */
    private function _transferOrderCondition($query)
    {
        if (isset($query['orderby'])) {
            $orderBy = $query['orderby'];
            unset($query['orderby']);
            if (StringUtil::isJson($orderBy)) {
                $orderBy = Json::decode($orderBy, true);

                foreach ($orderBy as $key => $value) {
                    if ($value === 'asc') {
                        $query['orderBy'] = $key;
                        $query['ordering'] = 'ASC';
                    } else {
                        $query['orderBy'] = $key;
                        $query['ordering'] = 'DESC';
                    }
                }
            } else {
                $query['orderBy'] = $orderBy;
                $query['ordering'] = 'DESC';
            }
        }
        return $query;
    }

    /**
     * Transfer order condition for wechat API
     * @param array the query condition got from request
     * @return array, formatted query condition for requsting we connect
     */
    private function _transferPaginationCondition($query)
    {
        unset($query['channelId']);
        $query['pageSize'] = isset($query['per-page']) ? $query['per-page'] : '';
        $query['pageNum'] = isset($query['page']) ? $query['page'] : '';
        unset($query['per-page']);
        unset($query['page']);
        return $query;
    }

    /**
     * Get qrcode key indicator statistics
     * @param string $accountId
     * @param string $id
     * @throws ApiDataException
     * @return array
     */
    public function getQrcodekeyIndicator($accountId, $id)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/qrcodes/$id/keyIndicator";
        $result = $this->_curl(self::METHOD_GET, $url, 'channel');

        return $result;
    }

    /**
     * Get qrcode key
     * @param string $accountId
     * @param string $id
     * @param array $dateCondition
     * @throws ApiDataException
     * @return array
     */
    public function getQrcodeTimeSeries($accountId, $id, $dateCondition)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/qrcodes/$id/timeSeries";
        $result = $this->_curl(self::METHOD_GET, $url, 'channel', $dateCondition);

        return $result;
    }

    /**
     * Get users growth statistics
     * @param string $accountId
     * @param array $dateCondition, ['startDate'=>'','endDate'=>'']
     * @throws ApiDataException
     * @return array
     */
    public function getUsersGrowthStatistics($accountId, $dateCondition = [])
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/users/growth/detail";

        $result = $this->_curl(self::METHOD_GET, $url, 'admin', $dateCondition);

        //format date
        if (!empty($result)) {
            $result['refDate'] = $this->_formatTime($result['refDate'], 'Y-m-d');
        } else {
            $tempResult = [];
            empty($result) ? $result = [] : '';
            foreach ($result as $item) {
                $item['refDate'] = $this->_formatTime($item['refDate'], 'Y-m-d');
                $tempResult[] = $item;
            }
            $result = $tempResult;
        }
        return $result;
    }

    /**
     * Get users growth statistics by yesterday
     * @param string $accountId
     * @throws ApiDataException
     * @return array
     */
    public function getUsersGrowthStatisticsByYesterday($accountId)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/users/growth/keyIndicator";

        $result = $this->_curl(self::METHOD_GET, $url, 'admin');

        //format date
        if (!empty($result)) {
            $result['refDate'] = $this->_formatTime($result['refDate'], 'Y-m-d');
        }
        return $result;
    }

    /**
     * Get users growth statistics list
     * @param  String $accountId
     * @param  array $condition ['startDate'=>'1419782400000', 'endDate' => '1423368000000', 'type'=>'', 'subType'=>'']
     * @throws ApiDataException
     * @return array
     */
    public function getFollowersGrowthStatistics($accountId, $condition)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/users/growth/timeSeries";

        return $this->_curl(self::METHOD_GET, $url, 'admin', $condition);
    }

    /**
     * Get mass articles statistics list
     * @param string $accountId
     * @param array $conditions
     * @throws ApiDataException
     * @return array
     */
    public function searchMassArticlesStatistics($accountId, $conditions)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/mpnews/sent";

        $result = $this->_curl(self::METHOD_GET, $url, 'admin', $conditions);

        //format date
        $items = [];
        foreach ($result['results'] as $item) {
            $item['sentDate'] = $this->_formatTime($item['sentDate'], 'Y-m-d');
            $dailyStatistics = [];
            if (isset($item['dailyStatistics'])) {
                foreach ($item['dailyStatistics'] as $daily) {
                    $daily['refDate'] = $this->_formatTime($daily['refDate'], 'Y-m-d');
                    $dailyStatistics[] = $daily;
                }
            }
            $item['dailyStatistics'] = $dailyStatistics;
            $items[] = $item;
            $result['results'] = $items;
        }
        return $result;
    }

    /**
     * Get mass ariticles statistics detail
     * @param string $accountId
     * @param string $interval, yesterday
     * @param array $dateCondition, ['refDate'=>'']
     * @throws ApiDataException
     * @return unknown
     */
    public function getMassArticlesStatistics($accountId, $interval, $dateCondition)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/mpnews/summary/keyIndicator";

        if ($interval == 'yesterday') {
            $dateCondition = [];
        }

        $result = $this->_curl(self::METHOD_GET, $url, 'admin', $dateCondition);

        //format date
        if (!empty($result)) {
            if ($interval == 'yesterday') {
                $result['refDate'] = $this->_formatTime($result['refDate'], 'Y-m-d');
            } else {
                $tempResult = [];
                foreach ($result as $item) {
                    $item['refDate'] = $this->_formatTime($item['refDate'], 'Y-m-d');
                    $tempResult[] = $item;
                }
                $result = $tempResult;
            }
        }
        return $result;
    }

    /**
     * Get mass ariticles statistics detail by time series
     * @param string $accountId
     * @param array $conditions
     * @throws ApiDataException
     * @return unknown
     */
    public function getNpnewsStatisticsByDate($accountId, $conditions)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/mpnews/summary/timeSeries";
        return $this->_curl(self::METHOD_GET, $url, 'admin', $conditions);
    }

    /**
     * Get users property by property
     * @param string $accountId
     * @param array $conditions
     * @param string $property, 'gender', 'language', 'subscribeSource'
     * @throws ApiDataException
     * @return unknown
     */
    public function getUsersByProperty($accountId, $conditions, $property)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/users/property/" . $property;
        return $this->_curl(self::METHOD_GET, $url, 'admin', $conditions);
    }

    /**
     * Get users property by location
     * @param string $accountId
     * @param array $conditions
     * @param string $location, 'province' or 'city'
     * @throws ApiDataException
     * @return unknown
     */
    public function getUsersByLocation($accountId, $conditions, $location)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/users/property/" . $location;
        return $this->_curl(self::METHOD_GET, $url, 'admin', $conditions);
    }

    /**
     * Get weibo status summary
     * @param strong $accountId
     * @param array $dateCondition, ['refDate'=>'']
     * @throws ApiDataException
     * @return array
     */
    public function getStatusSummary($accountId, $dateCondition = [])
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/statuses/keyIndicator";
        return $this->_curl(self::METHOD_GET, $url, 'admin', $dateCondition);
    }

    /**
     * Get weibo status daily statistics
     * @param strong $accountId
     * @param array $Condition, ['startDate'=>'', 'endDate'=>'', 'type'=>'']
     * @throws ApiDataException
     * @return array
     */
    public function getDailyStatus($accountId, $condition = [])
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/statuses/timeSeries";
        return $this->_curl(self::METHOD_GET, $url, 'admin', $condition);
    }

    /**
     * Update weibo fans service token to make weibo access
     * @param string $accountId
     * @param string $fansServiceToken
     * @return array
     */
    public function updateWeiboFansServiceToken($accountId, $fansServiceToken)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/wbFansServiceToken";
        $params = ['fansServiceToken' => $fansServiceToken];

        return $this->_curl(self::METHOD_PUT, $url, 'management', $params);
    }

    public function getTagStats($accountIds, $tags)
    {
        $url = $this->weconnectDomain . '/users/countTags';
        $params = ['accountIds' => $accountIds, 'tags' => $tags];

        return $this->_curl(self::METHOD_POST, $url, 'common', $params);
    }

    public function deleteTag($accountIds, $tag)
    {
        $url = $this->weconnectDomain . '/users/removeTag';
        $params = ['accountIds' => $accountIds, 'tag' => $tag];

        return $this->_curl(self::METHOD_POST, $url, 'common', $params);
    }

    public function renameTag($accountIds, $tag, $newName)
    {
        $url = $this->weconnectDomain . '/users/renameTag';
        $params = [
            'tag' => $tag,
            'newName' => $newName,
            'accountIds' => $accountIds,
        ];

        return $this->_curl(self::METHOD_POST, $url, 'common', $params);
    }

    /**
     * send template message
     */
    public function sendTemplateMessage($accountId, $scheduleTime, $userQuery, $templateMessage)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/templateMessages";
        $message = [];
        if (!empty($scheduleTime)) {
            $message['scheduleTime'] = $scheduleTime;
        }
        if (!empty($userQuery)) {
            $message['userQuery'] = $userQuery;
        }

        if (!empty($templateMessage)) {
            $message['templateMessage'] = $templateMessage;
        }
        return $this->_curl(self::METHOD_POST, $url, 'message', $message);
    }

    /**
     * Set template message mp industry
     * @param string $accountId The wechat id
     * @param string $industry_id1 template message mp industry code
     * @param string $industry_id2 template message mp industry code
     * @return
     *         {
     *             "code": 200,
     *             "message": "OK",
     *             "data": null
     *         }
     */
    public function setTemplateMessageMpIndustry($accountId, $industryId1, $industryId2)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/templateMessages/setIndustry";
        $params['industryId1'] = $industryId1;
        $params['industryId2'] = $industryId2;
        return $this->_curl(self::METHOD_POST, $url, 'message', $params);
    }

    /**
     * Get template id
     * @param string $accountId The wechat id
     * @param string $templateIdShort
     * @return
     *     {
     *          "code": 200,
     *          "message": "OK",
     *          "data": "908qmydkfOJS84h5TX2HDu36y9GoyK3UTGvqreu9Htc"
     *     }
     */
    public function getTemplateId($accountId, $templateIdShort)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/templateMessages/templateId/$templateIdShort";
        return $this->_curl(self::METHOD_GET, $url, 'message');
    }

    /**
     * This function is provide to get alipay openId
     * @param $channelId, string
     * @param $authCode, string
     */
    public function getAlipayOpenId($channelId, $authCode)
    {
        $url = $this->weconnectDomain . "/accounts/$channelId/users/fetchUserOriginId/baseAuthCode/$authCode";
        return $this->_curl(self::METHOD_POST, $url, 'alipay');
    }

    /**
     * THis function is provide to get user info from alipay
     * @param $channelId, string
     * @param $authCode, string
     */
    public function getAlipayUserInfo($channelId, $authCode)
    {
        $url = $this->weconnectDomain . "/accounts/$channelId/users/fetchUser/userinfoAuthCode/$authCode";
        return $this->_curl(self::METHOD_POST, $url, 'alipay');
    }

    /**
     * This function is provie to format message to request WeConnect
     * @param string, $msgType
     * @param array/string $content
     * @throws ServerErrorHttpException
     * @return array, message info
     */
    private function _formatMsgRequest($msgType, $content, $createTime = '', $customerServiceAccount = '')
    {
        $result['msgType'] = $msgType;
        if (!empty($createTime)) {
            $result['createTime'] = $createTime;
        }
        if (!empty($customerServiceAccount)) {
            $result['customerServiceAccount'] = $customerServiceAccount;
        }

        if ($msgType == self::MESSAGE_TEXT || $msgType == self::MESSAGE_EVENT) {
            $result['content'] = $content;
        } else if ($msgType == self::MESSAGE_NEWS) {
            $articles = $content['articles'];
            $count = count($articles);
            //replace picUrl with url
            for ($i = 0; $i < $count; $i++) {
                $content['articles'][$i]['url'] = $articles[$i]['picUrl'];
                unset($content['articles'][$i]['picUrl']);
            }
            $result['articles'] = $content['articles'];
        } else if ($msgType == self::MESSAGE_URL) {
            $result['url'] = $content;
        } else if ($msgType == self::MESSAGE_MPNEWS) {
            $count = count($content['articles']);
            for ($i = 0; $i < $count; $i++) {
                $content['articles'][$i]['url'] = $content['articles'][$i]['picUrl'];
                unset($content['articles'][$i]['picUrl']);
            }
            $result['articles'] = $content['articles'];
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'data_error'));
        }

        return $result;
    }

    /**
     * This function is provide to format WeConnect's response.
     * @param array, $msg: WeConnect's response message info
     * @return array, message info
     */
    private function _formatMsgResponse($msg)
    {
        $msgType = isset($msg['msgType']) ? $msg['msgType'] : '';
        if ($msgType == self::MESSAGE_TEXT) {
            return $msg;
        } else if ($msgType == self::MESSAGE_NEWS) {
            $articles = $msg['articles'];
            $count = count($articles);
            //replace url with picUrl
            for ($i = 0; $i < $count; $i++) {
                $msg['articles'][$i]['picUrl'] = $msg['articles'][$i]['url'];
                unset($msg['articles'][$i]['url']);
            }

            //add articles to content
            $result['content'] = ['articles' => $msg['articles']];
            $result['msgType'] = $msgType;
        } else if ($msgType == self::MESSAGE_URL) {
            //add url to content
            $result['content'] = $msg['url'];
            $result['msgType'] = $msgType;
        } else if ($msgType == self::MESSAGE_MPNEWS) {
            $articles = $msg['articles'];
            $count = count($articles);
            //replace url with picUrl
            for ($i = 0; $i < $count; $i++) {
                $msg['articles'][$i]['picUrl'] = $msg['articles'][$i]['url'];
                unset($msg['articles'][$i]['url']);
            }

            //add articles to content
            $result['content'] = ['articles' => $msg['articles']];
            $result['msgType'] = $msgType;
        } else {
            $result['msgType'] = false;
        }

        return $result;
    }

    /**
     * This function is provide to format time
     * @param $time, time stamp millisecond
     * @param $format, string
     * @return string
     */
    private function _formatTime($time, $format = 'Y-m-d H:i:s')
    {
        return $time ? TimeUtil::msTime2String($time, $format) : '';
    }

    private function _curl($method, $url, $logTarget, $params = [], $returnResultData = true, $exception = null)
    {
        if (empty($this->weconnectDomain)) {
            return [];
        }
        //format header and params for post and put
        if (in_array($method, [self::METHOD_POST, self::METHOD_PUT])) {
            $method = $method . 'Json';
            $params = $this->_a2j($params);
        }
        $resultJson = Yii::$app->curl->$method($url, $params);

        $logUrl = strtoupper($method) . ' ' . $url;
        if (StringUtil::isJson($resultJson)) {
            $result = Json::decode($resultJson, true);
        } else {
            throw new ApiDataException($logUrl, $resultJson, $params, $logTarget);
        }
        LogUtil::info(['url' => $logUrl, 'response' => $resultJson, 'params' => $params], $logTarget);

        if ($result && isset($result['code']) && 412 == $result['code'] && !empty($exception)) {
            LogUtil::error(['url' => $logUrl, 'response' => $resultJson, 'params' => $params, 'code' => LogUtil::WECONNECT_SERVER_ERROR], $logTarget);
            throw $exception;
        }

        if ($result && isset($result['code']) && (200 == $result['code'] || 204 == $result['code'])) {
            if ($returnResultData && $method != self::METHOD_DELETE) {
                return empty($result['data']) ? [] : $result['data'];
            } else {
                return true;
            }
        } else {
            throw new ApiDataException($logUrl, $resultJson, $params, $logTarget);
        }
    }

     /**
     * This function is provide to format time
     * @param $userId, string
     * @param $channelId, string
     * @return string
     */
    public function getFollowerById($userId, $accountId)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/users/$userId";
        return $this->_curl(self::METHOD_GET, $url, 'admin');
    }

    /**
     * This function is provide to get menus hits.
     * @param $userId, string
     * @return $accountId, string
     */
    public function statsMenusHits($userId, $accountId)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/statistics/users/$userId/profile";
        return $this->_curl(self::METHOD_GET, $url, 'member');
    }

    /**
     * This function is provide to get menus hits.
     * @param $accountId, string
     * @param $userIds, string
     * @return $condition, Array
     */
    public function statsMessage($accountId, $userId, $condition = [])
    {
        $condition = $this->_transferPaginationCondition($condition);
        unset($condition['openId']);

        $url = $this->weconnectDomain . '/accounts/' . $accountId . '/interactMessageHistories/user/' . $userId;
        $result = $this->_curl(self::METHOD_GET, $url, 'channel', $condition);

        return $result;
    }

    /**
     * This function is provided to update message type webhook rule.
     * @param $accountId, string
     * @param $type, string
     * @param $action, string
     * @return boolean
     */
    public function updateMsgWebhookRule($accountId, $type, $action)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/webhookRules/msgType/$type/$action";
        return $this->_curl(self::METHOD_POST, $url, 'channel');
    }

    /**
     * This function is provided to update event type webhook rule.
     * @param $accountId, string
     * @param $type, string
     * @param $action, string
     * @return boolean
     */
    public function updateEventWebhookRule($accountId, $type, $action)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/webhookRules/eventType/$type/$action";
        return $this->_curl(self::METHOD_POST, $url, 'channel');
    }

    /**
     * This function is provided to update evet type webhook rule.
     * @param $accountId, string
     * @return boolean
     */
    public function disableEventWebhookRules($accountId)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/webhookRules";
        $data = WebhookEvent::getWebhookRuleData(WebhookEvent::DISENABLE_RULE);
        return $this->_curl(self::METHOD_POST, $url, 'channel', $data);
    }

    /**
     * This function is provided to update evet type webhook rule.
     * @param $accountId, string
     * @return boolean
     */
    public function enableEventWebhookRules($accountId)
    {
        $url = $this->weconnectDomain . "/accounts/$accountId/webhookRules";
        $data = WebhookEvent::getWebhookRuleData(WebhookEvent::ENABLE_RULE);
        return $this->_curl(self::METHOD_POST, $url, 'channel', $data);
    }

    public function getWechatPaymentConfigedChannelId($accountId)
    {
        $url = $this->weconnectDomain . "/weixin/pay/configuration/quncrmAccountId/$accountId";
        $result = $this->_curl(self::METHOD_GET, $url, 'channel');
        $channelId = '';
        if (!empty($result['weconnectAccountId'])) {
            $channelId = $result['weconnectAccountId'];
        }
        return $channelId;
    }

    public function getWechatPaymentConfigedAppId($accountId)
    {
        $url = $this->weconnectDomain . "/weixin/pay/configuration/quncrmAccountId/$accountId";
        $result = $this->_curl(self::METHOD_GET, $url, 'channel');
        $appId = '';
        if (!empty($result['appId'])) {
            $appId = $result['appId'];
        }
        return $appId;
    }

    /**
     * This function is provided to open wechat payment.
     * @param $file, file
     * @return json
     */
    public function openWechatPayment($file)
    {
        $url = $this->weconnectDomain . "/weixin/pay/configuration";
        $resultJson = Yii::$app->curl->setHeaders(['content-type: multipart/form-data'])->post($url, $file);
        LogUtil::info(['url' => 'POST ' . $url, 'file' => $file, 'response' => $resultJson], 'channel');
        return $resultJson;
    }

    /**
     * This function is provided to get the message of wechat payment.
     * @param $accountId, string
     * @return json
     */
    public function getWechatPaymentMessage($accountId)
    {
        $url = $this->weconnectDomain . "/weixin/pay/configuration/quncrmAccountId/" . $accountId;
        $result = $this->_curl(self::METHOD_GET, $url, 'channel');
        LogUtil::info(['url' => 'GET ' . $url, 'response' => $result], 'channel');
        return $result;
    }

    /**
     * This function is provided to get the message of wechat payment.
     * @param $condition, Array
     * @return json
     */
    public function checkPayment($condition)
    {
        $url = $this->weconnectDomain . "/weixin/orders";
        $result = $this->_curl(self::METHOD_POST, $url, 'channel', $condition);
        LogUtil::info(['url' => 'POST ' . $url, 'response' => $result], 'channel');
        return $result;
    }

    /**
     * This function is provided to get the message of wechat payment.
     * @param $condition, Array
     * @return json
     */
    public function checkRefund($condition)
    {
        $url = $this->weconnectDomain . "/weixin/refunds";
        $result = $this->_curl(self::METHOD_POST, $url, 'channel', $condition);
        LogUtil::info(['url' => 'POST ' . $url, 'response' => $result], 'channel');
        return $result;
    }

    /**
     * This function is provided to get dingding corpToken.
     * @param string $suiteKey
     * @param string $corpId
     * @param string $appId
     * @return array
     * @author Rex Chen
     */
    public function getDDCorpToken($suiteKey, $corpId, $appId)
    {
        $url = $this->weconnectDomain . "/dingding/getCorpToken";
        $params = [
            'suiteKey' => $suiteKey,
            'cropId' => $corpId,
            'appId' => $appId,
        ];
        return $this->_curl(self::METHOD_POST, $url, 'dingding', $params);
    }

    public function getDDTokenByQunAccountId($qunAccountId)
    {
        $url = $this->weconnectDomain . "/dingding/getCorpToken";
        $params = [
            'accountId' => $qunAccountId,
        ];
        return $this->_curl(self::METHOD_POST, $url, 'dingding', $params);
    }


    public function buildPayOauthUrl($quncrmAccountId, $redirectUri, $state = '')
    {
        $url = $this->weconnectDomain . "/weixin/pay/oauth/authorize?quncrmAccountId=$quncrmAccountId&redirectUri=$redirectUri&state=$state";
        return $url;
    }

    public function getPayOauthOpenId($quncrmAccountId, $oauthCode)
    {
        $url = $this->weconnectDomain . "/weixin/pay/oauth/userInfo?quncrmAccountId=$quncrmAccountId&oauthCode=$oauthCode";
        $data =  $this->_curl(self::METHOD_GET, $url, 'channel');
        if (!empty($data)) {
            return $data['openid'];
        }
    }

    /**
     * This function is provided to get the user info of weixin oAuth enery.
     * @param string, $corpId
     * @param string, $code
     * @return json
     */
    public function getUserInfoByOAuth($corpId, $code)
    {
        $url = $this->weconnectDomain . "/wechatcp/corp/$corpId/userInfo";
        $result = $this->_curl(self::METHOD_GET, $url, 'wechatcp', ['code' => $code]);
        LogUtil::info(['url' => 'GET ' . $url, 'corpId' => $corpId, 'code' => $code, 'response' => $result], 'wechatcp');
        return $result;
    }

    /**
     * This function is provided to get the preOAuthCode.
     * @param string $suiteId
     * @return json
     */
    public function getPreOAuthCode($suiteId, $appIds)
    {
        $url = $this->weconnectDomain . "/wechatcp/suite/$suiteId/preOAuth";
        $result = $this->_curl(self::METHOD_GET, $url, 'wechatcp', ['appIds' => $appIds]);
        LogUtil::info(['url' => 'GET ' . $url, 'suiteId' => $suiteId, 'appIds' => $appIds, 'response' => $result], 'wechatcp');
        return $result;
    }

    /**
     * This function is provided to get oAuth corp info.
     * @param string, $suiteId
     * @param string, $authCode
     * @return json
     */
    public function getCorpInfoByOAuth($suiteId, $authCode)
    {
        $url = $this->weconnectDomain . "/wechatcp/suite/$suiteId/oauth";
        $result = $this->_curl(self::METHOD_GET, $url, 'wechatcp', ['authCode' => $authCode]);
        LogUtil::info(['url' => 'GET ' . $url, 'suiteId' => $suiteId, 'authCode' => $authCode, 'response' => $result], 'wechatcp');
        return $result;
    }

    /**
     * This function is provided to send messages to members.
     * @param string, $corpId
     * @param array, $message
     * @return json
     */
    public function sendWechatCpMessage($corpId, $message)
    {
        $url = $this->weconnectDomain . "/wechatcp/corp/$corpId/messageSend";
        $result = $this->_curl(self::METHOD_POST, $url, 'wechatcp', $message);
        LogUtil::info(['url' => 'POST ' . $url, 'corpId' => $corpId, 'message' => $message, 'response' => $result], 'wechatcp');
        return $result;
    }
}
