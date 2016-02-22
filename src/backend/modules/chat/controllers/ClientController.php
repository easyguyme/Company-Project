<?php
namespace backend\modules\chat\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberProperty;
use backend\modules\helpdesk\models\ChatConversation;
use backend\exceptions\InvalidParameterException;
use yii\web\ServerErrorHttpException;
use backend\modules\helpdesk\models\HelpDesk;
use yii\helpers\ArrayHelper;
use backend\behaviors\MemberBehavior;
use backend\models\Follower;

class ClientController extends Controller
{
    /**
     * Inform server user is online
     * after tuisongbao trigger 'login:successed' event on clients
     * @return [type] [description]
     */
    public function actionOnline()
    {
        $params = $this->getParams();
        $id = $params['id'];
        $accountId = $params['cid'];
        if (empty($accountId) || empty($id)) {
            throw new BadRequestHttpException('Lack of client ID or account ID');
        }

        return HelpDesk::connect([
            'openId' => $id,
            'nick' => 'guest-' . split('-', $id)[0],
            'avatar' => '',
            'source' => ChatConversation::TYPE_WEBSITE,
            'accountId' => $accountId
        ], $accountId);
    }

    /**
     * View the member follower properties and
     * @return Object member
     */
    public function actionInfo()
    {
        $channelId = $this->getQuery('channelId', '');
        $openId = $this->getQuery('openId', '');
        $accountId = $this->getAccountId();

        if (empty($openId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $client = Member::getByOpenId($openId);
        if (empty($client)) {
            if (empty($channelId)) {
                return null;
            }
            //init the client user
            $follower = Yii::$app->weConnect->getFollowerByOriginId($openId, $channelId);
            Follower::upsert($accountId, $follower);
            $client = Follower::getByOpenId($accountId, $openId);
        }

        if (!empty($client)) {
            $client = $client->toArray();
            $properties = ArrayHelper::toArray(MemberProperty::getAllByAccount($accountId));
            $mapPropertyDefault = ArrayHelper::map($properties, 'id', 'isDefault');
            foreach ($client['properties'] as &$property) {
                $property['isDefault'] = empty($mapPropertyDefault[$property['id']]) ? false : $mapPropertyDefault[$property['id']];
            }
        }

        return $client;
    }

    public function actionUpdate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (empty($params['openId']) && empty($params['properties'])) {
            throw new BadRequestHttpException(\Yii::t('common', 'parameters_missing'));
        }

        $member = Member::getByOpenId($params['openId']);

        if (!empty($member)) {
            # Verify properties.
            $memberProperty = MemberProperty::checkProperties($params, $accountId);
            # Update proerties.
            $properties = $this->_mergeProperties($member, $memberProperty);
            $member->properties = $properties;

            if ($member->save()) {
                $memberBehavior = new MemberBehavior();
                $memberBehavior->updateItemByScoreRule($member);
            } else {
                throw new BadRequestHttpException(\Yii::t('common', 'update_fail'));
            }
        } else {
            $follower = Follower::getByOpenId($accountId, $params['openId']);
            if (!empty($follower)) {
                # Verify properties.
                $memberProperty = MemberProperty::checkProperties($params, $accountId);
                # Update proerties.
                $properties = $this->_mergeProperties($follower, $memberProperty);
                $follower->properties = $properties;
                if (!$follower->save()) {
                    throw new BadRequestHttpException(\Yii::t('common', 'update_fail'));
                }
            } else {
                throw new BadRequestHttpException(\Yii::t('chat', 'chat_not_member'));
            }
        }

        return ["status" => "ok"];
    }

    public function actionProperties()
    {
        $accountId = $this->getAccountId();
        return MemberProperty::getByAccount($accountId);
    }

    private function _mergeProperties($model, $property)
    {
        $propertyMap = $this->getPropertyMap($model->properties);
        $propertyMap[(string)$property['id']] = $property;
        return array_values($propertyMap);
    }

    private function getPropertyMap($properties)
    {
        $memberPropertiesMap = [];
        foreach ($properties as $property) {
            $memberPropertiesMap[(string)$property['id']] = $property;
        }
        return $memberPropertiesMap;
    }
}
