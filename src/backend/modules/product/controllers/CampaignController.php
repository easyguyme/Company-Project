<?php

namespace backend\modules\product\controllers;

use backend\components\BaseModel;
use backend\modules\product\models\Campaign;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use backend\models\Account;

class CampaignController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\Campaign';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionCreate()
    {
        $params = $this->getParams();

        if (empty($params['productIds'])) {
            throw new BadRequestHttpException('missing param productIds');
        }

        if (empty($params['products'])) {
            throw  new InvalidParameterException(['productExperice' => \Yii::t('product', 'not_exchange_experience')]);
        }

        $campaign = new Campaign([
            'scenario' => BaseModel::SCENARIO_CREATE,
        ]);

        $accountId = $this->getAccountId();
        $params['accountId'] = $accountId;

        $promotion['type'] = Campaign::TYPE_PROMOTION_CODE;
        if (!empty($params['productIds'])) {
            $promotion['data'] = $params['productIds'];
        }
        if (!empty($params['gift'])) {
            $promotion['gift'] = $params['gift'];
        } else {
            $promotion['gift'] = null;
        }

        $promotion['userTags'] = empty($params['userTags']) ? [] : $params['userTags'];
        $promotion['isAddTags'] = $params['isAddTags'];
        $promotion['products'] = $params['products'];
        $promotion['tags'] = empty($params['tags']) ? [] : $params['tags'];
        $promotion['channels'] = empty($params['channels']) ? [] : $params['channels'];
        $params['promotion'] = $promotion;
        $params['participantCount'] = empty($params['participantCount']) ? null : intval($params['participantCount']);
        $params['limitTimes'] = empty($params['limitTimes']) ? null : intval($params['limitTimes']);
        $params['usedCount'] = 0;

        // add tags to account`s tags
        if (!empty($params['userTags'])) {
            $updateTags['$addToSet'] = ['tags' => ['$each' => $params['userTags']]];
            Account::updateAll($updateTags, ['_id' => $accountId]);
        }

        $campaign->load($params, '');

        $campaign->save();

        return $campaign;
    }

    public function actionUpdate($id)
    {
        $campaign = Campaign::findByPk($id);
        $accountId = $this->getAccountId();

        if (empty($campaign)) {
            throw new BadRequestHttpException(Yii::t('product', 'campaign_not_found'));
        }

        if (MongodbUtil::isExpired($campaign->endTime)) {
            throw new BadRequestHttpException(Yii::t('product', 'can_not_update'));
        }

        $params = $this->getParams();
        $params['startTime'] = empty($params['startTime']) ? $campaign->startTime : new \MongoDate(TimeUtil::ms2sTime($params['startTime']));
        $params['endTime'] = empty($params['endTime']) ? $campaign->endTime : new \MongoDate(TimeUtil::ms2sTime($params['endTime']));
        $attributeNames = null;
        foreach ($params as $key => $value) {
            if (in_array($key, ['productIds', 'gift', 'products', 'tags', 'channels'])) {
                $attributeNames[] = 'promotion';
                $promotion = $campaign->promotion;
                $promotion['type'] = Campaign::TYPE_PROMOTION_CODE;
                ($key == 'productIds') ? $promotion['data'] = $params['productIds'] : '';
                ($key == 'gift') ? $promotion['gift'] = $params['gift'] : '';
                ($key == 'products') ? $promotion['products'] = $params['products'] : '';
                ($key == 'tags') ? $promotion['tags'] = $params['tags'] : '';
                ($key == 'channels') ? $promotion['channels'] = $params['channels'] : '';
                $campaign->promotion = $promotion;
            } else if (in_array($key, ['participantCount', 'limitTimes'])) {
                $attributeNames[] = $key;
                $campaign->$key = is_null($value) ? null : intval($value);
            } else {
                $attributeNames[] = $key;
                $campaign->$key = $value;
            }
        }
        $campaign->isAddTags = $params['isAddTags'];
        $campaign->userTags = empty($params['userTags']) ? [] : $params['userTags'];

        // add tags to account`s tags
        if (!empty($params['userTags'])) {
            $updateTags['$addToSet'] = ['tags' => ['$each' => $params['userTags']]];
            Account::updateAll($updateTags, ['_id' => $accountId]);
        }

        $campaign->save(true, $attributeNames);
        return $campaign;
    }

    public function actionDelete($id)
    {
        $campaign = Campaign::findByPk($id);

        if (empty($campaign)) {
            throw new BadRequestHttpException(Yii::t('product', 'campaign_not_found'));
        }

        if ($campaign->isActivated) {
            throw new BadRequestHttpException(Yii::t('product', 'can_not_delete'));
        }

        if (Campaign::deleteAll(['_id' => new \MongoId($id)]) == false) {
            throw new ServerErrorHttpException('Failed to delete the campaign for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }

    public function actionAll()
    {
        $accountId = $this->getAccountId();
        return Campaign::getByAccount($accountId);
    }

    public function actionNames()
    {
        $ids = $this->getQuery('campaignIds');
        if (empty($ids)) {
            return [];
        }

        $ids = explode(',', $ids);
        $campaignIds = MongodbUtil::toMongoIdList($ids);
        $campaigns = Campaign::getByIds($campaignIds);

        $names = [];
        foreach ($campaigns as $campaign) {
            $names[] = $campaign->name;
        }

        return $names;
    }

    /**
     * get the product info in the campaign
     * if the pageSize = 0,the api will return all products
     */
    public function actionProductInfo()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        $pageSize = $page = 0;

        if (!empty($params['pageSize'])) {
            if (empty($params['page'])) {
                throw new BadRequestHttpException('missing param page');
            }
            $pageSize = intval($params['pageSize']);
            $page = intval($params['page']);
        }
        return Campaign::searchProductInfo($accountId, $pageSize, $page);
    }
}
