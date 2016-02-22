<?php
namespace console\modules\member\controllers;

use Yii;
use yii\console\Controller;
use backend\components\BaseModel;
use backend\modules\member\models\Member;
use backend\models\Message;
use backend\models\Account;
use backend\utils\LogUtil;
use backend\exceptions\ApiDataException;
use yii\helpers\FileHelper;
use backend\modules\member\models\MemberLogs;
use yii\helpers\ArrayHelper;
use backend\modules\product\models\CampaignLog;
use backend\modules\product\models\GoodsExchangeLog;
use backend\models\Qrcode;
use backend\modules\member\models\MemberShipCard;

/**
 * Member relative actions, as reset the member score
 */
class MemberController extends Controller
{
    const BATCH = 1000;

    public function actionAddMemberPhone()
    {
        $startTime = microtime(true);
        $updateNumber = 0;

        $accounts = Account::findAll(['status' => Account::STATUS_ACTIVATED]);
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $offset = 0;
                $where = ['accountId' => $account->_id, 'isDeleted' => false];
                $members = Member::find()->where($where)
                              ->orderBy(['createdAt' => SORT_ASC])
                              ->offset($offset)
                              ->limit(self::BATCH)
                              ->all();
                while (!empty($members)) {
                    foreach ($members as $member) {
                        if (!empty($member->properties)) {
                            foreach ($member->properties as $properties) {
                                if (Member::DEFAULT_PROPERTIES_MOBILE == $properties['name']) {
                                    if ($member->phone != $properties['value']) {
                                        $member->phone = $properties['value'];
                                        $member->save(true, ['phone']);
                                        ++$updateNumber;
                                    }
                                }
                            }
                        }
                    }
                    $offset += self::BATCH;
                    $members = Member::find()->where($where)
                              ->orderBy(['createdAt' => SORT_ASC])
                              ->offset($offset)
                              ->limit(self::BATCH)
                              ->all();
                }
            }
        }

        echo 'spend time:' . (microtime(true) - $startTime) . PHP_EOL;
        echo 'total member :' . Member::count(['isDeleted' => false]) . PHP_EOL;
        $where = [
            'isDeleted' => false,
            'phone' => ['$exists' => true]
        ];
        echo 'exists phone field member : ' . Member::count($where) . PHP_EOL;
        echo 'Add member phone success, update member:' . $updateNumber . PHP_EOL;
    }

    /*
     * 1. channel migration: add origin and origin_scene
     * 2. delete member with same phone and score
     * 3. save migration result
     */
    public function actionMigration()
    {
        $this->_channel();
        echo 'Update channel success' . PHP_EOL;

        $this->_repairData();
        echo 'Repair data success' . PHP_EOL;

        $result = $this->_getRepairData();
        if (!empty($result)) {
            echo 'Need Repair data' . PHP_EOL;
        }
        $fileName = 'todelete-' . time();
        foreach ($result as $item) {
            $msg = $item['_id']['tel'] . ' ' . $item['_id']['accountId'] . ' ' . $item['count'] . PHP_EOL;
            echo $msg;
            $this->_saveFile($msg, $fileName);
        }

        echo 'Done' . PHP_EOL;
    }

    /**
     * Clear card expired time if account's card all are auto upgrade
     */
    public function actionClearCardExpiredTime()
    {
        $accounts = Account::findAll(['enabledMods' => 'member']);
        foreach ($accounts as $account) {
            $unAutoUpgradeCards = MemberShipCard::findAll(['isAutoUpgrade' => false, 'accountId' => $account->_id]);
            if (empty($unAutoUpgradeCards)) {
                Member::updateAll(['$unset' => ['cardExpiredAt' => true]], ['accountId' => $account->_id]);
            }
        }
    }

    /**
     * create member log
     */
    public function actionMemberLog($accountId = null)
    {
        $skip = 0;
        $limit = 100;
        $condition = ['isDeleted' => Member::NOT_DELETED];
        if (!empty($accountId)) {
            $condition['accountId'] = new \MongoId($accountId);
        }
        $query = Member::find()->where($condition)->orderBy(['_id' => SORT_ASC]);
        $query = $query->offset($skip)->limit($limit);
        $members = $query->all();

        while (!empty($members)) {
            $memberLogs = [];
            foreach ($members as $member) {
                $memberLogs[] = [
                    'memberId' => $member->_id,
                    'operation' => MemberLogs::OPERATION_VIEWED,
                    'operationAt' => $member->createdAt,
                    'createdAt' => $member->createdAt,
                    'accountId' => $member->accountId
                ];
            }
            MemberLogs::batchInsert($memberLogs);
            $skip += $limit;
            $query = $query->offset($skip)->limit($limit);
            unset($memberLogs, $members);
            $members = $query->all();
        }
    }

    /**
     * Update card number ensure unique
     */
    public function actionCardNumberMigration()
    {
        $skip = 0;
        $limit = 1000;
        $query = Member::find()->orderBy(['cardNumber' => SORT_ASC]);
        $query = $query->offset($skip)->limit($limit);
        $members = $query->all();
        $lastCardNumber = '';
        $currentCardNumber = '';

        $memberIds = [];
        while (!empty($members)) {
            foreach ($members as $member) {
                $currentCardNumber = $member->cardNumber;
                if ($currentCardNumber === $lastCardNumber) {
                    $memberIds[] = $member->_id;
                } else {
                    $lastCardNumber = $member->cardNumber;
                }
            }
            $skip += $limit;
            $query = $query->offset($skip)->limit($limit);
            unset($members);
            $members = $query->all();
        }

        $memberIdCount = Member::find()->count();
        $repeatCount = count($memberIds);
        echo $repeatCount . ' members to be update' . PHP_EOL;
        if ($repeatCount === 0) {
            echo 'success' . PHP_EOL;
            return;
        }
        $redis = Yii::$app->cache->redis;
        $cardNumber = $redis->get(Member::MAX_CARD_NUMBER);
        if (empty($cardNumber)) {
            //Find the member order by cardNumber
            $member = Member::find()->orderBy(['cardNumber' => SORT_DESC])->one();
            if (empty($member) || empty($member['cardNumber'])) {
                $cardNumber = 10000000 + $repeatCount;
            } else {
                $cardNumber = $member['cardNumber'] + $repeatCount;
            }
            $redis->set(Member::MAX_CARD_NUMBER, intval($cardNumber));
        } else {
            $cardNumber = $redis->incrby(Member::MAX_CARD_NUMBER, $repeatCount);
        }

        $index = 0;
        foreach ($memberIds as $memberId) {
            $member = Member::find()->where(['_id' => $memberId])->one();
            $member->cardNumber = (string) ($cardNumber - $index);
            $member->save(true, ['cardNumber']);
            $index++;
        }

        echo 'success' . PHP_EOL;
    }

    /**
     * create redeem member log
     */
    public function actionRedeemMemberLog()
    {
        $accounts = Account::findAll(['enabledMods' => 'product']);
        foreach ($accounts as $account) {
            $accountId = $account->_id;
            $this->_memberLogsFromCampaign($accountId);
            $this->_memberLogsFromGoodsExchange($accountId);
        }
    }

    private function _memberLogsFromGoodsExchange($accountId)
    {
        $skip = 0;
        $limit = 100;
        $query = GoodsExchangeLog::find()->where(['accountId' => $accountId])->orderBy(['createdAt' => SORT_ASC]);
        $query = $query->offset($skip)->limit($limit);
        $goodsExchangeLogs = $query->all();
        while (!empty($goodsExchangeLogs)) {
            $memberLogs = [];
            foreach ($goodsExchangeLogs as $goodsExchangeLog) {
                $memberLogs[] = [
                    'memberId' => $goodsExchangeLog->memberId,
                    'operation' => MemberLogs::OPERATION_REDEEM,
                    'operationAt' => $goodsExchangeLog->createdAt,
                    'createdAt' => $goodsExchangeLog->createdAt,
                    'accountId' => $goodsExchangeLog->accountId
                ];
            }
            MemberLogs::batchInsert($memberLogs);
            $skip += $limit;
            $query = $query->offset($skip)->limit($limit);
            $goodsExchangeLogs = $query->all();
        }
    }

    private function _memberLogsFromCampaign($accountId)
    {
        $skip = 0;
        $limit = 100;
        $query = CampaignLog::find()->where(['accountId' => $accountId])->orderBy(['createdAt' => SORT_ASC]);
        $query = $query->offset($skip)->limit($limit);
        $campaignLogs = $query->all();
        while (!empty($campaignLogs)) {
            $memberLogs = [];
            foreach ($campaignLogs as $campaignLog) {
                $member = $campaignLog->member;
                $memberLogs[] = [
                    'memberId' => $member['id'],
                    'operation' => MemberLogs::OPERATION_REDEEM,
                    'operationAt' => empty($campaignLog->redeemTime) ? $campaignLog->createdAt : $campaignLog->redeemTime,
                    'createdAt' => $campaignLog->createdAt,
                    'accountId' => $campaignLog->accountId
                ];
            }
            MemberLogs::batchInsert($memberLogs);
            $skip += $limit;
            $query = $query->offset($skip)->limit($limit);
            $campaignLogs = $query->all();
        }
    }

    /**
     * delete member log when the member is deleted(delete-member-log)
     */
    public function actionDeleteMemberLog()
    {
        $accounts = Account::findAll(['enabledMods' => 'member']);
        foreach ($accounts as $account) {
            $deletedMembers = Member::find()->where(['isDeleted' => Member::DELETED, 'accountId' => $account->_id])->all();
            $deletedMemberIds = ArrayHelper::getColumn($deletedMembers, '_id');
            MemberLogs::deleteAll(['memberId' => ['$in' => $deletedMemberIds]]);
        }
    }

    /**
     * unset the type in member property(unset-property-type)
     */
    public function actionUnsetPropertyType()
    {
        $members = Member::findAll([]);
        foreach ($members as $member) {
            $properties = $member->properties;
            foreach ($properties as &$property) {
                unset($property['type']);
            }
            $member->properties = $properties;
            $member->save(true, ['properties']);
        }
        echo 'success' . PHP_EOL;
    }

    /**
     * migrate qrcode(qrcode-migration)
     */
    public function actionQrcodeMigration($domain)
    {
        $skip = 0;
        $limit = 1000;
        $query = Member::find()->orderBy(['_id' => SORT_ASC]);
        $query = $query->offset($skip)->limit($limit);
        $members = $query->all();
        while (!empty($members)) {
            foreach ($members as $member) {
                if ($member->isDeleted === false) {
                    $memberId = $member->_id;
                    $qrcode = Qrcode::findOne(['associatedId' => $memberId]);
                    if (empty($qrcode)) {
                        $result = Yii::$app->qrcode->create($domain, Qrcode::TYPE_MEMBER, $memberId, $member->accountId);
                        if (!$result) {
                            echo $memberId . PHP_EOL;
                        }
                    }
                }
            }
            $skip += $limit;
            $query = $query->offset($skip)->limit($limit);
            unset($members);
            $members = $query->all();
        }
    }

    private function _recordMessage($accountId, $content)
    {
        $message = new Message;
        $message->title = 'member_card_expiration_reminder';
        $message->accountId = $accountId;
        $message->status = Message::STATUS_WARNING;
        $message->to = ['id' => Message::ID_ACCOUNT, 'target' => Message::TO_TARGET_ACCOUNT];
        $message->sender = ['id' => Message::ID_SYSTEM, 'from' => Message::SENDER_FROM_SYSTEM];
        $message->content = $content;
        $message->save();
    }

    private function _channel()
    {
        $skip = 0;
        $limit = 100;
        $map = [
            'WEIXIN' => 'wechat',
            'WEIBO' => 'weibo',
            'ALIPAY' => 'alipay'
        ];
        $channelTypeMap = [];
        $channels = \Yii::$app->weConnect->allAccounts();
        foreach ($channels as $channel) {
            $channelTypeMap[$channel['id']] = $map[$channel['channel']];
        }
        $query = Member::find()->where([])->orderBy(['createdAt' => SORT_ASC]);
        $query = $query->offset($skip)->limit($limit);
        $members = $query->all();
        $fileMemberNeedCheck = 'fail-' . time();
        $fileMemberError = 'error-' . time();

        while (!empty($members)) {
            foreach ($members as $member) {
                try {
                    if (!empty($member->socialAccountId) && empty($member->origin)) {
                        $member->origin = $channelTypeMap[$member->socialAccountId];
                    }
                    if (!empty($member->openId) && !empty($member->socialAccountId) && empty($member->originScene)) {
                        $openId = $member->openId;
                        $channelId = $member->socialAccountId;
                        $follower = \Yii::$app->weConnect->getFollowerByOriginId($openId, $channelId);
                        $member->originScene = empty($follower['firstSubscribeSource']) ? '' : $follower['firstSubscribeSource'];
                    }
                    $member->save(true, ['origin', 'originScene', 'socials']);
                } catch (\Exception $e) {
                    $msg = 'error member ' . $member->_id . PHP_EOL;
                    echo $msg;
                    $this->_saveFile($msg . $e->getMessage() . PHP_EOL, $fileMemberError);
                }
            }
            $skip += $limit;
            $query = $query->offset($skip)->limit($limit);
            $members = $query->all();
        }
    }

    private function _getRepairData()
    {
        $pipeline = [
            ['$unwind' => '$properties'],
            ['$match' => ['properties.name' => Member::DEFAULT_PROPERTIES_MOBILE, 'isDeleted' => Member::NOT_DELETED]],
            ['$group' => ['_id' => ['tel' => '$properties.value', 'accountId' => '$accountId'], 'count' => ['$sum' => 1]]],
            ['$match' => ['count' => ['$gt' => 1]]]
        ];
        $result = Member::getCollection()->aggregate($pipeline);

        return $result;
    }

    private function _repairData()
    {
        $fileName = 'deleted-' . time();
        $result = $this->_getRepairData();
        foreach ($result as $item) {
            $condition = [
                'properties' => [
                    '$elemMatch' => [
                        'name' => Member::DEFAULT_PROPERTIES_MOBILE,
                        'value' => $item['_id']['tel']
                    ]
                ],
                'accountId' => $item['_id']['accountId']
            ];
            $members = Member::findAll($condition);
            $score = [];
            $keepMember = [];
            $deleteMember = [];
            foreach ($members as $member) {
                if (!in_array($member->score, $score)) {
                    $score[] = $member->score;
                    $keepMember[] = $member->_id;
                } else {
                    $msg = 'deleted member ' . $member->_id . PHP_EOL;
                    $deleteMember[] = $member->_id;
                    echo $msg;
                    $this->_saveFile($msg, $fileName);
                }
            }
            $deleteCondition = [
                '_id' => ['$in' => $deleteMember],
            ];
            Member::deleteAll($deleteCondition);
        }
    }

    private function _saveFile($data, $fileName)
    {
        if (!empty($data)) {
            $target = strtolower($fileName);
            $targetFile = \Yii::$app->getRuntimePath() . '/migration/' . $fileName . '.log';
            $targetPath = dirname($targetFile);
            if (!is_dir($targetPath)) {
                FileHelper::createDirectory($targetPath, 0777, true);
            }

            $file = fopen($targetFile, 'a');
            fwrite($file, $data);
            fclose($file);

            return $targetFile;
        }
    }
}
