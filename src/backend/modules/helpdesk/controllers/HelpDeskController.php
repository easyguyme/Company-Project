<?php
namespace backend\modules\helpdesk\controllers;

use backend\utils\EmailUtil;
use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\member\models\Member;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\models\Token;
use backend\exceptions\InvalidParameterException;
use backend\utils\StringUtil;
use backend\utils\LogUtil;
use Yii;
use backend\utils\UrlUtil;

/**
 * Controller class for help desk
 **/
class HelpDeskController extends BaseController
{
    const SUBJECT = '群脉·客服邀请邮件';
    public $modelClass = "backend\modules\helpdesk\models\HelpDesk";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update']);
        return $actions;
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (empty($params['email'])) {
            throw new InvalidParameterException(['email' => Yii::t('common', 'required_filed')]);
        }
        if (empty($params['badge'])) {
            throw new InvalidParameterException(['number' => Yii::t('common', 'required_filed')]);
        }

        $email = $params['email'];
        $badge = $params['badge'];
        if (!StringUtil::isEmail($email)) {
            throw new InvalidParameterException(['email'=>Yii::t('helpDesk', 'email_format_wrong')]);
        }
        $helpDesk = HelpDesk::getByEmail($email);

        if (!empty($helpDesk)) {
            throw new InvalidParameterException(['email'=>Yii::t('helpDesk', 'email_has_used')]);
        } else {
            $helpDesk = HelpDesk::getByBadge($params['badge'], $accountId);
            if (!empty($helpDesk)) {
                throw new InvalidParameterException(['number'=>Yii::t('helpDesk', 'badge_has_used')]);
            }
        }
        $helpDesk = new HelpDesk();
        $helpDesk->email = $email;
        $helpDesk->badge = $badge;

        $helpDesk->accountId = $accountId;
        $currentUser = $this->getUser();

