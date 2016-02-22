<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use backend\utils\LogUtil;
use backend\models\Token;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\Registration;
use backend\modules\uhkklp\models\ActivitySetting;
use backend\models\User;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;

class RegistrationController extends BaseController
{
    public $enableCsrfValidation = false;

    private function _setJSONFormat($app) {
        $app->request->parsers = [
            'application/json' => 'yii\web\JsonParser',
            'text/json' => 'yii\web\JsonParser',
        ];
        $app->response->format = 'json';
    }

    public function actionSave()
    {
        $this->_setJSONFormat(Yii::$app);
        $request = Yii::$app->request;
        $userId = $request->post("userId", '');

        if ($userId == "") {
            $adminId = $request->post("id");
            $admin = User::findOne($adminId);
            if ($admin == null) {
                return ['code' => 1209,'msg' => 'not login'];
            }
        }

        $activityName = $request->post("activityName", '');
        $mobile = $request->post("mobile", '');
        $resultCondition = ['activityName' => $activityName, 'mobile' => $mobile, 'isDeleted' => false];
        $result = Registration::findOne($resultCondition);
        if (!empty($result)) {
            return ['msg' => 'failed', 'code' => '2000'];
        }

        $accountId = $this->getAccountId();
        $result = Member::getByMobile($mobile, $accountId);
        $tags = $result['tags'];
        $isAlllow = false;
        $activity = ActivitySetting::findOne(['name' => $activityName]);

        if ($activity['registrationTagString'] == "" || empty($result)) {
            $isAlllow = true;
        } else {
            for ($i = 0; $i < count($tags); $i++) {
                if ($activity['registrationTagString'] == $tags[$i]) {
                    $isAlllow = true;
                }
            }

            $splitTmp = explode("^", $activity['registrationTagString']);
            for ($i = 0; $i < count($tags); $i++) {
                for ($j = 0; $j < count($splitTmp); $j++) {
                    if ($tags[$i] == $splitTmp[$j]) {
                        $isAlllow = true;
                    }
                }
            }
        }

        if ($isAlllow == false) {
            return ['msg' => 'failed', 'code' => '20000'];
        }

        $name = $request->post("name", '');
        $restaurantName = $request->post("restaurantName", '');
        $businessForm = $request->post("businessForm", '');
        $perPrice = $request->post("perPrice", '');
        $registrationNumber = $request->post("registrationNumber", '');
        $perComingDay = $request->post("perComingDay", '');
        $city = $request->post("city", '');
        $address = $request->post("address", '');
        $lineName = $request->post("lineName", '');
        $registrationTime = $request->post("registrationTime", '');
        if ($registrationTime == "") {
            list($tmp1, $tmp2) = explode(' ', microtime());
            $registrationTime = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
        }
        $restaurantId = $request->post("restaurantId", '');
        $confirmRegistration = $request->post("confirmRegistration", '');

        $Registration = new Registration();
        $Registration->accountId = $accountId;
        $Registration->userId = $userId;
        $Registration->activityName = $activityName;
        $Registration->name = $name;
        $Registration->mobile = $mobile;
        $Registration->restaurantName = $restaurantName;
        $Registration->businessForm = $businessForm;
        $Registration->perPrice = $perPrice;
        $Registration->registrationNumber = $registrationNumber;
        $Registration->perComingDay = $perComingDay;
        $Registration->city = $city;
        $Registration->address = $address;
        $Registration->lineName = $lineName;
        $Registration->registrationTime = $registrationTime;
        $Registration->restaurantId = $restaurantId;
        $Registration->confirmRegistration = $confirmRegistration;
        $result = $Registration->save();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($result > 0) {
            $res = Member::getByMobile($mobile, $accountId);
            if (!empty($res)) {
                $memberIds = array($res['_id']);
                $tags = array($activityName . 'A');
                foreach ($memberIds as &$memberId) {
                    $memberId = new \MongoId($memberId);
                }

                $condition = ['in', '_id', $memberIds];
                Member::updateAll(['$addToSet' => ['tags' => ['$each' => $tags]]], $condition);

                $accountId = new \MongoId($this->getAccountId());
                $service = Yii::$app->service->setAccountId($accountId);
                $service->tag->create([$activityName . 'A']);

                // change member properties
                // $userId = $res['_id']->{'$id'};
                // if ($userId != '' || $userId != null) {
                //     $properties = [];
                //     $service = Yii::$app->service->setAccountId($this->getAccountId());
                //     $result = ['properties' => $service->memberProperty->all()];
                //     $items = $result['properties'];
                //     if ($items != null) {
                //         for ($i=0; $i < count($items); $i++) {
                //             $property = null;
                //             if ($items[$i]['name'] == '餐廳名稱') {
                //                 $property['value'] = $restaurantName;
                //             } else if ($items[$i]['name'] == '經營形態') {
                //                 $property['value'] = $businessForm;
                //             } else if ($items[$i]['name'] == '平均消費單價') {
                //                 $property['value'] = $perPrice;
                //             } else if ($items[$i]['name'] == '每日來客數量') {
                //                 $property['value'] = $perComingDay;
                //             } else if ($items[$i]['name'] == '餐廳縣市') {
                //                 $property['value'] = $city;
                //             } else if ($items[$i]['name'] == '餐廳地址') {
                //                 $property['value'] = $address;
                //             } else {
                //                 continue;
                //             }
                //             $property['id'] = $items[$i]['_id']->{'$id'};
                //             $property['name'] = $items[$i]['name'];
                //             array_push($properties, $property);
                //         }
                //         $result = $service->member->updateProperties($userId, $properties);
                //         if ($result['message'] != 'ok') {
                //             LogUtil::error('Update' . ' mobile:' . $mobile . 'Save member error!' . ' time' . time(),'registrationDelete');
                //             //return ['code' => 1209, 'msg' => 'Save member error!'];
                //         }
                //     }
                // }
                return ['msg' => 'success', 'code' => '200'];
            } else {
                return ['msg' => 'success', 'code' => '200'];
            }
        } else {
            return ['msg' => 'failed', 'code' => '500'];
        }
    }

