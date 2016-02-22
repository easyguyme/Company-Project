<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\mongodb\Query;
use backend\modules\uhkklp\controllers\BaseController;
use backend\modules\uhkklp\models\UpdatedNewsTime;

class UpdatedNewsTimeController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionSave(){
    	$accountId = $this->getAccountId();
    	$updateTime = $this->getParams('updateTime','');
    	if ($updateTime == '') {
    		return ['code' => 1201, 'msg' => 'Time is required!'];
    	}
        $id = $this->getParams('id','');
        $updatedNewsTime = null;
        if ($id != '') {
            $updatedNewsTime = UpdatedNewsTime::find(['_id' => $id])->one();
        } else {
    	    $updatedNewsTime = new UpdatedNewsTime();
        }
        if ($updatedNewsTime == null) {
            return ['code' => 1202, 'msg' => 'Not found!'];
        }
    	$updatedNewsTime->updateTime = $updateTime/1000;
    	$updatedNewsTime->accountId = $accountId;
    	$updatedNewsTime->save();
    	return ['code' => 200, 'msg' => 'OK'];
    }

    public function actionGetCount(){
        $accountId = $this->getAccountId();
        $query = new Query();
        $query->from('uhkklpUpdatedNewsTime')
            ->where(['accountId' => $accountId]);
        $count = $query->count();
        return ['code' => 200, 'msg' => 'OK', 'result' => $count];
    }

    public function actionGet(){
		$accountId = $this->getAccountId();
        $page = Yii::$app->request->get("page",1);
        if ($page == 'undefined') {
            $page = 1;
        }
        $pageSize = Yii::$app->request->get("pageSize",10);
        if ($pageSize == 'undefined') {
            $pageSize = 10;
        }
        $offset = ($page-1)*$pageSize;
        $sortName = Yii::$app->request->get("sortName",'createdAt');
        $sortDesc = Yii::$app->request->get("sortDesc",'DESC');
        $sort = $sortName . ' ' . $sortDesc;
		$query = new Query();
		$query->from('uhkklpUpdatedNewsTime')
			->select(['_id', 'updateTime'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->limit($pageSize)
            ->offset($offset)
            ->orderBy($sort);
        $list = $query->all();
        $list = $this->formatList($list);
        return ['code' => 200, 'msg' => 'OK', 'result' => $list];
    }

    private function formatList($list){
    	for ($i=0; $i < count($list); $i++) {
    		$list[$i] = $this->formatItem($list[$i]);
    	}
    	return $list;
    }

    private function formatItem($item){
    	$item['updatedTimeId'] = $item['_id'] . "";
    	unset($item['_id']);
        if (array_key_exists('updateTime', $item)) {
            $updateTime = $item['updateTime'];
            $item['updateTime'] = date('Y-m-d H:i:s', $updateTime);
        }
    	return $item;
    }

    public function actionDelete(){
        $accountId = $this->getAccountId();
        $updatedTimeId = Yii::$app->request->post("updatedTimeId");
        $updatedTime = updatedNewsTime::find(['updateTime' => $updatedTimeId]);
        if ($updatedTime != null) {
            updatedNewsTime::deleteAll(['_id' => $updatedTimeId]);
            return ['code' => 200, 'msg' => 'OK'];
        }
        return ['code' => 1202, 'msg' => 'Not found'];
    }

    public function actionGetById(){
        $accountId = $this->getAccountId();
        $updatedTimeId = Yii::$app->request->get("updatedTimeId","");
        $query = new Query();
        $query->from('uhkklpUpdatedNewsTime')
            ->where(['_id' => $updatedTimeId]);
        $updatedTime = $query->one();
        if ($updatedTime != null) {
            return ['code' => 200, 'msg' => 'OK', 'result' => $updatedTime];
        } else {
            return ['code' => 1203, 'msg' => 'Not found'];
        }
    }

    //API
    public function actionIsUpdatedNews(){
        $accountId = $this->getAccountId();
        $query = new Query();
        $query->from('uhkklpUpdatedNewsTime')
            ->select(['updateTime'])
            ->where(['accountId' => $accountId, 'isDeleted' => false])
            ->andWhere(['updateTime' => ['$lte' => time()]])
            ->orderBy('updateTime DESC');
        $result = $query->one();
        unset($result['_id']);
        $result['updateTime'] = date('Y-m-d H:i:s', $result['updateTime']);
        return ['code' => 200, 'msg' => 'OK', 'result' => $result];
    }
}