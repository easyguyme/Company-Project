<?php
namespace backend\components\extservice\models;

use Yii;
use Exception;
use MongoId;
use backend\modules\member\models\Member as ModelMember;
use backend\modules\member\models\ScoreHistory;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\utils\LogUtil;
use backend\components\ActiveDataProvider;
use backend\modules\member\models\MemberProperty;

/**
 * Member for extension
 */
class Member extends BaseComponent
{
    /**
     * @return array
     * @param $param,array,this array only suport openId and memberId,so the array key should have these words
     */
    public function one($param)
    {
        if (!is_array($param)) {
            throw new Exception('Please make sure the param is a array.');
        }

        if (isset($param['openId'])) {
            return ModelMember::getByOpenId($param['openId']);
        } else if (isset($param['memberId'])) {
            return ModelMember::findOne(['_id' => new MongoId($param['memberId'])]);
        } else {
            throw new Exception('This param only suport openId and memberId');
        }
    }

    /**
     * Reward score by memberId
     * @param array $memberIds
     * @param int $score
     * @param string $description
     * @throws Exception
     * @return boolean
     */
    public function rewardScore($memberIds, $score, $description = '')
    {
        if ($score <= 0) {
            throw new Exception('Score must gather than 0');
        }
        $condition = ['_id' => ['$in' => $memberIds], 'accountId' => $this->accountId];
        ModelMember::updateAll(['score' => $score, 'totalScore' => $score, 'totalScoreAfterZeroed' => $score], $condition);
        $members = ModelMember::findAll($condition);
        foreach ($members as $member) {
            $scoreHistory = new ScoreHistory;
            $scoreHistory->assigner = ScoreHistory::ASSIGNER_REWARD_SCORE;
            $scoreHistory->increment = $score;
            $scoreHistory->memberId = $member->_id;
            $scoreHistory->brief = ScoreHistory::ASSIGNER_REWARD_SCORE;
            $scoreHistory->description = $description;
            $scoreHistory->channel = ['origin' => ScoreHistory::PORTAL];
            $scoreHistory->accountId = $this->accountId;

            if (!$scoreHistory->save()) {
                LogUtil::error(['message' => 'Record scoreHistory failed', 'scoreHistory' => json_encode($scoreHistory)], 'member-service');
            }
        }

        return true;
    }

    /**
     * Reward shake score
     * @param MongoId $memberId
     * @param int $score
     */
    public function shakeScore($memberId, $score, $channelInfo)
    {
        ModelMember::updateAll(
            ['$inc' => ['score' => $score, 'totalScore' => $score, 'totalScoreAfterZeroed' => $score]],
            ['_id' => $memberId]
        );
        $scoreHistory = new ScoreHistory;
        $scoreHistory->assigner = ScoreHistory::ASSIGNER_SHAKE_SCORE;
        $scoreHistory->increment = $score;
        $scoreHistory->memberId = $memberId;
        $scoreHistory->brief = ScoreHistory::ASSIGNER_SHAKE_SCORE;
        $scoreHistory->description = '';
        $scoreHistory->channel = $channelInfo;
        $scoreHistory->user = null;
        $scoreHistory->accountId = $this->accountId;
        if (!$scoreHistory->save()) {
            LogUtil::error(['message' => 'Failed to save the history for unknown problem', 'scoreHistory' => $scoreHistory->toArray()], 'resque');
        }
    }

    /**
     * get all member by member id
     * @return array
     * @param $memberIds, array
     */
    public function getByIds($memberIds)
    {
        if (!empty($memberIds) && is_array($memberIds)) {
            return ModelMember::findAll(['_id' => ['$in' => $memberIds]]);
        }
        return [];
    }

    /**
     * get member info by condition
     * @return array
     * @param $condition, array
     * @param $one,boolean,if this value is true,it will return a data,otherwise it will return all datas
     */
    public function getByCondition($condition, $one = true)
    {
        if ($one) {
            return ModelMember::findOne($condition);
        } else {
            return ModelMember::findAll($condition);
        }
    }

    /**
     * Search member by conditions
     * @param $conditions array
     * @param $page
     * @param $pageSize
     * @author Rex Chen
     */
    public function search($conditions, $page = 1, $pageSize = 20)
    {
        $conditions['tags'] = empty($conditions['tags']) ? '' : implode(',', $conditions['tags']);
        $dataProvider = ModelMember::search($conditions, $this->accountId);
        return $this->formatPageResult($dataProvider, $page, $pageSize);
    }

