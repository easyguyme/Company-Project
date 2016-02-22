<?php
namespace console\modules\management\controllers;

use yii\console\Controller;
use backend\models\Account;
use backend\models\User;
use backend\utils\StringUtil;
use backend\models\SensitiveOperation;
use backend\models\MessageTemplate;
use Yii;
use \MongoId;
use backend\models\Channel;
use backend\modules\microsite\models\ArticleChannel;
use backend\modules\microsite\models\Page;
use backend\modules\microsite\models\PageComponent;
use backend\models\ServiceSetting;
use backend\modules\reservation\models\ReservationShelf;

/**
 * Scan the modules folder to get the public extensions
 **/
class AccountController extends Controller
{
    /**
     * save account menus and modules(add-menus-and-mods)
     */
    public function actionAddMenusAndMods()
    {
        $accounts = Account::findAll([]);
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $enabledMods = $account->enabledMods;
                if (!empty($enabledMods)) {
                    $result = Yii::$app->extModule->getMergedConfig($enabledMods);
                    $account->menus = $result['menus'];
                    $account->mods = $result['mods'];
                    if ($account->save(true, ['menus', 'mods'])) {
                        echo 'Save account menus and mods successfully with account id ' . $account->_id . "\n";
                    } else {
                        echo 'Fail to save account menus and mods with account id  ' . $account->_id . "\n";
                    }
                }
            }
        }
    }

    /**
     * remove account modules
     */
    public function actionRemoveMods($accountId, $modsStr)
    {
        $account = Account::findByPK(new \MongoId($accountId));
        $mods = split(',', $modsStr);
        $accountMods = [];
        $oldMods = $account->mods;
        foreach ($oldMods as $oldMod) {
            if (!in_array($oldMod['name'], $mods)) {
                $accountMods[] = $oldMod;
            }
        }
        $accoundMenus = [];
        foreach ($account->menus as $mod => $menus) {
            if (!in_array($mod, $mods)) {
                $accountMenus[$mod] = $menus;
            }
        }
        $account->menus = $accountMenus;
        $account->mods = $accountMods;
        if ($account->save()) {
            echo 'Remove account mods successfully with account id ' . $account->_id . "\n";
        } else {
            echo 'Fail to remove account mods with account id  ' . $account->_id . "\n";
        }
    }

    /**
     * remove account menu
     */
    public function actionRemoveMenus($accountId, $menusStr)
    {
        $account = Account::findByPK(new \MongoId($accountId));
        $menus = split(',', $menusStr);
        $menusMap = [];
        foreach ($menus as $menu) {
            list($mod, $menuName) = split('-', $menu);
            if (empty($menusMap[$mod])) {
                $menusMap[$mod] = [];
            }
            $menusMap[$mod][] = $menuName;
        }
        $accountMenus = [];
        $oldMenus = $account->menus;
        foreach ($oldMenus as $mod => $oldMenuItems) {
            $accountMenus[$mod] = [];
            foreach ($oldMenuItems as $menuItem) {
                if (empty($menusMap[$mod]) || !in_array($menuItem['name'], $menusMap[$mod])) {
                    $accountMenus[$mod][] = $menuItem;
                }
            }
        }
        $account->menus = $accountMenus;
        if ($account->save()) {
            echo 'Remove account menus successfully with account id ' . $account->_id . "\n";
        } else {
            echo 'Fail to remove account menus with account id  ' . $account->_id . "\n";
        }
    }

    /**
     * Operate the enabled mods of account
     */
    public function actionOperateEnabledMods($operator, $modsStr, $accountIds = null)
    {
        $mods = split(',', $modsStr);
        $condition = [];
        if (!empty($accountIds)) {
            $ids = split(',', $accountIds);
            $mongoIds = [];
            foreach ($ids as $id) {
                $mongoIds[] = new \MongoId($id);
            }
            $condition['_id'] = ['$in' => $mongoIds];
        }
        $accounts = Account::find()->where($condition)->all();
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $oldEnabledMods = $account->enabledMods;
                if ('add' === $operator) {
                    $newEnabledMods = array_merge($oldEnabledMods, $mods);
                } else if ('remove' === $operator) {
                    $newEnabledMods = array_diff($oldEnabledMods, $mods);
                }
                $account->enabledMods = array_unique($newEnabledMods);
                if ($account->save()) {
                    echo ucwords($operator) . ' account enabled mods successfully with account id ' . $account->_id . "\n";
                } else {
                    echo 'Fail to ' . $operator . ' add account enabled mods with account id  ' . $account->_id . "\n" . $account->getErrors() . "\n";
                }
            }
        }
    }

    /**
     * add account service time(add-service-start-time)
     */
    public function actionAddServiceStartTime()
    {
        $accounts = Account::findAll([]);
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                if (empty($account->serviceStartAt) && $account->status === Account::STATUS_ACTIVATED) {
                    $account->serviceStartAt = $account->createdAt;
                    if ($account->save()) {
                        echo 'Init account\'s service start time successfully, account id is ' . $account->_id . PHP_EOL;
                    } else {
                        echo 'Fail to init account\'s service start time, account id is ' . $account->_id . PHP_EOL;
                    }
                }
            }
        }
    }

    /**
     * migrate account channel(channel-migration)
     */
    public function actionChannelMigration()
    {
        $accounts = Account::findAll([]);
        $channels = [];
        foreach ($accounts as $account) {
            $channelIds = $account->channels;
            $testWechat = empty($channelIds['testWechat']) ? [] : $channelIds['testWechat'];
            $channelIds = array_merge($channelIds['wechat'], $channelIds['weibo'], $testWechat);
            if (empty($channelIds)) {
                continue;
            }
            try {
                $weChannels = Yii::$app->weConnect->getAccounts($channelIds);
            } catch (\Exception $e) {
                echo 'error channel' . json_encode($channelIds) . PHP_EOL;
                continue;
            }
            foreach ($weChannels as $weChannel) {
                $channelOrigin = $weChannel['channel'];
                switch ($channelOrigin) {
                    case 'WEIXIN':
                        $origin = Channel::WECHAT;
                        break;
                    case 'WEIBO':
                        $origin = Channel::WEIBO;
                        break;
                    case 'ALIPAY':
                        $origin = Channel::ALIPAY;
                        break;
                    default:
                        $origin = strtolower($weChannel['channel']);
                        echo $weChannel['id'] . ' ' . $origin . PHP_EOL;
                        break;
                }
                $type = empty($weChannel['accountType']) ? '' : $weChannel['accountType'];
                $channel = Channel::getByAccountAndChannelId($account->_id, $weChannel['id']);
                $isTest = ($origin == Channel::WECHAT && !empty($weChannel['appSecret'])) ? true : false;
                if (!empty($channel)) {
                    $channel->origin = $origin;
                    $channel->name = $weChannel['name'];
                    $channel->type = $type;
                    $channel->status = strtolower($weChannel['status']);
                    $channel->isTest = $isTest;
                    if (!$channel->save()) {
                        echo 'update-' . $weChannel['id'] . ' ' . $origin . PHP_EOL;
                    }
                } else {
                    $channels[] = [
                        'channelId' => $weChannel['id'],
                        'origin' => $origin,
                        'name' => $weChannel['name'],
                        'type' => $type,
                        'status' => strtolower($weChannel['status']),
                        'isTest' => $isTest,
                        'accountId' => $account->_id
                    ];
                }
            }
        }

        if (Channel::batchInsert($channels)) {
            echo 'success' . PHP_EOL;
        } else {
            echo 'fail' . PHP_EOL;
        }
    }

    /**
     * create a user by email(generate-by-email)
     */
    public function actionGenerateByEmail($email)
    {
        $email = mb_strtolower($email);
        $user = User::getByEmail($email);
        if (!empty($user)) {
            echo 'email is used' . PHP_EOL;
            return;
        }

        $name = Yii::$app->params['defaultName'];
        $accountId = Account::create('', '', $name);
        $attributes = [
            'status' => Account::STATUS_ACTIVATED,
            'serviceStartAt' => new \MongoDate(),
        ];
        Account::updateAll($attributes, ['_id' => $accountId]);

        $salt = StringUtil::rndString(6);
        $password = User::encryptPassword(md5(Yii::$app->params['defaultPwd']), $salt);

        $user = new User();
        $user->email = $email;
        $user->accountId = $accountId;
        $user->name = $name;
        $user->role = User::ROLE_ADMIN;
        $user->isActivated = User::ACTIVATED;
        $user->avatar = Yii::$app->params['defaultAvatar'];
        $user->language = Yii::$app->params['defaultLanguage'];
        $user->salt = $salt;
        $user->password = $password;

        if (!$user->save()) {
            Account::deleteAll(['_id' => $accountId]);
            SensitiveOperation::deleteAll(['accountId' => $accountId]);
            MessageTemplate::deleteAll(['accountId' => $accountId]);
            echo 'create account fail' . PHP_EOL;
        } else {
            echo 'create account successfully' . PHP_EOL;
        }
    }

    /**
     * create default channel base on the account id(create-default-channel)
     * @param $accountId, string; if this value is all,it will support all accounts,otherwise it only support this account
     */
    public function actionCreateDefaultChannel($accountId)
    {
        $where = ['enabledMods' => ['$all' => ['microsite']]];
        if (empty($accountId)) {
            echo 'AccountId can not be empty' . PHP_EOL;
            exit();
        } elseif ($accountId == 'all') {
            $accounts = Account::findAll($where);
            if (!empty($accounts)) {
                foreach ($accounts as $account) {
                    $this->_createDefaultChannel($account->_id);
                }
            }
        } else {
            $accountId = new MongoId($accountId);
            $account = Account::findOne(array_merge(['_id' => $accountId], $where));
            if (empty($account)) {
                echo 'Can not find the account by ' . $accountId . PHP_EOL;
                exit();
            }
            $this->_createDefaultChannel($accountId);
        }
        echo 'Create default value successfully' . PHP_EOL;
    }

    private function _createDefaultChannel($accountId)
    {
        $defaultChannel = ArticleChannel::getDefault($accountId);

        if (empty($defaultChannel)) {
            $channel = new ArticleChannel;
            $channel->name = 'default_channel';
            $channel->fields = [];
            $channel->isDefault = true;
            $channel->accountId = $accountId;

            if (!$channel->save()) {
                echo $channel->getErrors() . PHP_EOL;
                exit();
            }
        } else {
            echo $accountId . 'data is exists' . PHP_EOL;
        }
    }

    /**
     * create default page cover base on account id and host info(域名)(create-default-page-cover)
     * @param $accountId, string; if this value is all,it will support all accounts,otherwise it only support this account
     * @param $hostinfo, string, domain name example:http://wm.com
     */
    public function actionCreateDefaultPageCover($accountId, $hostinfo)
    {
        $where = ['enabledMods' => ['$all' => ['microsite']]];
        if (empty($accountId) || empty($hostinfo)) {
            echo 'accountId and hostinfo can not be empty' . PHP_EOL;
            exit();
        } elseif ($accountId == 'all') {
            $accounts = Account::findAll($where);
            if (!empty($accounts)) {
                foreach ($accounts as $account) {
                    $this->_createDefaultPageCover($account->_id, $hostinfo);
                }
            }
        } else {
            $accountId = new MongoId($accountId);
            $account = Account::findOne(array_merge(['_id' => $accountId], $where));
            if (empty($account)) {
                echo 'Can not find the account by ' . $accountId . PHP_EOL;
                exit();
            }
            $this->_createDefaultPageCover($accountId, $hostinfo);
        }

        echo 'Create default value successfully' . PHP_EOL;
    }

    public function actionAddMessageSetting($accountId, $apiKey = '', $url = '')
    {
        $accountId = new MongoId($accountId);
        $account = Account::findByPK($accountId);
        if (empty($account)) {
            echo 'Can not create account that is not in the system' . PHP_EOL;
            return;
        }
        empty($url) && ($url = YUNPIAN_DOMAIN);
        empty($apiKey) && ($apiKey = YUNPIAN_API_KEY);
        $setting = ServiceSetting::findByAccountId($accountId);
        if (empty($setting)) {
            $setting = new ServiceSetting();
            $setting->accountId = $accountId;
        }
        $setting->message = [
            'url' => $url,
            'apiKey' => $apiKey
        ];
        echo 'Save status: ' . $setting->save() . PHP_EOL;
    }

    public function actionAddEmailSetting($accountId, $apiUser = '', $apiKey = '')
    {
        $accountId = new MongoId($accountId);
        $account = Account::findByPK($accountId);
        if (empty($account)) {
            echo 'Can not create account that is not in the system' . PHP_EOL;
            return;
        }
        empty($apiUser) && ($apiUser = SENDCLOUD_API_USER);
        empty($apiKey) && ($apiKey = SENDCLOUD_API_KEY);
        $setting = ServiceSetting::findByAccountId($accountId);
        if (empty($setting)) {
            $setting = new ServiceSetting();
            $setting->accountId = $accountId;
        }
        $setting->email = [
            'apiUser' => $apiUser,
            'apiKey' => $apiKey
        ];
        echo 'Save status: ' . $setting->save() . PHP_EOL;
    }

    private function _createDefaultPageCover($accountId, $hostinfo)
    {
        $defaultCoverPage = [
            'title' => '默认首页',
            'description' => '默认首页',
            'type' => 'cover',
            'isFinished' => true,
            'deletable' => false
        ];

        $defaultCoverPageCompnent = [
            'name' => 'cover1',
            'color' => '#6AB3F7',
            'order' => 0
        ];

        $page = Page::findOne(array_merge($defaultCoverPage, ['accountId' => $accountId]));
        if (empty($page)) {
            $page = new Page(['scenario' => 'createBasic']);
            $page->load($defaultCoverPage, '');
            $page->_id = new MongoId();
            $page->accountId = $accountId;
            $page->url = $hostinfo . '/msite/page/' . $page->_id;
            $shortUrl = Yii::$app->urlService->shortenUrl($page->url);
            $page->shortUrl = $shortUrl['Short'];

            if ($page->save()) {
                $pageComponent = new PageComponent(['scenario' => PageComponent::SCENARIO_CREATE]);
                $pageComponent->load($defaultCoverPageCompnent, '');
                $pageComponent->pageId = $page->_id;
                $pageComponent->parentId = $page->_id;
                $pageComponent->accountId = $accountId;
                $pageComponent->jsonConfig = Yii::$app->params['micrositeDefaultConfig'][$pageComponent->name];

                $pageComponent->save();
            } else {
                echo $page->getErrors() . PHP_EOL;
                exit();
            }
        } else {
            echo $accountId . 'data is exists' . PHP_EOL;
        }
    }

    /**
     * create-default-reservation-shelf
     */
    public function actionCreateDefaultReservationShelf($accountId)
    {
        $where = ['enabledMods' => ['$all' => ['reservation']]];
        if (empty($accountId)) {
            echo 'AccountId can not be empty' . PHP_EOL;
            exit();
        } elseif ($accountId == 'all') {
            $accounts = Account::findAll($where);
            if (!empty($accounts)) {
                foreach ($accounts as $account) {
                    ReservationShelf::createDefaultReservationShelf($account->_id);
                }
            }
        } else {
            $accountId = new MongoId($accountId);
            $account = Account::findOne(array_merge(['_id' => $accountId], $where));
            if (empty($account)) {
                echo 'Can not find the account by ' . $accountId . PHP_EOL;
                exit();
            }
            ReservationShelf::createDefaultReservationShelf($accountId);
        }
        echo 'Create default value successfully' . PHP_EOL;
    }
}
