<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use backend\modules\member\models\Member;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\utils\EarlybirdSmsUtil;

class ExportEarlyBirdDrawMembers
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header'])) {
            ResqueUtil::log(['status' => 'fail to export early bird members of lucky draw', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        $accountId = new \MongoId($args['accountId']);
        $condition = unserialize($args['condition']);
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $rows = $this->preProcessDrawMembers($condition, $accountId);
        ExcelUtil::exportCsv($header, $rows, $filePath, 1);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export early bird failed sms record', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }
    }

    private function preProcessDrawMembers($condition, $accountId)
    {
        $rows = array();
        $scores = EarlybirdSmsUtil::getExchangeGoodsScore($condition['startDate'], $condition['endDate'], $accountId);
        asort($scores);
        foreach ($scores as $key => $value) {
            $row = array();
            if (abs($value) >= $condition['pointsThree']) {
                $member = Member::findByPk(new \MongoId($key));
                if (!$member->isDeleted) {
                    $row['id'] = $key;
                    $row['exchangeGoodsScore'] = abs($value);
                    $row['cardNumber'] = $member->cardNumber;
                    //拿到符合一等奖条件的人
                    if (abs($value) >= $condition['pointsOne']) { //eg: points>=2000
                        $row['prizeName'] = $condition['prizeNameOne'];
                        $row['prizeLevel'] = '一等獎資格';
                    }
                    //拿到符合二等奖条件的人
                    if (abs($value) < $condition['pointsOne'] && abs($value) >= $condition['pointsTwo']) { //eg: 1000<=points<2000
                        $row['prizeName'] = $condition['prizeNameTwo'];
                        $row['prizeLevel'] = '二等獎資格';
                    }
                    //拿到符合三等奖条件的人
                    if (abs($value) < $condition['pointsTwo'] && abs($value) >= $condition['pointsThree']) { //eg: 200<=points<1000
                        $row['prizeName'] = $condition['prizeNameThree'];
                        $row['prizeLevel'] = '三等獎資格';
                    }

                    if (!empty($member->properties)) {
                        foreach ($member->properties as $property) {
                            if ($property['name'] == 'tel') {
                                $row['mobile'] = "'" . $property['value'];
                            }
                            if ($property['name'] == 'name') {
                                $row['name'] = $property['value'];
                            }
                        }
                    }
                }
            }

            $rows[] = $row;
            unset($row, $member);
        }
        return $rows;
    }
}
