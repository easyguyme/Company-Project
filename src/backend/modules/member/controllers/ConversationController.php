<?php
namespace backend\modules\member\controllers;

use Yii;
use MongoId;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\Member;
use yii\helpers\ArrayHelper;
use backend\modules\helpdesk\models\ChatSession;
use backend\utils\MongodbUtil;

class ConversationController extends BaseController
{
    //Can not extends backend\components\Controller because of actionIndex currentPage error
    public $modelClass = 'backend\modules\helpdesk\models\ChatConversation';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['update'], $actions['create'], $actions['view'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        if (empty($params['memberId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        //get members openIds
        $member = Member::findByPk(new MongoId($params['memberId']));
        $openIds = empty($member->socials) ? [] : ArrayHelper::getColumn($member->socials, 'openId');
        !empty($member->openId) && $openIds[] = $member->openId;

        //get lastChatDate
        $lastSession = ChatSession::getLastByOpenIds($accountId, $openIds);
        $lastChatDate = empty($lastSession->lastChatTime) ? '' :  MongodbUtil::MongoDate2String($lastSession->lastChatTime, 'Y-m-d');

        //get conversations
        $params['openIds'] = $openIds;
        $conversations =  ChatSession::search($accountId, $params);

        $result = $this->serializeData($conversations);
        $result['lastChatDate'] = $lastChatDate;
        return $result;
    }
}