    /**
     * Search member by tags
     * @param array $tags
     * @param number $page
     * @param number $pageSize
     * @return array
     * @author Rex Chen
     */
    public function searchByTags($tags, $page = 1, $pageSize = 20)
    {
        $condition = ['accountId' => $this->accountId, 'isDeleted' => ModelMember::NOT_DELETED];
        $query = ModelMember::find()->where($condition);
        foreach ($tags as $tag) {
            if (is_array($tag)) {
                $tagCondition['$or'][] = ['tags' => ['$all' => $tag]];
            } else {
                $tagCondition['$or'][] = ['tags' => $tag];
            }
        }
        $query->andWhere($tagCondition);
        $query->orderBy(['createdAt' => SORT_DESC]);
        $dataProvider = new ActiveDataProvider(['query' => $query]);
        return $this->formatPageResult($dataProvider, $page, $pageSize);
    }

    /**
     * return member query object
     */
    public function getQueryObject()
    {
        return ModelMember::find();
    }

    /**
     * Get member's openId
     * @param  string $memberId
     * @param  string $channelId
     * @return string $openId
     */
    public function getOpenId($memberId, $channelId)
    {
        if (is_string($memberId)) {
            $memberId = new MongoId($memberId);
        }
        $member = ModelMember::findByPk($memberId);
        if (empty($member)) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        LogUtil::info(['member' => $member->toArray(), 'channelId' => $channelId, 'memberId' => $memberId], 'member');
        if (!empty($member['socialAccountId']) && $member['socialAccountId'] == $channelId) {
            if (!empty($member['openId'])) {
                return $member['openId'];
            }
        }

        if (!empty($member['socials'])) {
            LogUtil::info(['member socials' => $member['socials']], 'member');
            foreach ($member['socials'] as $value) {
                LogUtil::info(['social' => $value, 'channel' => $value['channel']], 'member');
                if ($value['channel'] == $channelId) {
                    return $value['openId'];
                }
            }
        }
    }

    public function getProperty($memberId, $name = 'name')
    {
        if (is_string($memberId)) {
            $memberId = new MongoId($memberId);
        }
        return ModelMember::getMemberInfo($memberId, $name);
    }

    /**
     * Get member's openId
     * @param mongoId $id
     * @param  string $memberId
     * @param  string $channelId
     * @return string $openId
     */
    public function bindChannel($id, $origin, $channelId = '', $openId = '')
    {
        $params = [];
        if (empty($origin)) {
            throw new BadRequestHttpException(Yii::t('member', 'member_origin_missing'));
        }

        // check if the member has exist.
        $member = ModelMember::findOne(['_id' => $id, 'accountId' => $this->accountId]);
        if (empty($member)) {
            throw new BadRequestHttpException(Yii::t('member', 'member_has_error'));
        }

        // check if the member has already bind channel.
        if (!empty($member->openId) && $member->openId == $openId) {
            throw new BadRequestHttpException(Yii::t('common', 'member_has_bind'));
        }

        $member->socialAccountId = empty($channelId) ? '' : $channelId;
        $member->openId = empty($openId) ? '' : $openId;
        $member->origin = $origin;

        // Get message that how to pay attention to the public accounts with member.
        if (!empty($member->openId) && !empty($member->socialAccountId) && empty($member->originScene)) {
            $follower = \Yii::$app->weConnect->getFollowerByOriginId($openId, $channelId);
            $originScene = empty($follower['subscribeSource']) ? '' : $follower['subscribeSource'];
            $member->originScene = $originScene;
        }

        $result = $member->save(true, ['socialAccountId', 'openId', 'origin', 'originScene']);
        if (!$result) {
            throw new BadRequestHttpException(Yii::t('member', 'member_bind_error'));
        }
        return ["message" => "ok", "data" => $member];
    }

    /**
     * Update the properties of member.
     * @param string $id
     * @param  array $properties
     *
     * @return array $result
     */
    public function updateProperties($id, $properties = [])
    {
        $id = new MongoId($id);

        // check if the member has exist.
        $member = ModelMember::findOne(['_id' => $id, 'accountId' => $this->accountId]);
        if (empty($member)) {
            throw new BadRequestHttpException(Yii::t('member', 'member_has_error'));
        }

        if (!empty($properties)) {
            $memberProperties = MemberProperty::mergeProperties($member->properties, $properties, $this->accountId);

            $member->properties = $memberProperties;

            $result = $member->save(true, ['properties']);
            if (!$result) {
                throw new BadRequestHttpException(Yii::t('member', 'member_update_error'));
            }
        }
        return ["message" => "ok", "data" => $member];
    }
}