        if ($helpDesk->save()) {
            $link = UrlUtil::getDomain() . '/site/invite/code?type=1'; //type=1 means invite helpdesk account
            $result = EmailUtil::sendInviteEmail($helpDesk, $currentUser->name, $link, self::SUBJECT, 'helpdeskinvition');
            if ($result) {
                return ['email' => $helpDesk->email];
            } else {
                throw new ServerErrorHttpException("validation save fail");
            }
        }
        throw new ServerErrorHttpException("helpDesk save fail");
    }

    public function actionSendEmail()
    {
        $id = $this->getParams('id');
        $currentUser = $this->getUser();
        $helpDesk = HelpDesk::findOne(['_id' => $id]);
        $link = UrlUtil::getDomain() . '/site/invite/code?type=1';
        $result = EmailUtil::sendInviteEmail($helpDesk, $currentUser->name, $link, self::SUBJECT, 'helpdeskinvition');

        if ($result) {
            return ['email' => $helpDesk->email];
        } else {
            throw new ServerErrorHttpException("validation save fail");
        }
    }

    public function actionCheckUnique()
    {
        $query = $this->getQuery();
        $accountId = $this->getAccountId();

        if (!empty($query['email'])) {
            $email = $query['email'];
            if (!StringUtil::isEmail($email)) {
                throw new InvalidParameterException(['email'=>Yii::t('helpDesk', 'email_format_wrong')]);
            }
            $helpDesk = HelpDesk::getByEmail($email);
            if (!empty($helpDesk)) {
                throw new InvalidParameterException(['email'=>Yii::t('helpDesk', 'email_has_used')]);
            }
        }
        if (!empty($query['badge'])) {
            $helpDesk = HelpDesk::getByBadge($query['badge'], $accountId);
            if (!empty($helpDesk)) {
                throw new InvalidParameterException(['number'=>Yii::t('helpDesk', 'badge_has_used')]);
            }
        }
        return true;
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $id = new \MongoId($id);

        if (empty($params['badge'])) {
            throw new InvalidParameterException(['number' => Yii::t('common', 'required_filed')]);
        }
        $helpDesk = HelpDesk::getByBadge($params['badge'], $accountId);
        if (!empty($helpDesk)) {
            throw new InvalidParameterException(['number'=>Yii::t('helpDesk', 'badge_has_used')]);
        } else {
            HelpDesk::updateAll(['badge' => $params['badge']], ['_id' => $id]);
            $helpDesk = HelpDesk::findOne(['_id' => $id]);
            return $helpDesk;
        }
    }

    public function actionUpdateTag()
    {
        $result = ['status' => 'ok'];
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (empty($params['tagName'])) {
            throw new BadRequestHttpException("Tag name can not be empty");
        }

        if (empty($params['helpdeskIds'])) {
            throw new BadRequestHttpException("Helpdesks can not be empty");
        }

        $tagName = $params['tagName'];
        $helpdeskIds = $params['helpdeskIds'];
        if (!empty($helpdeskIds)) {
            $helpdeskMongoIds = [];
            foreach ($helpdeskIds as $helpdeskId) {
                $helpdeskMongoIds[] = new \MongoId($helpdeskId);
            }
            HelpDesk::addTag($tagName, $helpdeskMongoIds);
        }

        return $result;
    }

    public function actionChangeStatus()
    {
        $id = $this->getParams('id');
        $isEnabled = $this->getParams('isEnabled');
        HelpDesk::updateAll(['isEnabled' => $isEnabled], ['_id' => $id]);
        return 'ok';
    }

    public function actionListByTag()
    {
        $tagName = $this->getQuery('tagName');
        $orderBy = $this->getQuery('orderBy', 'createdAt');
        $accountId = $this->getAccountId();
        $result = ['items' => [], 'memberCount' => 0];
        $result['items'] = HelpDesk::getByAccountAndTags($tagName, $accountId, $orderBy);
        $result['memberCount'] = Member::getMemberCountByTags($tagName, $accountId);
        return $result;
    }

    public function actionListExcludeTag()
    {
        $tagName = $this->getQuery('tagName');
        $accountId = $this->getAccountId();
        $helpdesks = HelpDesk::getExculdeTags([$tagName], $accountId);
        return $helpdesks;
    }

    public function actionAssignTag()
    {
        $result = ['status' => 'ok'];
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (empty($params['tagName'])) {
            throw new BadRequestHttpException("Tag name can not be empty");
        }

        $tagName = $params['tagName'];
        $addTagHelpdeskIds = $params['addTagHelpdeskIds'];
        $removeTagHelpdeskIds = $params['removeTagHelpdeskIds'];

        if (!empty($addTagHelpdeskIds)) {
            $helpdeskMongoIds = [];
            foreach ($addTagHelpdeskIds as $helpdeskId) {
                $helpdeskMongoIds[] = new \MongoId($helpdeskId);
            }
            HelpDesk::addTag($tagName, $helpdeskMongoIds);
        }

        if (!empty($removeTagHelpdeskIds)) {
            $helpdeskMongoIds = [];
            foreach ($removeTagHelpdeskIds as $helpdeskId) {
                $helpdeskMongoIds[] = new \MongoId($helpdeskId);
            }
            HelpDesk::removeTag($tagName, $helpdeskMongoIds);
        }

        return $result;
    }

    public function actionRemoveTag()
    {
        $result = ['status' => 'ok'];
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (empty($params['tagName'])) {
            throw new BadRequestHttpException("Tag name can not be empty");
        }

        if (empty($params['helpdeskIds'])) {
            throw new BadRequestHttpException("Helpdesk id can not be empty");
        }

        $tagName = $params['tagName'];
        $helpdeskIds = $params['helpdeskIds'];
        if (!empty($helpdeskIds)) {
            $helpdeskMongoIds = [];
            foreach ($helpdeskIds as $helpdeskId) {
                $helpdeskMongoIds[] = new \MongoId($helpdeskId);
            }
            HelpDesk::removeTag($tagName, $helpdeskMongoIds);
        }

        return $result;
    }
}
