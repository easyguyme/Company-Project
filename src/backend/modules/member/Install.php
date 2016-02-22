<?php
namespace backend\modules\member;

use Yii;
use backend\modules\member\models\MemberProperty;
use backend\modules\member\models\ScoreRule;
use backend\modules\member\models\MemberShipCard;
use backend\components\BaseInstall;
use backend\utils\LogUtil;
use yii\web\ServerErrorHttpException;

class Install extends BaseInstall
{
    /**
     * Create default member properties
     */
    private function _createDefaultMemberProperties($accountId)
    {
        $defaultProperties = [
            [
                'order' => 1,
                'name' => 'name',
                'type' => MemberProperty::TYPE_INPUT,
                'defaultValue' => '',
                'isRequired' => true,
                'isUnique' => true,
                'isVisible' => true,
                'isDefault' => true,
                'accountId' => $accountId,
            ],
            [
                'order' => 2,
                'name' => 'tel',
                'type' => MemberProperty::TYPE_INPUT,
                'defaultValue' => '',
                'isRequired' => true,
                'isUnique' => true,
                'isVisible' => true,
                'isDefault' => true,
                'accountId' => $accountId,
            ],
            [
                'order' => 3,
                'name' => 'gender',
                'type' => MemberProperty::TYPE_RADIO,
                'defaultValue' => 'male',
                'options' => [
                    'male', 'female'
                ],
                'isRequired' => false,
                'isUnique' => false,
                'isVisible' => true,
                'isDefault' => true,
                'accountId' => $accountId,
            ],
            [
                'order' => 4,
                'name' => 'birthday',
                'type' => MemberProperty::TYPE_DATE,
                'defaultValue' => '',
                'isRequired' => true,
                'isUnique' => false,
                'isVisible' => true,
                'isDefault' => true,
                'accountId' => $accountId,
            ],
            [
                'order' => 5,
                'name' => 'email',
                'type' => MemberProperty::TYPE_EMAIL,
                'defaultValue' => '',
                'isRequired' => false,
                'isUnique' => true,
                'isVisible' => true,
                'isDefault' => true,
                'accountId' => $accountId,
            ]
        ];
        $rows = [];
        foreach ($defaultProperties as $defaultProperty) {
            $property = MemberProperty::getDefaultByName($accountId, $defaultProperty['name']);
            if (empty($property)) {
                $rows[] = $defaultProperty;
            }
        }
        $result = MemberProperty::batchInsert($rows);
        if (!$result) {
            LogUtil::error(['message' => 'Failed to create default member propertise', 'accountId' => (string) $accountId], 'member');
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
    }

    /**
     * create default score rule for the current account
     */
    private function _createDefaultScoreRule($accountId)
    {
        $defaultRules = [
            [
                'name' => ScoreRule::NAME_PERFECT_INFO,
                'type' => ScoreRule::TYPE_EVENT,
                'accountId' => $accountId,
                'isEnabled' => false,
            ],
            [
                'name' => ScoreRule::NAME_BIRTHDAY,
                'type' => ScoreRule::TYPE_EVENT,
                'accountId' => $accountId,
                'isEnabled' => false,
            ],
            [
                'name' => ScoreRule::NAME_FIRST_CARD,
                'type' => ScoreRule::TYPE_EVENT,
                'accountId' => $accountId,
                'isEnabled' => false,
            ]
        ];
        $rows = [];
        foreach ($defaultRules as $defaultRule) {
            $scoreRule = ScoreRule::getByName($defaultRule['name'], $accountId);
            if (empty($scoreRule)) {
                $rows[] = $defaultRule;
            }
        }
        $result = ScoreRule::batchInsert($rows);
        if (!$result) {
            LogUtil::error(['message' => 'Failed to create default score rule', 'accountId' => (string) $accountId], 'member');
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
    }

    private function _createDefaultMemberShipCard($accountId)
    {
        $defaultCard = MemberShipCard::getDefault($accountId);
        if (empty($defaultCard)) {
            $defaultMemberShipCard = Yii::$app->params['default_member_ship_card'];
            $memberShipCard = new MemberShipCard;
            $memberShipCard->attributes = $defaultMemberShipCard;
            $memberShipCard->accountId = $accountId;

            if (!$memberShipCard->save()) {
                LogUtil::error(['message' => 'Failed to create default card', 'accountId' => (string) $accountId], 'member');
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        }
    }

    public function run($accountId)
    {
        parent::run($accountId);

        $this->_createDefaultScoreRule($accountId);
        $this->_createDefaultMemberProperties($accountId);
        $this->_createDefaultMemberShipCard($accountId);
    }
}
