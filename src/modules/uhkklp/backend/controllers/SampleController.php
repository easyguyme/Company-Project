<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use backend\modules\uhkklp\models\Sample;
use backend\models\User;
use yii\mongodb\Query;
use backend\modules\uhkklp\controllers\BaseController;

class SampleController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionSave()
    {
        $accountId = $this->getAccountId();
        $request = Yii::$app->request;
        $operatorId = $request->post('operatorId', '');
        $sampleId = $request->post('sampleId', '');
        $name = $request->post('name', '');
        $quantity = $request->post('quantity', '');
        $imgUrl = $request->post('imgUrl', '');

        $operator = User::find()->where(['_id'=>$operatorId])->one();
        if ($operator === null) {
            return ['code' => 1309, 'msg' => 'not login'];
        }

        if ($name == '') {
            return ['code' => 1300, 'msg' => 'name not get'];
        }

        if ($quantity == '') {
            return ['code' => 1300, 'msg' => 'quantity not get'];
        }

        $sample = Sample::find()->where(['_id'=>$sampleId])->one();
        if ($sampleId == '' || $sample == null) {
            $sample = new Sample();
            $sample->usedNumber = 0;
        }

        $sample->name = $name;
        $sample->quantity = $quantity;
        $sample->operator = $operator->name;
        $sample->imgUrl = $imgUrl;
        $sample->accountId = $accountId;
        $sample->save();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK'];
    }

    public function actionCountList()
    {
        $accountId = $this->getAccountId();
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $query = new Query();
        $query->from('uhkklpSample')
            ->where(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['like','name',$keyword]);
        $count = $query->count();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $count];
    }

    public function actionList()
    {
        $accountId = $this->getAccountId();
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $page = $request = Yii::$app->request->get("page",1);
        $pageSize = $request = Yii::$app->request->get("pageSize",10);
        $offset = ($page-1)*$pageSize;
        $sortName = Yii::$app->request->get('sortName','createdAt');
        $sortDesc = Yii::$app->request->get('sortDesc','DESC');
        $sort = $sortName . ' ' . $sortDesc;

        $query = new Query();
        $query->from('uhkklpSample')
            ->where(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['like','name',$keyword])
            ->limit($pageSize)
            ->offset($offset)
            ->orderBy($sort);
        $list = $query->all();
        $list = $this->formatSampleList($list);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $list];
    }

    private function formatSample($item)
    {
        $item['id'] = $item['_id'] . '';
        if (array_key_exists('updatedAt', $item)) {
            $updatedAt = $item['updatedAt']->sec;
            $item['updatedAt'] = date('Y-m-d H:i:s', $updatedAt);
        }
        return $item;
    }

    private function formatSampleList($list)
    {
        date_default_timezone_set('Asia/Taipei');
         for ($i = 0; $i < count($list); $i++) {
            $item = $this->formatSample($list[$i]);
            $list[$i] = $item;
        }
        return $list;
    }

    public function actionGetById()
    {
        $sampleId = Yii::$app->request->get('sampleId', '');
        $query = new Query();
        $query->from('uhkklpSample')
            ->where(['_id' => $sampleId]);
        $sample = $query->one();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $sample];
    }

    public function actionDelete()
    {
        $sampleId = $this->getParams('sampleId', '');
        $sample = Sample::find()->where(['_id' => $sampleId])->one();
        if ($sample == '') {
            return ['code' => 1300, 'msg' => 'sample not found'];
        }
        $sample->isDeleted = true;
        $sample->save();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK'];
    }

    public function actionListAll()
    {
        $accountId = $this->getAccountId();
        $query = new Query();
        $query->from('uhkklpSample')
            ->where(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->orderBy('createdAt DESC');
        $list = $query->all();
        $list = $this->formatSampleList($list);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $list];
    }
}
