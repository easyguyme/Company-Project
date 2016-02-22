<?php
namespace backend\modules\member\controllers;

use Yii;
use backend\models\Token;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberShipCard;
use yii\web\GoneHttpException;
use backend\exceptions\InvalidParameterException;
use yii\web\ServerErrorHttpException;
use backend\utils\TimeUtil;

/**
 * Controller class for card
 **/
class CardController extends BaseController
{
    public $modelClass = "backend\modules\member\models\MemberShipCard";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * Provide card
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/member/card/provide-card<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for provide card.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     cardId: string<br/>
     *     cardNumbers: Array, card number<br/>
     *     names: Array
     *     tags: Array<br/>
     *     cardExpiredAt: timestamp<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     message:
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *  {"message" : "OK"}
     * </pre>
     */
    public function actionProvideCard()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $params['accountId'] = $accountId . '';

        if (empty($params['cardId'])) {
            throw new BadRequestHttpException('param error');
        }

        if (empty($params['cardExpiredAt'])) {
            throw new InvalidParameterException(['schedule-picker' => \Yii::t('common', 'required_filed')]);
        }

        $cardId = new \MongoId($params['cardId']);
        $card = MemberShipCard::findByPk($cardId);
        if (empty($card)) {
            throw new BadRequestHttpException(\Yii::t('member', 'no_card_find'));
        }
        if ($card->isAutoUpgrade) {
            throw new InvalidParameterException(Yii::t('member', 'error_issue_auto_card'));
        }

        if ($params['cardExpiredAt'] < TimeUtil::msTime()) {
            throw new InvalidParameterException(['schedule-picker' => \Yii::t('member', 'not_less_than_current')]);
        }

        $members = [];
        if (!empty($params['cardNumbers']) && is_array($params['cardNumbers'])) {
            $members = Member::getByCardNumbers($params['cardNumbers']);
            if (empty($members)) {
                throw new InvalidParameterException(['cardNumber' => \Yii::t('member', 'no_member_find')]);
            }
        } else if (!empty($params['names']) && is_array($params['names'])) {
            $members = Member::getByNames($params['names']);
            if (empty($members)) {
                throw new InvalidParameterException(['memberNames' => \Yii::t('member', 'no_member_find')]);
            }
        } else if (!empty($params['tags']) && is_array($params['tags'])) {
            $members = Member::getByTags($params['tags']);
            if (empty($members)) {
                throw new InvalidParameterException(['memberTags' => \Yii::t('member', 'no_member_find')]);
            }
        }

        $memberIds = Member::getIdList($members);
        Member::updateAll(['$set' => ['cardId' => $cardId, 'cardExpiredAt' => $params['cardExpiredAt']]], ['_id' => ['$in' => $memberIds]]);

        return ['message' => 'OK'];
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $params['accountId'] = $accountId;

        $card = new MemberShipCard();
        $card->load($params, '');

        $card->isAutoUpgrade = empty($params['isAutoUpgrade']) ? false : true;
        $this->_validateCard($card);

        if ($card->save()) {
            return $card;
        } else {
            throw new ServerErrorHttpException('Fail to create card');
        }
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $cardId = new \MongoId($id);
        $card = MemberShipCard::findByPk($cardId);

        $card->load($params, '');

        $card->isAutoUpgrade = empty($params['isAutoUpgrade']) ? false : true;
        $this->_validateCard($card);

        if ($card->save()) {
            return $card;
        } else {
            throw new ServerErrorHttpException('Fail to create card');
        }
    }

    public function actionDelete($id)
    {
        $idList = explode(',', $id);

        foreach ($idList as &$perId) {
            $perId = new \MongoId($perId);
            $card = MemberShipCard::findByPk($perId);
            if ($card->isDefault) {
                throw new BadRequestHttpException(\Yii::t('member', 'delete_default_card_error'));
            }
            $provideCount = Member::getCountByCardId($perId);
            if ($provideCount > 0) {
                throw new BadRequestHttpException(\Yii::t('member', 'delete_member_exist_card_error'));
            }
        }

        $modelClass = $this->modelClass;

        if ($modelClass::deleteAll(['in', '_id', $idList]) == false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }

    public function actionSetDefault()
    {
        $cardId = $this->getParams('id');
        $card = MemberShipCard::findByPk(new \MongoId($cardId));
        if (empty($card)) {
            throw new InvalidParameterException(Yii::t('member', 'no_card_find'));
        }

        $card->isDefault = true;
        if ($card->save(true, ['isDefault'])) {
            MemberShipCard::updateAll(['$set' => ['isDefault' => false]], ['accountId' => $card->accountId, '_id' => ['$ne' => $card->_id]]);
            return ['message' => 'OK', 'data' => ''];
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'update_fail'));
        }
    }

    /**
     * Validator for field 'condition'
     * condition can not be mixed. for example:
     *      the right condition [0, 200], [201, 300], [400, 600]
     *      the error condition [0, 200], [100, 300]
     * @param object $card
     * @throws InvalidParameterException
     */
    private function _validateCard($card)
    {
        $accountId = new \MongoId($card->accountId);
        //validate name
        $memberShipCard = MemberShipCard::getByName($card->name, $accountId);
        if (!empty($memberShipCard) && $memberShipCard->_id . '' != $card->_id . '') {
            throw new InvalidParameterException(['cardName' => \Yii::t('member', 'unique_filed')]);
        }

        if (!$card->isAutoUpgrade) {
            $card->condition = null;
            return;
        }

        //validate condition
        $condition = $card->condition;

        if (!isset($condition['minScore']) || !is_int($condition['minScore']) || $condition['minScore'] < 0) {
            throw new InvalidParameterException(['conditionScore'=>\Yii::t('member', 'positive_integer_required')]);
        } else {
            // minScore can not be overlap
            $minscoreCard = MemberShipCard::getByScore($condition['minScore'], $accountId);
            if (!empty($minscoreCard) && $minscoreCard->_id . '' != $card->_id . '') {
                throw new InvalidParameterException(['conditionScore' => \Yii::t('member', 'score_overlap_error')]);
            }
        }

        if (!isset($condition['maxScore']) || !is_int($condition['maxScore']) ||
            (isset($condition['minScore']) && $condition['maxScore'] < $condition['minScore'])) {
            throw new InvalidParameterException(['conditionScore' => \Yii::t('member', 'positive_integer_required')]);
        } else {
            //max score can not be overlap
            $maxscoreCard = MemberShipCard::getByScore($condition['maxScore'], $accountId);
            if (!empty($maxscoreCard) && $maxscoreCard->_id . '' != $card->_id . '') {
                throw new InvalidParameterException(['conditionScore' => \Yii::t('member', 'score_overlap_error')]);
            } else {
                $allCards = MemberShipCard::getAutoUpgradeByAccount($accountId);

                // if card condition contain others condition. throw exception
                // for example:The condition [100, 201] and [100, 400] is error if there is a condition [200,300].
                foreach ($allCards as $item) {
                    $preCondition = $item->condition;
                    if ($condition['minScore'] < $preCondition['minScore'] && $condition['maxScore'] > $preCondition['maxScore'] &&
                        $card->_id . '' != $item->_id . '') {
                        throw new InvalidParameterException(['conditionScore' => \Yii::t('member', 'score_overlap_error')]);
                    }
                }
            }
        }
    }
}
