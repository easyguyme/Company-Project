<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use backend\utils\LogUtil;
use backend\models\Token;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\Order;
use backend\modules\uhkklp\models\ActivitySetting;
use backend\models\User;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;

class OrderController extends BaseController
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
        $flag = $request->post("flag", '');

        if ($flag == "") {
            $adminId = $request->post("id");
            $admin = User::findOne($adminId);
            if ($admin == null) {
                return ['code' => 1209,'msg' => 'not login'];
            }
        }


        $activityName = $request->post("activityName", '');
        $mobile = $request->post("mobile", '');
        $resultCondition = ['activityName' => $activityName, 'mobile' => $mobile, 'isDeleted' => false];
        $result = Order::findOne($resultCondition);
        if (!empty($result)) {
            return ['msg' => 'failed', 'code' => '2000'];
        }

        //TODU
        //weather user can order according tags
        $accountId = $this->getAccountId();
        $result = Member::getByMobile($mobile, $accountId);
        $tags = $result['tags'];
        // $tags = ["s", "b"];
        $isAlllow = false;
        $activity = ActivitySetting::findOne(['name' => $activityName]);

        if ($activity['orderTagString'] == "" || empty($result)) {
            $isAlllow = true;
        } else {
            for ($i = 0; $i < count($tags); $i++) {
                if ($activity['orderTagString'] == $tags[$i]) {
                    $isAlllow = true;
                }
            }

            $splitTmp = explode("^", $activity['orderTagString']);
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
        $city = $request->post("city", '');
        $address = $request->post("address", '');
        $productor = $request->post("productor", '');
        $product = $request->post("product");
        $orderTime = $request->post("orderTime", '');
        if ($orderTime == "") {
            list($tmp1, $tmp2) = explode(' ', microtime());
            $orderTime = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
        }
        $lineName = $request->post("lineName");
        $restaurantId = $request->post("restaurantId");
        // LogUtil::error(date('Y-m-d h:i:s') . ' $product: ' . $product);

        $Order = new Order();
        $Order->accountId = $accountId;
        $Order->userId = $userId;
        $Order->name = $name;
        $Order->activityName = $activityName;
        $Order->mobile = $mobile;
        $Order->restaurantName = $restaurantName;
        $Order->city = $city;
        $Order->address = $address;
        $Order->productor = $productor;
        $Order->product = $product;
        $Order->orderTime = $orderTime;
        $Order->lineName = $lineName;
        $Order->restaurantId = $restaurantId;
        $result = $Order->save();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($result > 0) {
            $res = Member::getByMobile($mobile, $accountId);
            if (!empty($res)) {
                $memberIds = array($res['_id']);
                $tags = array($activityName . 'B');
                foreach ($memberIds as &$memberId) {
                    $memberId = new \MongoId($memberId);
                }

                $condition = ['in', '_id', $memberIds];
                Member::updateAll(['$addToSet' => ['tags' => ['$each' => $tags]]], $condition);

                $accountId = new \MongoId($this->getAccountId());
                $service = Yii::$app->service->setAccountId($accountId);
                $service->tag->create([$activityName . 'B']);

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
                //             } else if ($items[$i]['name'] == '首選經銷商' && $productor !="") {
                //                 $property['value'] = $productor;
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
            $records = $query->from('uhkklpOrder')
            ->select(['_id', 'createdAt', 'name', 'activityName', 'mobile', 'restaurantName', 'address', 'city'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->orderBy($sort)
            ->offset($offset)
            ->limit($pageSize)
            ->all();
        } else {
            $records = $query->from('uhkklpOrder')
            ->select(['_id', 'createdAt', 'name', 'activityName', 'mobile', 'restaurantName', 'address', 'city'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->andWhere(['like', 'mobile', $keyword])
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
            $totalPageCount = $query->from('uhkklpOrder')
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->count();
        } else {
            $totalPageCount = $query->from('uhkklpOrder')
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->andWhere(['like', 'mobile', $keyword])
            ->count();
        }

        // LogUtil::error(date('Y-m-d h:i:s') . ' $totalPageCount: ' . $totalPageCount);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records, 'totalPageCount' => $totalPageCount];
    }

    public function actionDeleteOne()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id)];

        $result = Order::findOne($resultCondition);
        $result->isDeleted = true;
        $res = $result->save();
        if (!empty($res)) {
            return ['result' => 'success'];
        } else {
            LogUtil::error(['message'=>'Order刪除失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'orderDelete');
            return ['result' => 'error'];
        }
    }

    public function actionExportOrder()
    {
        $keyword = $this->getQuery('keyword');

        $key = '訂單列表' . date('YmdHis');
        $header = [
            '_id' => '訂單ID',
            'orderTime' => '下單時間',
            'name' => '名字',
            'activityName' => '活動名稱',
            'mobile' => '手機號',
            'restaurantName' => '餐廳名稱',
            'address' => '餐廳地址',
            'city' => '餐廳城市',
            'productor' => '指定康寶經銷商',
            'lineName' => 'LINE暱稱',
            'restaurantId' => '餐廳ID',
            'product' => '優惠訂單'
        ];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'keyword' => $keyword,
            'accountId' => serialize($this->getAccountId())
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportOrderList', $exportArgs);
        return ['result' => 'success', 'message' => 'exporting order list', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionGetOne()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $result = Order::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            return ['result' => 'success', 'name' => $result['name'], 'activityName' => $result['activityName'], 'mobile' => $result['mobile']
            , 'restaurantName' => $result['restaurantName'], 'city' => $result['city'], 'address' => $result['address'], 'productor' => $result['productor'], 'product' => $result['product'],
            'orderTime' => $result['orderTime'], 'lineName' => $result['lineName'], 'restaurantId' => $result['restaurantId']];
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
        $name = $request->post("name", '');
        $activityName = $request->post("activityName", '');
        $mobile = $request->post("mobile", '');
        $restaurantName = $request->post("restaurantName", '');
        $city = $request->post("city", '');
        $address = $request->post("address", '');
        $productor = $request->post("productor", '');
        $product = $request->post("product");
        $orderTime = $request->post("orderTime");
        $lineName = $request->post("lineName");
        $restaurantId = $request->post("restaurantId");

        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $order = Order::findOne($resultCondition);
        $order->name = $name;
        $order->activityName = $activityName;
        $order->mobile = $mobile;
        $order->restaurantName = $restaurantName;
        $order->address = $address;
        $order->city = $city;
        $order->productor = $productor;
        $order->product = $product;
        $order->orderTime = $orderTime;
        $order->lineName = $lineName;
        $order->restaurantId = $restaurantId;
        $result = $order->save();
        // LogUtil::error(date('Y-m-d h:i:s') . ' $result: ' . $result);

        $accountId = $this->getAccountId();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($result > 0) {
            $res = Member::getByMobile($mobile, $accountId);
            if (!empty($res)) {
                $memberIds = array($res['_id']);
                $tags = array($activityName . 'B');
                foreach ($memberIds as &$memberId) {
                    $memberId = new \MongoId($memberId);
                }

                $condition = ['in', '_id', $memberIds];
                Member::updateAll(['$addToSet' => ['tags' => ['$each' => $tags]]], $condition);

                $accountId = new \MongoId($this->getAccountId());
                $service = Yii::$app->service->setAccountId($accountId);
                $service->tag->create([$activityName . 'B']);

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
                //             } else if ($items[$i]['name'] == '首選經銷商' && $productor !="") {
                //                 $property['value'] = $productor;
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

    public function actionBatchDelete()
    {
        $ids = $this->getParams('ids', '');

        for ($i = 0; $i < count($ids); $i++) {
            $resultCondition = ['_id' => new \MongoId($ids[$i])];
            $result = Order::findOne($resultCondition);
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
        $result = Order::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            return ['result' => 'success', 'name' => $result['name'], 'activityName' => $result['activityName'], 'mobile' => $result['mobile']
            , 'restaurantName' => $result['restaurantName'], 'city' => $result['city'], 'address' => $result['address'], 'productor' => $result['productor'], 'product' => $result['product'],
            'orderTime' => $result['orderTime'], 'lineName' => $result['lineName'], 'restaurantId' => $result['restaurantId']];
        } else {
            return ['result' => 'failed'];
        }
    }
}
