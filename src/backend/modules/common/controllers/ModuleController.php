<?php
namespace backend\modules\common\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Account;
use backend\models\Token;
use backend\models\User;
use backend\models\SensitiveOperation;

class ModuleController extends BaseController
{
    /**
     * Get account modules config
     *
     * <b>Request Type </b>:GET
     * <b>Request Endpoints </b>: http://{server-domain}/api/common/module/config
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to get the account modules config.
     *
     * <b>Response Example</b>
     * {
     *     "menus": {
     *         "content": [
     *             {
     *                 "order": 1,
     *                 "title": "graphics_content",
     *                 "name": "graphics",
     *                 "state": "content-graphics"
     *             },
     *             {
     *                 "order": 2,
     *                 "title": "webpage_content",
     *                 "name": "webpage",
     *                 "state": "content-webpage"
     *             }
     *         ],
     *         "analytic": [
     *             {
     *                 "order": 1,
     *                 "title": "analytic_followers_growth",
     *                 "name": "growth",
     *                 "state": "analytic-growth"
     *             },
     *             {
     *                 "order": 2,
     *                 "title": "analytic_followers_property",
     *                 "name": "property",
     *                 "state": "analytic-property"
     *             },
     *             {
     *                 "order": 3,
     *                 "title": "analytic_content_spread",
     *                 "name": "content",
     *                 "state": "analytic-content"
     *             },
     *             {
     *                 "order": 4,
     *                 "title": "analytic_store",
     *                 "name": "store",
     *                 "state": "analytic-score"
     *             }
     *         ]
     *     },
     *     "mods": [
     *         {
     *             "name": "channel",
     *             "order": 1,
     *             "stateUrl": ""
     *         },
     *         {
     *             "name": "customer",
     *             "order": 2,
     *             "stateUrl": "/customer/follower"
     *         },
     *         {
     *             "name": "helpdesk",
     *             "order": 1,
     *             "stateUrl": "/helpdesk/helpdesk"
     *         }
     *     ],
     *     "forbiddenStates": [
     *         "member-score",
     *         "product-edit-product",
     *         "product-edit-product-{id}"
     *     ]
     * }
     **/
    public function actionConfig()
    {
        $accountId = $this->getAccountId();
        $account = Account::findByPk($accountId);
        $result = ['menus' => $account->menus, 'mods' => $account->mods];

        $token = Token::getToken();
        $forbiddenStates = [];

        if (empty($token->role) || $token->role !== User::ROLE_ADMIN) {
            $userId = empty($token->userId) ? '' : $token->userId;
            $forbiddenStates = SensitiveOperation::getForbiddenStates($userId, $accountId);
        }

        $menus = &$result['menus'];
        // Remove the forbidden menu
        foreach ($menus as &$menu) {
            foreach ($menu as $index => $subMenu) {
                if (!empty($subMenu['state']) && in_array($subMenu['state'], $forbiddenStates)) {
                    array_splice($menu, $index, 1);
                }
            }
        }

        $mods = &$result['mods'];
        foreach ($mods as $index => &$mod) {
            // Get the first menu's state in this mod
            if (!empty($menus[$mod['name']][0]['state'])) {
                // Use first menu's state to generate the mod's stateUrl
                $mod['stateUrl'] = $this->_state2Url($menus[$mod['name']][0]['state']);
            } else {
                // Remove the mod
                array_splice($mods, $index, 1);
            }
        }
        $result['forbiddenStates'] = $forbiddenStates;

        // Sort the menus and mods
        foreach ($result['menus'] as &$moduleItems) {
            ArrayHelper::multisort($moduleItems, 'order', SORT_ASC);
        }
        ArrayHelper::multisort($result['mods'], 'order', SORT_ASC);

        return $result;
    }

    /**
     * Change state to url
     * @param  string $state
     * @return string
     */
    private function _state2Url($state)
    {
        $url = str_replace('-', '/', $state);
        return '/' . $url;
    }
}
