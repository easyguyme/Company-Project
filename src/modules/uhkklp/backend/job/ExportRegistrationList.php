<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use yii\mongodb\Query;
use backend\utils\LogUtil;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\Registration;
use backend\modules\uhkklp\models\SmsModel;
use backend\utils\MongodbUtil;

class ExportRegistrationList
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['key']) || empty($args['header']) || empty($args['accountId'])) {
            ResqueUtil::log(['status' => 'fail to export registration record', 'message' => 'missing params', 'args' => $args]);
            return false;
        }

        $keyword = $args['keyword'];
        $header = $args['header'];
        $fileName = $args['key'];
        $accountId = unserialize($args['accountId']);
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $query = new Query();

        if ($keyword == '') {
            $records = $query->from('uhkklpRegistration')
            ->select(['activityName', 'name', 'mobile', 'restaurantName', 'businessForm', 'perComingDay', 'perPrice', 'registrationNumber', 'address', 'city', 'registrationTime', 'confirmRegistration', 'restaurantId', 'lineName'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->all();
        } else {
            $records = $query->from('uhkklpRegistration')
            ->select(['activityName', 'name', 'mobile', 'restaurantName', 'businessForm', 'perComingDay', 'perPrice', 'registrationNumber', 'address', 'city', 'registrationTime', 'confirmRegistration', 'restaurantId', 'lineName'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->andWhere(['like','mobile',$keyword])
            ->all();
        }

        for ($i = 0;$i < count($records);$i++) {
            $records[$i]['registrationTime'] = MongodbUtil::MongoDate2String(MongodbUtil::msTimetamp2MongoDate($records[$i]['registrationTime']));
        }

        $rows = $records;

        ExcelUtil::exportCsv($header, $rows, $filePath, 1);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export registration', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }
    }
}
