<?php
namespace backend\modules\member\job;

use backend\modules\resque\components\ResqueUtil;
use backend\models\Store;
use backend\models\StoreLocation;
use backend\modules\member\models\ScoreHistory;
use backend\modules\member\models\MemberLogs;
use backend\modules\product\models\CampaignLog;
use backend\modules\product\models\PromotionCode;
use backend\modules\product\models\GoodsExchangeLog;
use backend\models\Qrcode;
use backend\modules\member\models\Member;
use backend\components\resque\BaseJob;
use backend\models\Order;

/**
* Job for Merge Member
*/
class MergeMember extends BaseJob
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['mainMember']) || empty($args['otherMemberIds'])) {
            ResqueUtil::log(['Merge member args error' => $args]);
            return;
        }
        $mainMember = unserialize($args['mainMember']);
        $otherMemberIds = unserialize($args['otherMemberIds']);
        //Get Name and phone
        $name = $phone = '';
        foreach ($mainMember->properties as $mainProperty) {
            if ($mainProperty['name'] === Member::DEFAULT_PROPERTIES_NAME) {
                $name = $mainProperty['value'];
            }
            if ($mainProperty['name'] === Member::DEFAULT_PROPERTIES_MOBILE) {
                $phone = $mainProperty['value'];
            }
        }
        ScoreHistory::updateAll(['$set' => ['memberId' => $mainMember->_id]], ['memberId' => ['$in' => $otherMemberIds]]);
        MemberLogs::deleteAll(['memberId' => ['$in' => $otherMemberIds]]);
        CampaignLog::updateAll(
            [
                '$set' => [
                    'member.id' => $mainMember->_id,
                    'member.cardNumber' => $mainMember->cardNumber,
                    'member.name' => $name,
                    'member.phone' => $phone,
                ]
            ],
            ['member.id' => ['$in' => $otherMemberIds]]
        );
        PromotionCode::updateAll(
            [
                '$set' => [
                    'usedBy.memberId' => $mainMember->_id,
                    'usedBy.memberNumber' => $mainMember->cardNumber
                ]
            ],
            ['usedBy.memberId' => ['$in' => $otherMemberIds]]
        );
        GoodsExchangeLog::updateAll(
            [
                '$set' => [
                    'memberId' => $mainMember->_id,
                    'memberName' => $name,
                    'telephone' => $phone
                ]
            ],
            ['memberId' => ['$in' => $otherMemberIds]]
        );
        $otherMemberStrIds = [];
        foreach ($otherMemberIds as $otherMemberId) {
            $otherMemberStrIds[] = (string) $otherMemberId;
        }
        Order::updateAll(
            [
            '$set' => [
                'consumer.id' => (string) $mainMember->_id,
                'consumer.name' => $name,
                'consumer.phone' => $phone
            ]
            ],
            ['consumer.id' => ['$in' => $otherMemberStrIds]]
        );
        Qrcode::deleteAll(['type' => Qrcode::TYPE_MEMBER, 'associatedId' => ['$in' => $otherMemberIds]]);
    }
}
