<?php
namespace backend\modules\mall\controllers;

use Yii;
use MongoDate;
use MongoId;
use backend\models\Goods;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;
use backend\modules\member\models\Member;
use backend\modules\member\models\ScoreHistory;
use backend\modules\member\models\MemberLogs;
use backend\models\Captcha;
use backend\modules\product\models\GoodsExchangeLog;
use backend\models\Account;
use backend\exceptions\InvalidParameterException;
use backend\utils\LogUtil;
use backend\models\User;
use backend\models\Channel;
use backend\components\Webhook;
use backend\behaviors\CaptchaBehavior;

class GoodsController extends BaseController
{
    public $modelClass = 'backend\models\Goods';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['update']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        return Goods::search($params, $accountId);
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        if (empty($params['goods']) || count($params['goods']) <= 0) {
            throw new BadRequestHttpException('invailid params');
        }

        $goods = Goods::checkAndPackGoodsInfo($params['goods']);
        return Goods::createGoods($goods, $this->getAccountId());
    }

    public function actionUpdateGoodsStatus()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $params['accountId'] = $accountId;

        if (empty($params['operation']) || (!isset($params['id']) && !isset($params['all']))) {
            throw new BadRequestHttpException('missing param');
        }
        //check the receiveModes
        if (Goods::STATUS_ON == $params['operation']) {
            if (false === Goods::checkGoodsReceiveModes($params, $accountId)) {
                throw new InvalidParameterException(Yii::t('product', 'receive_mode_not_empty'));
            }
            if (false === Goods::checkGoodsSelfAddress($params, $accountId)) {
                throw new InvalidParameterException(Yii::t('product', 'pickup_location_not_empty'));
            }
        }

        return Goods::updateGoodsStatus($params, $accountId);
    }

    public function actionDelete($id)
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $where = ['status' => Goods::STATUS_OFF, 'accountId' => $accountId];

        if (isset($params['all']) && $params['all']) {
            if (Goods::deleteAll($where) === false) {
                throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
            }
        } else {
            $idstrList = explode(',', $id);
            $ids = [];
            foreach ($idstrList as $perId) {
                $ids[] = new \MongoId($perId);
            }
            //check the goods are not  on shelves
            $where['status'] = Goods::STATUS_ON;
            $where = array_merge($where, ['_id' => ['$in' => $ids]]);
            if (!empty($goods = Goods::findAll($where))) {
                throw new ServerErrorHttpException(Yii::t('product', 'delete_on_shelves'));
            }
            unset($where['status']);
            if (Goods::deleteAll($where) == false) {
                throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
            }
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $goods = Goods::findByPk(new MongoId($id));
        if (empty($goods)) {
            throw new BadRequestHttpException('Invalid goods id');
        }
        if ($goods->status == Goods::STATUS_ON) {
            throw new BadRequestHttpException(Yii::t('product', 'goods_on_shelves'));
        }

        $goods->pictures = empty($params['pictures']) ? $goods->pictures : $params['pictures'];
        $goods->score = !isset($params['score']) ? $goods->score : $params['score'];
        $goods->total = !isset($params['total']) ? $goods->total : $params['total'];
        $goods->description = isset($params['description']) ? $params['description'] : $goods->description;

        $goods = Goods::setGoodsStatusAndTime($params, $goods);

        $goods->receiveModes = empty($params['receiveModes']) ? '' : $params['receiveModes'];
        $goods->addresses = empty($params['addresses']) ? [] : $params['addresses'];

        if ($goods->save(true)) {
            $goods->_id = (string) $goods->_id;
            return $goods;
        } else {
            throw new ServerErrorHttpException('save error');
        }
    }

    public function actionOfflineExchange()
    {
        $params = $this->getParams();
        if (empty($params['goods']) || empty($params['memberId']) || !isset($params['usedScore']) || empty($params['address']) || empty($params['receiveMode'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $userId = $this->getUserId();
        $user = User::findByPk($userId);
        $user = [
            'id' => $user->_id,
            'name' => $user->name
        ];

        if ($params['usedScore'] < 0) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }

        $member = Member::findByPk(new \MongoId($params['memberId']));
        if (empty($member) || $member->isDisabled) {
            throw new InvalidParameterException(Yii::t('member', 'invalid_member_id'));
        }

        $goodsExchangeMap = [];
        $goodsIds = [];
        foreach ($params['goods'] as $item) {
            if ($item['count'] <= 0) {
                throw new BadRequestHttpException(Yii::t('common', 'data_error'));
            }
            $goodsExchangeMap[$item['id']] = $item['count'];
            $goodsIds[] = new \MongoId($item['id']);
        }

        $allExchangeGoods = Goods::getByIds($goodsIds);
        if (count($allExchangeGoods) != count($goodsIds)) {
            throw new InvalidParameterException(Yii::t('product', 'product_deleted'));
        }
        $usedScore = $params['usedScore'];
        $expectedScore = 0;
        $exchanges = [];
        foreach ($allExchangeGoods as $goods) {
            $exchangeCount = $goodsExchangeMap[(string) $goods->_id];
            if ($goods->total === 0) {
                throw new InvalidParameterException([(string) $goods->_id => Yii::t('product', 'goods_sold_out')]);
            } else if (!empty($goods->total) && $exchangeCount > $goods->total) {
                throw new InvalidParameterException([(string) $goods->_id => Yii::t('product', 'goods_not_enough')]);
            }
            $expectedScore += $goods->score * $exchangeCount;
            $exchanges[] = [
                'goods' => $goods,
                'count' => $exchangeCount,
            ];
        }
        $params['expectedScore'] = $expectedScore;
        if ($member->score < $usedScore) {
            throw new InvalidParameterException(Yii::t('product', 'member_score_not_enough'));
        }
        $successExchange = [];
        foreach ($exchanges as $exchangeItem) {
            $goods = $exchangeItem['goods'];
            $count = $exchangeItem['count'];
            $goodsCondition = ['_id' => $goods->_id];
            $goodsModifier = ['$inc' => ['usedCount' => $count]];
            $goodsRollbackModifier = ['$inc' => ['usedCount' => (0 - $count)]];
            if ($goods->total !== '') {
                $goodsCondition['total'] = ['$gte' => $count];
                $goodsModifier['$inc']['total'] = 0 - $count;
                $goodsRollbackModifier['$inc']['total'] = $count;
            }
            $goodsUpdatedCount = Goods::updateAll($goodsModifier, $goodsCondition);
            if ($goodsUpdatedCount !== 1) {
                $this->_rollBackUsedCount($successExchange);
                throw  new InvalidParameterException([(string) $goods->_id => Yii::t('product', 'goods_not_enough')]);
            } else {
                $goodsId = (string) $goods->_id;
                $successExchange[$goodsId] = $goodsRollbackModifier;
            }
        }
        $memberUpdatedCount = Member::updateAll(['$inc' => ['score' => (0-$usedScore)]], ['_id' => $member->_id, 'score' => ['$gte' => $usedScore]]);
        if ($memberUpdatedCount === 1) {
            $this->_saveLog($member, $exchanges, $params, $user);
            if (!empty($params['useWebhook'])) {
                $eventData = [
                    'type'=> Webhook::EVENT_PRODUCT_REDEEMED,
                    'member_id'=> $params['memberId'],
                    'products'=> $params['goods'],
                    'address'=> $params['address'],
                    'postcode'=> $params['postcode'],
                    'used_score'=> $params['usedScore'],
                    'origin' => Member::PORTAL,
                    'account_id' => (string) $member->accountId,
                    'created_at' => TimeUtil::msTime2String(time()*TimeUtil::MILLI_OF_SECONDS, \DateTime::ATOM)
                ];
                Yii::$app->webhook->triggerEvent($eventData);
            }
        } else {
            $this->_rollBackUsedCount($successExchange);
            throw  new InvalidParameterException(Yii::t('product', 'member_score_not_enough'));
        }
    }

    public function actionExchange()
    {
        $params = $this->getParams();
        if (empty($params['memberId']) || empty($params['goodsId']) || empty($params['channelId']) || empty($params['receiveMode']) ||
            empty($params['phone']) || empty($params['captcha']) || !isset($params['count']) || empty($params['address'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if ($params['count'] < 0) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }

        $this->attachBehavior('CaptchaBehavior', new CaptchaBehavior);
        $this->checkCaptcha($params['phone'], $params['captcha']);

        $member = Member::findByPk(new \MongoId($params['memberId']));
        if (empty($member) || $member->isDisabled) {
            throw new BadRequestHttpException(Yii::t('member', 'invalid_member_id'));
        }
        $goods = Goods::findByPk(new \MongoId($params['goodsId']));
        if (empty($goods)) {
            throw new BadRequestHttpException(Yii::t('product', 'invalid_goods_id'));
        }
        if ((string) $goods->accountId !== (string) $member->accountId) {
            throw new BadRequestHttpException(Yii::t('product', 'different_account'));
        }
        if ($goods->status === Goods::STATUS_OFF) {
            throw new InvalidParameterException(Yii::t('product', 'goods_not_on_shelves'));
        }
        if (!is_array($goods->receiveModes) || !in_array($params['receiveMode'], $goods->receiveModes)) {
            throw new InvalidParameterException(Yii::t('product', 'receive_mode_invalid'));
        }

        $usedScore = $goods->score * $params['count'];
        $params['usedScore'] = $usedScore;
        $params['expectedScore'] = $usedScore;
        if ($member->score < $usedScore) {
            throw new InvalidParameterException(Yii::t('product', 'member_score_not_enough'));
        }
        //if $goods->total is '', means no limit
        if ($goods->total === 0) {
            throw new InvalidParameterException(Yii::t('product', 'goods_sold_out'));
        }
        if (!empty($goods->total) && $params['count'] > $goods->total) {
            throw new InvalidParameterException(Yii::t('product', 'goods_not_enough'));
        }

        $goodsCondition = ['_id' => $goods->_id];
        $goodsModifier = ['$inc' => ['usedCount' => $params['count']]];
        $goodsRollbackModifier = ['$inc' => ['usedCount' => (0-$params['count'])]];
        if ($goods->total !== '') {
            $goodsCondition['total'] = ['$gte' => $params['count']];
            $goodsModifier['$inc']['total'] = 0 - $params['count'];
            $goodsRollbackModifier['$inc']['total'] = $params['count'];
        }
        $goodsUpdatedCount = Goods::updateAll($goodsModifier, $goodsCondition);
        if ($goodsUpdatedCount === 1) {
            $memberUpdatedCount = Member::updateAll(['$inc' => ['score' => (0-$usedScore)]], ['_id' => $member->_id, 'score' => ['$gte' => $usedScore]]);
            if ($memberUpdatedCount === 1) {
                $exchanges = [
                    ['goods' => $goods, 'count' => $params['count']]
                ];
                if ($this->_saveLog($member, $exchanges, $params)) {
                    return ['message' => 'OK', 'data' => null];
                } else {
                    Goods::updateAll($goodsRollbackModifier, ['_id' => $goods->_id]);
                    Member::updateAll(['$inc' => ['score' => $usedScore]], ['_id' => $member->_id]);
                    LogUtil::error(['message info' => 'save exchange log error', 'params' => $params, 'goods' => $goods->toArray(), 'member' => $member->toArray()], 'product');
                }
            } else {
                Goods::updateAll($goodsRollbackModifier, ['_id' => $goods->_id]);
                throw new InvalidParameterException(Yii::t('product', 'member_score_not_enough'));
            }
        } else {
            throw new InvalidParameterException(Yii::t('product', 'goods_sold_out'));
        }
    }

    private function _rollBackUsedCount($successExchanges)
    {
        foreach ($successExchanges as $goodsId => $goodsRollbackModifier) {
            Goods::updateAll($goodsRollbackModifier, ['_id' => new \MongoId($goodsId)]);
        }
    }

    private function _saveLog(Member $member, $exchanges, $params, $user = null)
    {
        $goodsExchangeLog = new GoodsExchangeLog;
        $allGoods = [];
        $totalCount = 0;
        $scoreHistoryDescription = '';
        foreach ($exchanges as $exchange) {
            $goods = $exchange['goods'];
            $count = $exchange['count'];
            $pictures = $goods->pictures;
            $allGoods[] = [
                'id' => $goods->_id,
                'productId' => $goods->productId,
                'sku' => $goods->sku,
                'picture' => empty($pictures[0]) ? '' : $pictures[0],
                'productName' => $goods->productName,
                'count' => $count
            ];
            $totalCount += $count;
            $scoreHistoryDescription .= $goods->productName . "($count); ";
        }
        $scoreHistoryDescription = trim($scoreHistoryDescription, '; ');
        $goodsExchangeLog->goods = $allGoods;
        $goodsExchangeLog->memberId = $member->_id;
        $properties = $member->properties;
        $name = '';
        foreach ($properties as $property) {
            if ($property['name'] == Member::DEFAULT_PROPERTIES_NAME) {
                $name = $property['value'];
            }
            if ($property['name'] == Member::DEFAULT_PROPERTIES_MOBILE) {
                $mobile = $property['value'];
            }
        }
        $goodsExchangeLog->memberName = $name;
        $goodsExchangeLog->telephone = empty($params['phone']) ? $mobile : $params['phone'];
        $goodsExchangeLog->usedScore = $params['usedScore'];
        $goodsExchangeLog->expectedScore = $params['expectedScore'];
        $goodsExchangeLog->count = $totalCount;
        $goodsExchangeLog->address = $params['address'];
        $goodsExchangeLog->receiveMode = $params['receiveMode'];
        $goodsExchangeLog->postcode = empty($params['postcode']) ? '' : $params['postcode'];
        $goodsExchangeLog->isDelivered = false;

        $scoreHistoryChannel = [];
        if (!empty($params['channelId'])) {
            $channelInfo = Channel::getByChannelId($params['channelId'], $member->accountId);
            $scoreHistoryChannel = [
                'id' => $channelInfo->channelId,
                'name' => $channelInfo->name,
                'origin' => $channelInfo->origin
            ];
            $goodsExchangeLog->usedFrom = ['id' => $params['channelId'], 'type' => $channelInfo->origin, 'name' => $channelInfo->name];
        } else {
            $scoreHistoryChannel = [
                'origin' => GoodsExchangeLog::PORTAL
            ];
            $goodsExchangeLog->usedFrom = ['id' => '', 'type' => GoodsExchangeLog::PORTAL, 'name' => GoodsExchangeLog::OFFLINE_EXCHANGE];
        }
        $goodsExchangeLog->accountId = $member->accountId;

        $scoreHistory = new ScoreHistory;
        $scoreHistory->assigner = ScoreHistory::ASSIGNER_EXCHAGE_GOODS;
        $scoreHistory->increment = 0 - $params['usedScore'];
        $scoreHistory->memberId = $member->_id;
        $scoreHistory->brief = ScoreHistory::ASSIGNER_EXCHAGE_GOODS;
        $scoreHistory->description = $scoreHistoryDescription;
        $scoreHistory->accountId = $member->accountId;
        $scoreHistory->channel = $scoreHistoryChannel;
        $scoreHistory->user = $user;

        if ($goodsExchangeLog->save(true) && $scoreHistory->save(true)) {
            MemberLogs::record($member->_id, $member->accountId, MemberLogs::OPERATION_REDEEM);
            return true;
        } else {
            LogUtil::error(['exchange fail' => [$scoreHistory->getErrors(), $goodsExchangeLog->getErrors()]]);
            return false;
        }
    }

     /**
    * get the name from product by string
    * */
    public function actionName()
    {
        $params = $this->getQuery();

        if (empty($params['id'])) {
            throw new BadRequestHttpException('missing params');
        }
        $accountId = $this->getAccountId();

        if (strrpos($params['id'], ',') !== false) {
            $ids = explode(',', $params['id']);
            foreach ($ids as $key => $id) {
                $ids[$key] = new \MongoId($id);
            }
            $params['id'] = $ids;
        } else {
            $params['id'] = [new \MongoId($params['id'])];
        }
        $products = Goods::getGoodsName($params, $accountId);

        $names = [];
        foreach ($products as $product) {
            $names[] = $product->productName;
        }

        return $names;
    }
}
