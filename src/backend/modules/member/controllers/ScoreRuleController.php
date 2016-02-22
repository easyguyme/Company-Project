<?php
namespace backend\modules\member\controllers;

use Yii;
use MongoId;
use backend\modules\member\models\ScoreRule;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use backend\modules\product\models\Coupon;

/**
 * Controller class for ScoreRule
 **/
class ScoreRuleController extends BaseController
{
    public $modelClass = "backend\modules\member\models\ScoreRule";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['update'], $actions['create'], $actions['delete']);
        return $actions;
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        if (empty($params['name']) || empty($params['code']) || empty($params['rewardType']) || empty($params['limit'])) {
            throw new InvalidParameterException(Yii::t('common', 'parameters_missing'));
        }
        if (!empty($params['rewardType']) && $params['rewardType'] == ScoreRule::REWARD_COUPON_TYPE && !empty($params['couponId'])) {
            $couponId = new MongoId($params['couponId']);
            $coupon = Coupon::findOne(['_id' => $couponId, 'total' => ['$gt' => 0]]);
            if (empty($coupon)) {
                throw new InvalidParameterException(Yii::t('member', 'score_rule_coupon_not_enough'));
            }
        }

        $rule = new ScoreRule();
        $rule->load($params, '');
        $rule->accountId = $accountId;
        $rule->isDefault = false;
        if ($rule->save()) {
            return $rule;
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
    }

    public function actionUpdate($id)
    {
        $id = new MongoId($id);

        $rule = ScoreRule::findByPk($id);
        if (empty($rule)) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }

        $params = $this->getParams();
        if (!empty($params['rewardType']) && $params['rewardType'] == ScoreRule::REWARD_COUPON_TYPE && !empty($params['couponId'])) {
            $couponId = new MongoId($params['couponId']);
            $coupon = Coupon::findOne(['_id' => $couponId, 'total' => ['$gt' => 0]]);
            if (empty($coupon)) {
                throw new InvalidParameterException(Yii::t('member', 'score_rule_coupon_not_enough'));
            }
        }

        $rule->load($params, '');
        if (false == $rule->save(true)) {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
        return $rule;
    }

    public function actionDelete($id)
    {
        $id = new MongoId($id);
        $scoreRule = ScoreRule::findByPk($id);
        if (empty($scoreRule) || $scoreRule->isDefault) {
            throw new InvalidParameterException(Yii::t('common', 'data_error'));
        }
        ScoreRule::deleteByPk($id);
        return ['message' => 'OK', 'data' => null];
    }

    public function actionCode()
    {
        return ['code' => ScoreRule::generateCode($this->getAccountId())];
    }
}
