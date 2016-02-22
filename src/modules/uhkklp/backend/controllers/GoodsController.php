<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use backend\utils\LogUtil;
use backend\models\Token;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\Goods;
use backend\models\User;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;

class GoodsController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionSave()
    {
        $request = Yii::$app->request;
        $adminId = $request->post("id");
        // LogUtil::error(date('Y-m-d h:i:s') . ' $adminId: ' . $adminId);
        $admin = User::findOne($adminId);
        if ($admin == null) {
            return ['code' => 1209,'msg' => 'not login'];
        }

        $accountId = $this->getAccountId();
        $name = $request->post("name", '');
        $image = $request->post("image", '');
        $description = $request->post("description", '');
        $href = $request->post("href", '');

        $Goods = new Goods();
        $Goods->accountId = $accountId;
        $Goods->name = $name;
        $Goods->image = $image;
        $Goods->description = $description;
        $Goods->href = $href;
        $result = $Goods->save();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($result > 0) {
            return ['msg' => 'success', 'code' => '200'];
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
            $records = $query->from('uhkklpGoods')
            ->select(['_id', 'createdAt', 'name', 'image', 'description', 'href'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->orderBy($sort)
            ->offset($offset)
            ->limit($pageSize)
            ->all();
        } else {
            $records = $query->from('uhkklpGoods')
            ->select(['_id', 'createdAt', 'name', 'image', 'description', 'href'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->andWhere(['like', 'name', $keyword])
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
            $totalPageCount = $query->from('uhkklpGoods')
            ->where(['accountId' => ($accountId), 'isDeleted' => false])
            ->count();
        } else {
            $totalPageCount = $query->from('uhkklpGoods')
            ->where(['accountId' => ($accountId), 'isDeleted' => false])
            ->andWhere(['like', 'name', $keyword])
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

        $result = Goods::findOne($resultCondition);
        $result->isDeleted = true;
        $res = $result->save();
        if (!empty($res)) {
            return ['result' => 'success'];
        } else {
            LogUtil::error(['message'=>'Goods刪除失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'goodsDelete');
            return ['result' => 'error'];
        }
    }

    public function actionExportGoods()
    {
        $keyword = $this->getQuery('keyword');

        $key = '商品列表' . date('YmdHis');
        $header = [
            'name' => '商品名稱',
            'description' => '描述信息',
            'href' => '商品鏈接'
        ];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'keyword' => $keyword,
            'accountId' => serialize($this->getAccountId())
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportGoodsList', $exportArgs);
        return ['result' => 'success', 'message' => 'exporting goods list', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionGetOne()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $result = Goods::findOne($resultCondition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($result)) {
            return ['result' => 'success', 'name' => $result['name'], 'image' => $result['image'], 'description' => $result['description'], 'href' => $result['href']];
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
        $image = $request->post("image", '');
        $description = $request->post("description", '');
        $href = $request->post("href", '');

        $resultCondition = ['_id' => new \MongoId($id), 'isDeleted' => false];
        $Goods = Goods::findOne($resultCondition);
        $Goods->name = $name;
        $Goods->image = $image;
        $Goods->description = $description;
        $Goods->href = $href;
        $result = $Goods->save();
        // LogUtil::error(date('Y-m-d h:i:s') . ' $result: ' . $result);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($result > 0) {
            return ['msg' => 'success', 'code' => '200'];
        } else {
            return ['msg' => 'failed', 'code' => '500'];
        }
    }

    public function actionBatchDelete()
    {
        $ids = $this->getParams('ids', '');

        for ($i = 0; $i < count($ids); $i++) {
            $resultCondition = ['_id' => new \MongoId($ids[$i])];
            $result = Goods::findOne($resultCondition);
            $result->isDeleted = true;
            $result->save();
        }
        return ['msg' => 'success'];
    }

    public function actionGetProductList()
    {
        $query = new Query();
        $accountId = $this->getAccountId();

        $records = $query->from('uhkklpGoods')
        ->select(['name', 'image', 'description', 'href'])
        ->where(['accountId' => $accountId, 'isDeleted' => false])
        ->all();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }
}