    public function actionTest()
    {
        $this->_setJSONFormat(Yii::$app);
        $request = Yii::$app->request;
        $tagName = $request->post("tagName", '');

        $accountId = new \MongoId("55dd5e5c475df4a12c8b4567");
        $service = Yii::$app->service->setAccountId($accountId);
        $res = $service->tag->create([$tagName]);

        return ['result' => $res];
    }

    public function actionValidateNumber()
    {
        $mobile = Yii::$app->request->post("mobile");
        $result = Member::getByMobile($mobile, $this->getAccountId());
        return $result;
    }

    public function actionGetList()
    {
        $currentPage = Yii::$app->request->get("currentPage", 1);
        $pageSize = Yii::$app->request->get("pageSize", 10);
        $offset = ($currentPage - 1) * $pageSize;
        $sortName = "_id";
        $sortDesc = Yii::$app->request->get('sortDesc', 'ASC');
        $sort = $sortName . ' ' . $sortDesc;
        $keyword = Yii::$app->request->get("keyword", '');

        $query = new Query();
        $accountId = $this->getAccountId();

        if ($keyword == '') {
            $records = $query->from('uhkklpRegistration')
            ->select(['_id', 'createdAt', 'activityName', 'name', 'mobile', 'restaurantName', 'address', 'city', 'registrationNumber'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->orderBy($sort)
            ->offset($offset)
            ->limit($pageSize)
            ->all();
        } else {
            $records = $query->from('uhkklpRegistration')
            ->select(['_id', 'createdAt', 'activityName', 'name', 'mobile', 'restaurantName', 'address', 'city', 'registrationNumber'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->andWhere(['like','mobile',$keyword])
            ->orderBy($sort)
            ->offset($offset)
            ->limit($pageSize)
            ->all();
        }

        for ($i = 0;$i < count($records);$i++) {
            $records[$i]['createdAt'] = MongodbUtil::MongoDate2String($records[$i]['createdAt'], 'Y-m-d H:i:s', null);
            $records[$i]['_id'] = (string)$records[$i]['_id'];
        }

        $query = new Query();
        if ($keyword == '') {
            $totalPageCount = $query->from('uhkklpRegistration')
            ->where(['accountId' => ($accountId), 'isDeleted' => false])
            ->count();
        } else {
            $totalPageCount = $query->from('uhkklpRegistration')
            ->where(['accountId' => ($accountId), 'isDeleted' => false])
            ->andWhere(['like','mobile',$keyword])
            ->count();
        }

        // LogUtil::error(date('Y-m-d h:i:s') . ' $totalPageCount: ' . $totalPageCount);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records, 'totalPageCount' => $totalPageCount];
    }

    public function actionDeleteOne()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];

        $result = Registration::findOne($resultCondition);
        $result->isDeleted = true;
        $res = $result->save();
        if (!empty($res)) {
            return ['result' => 'success'];
        } else {
            LogUtil::error(['message'=>'Registration刪除失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'registrationDelete');
            return ['result' => 'error'];
        }
    }

    public function actionExportRegistration()
    {
        $keyword = $this->getQuery('keyword');

        $key = '報名列表' . date('YmdHis');
        $header = [
            'activityName' => '活動名稱',
            'name' => '名字',
            'mobile' => '手機號碼',
            "restaurantName" => '公司／餐廳名稱',
            "businessForm" => '經營形態',
            "perComingDay" => '平均每日來客數',
            "perPrice" => '平均客單價',
            "registrationNumber" => '報名場次',
            "address" => '餐廳地址',
            "city" => '餐廳城市',
            "registrationTime" => '報名時間',
            "confirmRegistration" => '確認報名',
            "restaurantId" => '餐廳ID',
            "lineName" => 'LINE暱稱'
        ];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'keyword' => $keyword,
            'accountId' => serialize($this->getAccountId())
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportRegistrationList', $exportArgs);
        return ['result' => 'success', 'message' => 'exporting registration list', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionGetOne()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $result = Registration::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            return ['result' => 'success', 'activityName' => $result['activityName'], 'name' => $result['name'], 'mobile' => $result['mobile'],
            'restaurantName' => $result['restaurantName'], 'lineName' => $result['lineName'], 'city' => $result['city'],
            'address' => $result['address'], 'businessForm' => $result['businessForm'], 'registrationTime' => $result['registrationTime'],
            'perPrice' => $result['perPrice'], 'perComingDay' => $result['perComingDay'], 'registrationNumber' => $result['registrationNumber'],
            'restaurantId'=> $result['restaurantId'], 'confirmRegistration'=> $result['confirmRegistration']];
        } else {
            return ['result' => 'failed'];
        }
    }

    public function actionUpdate()
    {
        $request = Yii::$app->request;
        $adminId = $request->post("id");
        $admin = User::findOne($adminId);
        if ($admin == null) {
            return ['code' => 1209,'msg' => 'not login'];
        }

        $id = $request->post("_id");
        $activityName = $request->post("activityName", '');
        $name = $request->post("name", '');
        $mobile = $request->post("mobile", '');
        $restaurantName = $request->post("restaurantName", '');
        $businessForm = $request->post("businessForm", '');
        $perPrice = $request->post("perPrice", '');
        $registrationNumber = $request->post("registrationNumber", '');
        $perComingDay = $request->post("perComingDay", '');
        $city = $request->post("city", '');
        $address = $request->post("address", '');
        $lineName = $request->post("lineName", '');
        $registrationTime = $request->post("registrationTime", '');
        $restaurantId = $request->post("restaurantId", '');
        $confirmRegistration = $request->post("confirmRegistration", '');

        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $Registration = Registration::findOne($resultCondition);
        $Registration->activityName = $activityName;
        $Registration->name = $name;
        $Registration->mobile = $mobile;
        $Registration->restaurantName = $restaurantName;
        $Registration->businessForm = $businessForm;
        $Registration->perPrice = $perPrice;
        $Registration->registrationNumber = $registrationNumber;
        $Registration->perComingDay = $perComingDay;
        $Registration->city = $city;
        $Registration->address = $address;
        $Registration->restaurantId = $restaurantId;
        $Registration->confirmRegistration = $confirmRegistration;
        $Registration->registrationTime = $registrationTime;
        $Registration->lineName = $lineName;
        $result = $Registration->save();
        // LogUtil::error(date('Y-m-d h:i:s') . ' $result: ' . $result);

        $accountId = $this->getAccountId();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        LogUtil::error(date('Y-m-d h:i:s') . '1.....');
        if ($result > 0) {
            LogUtil::error(date('Y-m-d h:i:s') . '2.....');
            $res = Member::getByMobile($mobile, $accountId);
            if (!empty($res)) {
                LogUtil::error(date('Y-m-d h:i:s') . '3.....');
                $memberIds = array($res['_id']);
                $tags = array($activityName . 'A');
                foreach ($memberIds as &$memberId) {
                    $memberId = new \MongoId($memberId);
                }

                $condition = ['in', '_id', $memberIds];
                Member::updateAll(['$addToSet' => ['tags' => ['$each' => $tags]]], $condition);

                $accountId = new \MongoId($this->getAccountId());
                $service = Yii::$app->service->setAccountId($accountId);
                $service->tag->create([$activityName . 'A']);
                LogUtil::error(date('Y-m-d h:i:s') . '4.....');
                //change member properties
                // $userId = $res['_id']->{'$id'};
                // if ($userId != '' || $userId != null) {
                //     LogUtil::error(date('Y-m-d h:i:s') . '5.....');
                //     $properties = [];
                //     $service = Yii::$app->service->setAccountId($this->getAccountId());
                //     $result = ['properties' => $service->memberProperty->all()];
                //     $items = $result['properties'];
                //     if ($items != null) {
                //         LogUtil::error(date('Y-m-d h:i:s') . '6.....');
                //         for ($i=0; $i < count($items); $i++) {
                //             $property = null;
                //             if ($items[$i]['name'] == '餐廳名稱') {
                //                 $property['value'] = $restaurantName;
                //             } else if ($items[$i]['name'] == '經營形態') {
                //                 $property['value'] = $businessForm;
                //             } else if ($items[$i]['name'] == '平均消費單價') {
                //                 $property['value'] = $perPrice;
                //             } else if ($items[$i]['name'] == '每日來客數量') {
                //                 $property['value'] = $perComingDay;
                //             } else if ($items[$i]['name'] == '餐廳縣市') {
                //                 $property['value'] = $city;
                //             } else if ($items[$i]['name'] == '餐廳地址') {
                //                 $property['value'] = $address;
                //             } else {
                //                 continue;
                //             }
                //             $property['id'] = $items[$i]['_id']->{'$id'};
                //             $property['name'] = $items[$i]['name'];
                //             array_push($properties, $property);
                //         }
                //         LogUtil::error(date('Y-m-d h:i:s') . '7.....');
                //         $result = $service->member->updateProperties($userId, $properties);
                //         if ($result['message'] != 'ok') {
                //             LogUtil::error('Update' . ' mobile:' . $mobile . 'Save member error!' . ' time' . time(),'registrationDelete');
                //             //return ['code' => 1209, 'msg' => 'Save member error!'];
                //         }
                //     }
                // }
                // LogUtil::error(date('Y-m-d h:i:s') . '8.....');
                return ['msg' => 'success', 'code' => '200'];
            } else {
                return ['msg' => 'success', 'code' => '200'];
            }
        } else {
            return ['msg' => 'failed', 'code' => '500'];
        }
    }

    public function actionBatchDelete()
    {
        $ids = $this->getParams('ids', '');

        for ($i = 0; $i < count($ids); $i++) {
            $resultCondition = ['_id' => new \MongoId($ids[$i]), 'isDeleted' => false];
            $result = Registration::findOne($resultCondition);
            $result->isDeleted = true;
            $result->save();
        }
        return ['msg' => 'success'];
    }

    public function actionGetOneByUser()
    {
        $this->_setJSONFormat(Yii::$app);
        $id = Yii::$app->request->post("userId");
        $activityName = Yii::$app->request->post("activityName");
        if ($id == null || $id == '' || $activityName == null || $activityName == "") {
            return ['code' => '500'];
        }
        $resultCondition = ['userId' => $id, 'activityName' => $activityName, 'isDeleted' => false];
        $result = Registration::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            return ['result' => 'success', 'activityName' => $result['activityName'], 'name' => $result['name'], 'mobile' => $result['mobile'],
            'restaurantName' => $result['restaurantName'], 'city' => $result['city'], 'address' => $result['address'], 'businessForm' => $result['businessForm'],
            'perPrice' => $result['perPrice'], 'perComingDay' => $result['perComingDay'], 'registrationNumber' => $result['registrationNumber']];
        } else {
            return ['result' => 'failed'];
        }
    }

    public function actionUpdateByUser()
    {
        $this->_setJSONFormat(Yii::$app);
        $request = Yii::$app->request;

        $id = $request->post("userId");
        $activityName = Yii::$app->request->post("activityName");

        if ($id == null || $id == '') {
            return ['code' => '500', 'reason' => 'no userId'];
        }

        $name = $request->post("name", '');
        $activityName = $request->post("activityName", '');
        $mobile = $request->post("mobile", '');
        $restaurantName = $request->post("restaurantName", '');
        $businessForm = $request->post("businessForm", '');
        $perPrice = $request->post("perPrice", '');
        $registrationNumber = $request->post("registrationNumber", '');
        $perComingDay = $request->post("perComingDay", '');
        $city = $request->post("city", '');
        $address = $request->post("address", '');

        $resultCondition = ['userId' => $id, 'activityName' => $activityName, 'isDeleted' => false];
        $Registration = Registration::findOne($resultCondition);
        $Registration->name = $name;
        $Registration->activityName = $activityName;
        $Registration->mobile = $mobile;
        $Registration->restaurantName = $restaurantName;
        $Registration->businessForm = $businessForm;
        $Registration->perPrice = $perPrice;
        $Registration->registrationNumber = $registrationNumber;
        $Registration->perComingDay = $perComingDay;
        $Registration->city = $city;
        $Registration->address = $address;
        $result = $Registration->save();
        // LogUtil::error(date('Y-m-d h:i:s') . ' $result: ' . $result);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($result > 0) {
            $accountId = $this->getAccountId();
            $res = Member::getByMobile($mobile, $accountId);
            if (!empty($res)) {
                $memberIds = array($res['_id']);
                $tags = array($activityName . 'A');
                foreach ($memberIds as &$memberId) {
                    $memberId = new \MongoId($memberId);
                }

                $condition = ['in', '_id', $memberIds];
                Member::updateAll(['$addToSet' => ['tags' => ['$each' => $tags]]], $condition);

                $accountId = new \MongoId($this->getAccountId());
                $service = Yii::$app->service->setAccountId($accountId);
                $service->tag->create([$activityName . 'A']);

                // change member properties
                // $userId = $res['_id']->{'$id'};
                // if ($userId != '' || $userId != null) {
                //     $properties = [];
                //     $service = Yii::$app->service->setAccountId($this->getAccountId());
                //     $result = ['properties' => $service->memberProperty->all()];
                //     $items = $result['properties'];
                //     if ($items != null) {
                //         for ($i=0; $i < count($items); $i++) {
                //             $property = null;
                //             if ($items[$i]['name'] == '餐廳名稱') {
                //                 $property['value'] = $restaurantName;
                //             } else if ($items[$i]['name'] == '經營形態') {
                //                 $property['value'] = $businessForm;
                //             } else if ($items[$i]['name'] == '平均消費單價') {
                //                 $property['value'] = $perPrice;
                //             } else if ($items[$i]['name'] == '每日來客數量') {
                //                 $property['value'] = $perComingDay;
                //             } else if ($items[$i]['name'] == '餐廳縣市') {
                //                 $property['value'] = $city;
                //             } else if ($items[$i]['name'] == '餐廳地址') {
                //                 $property['value'] = $address;
                //             } else {
                //                 continue;
                //             }
                //             $property['id'] = $items[$i]['_id']->{'$id'};
                //             $property['name'] = $items[$i]['name'];
                //             array_push($properties, $property);
                //         }
                //         $result = $service->member->updateProperties($userId, $properties);
                //         if ($result['message'] != 'ok') {
                //             LogUtil::error('Update' . ' mobile:' . $mobile . 'Save member error!' . ' time' . time(),'registrationDelete');
                //             //return ['code' => 1209, 'msg' => 'Save member error!'];
                //         }
                //     }
                // }
                return ['msg' => 'success', 'code' => '200'];
            }
            return ['msg' => 'success', 'code' => '200'];
        } else {
            return ['msg' => 'failed', 'code' => '500'];
        }
    }
}
