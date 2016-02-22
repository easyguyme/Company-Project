<?php
namespace backend\modules\common\controllers;

use Yii;
use backend\components\Controller;
use backend\models\Account;
use backend\modules\member\models\Member;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\behaviors\TagBehavior;
use backend\models\Channel;

class TagController extends Controller
{
    public function actionIndex()
    {
        $perPage = $this->getQuery('per-page', 20);
        $page = $this->getQuery('page', 1);

        $accountId = $this->getAccountId();
        $account = Account::findByPk($accountId);
        $channels = Channel::getEnableChannelIds($accountId);
        $isOpenChannle = false;

        $tags = empty($account->tags) ? [] : $account->tags;
        $tags = ArrayHelper::getColumn($tags, 'name', false);
        $totalCount = count($tags);
        rsort($tags);
        $tags = array_slice($tags, ($page - 1) * $perPage, $perPage);
        if (empty($tags)) {
            return ['items' => [], '_meta' => ['totalCount' => 0, 'pageCount' => 0, 'currentPage' => $page, 'perPage' => $perPage]];
        }

        if (!defined('KLP') || !KLP) {
            if (!empty($channels)) {
                $followerTags = Yii::$app->weConnect->getTagStats($channels, $tags);
            }
        }

        $memberTags = Member::getTagStats($accountId, $tags, $channels);
        $memberTagMap = ArrayHelper::map($memberTags, '_id', 'count');

        $items = [];
        foreach ($tags as $tag) {
            $items[] = [
                'name' => $tag,
                'memberCount' => empty($memberTagMap[$tag]) ? 0 : $memberTagMap[$tag],
                'followerCount' => empty($followerTags[$tag]) ? 0 : $followerTags[$tag]
            ];
        }
        $meta = [
            'totalCount' => $totalCount,
            'pageCount' => ceil($totalCount / $perPage),
            'currentPage' => $page,
            'perPage' => $perPage
        ];

        # Determine whether the account is open channel module.
        $condition = ['enabledMods' => 'channel', 'mods.name' => 'channel', '_id' => $accountId];
        $account = Account::findOne($condition);
        if (!empty($account)) {
            $isOpenChannle = true;
        }

        return ['items' => $items, 'data' => $isOpenChannle, '_meta' => $meta];
    }

    public function actionRename()
    {
        $name = $this->getParams('name');
        $newName = $this->getParams('newName');
        if (empty($name) || empty($newName)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if ($name === $newName) {
            throw new InvalidParameterException(['newTagName' => Yii::t('common', 'tag_never_changed')]);
        }

        $accountId = $this->getAccountId();
        $account = Account::findByPk($accountId);
        $channels = Channel::getEnableChannelIds($accountId);

        $tags = empty($account->tags) ? [] : $account->tags;
        $tags = ArrayHelper::getColumn($tags, 'name', false);
        if (in_array($newName, $tags)) {
            throw new InvalidParameterException(['newTagName' => Yii::t('common', 'unique_filed')]);
        }

        if (!defined('KLP') || !KLP) {
            if (!empty($channels)) {
                $followerTags = Yii::$app->weConnect->renameTag($channels, $name, $newName);
            }
        }

        $this->attachBehavior('TagBehavior', new TagBehavior());
        $this->renameTag($accountId, $name, $newName);

        return ['message' => 'OK', 'data' => null];
    }

    public function actionRemove()
    {
        $name = $this->getParams('name', '');
        if ($name === '') {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $accountId = $this->getAccountId();

        $account = Account::findByPk($accountId);
        $channels = Channel::getEnableChannelIds($accountId);

        if (!defined('KLP') || !KLP) {
            if (!empty($channels)) {
                $followerTags = Yii::$app->weConnect->deleteTag($channels, $name);
            }
        }

        $this->attachBehavior('TagBehavior', new TagBehavior());
        $this->deleteTag($accountId, $name);

        return ['message' => 'OK', 'data' => null];
    }
}
