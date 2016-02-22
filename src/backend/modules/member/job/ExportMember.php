<?php
namespace backend\modules\member\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\member\models\MemberProperty;
use backend\models\Account;
use backend\modules\member\models\MemberShipCard;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;
use backend\utils\ExcelUtil;
use backend\models\Message;
use backend\utils\LanguageUtil;
use backend\models\Channel;

class ExportMember
{

    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header'])) {
            ResqueUtil::log(['status' => 'fail to export member', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        Yii::$app->language = empty($args['language']) ? LanguageUtil::DEFAULT_LANGUAGE : $args['language'];
        $accountId = new \MongoId($args['accountId']);
        $header = $args['header'];
        // get member's customized properties
        $memberProperties = MemberProperty::getByAccount($accountId);
        foreach ($memberProperties as $memberProperty) {
            if ($memberProperty->isDefault) {
                $header[$memberProperty->name] = Yii::t('member', $memberProperty->name);
            } else {
                $header[$memberProperty->name] = $memberProperty->name;
            }
        }

        $socialAccountsMap = [];
        $account = Account::findByPk($accountId);
        $channelIds = Channel::getEnableChannelIds($accountId);
        if (!empty($channelIds)) {
            $socialAccounts = \Yii::$app->weConnect->getAccounts($channelIds);
            foreach ($socialAccounts as $socialAccount) {
                $socialAccountsMap[$socialAccount['id']] = $socialAccount['name'];
            }
        }

        $cardMap = [];
        $cards = MemberShipCard::getByAccount($accountId);
        foreach ($cards as $card) {
            $cardMap[(string) $card->_id] = $card->name;
        }

        $condition = unserialize($args['condition']);
        //get properties
        $memberProperties = MemberProperty::findAll(['accountId' => $accountId]);

        $base = [
            'cardMap' => $cardMap,
            'socialAccountsMap' => $socialAccountsMap,
            'memberProperties' => $memberProperties,
        ];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $orderBy = Member::normalizeOrderBy($args['params']);
        $object = Member::find();
        $classFunction = '\backend\modules\member\models\Member::preProcessMemberData';
        ExcelUtil::processMultiData($header, $filePath, $base, $condition, $object, $classFunction, [], $orderBy);
        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            \Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            return false;
        }
    }
}
