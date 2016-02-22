<?php

namespace backend\modules\uhkklp\controllers;

use backend\modules\uhkklp\models\ActivityBar;
use backend\modules\uhkklp\models\ActivityPrize;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use Yii;

class ActivityBarController extends BaseController
{
    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        if (empty($params['status'])) {
            $params['status'] = 'Y';
            $params['currentPage'] = 1;
            $params['pageSize'] = 10;
        }

        $condition = ['status'=>$params['status'], 'isDeleted'=>false, 'accountId'=>$accountId];
        $count = ActivityBar::getCountByCondition($condition);
        $list = ActivityBar::findList($params['currentPage'], $params['pageSize'], $condition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['count'=>$count, 'list'=>$list];
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        $bar = ActivityBar::findOne(['status'=>'Y', 'isDeleted'=>false, 'accountId'=>$accountId]);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        //Existing activity has been onshelve, can't create onshelve activity.
        if (!empty($bar) && $params['activity']['status'] == 'Y') {
            return ['code'=>1000];
        }

        if (empty($params['activity']['_id'])) {
            throw new BadRequestHttpException('activityId params missing');
        }

        if ($params['activity']['_id'] == 'create') {
            $mongoId = ActivityBar::createBar($params['activity']);
            $isTrue = ActivityPrize::createPrize($params['prizes'], $mongoId);
            if (!$isTrue) {
                return ['code'=>500, 'activityId'=>$mongoId];
            }
        }

        return ['code'=>200];
    }

    public function actionUpdate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (empty($params['activity']['_id'])) {
            throw new BadRequestHttpException('activityId params missing');
        }

        //Existing activity has been onshelve, can't change status to onshelve.
        $bar = ActivityBar::findOne(['status'=>'Y', 'isDeleted'=>false, 'accountId'=>$accountId]);
        if (!empty($bar) && (string)$bar['_id'] != $params['activity']['_id'] && $params['activity']['status'] == 'Y') {
            return ['code'=>1000];
        }

        if ($params['activity']['_id'] != 'create') {
            $mongoId = ActivityBar::updateBar($params['activity']);
            $isTrue = ActivityPrize::updatePrize($params['prizes'], $mongoId);
            if (!$isTrue) {
                return ['code'=>500, 'activityId'=>$mongoId];
            }
        }

        return ['code'=>200];
    }

    public function actionView($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException('activityId params missing');
        }

        if (strlen($id) > 10) {
            $activity = ActivityBar::getById($id);
            $prizes = ActivityPrize::getByActivityId($id);

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['activity'=>$activity->attributes, 'prizes'=>$prizes];
        }
    }

    /* Verification can shelf activity or not */
    public function actionCanOnshelve()
    {
        $code = 1; //can not
        $accountId = $this->getAccountId();
        $bar = ActivityBar::findOne(['status'=>'Y', 'isDeleted'=>false, 'accountId'=>$accountId]);

        if (empty($bar)) {
            $code = 0;
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code'=>$code];

    }

    /* onshelve or offshelve */
    public function actionOnOrOffShelve($id)
    {
        $accountId = $this->getAccountId();
        $bar = ActivityBar::findOne(new \mongoId($id));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($bar->status == 'Y') {
            $bar->status = 'N';
        } else {
            $activity = ActivityBar::findOne(['status'=>'Y', 'isDeleted'=>false, 'accountId'=>$accountId]);
            if (!empty($activity)) {
                unset($activity);
                return ['code'=>1000];
            }
            $bar->status = 'Y';
        }

        if (!$bar->save()) {
            throw new ServerErrorHttpException('Fail to change status');
        }

        return ['code'=>200];
    }

    public function actionDelete($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException('activityId params missing');
        }

        $bar = ActivityBar::findOne(new \mongoId($id));
        $bar->isDeleted = true;

        if (!$bar->save()) {
            throw new ServerErrorHttpException('Fail to delete activity');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code'=>200];
    }

    public function actionDeletePrize($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException('prizeId params missing');
        }

        $prize = ActivityPrize::findOne(new \mongoId($id));
        $prize->isDeleted = true;

        if (!$prize->save()) {
            throw new ServerErrorHttpException('Fail to delete prize');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code'=>200];
    }

}