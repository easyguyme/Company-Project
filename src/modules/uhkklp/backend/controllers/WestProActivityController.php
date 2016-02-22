<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use backend\utils\LogUtil;
use backend\models\Token;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\ActivitySetting;
use backend\models\User;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;

class WestProActivityController extends BaseController
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $adminId = $request->post("id");

        $admin = User::findOne($adminId);
        if ($admin == null) {
            return ['code' => 1209,'msg' => 'not login'];
        }

        $accountId = $this->getAccountId();
        $name = $request->post("name", '');
        $activityColor = $request->post("activityColor", '');
        $image = $request->post("image", '');
        $orderImage = $request->post("orderImage", '');
        $registrationStartDate = $request->post("registrationStartDate", '');
        $registrationEndDate = $request->post("registrationEndDate", '');
        $registrationTags = $request->post("registrationTags", '');
        if ($registrationTags == "") {
            $registrationTagString = "";
        } else {
            if (count($registrationTags) > 1) {
                $registrationTagString = "^";
            } else {
                $registrationTagString = "";
            }
            $concatStr = implode("^", $registrationTags);
            $registrationTagString .= $concatStr;
            if (count($registrationTags) > 1) {
                $registrationTagString .= "^";
            }
        }
        $registrationDescription = $request->post("registrationDescription", '');
        $registrationRule = $request->post("registrationRule", '');
        $registrationNumber = $request->post("registrationNumber", '');
        $startDateOrder = $request->post("startDateOrder", '');
        $endDateOrder = $request->post("endDateOrder", '');
        $tagsOrder = $request->post("tagsOrder", '');
        if ($tagsOrder == "") {
            $orderTagString = "";
        } else {
            if (count($tagsOrder) > 1) {
                $orderTagString = "^";
            } else {
                $orderTagString = "";
            }
            $concatStr = implode("^", $tagsOrder);
            $orderTagString .= $concatStr;
            if (count($tagsOrder) > 1) {
                $orderTagString .= "^";
            }
        }
        $orderDescription = $request->post("orderDescription");
        $orderRule = $request->post("orderRule");
        $promotionProducts = $request->post("promotionProducts");

        LogUtil::error(date('Y-m-d h:i:s') . ' $type: ' . gettype($tagsOrder));
        // LogUtil::error(date('Y-m-d h:i:s') . ' $string: ' . ($orderTagString));

        $ActivitySetting = new ActivitySetting();
        $ActivitySetting->accountId = $accountId;
        $ActivitySetting->name = $name;
        $ActivitySetting->activityColor = $activityColor;
        $ActivitySetting->image = $image;
        $ActivitySetting->orderImage = $orderImage;
        $ActivitySetting->registrationStartDate = (float)$registrationStartDate;
        $ActivitySetting->registrationEndDate = (float)$registrationEndDate;
        $ActivitySetting->registrationTags = $registrationTags;
        $ActivitySetting->registrationTagString = $registrationTagString;
        $ActivitySetting->registrationDescription = $registrationDescription;
        $ActivitySetting->registrationRule = $registrationRule;
        $ActivitySetting->registrationNumber = $registrationNumber;
        $ActivitySetting->orderStartDate = (float)$startDateOrder;
        $ActivitySetting->orderEndDate = (float)$endDateOrder;
        $ActivitySetting->orderTags = $tagsOrder;
        $ActivitySetting->orderTagString = $orderTagString;
        $ActivitySetting->orderDescription = $orderDescription;
        $ActivitySetting->orderRule = $orderRule;
        $ActivitySetting->promotionProducts = $promotionProducts;
        $ActivitySetting->IsActive = true;
        $result = $ActivitySetting->save();

        if ($result > 0) {
            return ['msg' => 'success', 'code' => '200'];
        } else {
            return ['msg' => 'failed', 'code' => '500'];
        }
    }

    public function actionUpdateRegisStatus()
    {
        $isActivated = Yii::$app->request->get("isActivated");
        $id = Yii::$app->request->get("_id");

        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $ActivitySetting = ActivitySetting::findOne($resultCondition);
        if ($isActivated == "true") {
            $ActivitySetting->IsActive = true;
        } else {
            $ActivitySetting->IsActive = false;
        }
        $result = $ActivitySetting->save();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($result > 0) {
            return ['msg' => 'success', 'code' => '200'];
        } else {
            return ['msg' => 'failed', 'code' => '500'];
        }
    }

    public function actionGetList()
    {
        list($tmp1, $tmp2) = explode(' ', microtime());
        $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
        ActivitySetting::updateAll(['$set' => ['IsActive' => false]], ['orderEndDate' => ['$lte' => $currentDate]]);

        $currentPage = Yii::$app->request->get("currentPage", 1);
        $pageSize = Yii::$app->request->get("pageSize", 10);
        $offset = ($currentPage - 1) * $pageSize;
        // $sortName = "_id";
        // $sortDesc = Yii::$app->request->get('sortDesc', 'ASC');
        // $sort = $sortName . ' ' . $sortDesc;
        $keyword = Yii::$app->request->get("keyword", '');

        $query = new Query();
        $accountId = $this->getAccountId();

        if ($keyword == '') {
            $records = $query->from('uhkklpActivitySetting')
            ->select(['_id', 'name', 'IsActive', 'registrationStartDate', 'registrationEndDate', 'registrationDescription', 'registrationRule', 'orderStartDate', 'orderEndDate'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->orderBy('createdAt DESC')
            ->offset($offset)
            ->limit($pageSize)
            ->all();
        } else {
            $records = $query->from('uhkklpActivitySetting')
            ->select(['_id', 'name', 'IsActive', 'registrationStartDate', 'registrationEndDate', 'registrationDescription', 'registrationRule', 'orderStartDate', 'orderEndDate'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->andWhere(['like','name',$keyword])
            ->orderBy('createdAt DESC')
            ->offset($offset)
            ->limit($pageSize)
            ->all();
        }

        for ($i = 0;$i < count($records);$i++) {
            $records[$i]['startDate'] = $records[$i]['registrationStartDate'];
            $records[$i]['endDate'] = $records[$i]['orderEndDate'];
            $records[$i]['currentDate'] = $currentDate;

            $records[$i]['registrationStartDate'] = MongodbUtil::MongoDate2String(MongodbUtil::msTimetamp2MongoDate($records[$i]['registrationStartDate'], 'Y-m-d H:i:s', null));
            $records[$i]['orderEndDate'] = MongodbUtil::MongoDate2String(MongodbUtil::msTimetamp2MongoDate($records[$i]['orderEndDate'], 'Y-m-d H:i:s', null));
            $records[$i]['_id'] = (string)$records[$i]['_id'];
        }

        $query = new Query();
        if ($keyword == '') {
            $totalPageCount = $query->from('uhkklpActivitySetting')
            ->where(['accountId' => ($accountId), 'isDeleted' => false])
            ->count();
        } else {
            $totalPageCount = $query->from('uhkklpActivitySetting')
            ->where(['accountId' => ($accountId), 'isDeleted' => false])
            ->andWhere(['like','name',$keyword])
            ->count();
        }

        // LogUtil::error(date('Y-m-d h:i:s') . ' $totalPageCount: ' . $totalPageCount);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records, 'totalPageCount' => $totalPageCount];
    }

    public function actionGetOne()
    {
        $this->_setJSONFormat(Yii::$app);
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $result = ActivitySetting::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            return ['result' => 'success', 'name' => $result['name'], 'activityColor' => $result['activityColor'], 'image' => $result['image'], 'orderImage' => $result['orderImage'], 'registrationStartDate' => $result['registrationStartDate'],
            'registrationEndDate' => $result['registrationEndDate'], 'registrationTags' => $result['registrationTags'],
            'registrationDescription' => $result['registrationDescription'], 'registrationRule' => $result['registrationRule'], 'registrationNumber' => $result['registrationNumber'],
            'orderStartDate' => $result['orderStartDate'], 'orderEndDate' => $result['orderEndDate'], 'orderTags' => $result['orderTags'],
            'orderDescription'=> $result['orderDescription'], 'orderRule'=> $result['orderRule'], 'promotionProducts'=> $result['promotionProducts']];
        } else {
            return ['result' => 'failed'];
        }
    }

    public function actionValidateActivityExist()
    {
        $activityName = Yii::$app->request->post("activityName");
        $resultCondition = ['name' => $activityName, 'isDeleted' => false];
        $result = ActivitySetting::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            return ['result' => 'exist'];
        } else {
            return ['result' => 'not exist'];
        }
    }

    public function actionUpdate()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $adminId = $request->post("id");

        $admin = User::findOne($adminId);
        if ($admin == null) {
            return ['code' => 1209,'msg' => 'not login'];
        }
        $id = $request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $result = ActivitySetting::findOne($resultCondition);
        $res = $result->delete();

        if (!empty($res)) {
            $accountId = $this->getAccountId();
            $name = $request->post("name", '');
            $activityColor = $request->post("activityColor", '');
            $image = $request->post("image", '');
            $orderImage = $request->post("orderImage", '');
            $registrationStartDate = $request->post("registrationStartDate", '');
            $registrationEndDate = $request->post("registrationEndDate", '');
            $registrationTags = $request->post("registrationTags", '');
            if ($registrationTags == "") {
                $registrationTagString = "";
            } else {
                if (count($registrationTags) > 1) {
                    $registrationTagString = "^";
                } else {
                    $registrationTagString = "";
                }
                $concatStr = implode("^", $registrationTags);
                $registrationTagString .= $concatStr;
                if (count($registrationTags) > 1) {
                    $registrationTagString .= "^";
                }
            }
            $registrationDescription = $request->post("registrationDescription", '');
            $registrationRule = $request->post("registrationRule", '');
            $registrationNumber = $request->post("registrationNumber", '');
            $startDateOrder = $request->post("startDateOrder", '');
            $endDateOrder = $request->post("endDateOrder", '');
            $tagsOrder = $request->post("tagsOrder", '');
            if ($tagsOrder == "") {
                $orderTagString = "";
            } else {
                if (count($tagsOrder) > 1) {
                    $orderTagString = "^";
                } else {
                    $orderTagString = "";
                }
                $concatStr = implode("^", $tagsOrder);
                $orderTagString .= $concatStr;
                if (count($tagsOrder) > 1) {
                    $orderTagString .= "^";
                }
            }
            $orderDescription = $request->post("orderDescription");
            $orderRule = $request->post("orderRule");
            $promotionProducts = $request->post("promotionProducts");

            $ActivitySetting = new ActivitySetting();
            $ActivitySetting->accountId = $accountId;
            $ActivitySetting->_id = new \MongoId($id);
            $ActivitySetting->name = $name;
            $ActivitySetting->activityColor = $activityColor;
            $ActivitySetting->image = $image;
            $ActivitySetting->orderImage = $orderImage;
            $ActivitySetting->registrationStartDate = (float)$registrationStartDate;
            $ActivitySetting->registrationEndDate = (float)$registrationEndDate;
            $ActivitySetting->registrationTags = $registrationTags;
            $ActivitySetting->registrationTagString = $registrationTagString;
            $ActivitySetting->registrationDescription = $registrationDescription;
            $ActivitySetting->registrationRule = $registrationRule;
            $ActivitySetting->registrationNumber = $registrationNumber;
            $ActivitySetting->orderStartDate = (float)$startDateOrder;
            $ActivitySetting->orderEndDate = (float)$endDateOrder;
            $ActivitySetting->orderTags = $tagsOrder;
            $ActivitySetting->orderTagString = $orderTagString;
            $ActivitySetting->orderDescription = $orderDescription;
            $ActivitySetting->orderRule = $orderRule;
            $ActivitySetting->promotionProducts = $promotionProducts;
            list($tmp1, $tmp2) = explode(' ', microtime());
            $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
            if ($currentDate >= (float)$registrationStartDate && $currentDate <= (float)$endDateOrder) {
                $ActivitySetting->IsActive = true;
            } else {
                $ActivitySetting->IsActive = false;
            }
            $re = $ActivitySetting->save();

            if ($re > 0) {
                return ['msg' => 'success', 'code' => '200'];
            } else {
                return ['msg' => 'failed', 'code' => '500'];
            }
        } else {
            LogUtil::error(['message'=>'activitySetting刪除失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'registrationDelete');
            return ['result' => 'error'];
        }
    }

    public function actionDeleteOne()
    {
        $this->_setJSONFormat(Yii::$app);
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];

        $result = ActivitySetting::findOne($resultCondition);
        $result->isDeleted = true;
        $res = $result->save();
        if (!empty($res)) {
            return ['result' => 'success'];
        } else {
            LogUtil::error(['message'=>'activitySetting刪除失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'registrationDelete');
            return ['result' => 'error'];
        }
    }

    public function actionBatchDelete()
    {
        $ids = $this->getParams('ids', '');

        for ($i = 0; $i < count($ids); $i++) {
            $resultCondition = ['_id' => new \MongoId($ids[$i]), 'isDeleted' => false];
            $result = ActivitySetting::findOne($resultCondition);
            $result->isDeleted = true;
            $result->save();
        }
        return ['msg' => 'success'];
    }

    public function actionGetActivityRegisList()
    {
        $query = new Query();
        $accountId = $this->getAccountId();
        list($tmp1, $tmp2) = explode(' ', microtime());
        $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);

        $records = $query->from('uhkklpActivitySetting')
        ->select(['name'])
        ->where(['accountId' => $accountId, 'isDeleted' => false])
        ->andWhere(['registrationEndDate' => ['$gte' => $currentDate]])
        ->andWhere(['IsActive' => true])
        ->orderBy('createdAt ASC')
        ->all();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }

    public function actionGetActivityOrderList()
    {
        $query = new Query();
        $accountId = $this->getAccountId();
        list($tmp1, $tmp2) = explode(' ', microtime());
        $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);

        $records = $query->from('uhkklpActivitySetting')
        ->select(['name'])
        ->where(['accountId' => $accountId, 'isDeleted' => false])
        ->andWhere(['orderEndDate' => ['$gte' => $currentDate]])
        ->andWhere(['IsActive' => true])
        ->orderBy('createdAt ASC')
        ->all();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }

    public function actionGetProductList()
    {
        $activityName = Yii::$app->request->post("activityName");
        $query = new Query();
        $accountId = $this->getAccountId();

        $records = $query->from('uhkklpActivitySetting')
        ->select(['promotionProducts'])
        ->where(['accountId' => $accountId, 'name' => $activityName, 'isDeleted' => false])
        ->all();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }

    public function actionGetRegisNums()
    {
        $activityName = Yii::$app->request->post("activityName");
        $query = new Query();
        $accountId = $this->getAccountId();

        $records = $query->from('uhkklpActivitySetting')
        ->select(['registrationNumber'])
        ->where(['accountId' => $accountId, 'name' => $activityName, 'isDeleted' => false])
        ->all();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }

    public function actionValidateRegistrationTime()
    {
        $this->_setJSONFormat(Yii::$app);
        $activityName = Yii::$app->request->post("activityName");
        $resultCondition = ['accountId' => $this->getAccountId(), 'name' => $activityName, 'isDeleted' => false];
        $result = ActivitySetting::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            $startDate = $result['registrationStartDate'];
            $endDate = $result['registrationEndDate'];
            list($tmp1, $tmp2) = explode(' ', microtime());
            $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
            // LogUtil::error(date('Y-m-d h:i:s') . ' $startDate: ' . $startDate);
            // LogUtil::error(date('Y-m-d h:i:s') . ' $endDate: ' . $endDate);
            // LogUtil::error(date('Y-m-d h:i:s') . ' $currentDate: ' . $currentDate);
            if ($currentDate > $endDate) {
                return ['code' => 1];
            } else if (($currentDate < $startDate) || $currentDate >= $startDate && $currentDate <= $endDate) {
                return ['code' => 3];
            } else {
                return ['code' => 4];
            }
        } else {
            return ['code' => 5];
        }
    }

    public function actionValidateOrderTime()
    {
        $this->_setJSONFormat(Yii::$app);
        $activityName = Yii::$app->request->post("activityName");
        $resultCondition = ['accountId' => $this->getAccountId(), 'name' => $activityName, 'isDeleted' => false];
        $result = ActivitySetting::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            $startDate = $result['orderStartDate'];
            $endDate = $result['orderEndDate'];
            list($tmp1, $tmp2) = explode(' ', microtime());
            $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);

            if ($currentDate > $endDate) {
                return ['code' => 1];
            } else if (($currentDate < $startDate) || $currentDate >= $startDate && $currentDate <= $endDate) {
                return ['code' => 3];
            } else {
                return ['code' => 4];
            }
        } else {
            return ['code' => 5];
        }
    }

    public function actionGetRegistrationListInMobile()
    {
        $this->_setJSONFormat(Yii::$app);
        $query = new Query();
        $accountId = $this->getAccountId();
        list($tmp1, $tmp2) = explode(' ', microtime());
        $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);

        $mobile = Yii::$app->request->post("mobile");
        $result = Member::getByMobile($mobile, $accountId);
        $tags = $result['tags'];
        // $tags = ['waef', 'b'];

        $recordsPublic = $query->from('uhkklpActivitySetting')
        ->select(['_id', 'name', 'image', 'activityColor'])
        ->where(['accountId' => $accountId, 'isDeleted' => false, 'registrationTagString' => ""])
        // ->where(['isDeleted' => false, 'registrationTagString' => ""])
        ->andWhere(['registrationEndDate' => ['$gte' => $currentDate]])
        ->andWhere(['registrationStartDate' => ['$lte' => $currentDate]])
        ->andWhere(['IsActive' => true])
        ->all();

        // LogUtil::error(date('Y-m-d h:i:s') . ' $count public: ' . count($recordsPublic));

        $recordsPrivate = array();
        for ($i = 0; $i < count($tags); $i++) {
            LogUtil::error(date('Y-m-d h:i:s') . ' $tagsType: ' . $tags[$i]);
            $tmpArray = $query->from('uhkklpActivitySetting')
            ->select(['_id', 'name', 'image', 'activityColor'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            // ->where(['isDeleted' => false])
            ->andWhere(['registrationEndDate' => ['$gte' => $currentDate]])
            ->andWhere(['registrationStartDate' => ['$lte' => $currentDate]])
            ->andWhere(['IsActive' => true])
            ->andWhere(['or', ['like', 'registrationTagString', '^' . $tags[$i] . '^'], ['registrationTagString' => $tags[$i]]])
            ->all();
            $recordsPrivate = array_merge($recordsPrivate, $tmpArray);
        }

        $tmp = array();
        for ($i = 0; $i < count($recordsPrivate); $i++) {
            if (!in_array($recordsPrivate[$i], $tmp)) {
                $tmp[] = $recordsPrivate[$i];
            }
        }
        $recordsPrivate = $tmp;
        // LogUtil::error(date('Y-m-d h:i:s') . ' $count private: ' . count($recordsPrivate));

        $records = array_merge($recordsPublic, $recordsPrivate);
        for ($i = 0;$i < count($records);$i++) {
            $records[$i]['_id'] = (string)$records[$i]['_id'];
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }

    public function actionGetOrderListInMobile()
    {
        $this->_setJSONFormat(Yii::$app);
        $query = new Query();
        $accountId = $this->getAccountId();
        list($tmp1, $tmp2) = explode(' ', microtime());
        $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);

        $mobile = Yii::$app->request->post("mobile");
        $result = Member::getByMobile($mobile, $accountId);
        $tags = $result['tags'];
        // LogUtil::error(date('Y-m-d h:i:s') . ' $tags: ' . $tags);
        // $tags = ['sas', 'waef'];

        $recordsPublic = $query->from('uhkklpActivitySetting')
        ->select(['_id', 'name', 'orderImage', 'activityColor'])
        ->where(['accountId' => $accountId, 'isDeleted' => false, 'orderTagString' => ""])
        // ->where(['isDeleted' => false, 'orderTagString' => ""])
        ->andWhere(['orderEndDate' => ['$gte' => $currentDate]])
        ->andWhere(['orderStartDate' => ['$lte' => $currentDate]])
        ->andWhere(['IsActive' => true])
        ->all();

        // LogUtil::error(date('Y-m-d h:i:s') . ' $count public: ' . count($recordsPublic));

        $recordsPrivate = array();
        for ($i = 0; $i < count($tags); $i++) {
            $tmpArray = $query->from('uhkklpActivitySetting')
            ->select(['_id', 'name', 'orderImage', 'activityColor'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            // ->where(['isDeleted' => false])
            ->andWhere(['orderEndDate' => ['$gte' => $currentDate]])
            ->andWhere(['orderStartDate' => ['$lte' => $currentDate]])
            ->andWhere(['IsActive' => true])
            ->andWhere(['or', ['like', 'orderTagString', '^' . $tags[$i] . '^'], ['orderTagString' => $tags[$i]]])
            ->all();
            $recordsPrivate = array_merge($recordsPrivate, $tmpArray);
        }

        // LogUtil::error(date('Y-m-d h:i:s') . ' $count private: ' . count($recordsPrivate));
        $tmp = array();
        for ($i = 0; $i < count($recordsPrivate); $i++) {
            if (!in_array($recordsPrivate[$i], $tmp)) {
                $tmp[] = $recordsPrivate[$i];
            }
        }
        $recordsPrivate = $tmp;
        // LogUtil::error(date('Y-m-d h:i:s') . ' $count private: ' . count($recordsPrivate));

        $records = array_merge($recordsPublic, $recordsPrivate);
        for ($i = 0;$i < count($records);$i++) {
            $records[$i]['_id'] = (string)$records[$i]['_id'];
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }
}
