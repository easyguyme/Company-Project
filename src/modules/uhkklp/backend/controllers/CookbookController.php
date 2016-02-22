<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use backend\modules\uhkklp\controllers\BaseController;
use backend\models\User;
use backend\modules\uhkklp\models\Cookbook;
use backend\modules\uhkklp\models\SampleRecord;
use backend\modules\uhkklp\models\CookingType;
use backend\modules\uhkklp\models\UserCookbook;
use backend\modules\uhkklp\models\Sample;
use yii\mongodb\Query;
use backend\utils\ExcelUtil;
use backend\utils\LogUtil;
use backend\modules\member\models\Member;

class CookbookController extends BaseController
{
    public $enableCsrfValidation = false;

    //get one cookbook by id
    //params cookbookid,mobile
    //cookbookid,string,the id of cookbook
    //mobile,string,the mobile of user,default value is null
    //return cookbook
    public function actionGetById()
    {
        $accountId = $this->getAccountId();
        $cookbook = null;
        $cookbookId = Yii::$app->request->get('cookbookId');
        $mobile = Yii::$app->request->get('mobile');
        if ($cookbookId) {
            $query = new Query();
            $query->from('uhkklpCookbook')
                ->where(['_id'=>$cookbookId])
                ->andWhere(['isDeleted'=>false])
                ->andWhere(['accountId' => $accountId]);
            $list = $query->all();
            if (count($list)) {
                $cookbook = $list[0];
                $cookbook = $this->formatCookbook($cookbook);

                $getSampled = 'N';
                if ($mobile != null && strlen($mobile) != 0) {
                    $query = new Query();
                    $query->from('uhkklpSamplerecord')->where(['mobile'=>$mobile, 'cookbookId'=>$cookbookId, 'isDeleted'=>false, 'accountId' => $accountId]);
                    $record = $query->one();
                    if ($record != null) {
                        $getSampled = 'Y';
                    }
                }
            }
        } else {
            return ['code' => 1205,'msg' => 'CookbookId is false!'];
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $cookbook];
    }

    public function actionGetCookbookById()
    {
        $accountId = $this->getAccountId();
        $cookbook = null;
        $cookbookId = Yii::$app->request->get('cookbookId');
        $mobile = Yii::$app->request->get('mobile');
        if ($cookbookId) {
            $query = new Query();
            $query->from('uhkklpCookbook')
                ->where(['_id'=>$cookbookId])
                ->andWhere(['isDeleted'=>false])
                ->andWhere(['accountId' => $accountId]);
            $list = $query->all();
            if (count($list)) {
                $cookbook = $list[0];
                $getSampled = 'N';
                if ($mobile != null && strlen($mobile) != 0) {
                    $query = new Query();
                    $query->from('uhkklpSamplerecord')->where(['mobile'=>$mobile, 'cookbookId'=>$cookbookId, 'isDeleted'=>false, 'accountId' => $accountId]);
                    $record = $query->one();
                    if ($record != null) {
                        $getSampled = 'Y';
                    }
                }
                $cookbook['getSampled'] = $getSampled;
                if (array_key_exists('sample', $cookbook)) {
                    for ($i=0; $i < count($cookbook['sample']); $i++) {
                        $sampleDate = $this->getSample($cookbook['sample'][$i]['id']);
                        $cookbook['sample'][$i] = $sampleDate;
                    }
                }
            }
        } else {
            return ['code' => 1205,'msg' => 'CookbookId is false!'];
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $cookbook];
    }

    public function actionSave()
    {
        $accountId = $this->getAccountId();
        $request = Yii::$app->request;
        $adminId = $request->post("id");
        $admin = User::findOne($adminId);
        if ($admin == null) {
            return ['code' => 1209,'msg' => 'not login'];
        }

        $cookbookId = $request->post('cookbookId', null);
        $title = $request->post('title', '');
        $image = $request->post('image', '');
        $content = $request->post('content', '');
        $ingredient = $request->post('ingredient', []);
        $startDate = $request->post('startDate', '');
        $endDate = $request->post('endDate', '');
        $shareUrl = $request->post('shareUrl', '');
        $isSampleOpen = $request->post('isSampleOpen', 'N');
        $sample = $request->post('sample', []);
        $active = $request->post('active', 'Y');
        $type = $request->post('type', []);
        $video = $request->post('video', '');
        $restaurantName = $request->post('restaurantName', '');
        $cookName = $request->post('cookName','');
        $tip = $request->post('tip','');
        $creativeExperience = $request->post('creativeExperience','');
        $deliciousSecret = $request->post('deliciousSecret','');
        $shareDescription = $request->post('shareDescription','');
        $activitySettingId = $request->post('activitySettingId','');
        $activitySettingName = $request->post('activitySettingName','');

        $cookbook = null;
        if ($cookbookId != null) {
            $cookbook = Cookbook::findOne($cookbookId);
        }
        if ($cookbook == null) {
            $cookbook = new Cookbook();
            $cookbook->createdDate = time();
        }

        //Sort time
        $activeSortTime = $cookbook->activeSortTime;
        $inactiveSortTime = $cookbook->inactiveSortTime;
        if ($activeSortTime == null) {
            $activeSortTime = $cookbook->createdDate;
            $inactiveSortTime = $cookbook->createdDate;
        }
        if ($active == 'Y' && $startDate/1000 < time()) {
            if ($cookbook->startDate < time() && $cookbook->active == 'Y') {
                $inactiveSortTime = $endDate/1000;
            } else {
                $activeSortTime = time();
                $inactiveSortTime = $endDate/1000;
            }
        } else {
           if ($cookbook->startDate < time() && $cookbook->active == 'Y') {
                $inactiveSortTime = time();
                $activeSortTime = $startDate/1000;
            } else {
                $activeSortTime = $startDate/1000;
            }
        }
        $cookbook->activeSortTime = $activeSortTime;
        $cookbook->inactiveSortTime = $inactiveSortTime;

        //lock samples
        for ($i=0; $i < count($cookbook->sample); $i++) {
            $result = Sample::unlockSample($cookbook->sample[$i]);
        }
        for ($j=0; $j < count($sample); $j++) {
            $result = Sample::lockSample($sample[$j]);
            if ($result['code'] == 500) {
                return ['code' => 1204,'msg' => $result];
            }
        }

        $cookbook->title = $title;
        $cookbook->image = $image;
        $cookbook->content = $content;
        $cookbook->ingredient = $this->clearHashKey($ingredient);
        $cookbook->startDate = strlen($startDate) > 0 ? $startDate/1000 : null;
        $cookbook->endDate = strlen($endDate) > 0 ? $endDate/1000 : null;
        $cookbook->isSampleOpen = $isSampleOpen;
        $cookbook->sample = $this->clearHashKey($sample);
        $cookbook->active = $active;
        $cookbook->updatedDate = time();
        $cookbook->operator = $admin['name'];
        $cookbook->type = $type;
        $cookbook->shareUrl = $shareUrl;
        $cookbook->accountId = $accountId;
        $cookbook->video = $video;
        $cookbook->restaurantName = $restaurantName;
        $cookbook->cookName = $cookName;
        $cookbook->hasImportImg = true;
        $cookbook->tip = $tip;
        $cookbook->creativeExperience = $creativeExperience;
        $cookbook->deliciousSecret = $deliciousSecret;
        $cookbook->shareDescription = $shareDescription;
        $cookbook->activitySettingId = $activitySettingId;
        $cookbook->activitySettingName = $activitySettingName;
        $cookbook->save();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $cookbook->_id;
    }

    private function clearHashKey($list)
    {
        for ($i = 0; $i < count($list); $i++) {
            $item = $list[$i];
            unset($item['$$hashKey']);
            $list[$i] = $item;
        }
        return $list;
    }

    public function actionListManage()
    {
        $accountId = $this->getAccountId();
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
            $ingredient = '';
        }
        $page = $request = Yii::$app->request->get("page",1);
        $pageSize = $request = Yii::$app->request->get("pageSize",10);
        $offset = ($page-1)*$pageSize;
        $active = $request = Yii::$app->request->get("active",'Y');
        $sortName = Yii::$app->request->get('sortName','createdDate');
        $sortDesc = Yii::$app->request->get('sortDesc','DESC');
        $sort = $sortName . ' ' . $sortDesc;
        $cookbooks = [];
        if ($active == 'Y') {
            $query = new Query();
            $query->from('uhkklpCookbook')
                ->select(['_id', 'title', 'image', 'content', 'ingredient', 'startDate', 'endDate', 'shareUrl',
            'isSampleOpen', 'sample', 'updatedDate', 'operator', 'active'])
                ->where(['startDate' => ['$ne' => null], 'startDate' => ['$lte' => time()]])
                ->andWhere(['endDate' => ['$ne' => null], 'endDate' => ['$gte' => time()]])
                ->andWhere(['active' => $active])
                ->andWhere(['isDeleted' => false])
                ->andWhere(['accountId' => $accountId])
                ->andWhere(['like','title',$keyword])
                ->limit($pageSize)
                ->offset($offset)
                ->orderBy($sort);
            $cookbooks = $query->all();
            $cookbooks = $this->formatCookbookList($cookbooks);
        } else {
            $query = new Query();
            $query->from('uhkklpCookbook')
                ->select(['_id', 'title', 'image', 'content', 'ingredient', 'startDate', 'endDate', 'shareUrl',
            'isSampleOpen', 'sample', 'updatedDate', 'operator', 'active'])
                ->where(['startDate' => null])
                ->orWhere(['startDate' => ['$gt' => time()]])
                ->orWhere(['endDate' => null])
                ->orWhere(['endDate' => ['$lt' => time()]])
                ->orWhere(['active' => $active])
                ->andWhere(['isDeleted' => false])
                ->andWhere(['accountId' => $accountId])
                ->andWhere(['like','title',$keyword])
                ->limit($pageSize)
                ->offset($offset)
                ->orderBy($sort);
            $cookbooks = $query->all();
            $cookbooks = $this->formatCookbookList($cookbooks);
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $cookbooks];
    }

    //get cookbooks as list
    //params titleKeyword,page,pageSize,active
    //titleKeyword,string,the keyword of seach conditions,default valus is empty string
    //page,number,the current page of list,default value is 1
    //pageSize,number,the pageSize of list,default value is 10
    //active,boolean,if the cookbook is active
    //return cookbooks as list
    public function actionListAll()
    {
        $accountId = $this->getAccountId();
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $page = $request = Yii::$app->request->get("page",1);
        $pageSize = $request = Yii::$app->request->get("pageSize",10);
        $offset = ($page-1)*$pageSize;
        $active = $request = Yii::$app->request->get("active",'Y');
        $mobile = Yii::$app->request->get('mobile',null);
        $cookbooks = [];
        if ($active == 'Y') {
            $query = new Query();
            $query->from('uhkklpCookbook')
                ->select(['_id', 'title', 'image', 'content', 'ingredient', 'startDate', 'endDate', 'shareUrl',
            'isSampleOpen', 'sample', 'updatedDate', 'operator', 'active'])
                ->where(['startDate' => ['$ne' => null], 'startDate' => ['$lte' => time()]])
                ->andWhere(['endDate' => ['$ne' => null], 'endDate' => ['$gte' => time()]])
                ->andWhere(['active' => $active])
                ->andWhere(['like','title',$keyword])
                ->andWhere(['isDeleted' => false])
                ->andWhere(['accountId' => $accountId])
                ->limit($pageSize)
                ->offset($offset)
                ->orderBy('updatedDate DESC');
            $cookbooks = $query->all();
            $cookbooks = $this->formatCookbookList($cookbooks);
        } else {
            $query = new Query();
            $query->from('uhkklpCookbook')
                ->select(['_id', 'title', 'image', 'content', 'ingredient', 'startDate', 'endDate', 'shareUrl',
            'isSampleOpen', 'sample', 'updatedDate', 'operator'])
                ->where(['startDate' => null])
                ->orWhere(['startDate' => ['$gt' => time()]])
                ->orWhere(['endDate' => null])
                ->orWhere(['endDate' => ['$lt' => time()]])
                ->orWhere(['active' => $active])
                ->andWhere(['like','title',$keyword])
                ->andWhere(['isDeleted' => false])
                ->andWhere(['accountId' => $accountId])
                ->limit($pageSize)
                ->offset($offset)
                ->orderBy('updatedDate DESC');
            $cookbooks = $query->all();
            $cookbooks = $this->formatCookbookList($cookbooks);
        }
        for ($i=0; $i < count($cookbooks); $i++) {
            $getSampled = 'N';
            if ($mobile != null && strlen($mobile) != 0) {
                $query = new Query();
                $query->from('uhkklpSamplerecord')->where(['mobile'=>$mobile, 'cookbookId'=>$cookbooks[$i]['cookbookId'], 'isDeleted'=>false, 'accountId' => $accountId]);
                $record = $query->one();
                if ($record != null) {
                    $getSampled = 'Y';
                }
            }
            $cookbooks[$i]['getSampled'] = $getSampled;
        }
        LogUtil::info('ListAll' . ' mobile:' . $mobile,'cookbook-log');
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $cookbooks];
    }

    //get count of list
    //params active,keyword,page,pageSize
    //active,string,the status of cookbook
    //keyword,string,the keyword of seach conditions,default valus is empty string
    //page,number,the current page of list,default value is 1
    //pageSize,number,the pageSize of list,default value is 10
    //return count of list
    public function actionCountList()
    {
        $accountId = $this->getAccountId();
        $active = $request = Yii::$app->request->get("active",'Y');
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $query = new Query();
        $query->from('uhkklpCookbook')
            ->where(['startDate' => ['$ne' => null], 'startDate' => ['$lte' => time()]])
            ->andWhere(['endDate' => ['$ne' => null], 'endDate' => ['$gte' => time()]])
            ->andWhere(['active' => $active])
            ->andWhere(['like','title',$keyword])
            ->andWhere(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId]);
        $count = $query->count();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $count];
    }

    public function actionCountListManage()
    {
        $accountId = $this->getAccountId();
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $query = new Query();
        $query->from('uhkklpCookbook')
            ->where(['startDate' => ['$ne' => null], 'startDate' => ['$lte' => time()]])
            ->andWhere(['endDate' => ['$ne' => null], 'endDate' => ['$gte' => time()]])
            ->andWhere(['active' => 'Y'])
            ->andWhere(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['like','title',$keyword]);
        $activeListCount = $query->count();

        $query = new Query();
        $query->from('uhkklpCookbook')
            ->where(['startDate' => null])
            ->orWhere(['startDate' => ['$gt' => time()]])
            ->orWhere(['endDate' => null])
            ->orWhere(['endDate' => ['$lt' => time()]])
            ->orWhere(['active' => 'N'])
            ->andWhere(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['like','title',$keyword]);
        $inactiveListCount = $query->count();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => ['activeList' => $activeListCount, 'inactiveList' => $inactiveListCount]];
    }

    private function getSample($id)
    {
        $query = new Query();
        $query->from('uhkklpSample')
            ->select(['_id', 'name', 'quantity'])
            ->where(['_id' => $id]);
        $sample = $query->one();
        $sample['id'] = $sample['_id'] . '';
        unset($sample['_id']);
        return $sample;
    }

    private function formatCookbook($item)
    {
        $item['cookbookId'] = $item['_id'] . '';
        unset($item['_id']);
        if (array_key_exists('updatedDate', $item)) {
            $updatedDate = $item['updatedDate'];
            $item['updatedDate'] = date('Y-m-d H:i:s', $updatedDate);
        }
        if (array_key_exists('startDate', $item)) {
            $startDate = $item['startDate'];
            $item['startDate'] = date('Y-m-d H:i:s', $startDate);
        }
        if (array_key_exists('endDate', $item)) {
            $endDate = $item['endDate'];
            $item['endDate'] = date('Y-m-d H:i:s', $endDate);
        }
        if (array_key_exists('sample', $item) && array_key_exists('id', $item['sample'])) {
            $sampleDate = $this->getSample($item['sample']['id']);
            $item['sample'] = $sampleDate;
        }
        return $item;
    }

    private function formatCookbookList($list)
    {
        date_default_timezone_set('Asia/Taipei');
        for ($i = 0; $i < count($list); $i++) {
            $item = $this->formatCookbook($list[$i]);
            $list[$i] = $item;
        }
        return $list;
    }

    //SaveSampleRecord
    //params mobile,cookbookId,deviceId,sampleId,username,city,address,quantity,sent
    //mobile, number, the mobile of user
    //cookbookId, number, the mongo id of cookbook
    //deviceId, string, the id of device
    //sampleId, number, the id of sample in cookbook
    //username, string, the username of user
    //city, string, the city name
    //address, string, the address to send to
    //quantity, number, the number of sample, default value is 1
    //sent, boolean, if the sample have been sent, it is true, else it is false, default value is false
    //return, if success, return the id of sample record, else if the record exists, return 'code => 400'
    public function actionSaveSampleRecord()
    {
        $accountId = $this->getAccountId();

        $mobile = $this->getParams('mobile','');
        $cookbookId = $this->getParams('cookbookId','');
        $deviceId = $this->getParams('deviceId','');
        $sampleId = $this->getParams('sampleId','');
        $sampleName = $this->getParams('sampleName','');
        $username = $this->getParams('username','');
        $city = $this->getParams('city','');
        $address = $this->getParams('address','');
        $sent = $this->getParams('sent',false);
        $cookbook = Cookbook::findOne($cookbookId);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($cookbook == null) {
            return ['code' => 1204, 'msg' => 'cookbook not found.'];
        }

        if (strlen($mobile) == 0) {
            return ['code' => 1202, 'msg' => 'mobile is required.'];
        }

        $sample = $cookbook->sample;
        for ($i=0; $i < count($sample); $i++) {
            if ($sample[$i]['id'] == $sampleId) {
                break;
            }
        }
        if ($i >= count($sample)) {
            return ['code' => 1208, 'msg' => 'sample is not exist.'];
        }
        $sampleQuantity = $sample[$i]['quantity'];

        if ($sampleName == '') {
            $sampleName = $sample[$i]['name'];
        }

        $query = new Query();
        $query->from('uhkklpSamplerecord')->where(['mobile' => $mobile, 'cookbookId' => $cookbookId, 'accountId' => $accountId]);
        $record = $query->one();

        if ($record == null) {
            $record = new SampleRecord();
            $record->mobile = $mobile;
            $record->cookbookId = $cookbookId;
            $record->cookbookTitle = $cookbook->title;
            $record->deviceId = $deviceId;
            $record->sampleId = $sampleId;
            $record->sampleName = $sampleName;
            $record->username = $username;
            $record->city = $city;
            $record->address = $address;
            $record->createdDate = time();
            $record->accountId = $accountId;
            $record->quantity = $sampleQuantity;
            $record->sent = $sent;
            if(!$record->save()) {
               return ['code' => 1205, 'msg' => 'Save error!'];
            }

        } else {
            return ['code' => 1206, 'msg' => 'Record exists!'];
        }
        LogUtil::info('SaveSampleRecord' . ' mobile:' . $mobile . ' cookbookTitle' . $cookbook->title .' time' . time(),'cookbook-log');
        return ['code' => 200, 'msg' => 'OK'];
    }

    //get sample record of user
    //params mobile
    //mobile,string,the mobile of user
    //return list
    public function actionGetSampleRecord()
    {
        $accountId = $this->getAccountId();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $mobile = $request->post('mobile');
        $query = new Query();
        $query->from('uhkklpSamplerecord')->where(['mobile' => $mobile, 'accountId' => $accountId]);
        $record = $query->all();
        return ['code' => 200, 'msg' => 'OK','result' => $record];
    }

    public function actionDelete()
    {
        $accountId = $this->getAccountId();
        $adminId = $request = Yii::$app->request->post("id");
        $admin = User::findOne($adminId);
        if ($admin == null) {
            return ['message' => 'not login'];
        }

        $cookbookId = Yii::$app->request->post('cookbookId');
        if ($cookbookId != null) {
            Cookbook::deleteAll(['_id' => $cookbookId]);
            return ['message' => 'success'];
        }
        return $cookbookId;
    }

    public function actionDownloadSampleRecordExcel()
    {
        $accountId = $this->getAccountId();
        $cookbookId = Yii::$app->request->get('cookbookId');
        $format = Yii::$app->request->get('f', null);
        $query = new Query();
        $query->from('uhkklpCookbook')->where(['_id' => $cookbookId, 'accountId' => $accountId]);
        $cookbook = $query->one();
        if ($cookbook == null) {
            return 'cookbook not found. cookbookId: ' . $cookbookId;
        }

        if ($cookbook) {
            $query = new Query();
            $query->from('uhkklpSamplerecord')
                ->select(['mobile', 'cookbookTitle', 'sampleId', 'sampleName', 'username', 'city', 'address', 'createdDate', 'quantity', 'sent'])
                ->where(['cookbookId'=>$cookbookId])
                ->andWhere(['accountId' => $accountId]);
            $list = $query->all();

            if ($format != null && $format == 'json') {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return $list;
            }

            $key = $cookbook['title'] . '_試用包申請記錄';
            $accountId = new \MongoId($this->getAccountId());
            $header = [
                'mobile' => '手機號碼',
                'username' => '姓名',
                'restaurantName' => '餐廳名稱',
                'city' => '餐廳城市',
                'address' => '餐廳地址',
                'sampleName' => '試用包',
                'quantity' => '說明',
                'userAppellation' => '称谓',
                'placeNumber' => '餐廳郵遞區號'
            ];
            $id = new \MongoId($cookbookId);
            $condition = serialize(['cookbookId' => $id]);
            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'cookbookTitle' => $cookbook['title'],
                'description' => 'export sample record'
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportSampleRecord', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        }
    }

    public function actionDownloadAllSampleRecordExcel()
    {
        $accountId = $this->getAccountId();
        $key = '所有試用包申請記錄';
        $accountId = new \MongoId($this->getAccountId());
        $header = [
            'sampleName' => '試用包',
            'quantity' => '試用包說明',
            'createdTime' => '申請時間',
            'cookbookTitle' => '食譜名稱',
            'mobile' => '手機號碼',
            'username' => '姓名',
            'userAppellation' => '称谓',
            'restaurantName' => '餐廳名稱',
            'city' => '餐廳城市',
            'address' => '餐廳地址',
            'placeNumber' => '餐廳郵遞區號'
        ];
        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'accountId' => (string)$accountId,
            'description' => 'export all sample record'
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportAllSampleRecord', $exportArgs);
        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    // 2015y 10m 20d
    public function actionSaveSampleRecordOneTime()
    {
        $accountId = $this->getAccountId();
        $mobile = $this->getParams('mobile','');
        $userId = $this->getParams('userId','');
        $cookbookId = $this->getParams('cookbookId','');
        $sampleIds = $this->getParams('sampleId','');
        $username = $this->getParams('username','');
        $city = $this->getParams('city','');
        $address = $this->getParams('address','');
        $rarea = $this->getParams('rarea','');
        $rdistrict = $this->getParams('rdistrict','');
        $raddr = $this->getParams('raddr','');
        $address = $address . $rarea . $rdistrict . $raddr;
        $restaurantName = $this->getParams('restaurantName','');
        $userAppellation = $this->getParams('userAppellation','');
        $placeNumber = $this->getParams('placeNumber','');
        $business = $this->getParams('business','');

        $cookbook = Cookbook::findOne($cookbookId);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($cookbook == null) {
            return ['code' => 1204, 'msg' => 'cookbook not found.'];
        }

        if (strlen($mobile) == 0) {
            return ['code' => 1202, 'msg' => 'mobile is required.'];
        }

        $sample = $cookbook->sample;
        $sampleIdArray = explode(",", $sampleIds);
        for ($j=1; $j <= count($sampleIdArray); $j++) {
            $sampleId = $sampleIdArray[$j-1];
            $query = new Query();
            $query->from('uhkklpSamplerecord')->where(['mobile' => $mobile, 'sampleId' => $sampleId, 'accountId' => $accountId]);
            $record = $query->one();
            if ($record != null) {
                $msg = 'sample ' . $record['sampleName'] . ' has been recorded';
                return ['code' => 1206, 'msg' => $msg];
            }
        }
        for ($j=1; $j <= count($sampleIdArray); $j++) {
            $sampleId = $sampleIdArray[$j-1];
            for ($i=0; $i < count($sample); $i++) {
                if ($sample[$i]['id'] == $sampleId) {
                    break;
                }
            }
            if ($i >= count($sample)) {
                return ['code' => 1208, 'msg' => 'sample is not exist.'];
            }
            $sampleQuantity = $sample[$i]['quantity'];
            $sampleName = $sample[$i]['name'];

            $record = new SampleRecord();
            $record->mobile = $mobile;
            $record->cookbookId = $cookbookId;
            $record->cookbookTitle = $cookbook->title;
            $record->sampleId = $sampleId;
            $record->sampleName = $sampleName;
            $record->username = $username;
            $record->city = $city;
            $record->address = $address;
            $record->createdDate = time();
            $record->accountId = $accountId;
            $record->quantity = $sampleQuantity;
            $record->restaurantName = $restaurantName;
            $record->userAppellation = $userAppellation;
            $record->placeNumber = $placeNumber;
            if (!$record->save()) {
               return ['code' => 1205, 'msg' => 'Save error!'];
            }

            if ($userId != '') {
                $properties = [];
                $service = Yii::$app->service->setAccountId($this->getAccountId());
                $result = ['properties' => $service->memberProperty->all()];
                $items = $result['properties'];
                if ($items != null) {
                    for ($i=0; $i < count($items); $i++) {
                        $property = null;
                        if ($items[$i]['name'] == '餐廳名稱') {
                            $property['value'] = $restaurantName;
                        } else if ($items[$i]['name'] == 'gender') {
                            $property['value'] = $userAppellation;
                        } else if ($items[$i]['name'] == 'name') {
                            $property['value'] = $username;
                        } else if ($items[$i]['name'] == '餐廳郵遞區號') {
                            $property['value'] = $placeNumber;
                        } else if ($items[$i]['name'] == '餐廳縣市') {
                            $property['value'] = $city;
                        } else if ($items[$i]['name'] == '餐廳地址') {
                            $property['value'] = $address;
                        } else if ($items[$i]['name'] == '經營形態') {
                            $property['value'] = $business;
                        } else {
                            continue;
                        }
                        $property['id'] = $items[$i]['_id']->{'$id'};
                        $property['name'] = $items[$i]['name'];
                        array_push($properties, $property);
                    }
                    $result = $service->member->updateProperties($userId, $properties);
                    if ($result['message'] != 'ok') {
                        LogUtil::error('SaveSampleRecordOneTime' . ' mobile:' . $mobile . 'Save member error!' . ' time' . time(),'cookbook-log');
                        //return ['code' => 1209, 'msg' => 'Save member error!'];
                    }
                }
            }
        }
        LogUtil::info('SaveSampleRecordOneTime' . ' mobile:' . $mobile . ' cookbookTitle' . $cookbook->title .' time' . time(),'cookbook-log');
        return ['code' => 200, 'msg' => 'OK'];
    }

    public function actionListAllByCategories()
    {
        $accountId = $this->getAccountId();
        $page = $request = Yii::$app->request->get("page",1);
        $pageSize = $request = Yii::$app->request->get("per_page",10);
        $offset = ($page-1)*$pageSize;
        $mobile = Yii::$app->request->get('mobile',null);
        $categories = Yii::$app->request->get('categories','-1');
        $sortName = Yii::$app->request->get('sortKeyWord','updatedDate');
        $sort = $sortName . ' DESC';
        $types = explode(',', $categories);
        $cookbooks = [];
        $query = new Query();
        $query->from('uhkklpCookbook')
            ->select(['_id', 'title', 'image', 'content', 'ingredient', 'startDate', 'endDate', 'shareUrl',
                'isSampleOpen', 'sample', 'active', 'createdDate', 'updatedDate', 'operator', 'type',
                'video', 'restaurantName', 'cookName', 'category', 'subCategory', 'portionSize',
                'preparationMethod', 'yield', 'creativeExperience', 'deliciousSecret', 'cuisineType',
                'averageScore', 'shareDescription', 'activitySettingId', 'activitySettingName'])
            ->where(['startDate' => ['$ne' => null], 'startDate' => ['$lte' => time()]])
            ->andWhere(['endDate' => ['$ne' => null], 'endDate' => ['$gte' => time()]])
            ->andWhere(['active' => 'Y'])
            ->andWhere(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->limit($pageSize)
            ->offset($offset)
            ->orderBy($sort);
        if (count($types) > 0 && $types[0] != "-1") {
            $sql = array('like', 'type', $types[0]);
            if (count($types) > 1){
                $sql = array("and");
                for ($i=0; $i < count($types) ; $i++) {
                    if ($types[$i] != "") {
                       array_push($sql, array('like', 'type', $types[$i]));
                    }
                }
            }
            //return $sql;
            $query->andWhere($sql);
        }
        $totalCount = $query->count();
        $cookbooks = $query->all();
        if ($categories == "") {
            $cookbooks = [];
        }
        $cookbooks = $this->formatCookbookList($cookbooks);
        for ($i=0; $i < count($cookbooks); $i++) {
            $cookbooks[$i] = $this->formatCookbookForAPI($cookbooks[$i]);
        }
        if (count($cookbooks) > 0 && $mobile != null && strlen($mobile) != 0) {
            //collection and score
            $query = new Query();
            $query->from('uhkklpUserCookbook')->where(['mobile'=>$mobile, 'isDeleted'=>false, 'accountId' => $accountId]);
            $userCookbook = $query->all();
            for ($i=0; $i < count($cookbooks); $i++) {
                $cookbooks[$i]['collection'] = 'N';
                $cookbooks[$i]['score'] = 0;
                for ($j=0;$j < count($userCookbook); $j++) {
                    if ($userCookbook[$j]['cookbookId'] == $cookbooks[$i]['cookbookId']) {
                        if (!isset($userCookbook[$j]['collection'])) {
                            $userCookbook[$j]['collection'] = 'N';
                        } else if ($userCookbook[$j]['collection'] != 'N') {
                            $userCookbook[$j]['collection'] = 'Y';
                        }
                        if (!isset($userCookbook[$j]['score'])) {
                            $userCookbook[$j]['score'] = 0;
                        }
                        $cookbooks[$i]['collection'] = $userCookbook[$j]['collection'];
                        $cookbooks[$i]['score'] = $userCookbook[$j]['score'];
                        break;
                    }
                }
            }
            //samplerecord
            $query = new Query();
            $query->from('uhkklpSamplerecord')->where(['mobile'=>$mobile, 'isDeleted'=>false, 'accountId' => $accountId]);
            $record = $query->all();
            for ($i=0; $i < count($cookbooks); $i++) {
                $sample = $cookbooks[$i]['sample'];
                for ($j=0;$j < count($sample); $j++) {
                    for ($k=0; $k < count($record); $k++) {
                        if ($record[$k]['sampleId'] == $sample[$j]['id']) {
                            $sample[$j]['getSampled'] = 'Y';
                            break;
                        }
                    }
                    if ($k >= count($record)) {
                        $sample[$j]['getSampled'] = 'N';
                    }
                }
                $cookbooks[$i]['sample'] = $sample;
            }
        }
        $result['cookbooks'] = $cookbooks;
        $result['per_page'] = $pageSize;
        $result['page'] = $page;
        if ($pageSize == 0 || $totalCount < $pageSize) {
            $result['total_page'] = 1;
        } else {
            $result['total_page'] = ceil($totalCount/$pageSize);
        }

        LogUtil::info('ListAllByCategories' . ' mobile:' . $mobile,'cookbook-log');
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $result];
    }

    public function actionListManageByCategories()
    {
        $accountId = $this->getAccountId();
        $keyword = $request = $this->getParams("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $page = $request = $this->getParams("page",1);
        $pageSize = $request = $this->getParams("pageSize",10);
        $offset = ($page-1)*$pageSize;
        $active = $request = $this->getParams("active",'Y');
        $defaultSort = 'activeSortTime';
        if ($active == 'N') {
            $defaultSort = 'inactiveSortTime';
        }
        $sortName = $this->getParams('sortName',$defaultSort);
        $sortDesc = $this->getParams('sortDesc','DESC');
        $sort = $sortName . ' ' . $sortDesc;
        $categories = $this->getParams('categories',[]);
        $restaurantNames = [];
        $types = [];
        for ($i=0; $i < count($categories); $i++) {
            if ($categories[$i]['name'] == '餐廳') {
                $restaurantNames = $categories[$i]['items'];
            } else {
                $types = array_merge($types, $categories[$i]['items']);
            }
        }

        $cookbooks = [];
        $query = new Query();
        $query->from('uhkklpCookbook');
        if ($active == 'Y') {
            $query->where(['startDate' => ['$ne' => null], 'startDate' => ['$lte' => time()]])
                ->andWhere(['endDate' => ['$ne' => null], 'endDate' => ['$gte' => time()]])
                ->andWhere(['active' => $active]);
        } else {
            $query->where(['or',['startDate' => null], ['startDate' => ['$gt' => time()]], ['endDate' => null], ['endDate' => ['$lt' => time()]], ['active' => $active]]);

        }
        $query->andWhere(['isDeleted' => false, 'hasImportImg' => true])
            ->andWhere(['accountId' => $accountId])
            ->limit($pageSize)
            ->offset($offset)
            ->orderBy($sort)
            ->addOrderBy('createdDate DESC')
            ->andWhere(['or',['like','title',$keyword],['like','ingredient.name',$keyword]]);
        if (count($restaurantNames) > 0) {
            $sql = array('like', 'restaurantName', $restaurantNames[0]);
            if (count($restaurantNames) > 1){
                $sql = array(['or']);
                for ($i=0; $i < count($restaurantNames) ; $i++) {
                    array_push($sql, array('like', 'restaurantName', $restaurantNames[$i]));
                }
            }
            $query->andWhere($sql);
        }
        if (count($types) > 0) {
            $sql = array('like', 'type', $types[0]);
            if (count($types) > 1){
                $sql = array("or");
                for ($i=0; $i < count($types) ; $i++) {
                    array_push($sql, array('like', 'category', $types[$i]));
                }
            }
            $query->andWhere($sql);
        }
        $cookbooks = $query->all();
        $cookbooks = $this->formatCookbookList($cookbooks);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $cookbooks];
    }

    public function actionGetOneCookbookById()
    {
        $accountId = $this->getAccountId();
        $cookbookId = $request = Yii::$app->request->get("cookbookId","");
        $mobile = Yii::$app->request->get('mobile',null);
        $query = new Query();
        $query->from('uhkklpCookbook')
            ->select(['_id', 'title', 'image', 'content', 'ingredient', 'startDate', 'endDate', 'shareUrl',
                'isSampleOpen', 'sample', 'active', 'createdDate', 'updatedDate', 'operator', 'type',
                'video', 'restaurantName', 'cookName', 'category', 'subCategory', 'portionSize',
                'preparationMethod', 'yield', 'creativeExperience', 'deliciousSecret', 'cuisineType', '
                averageScore', 'shareDescription', 'activitySettingId', 'activitySettingName'])
            ->where(['_id' => $cookbookId]);
        $cookbook = $query->one();
        if ($cookbook == false) {
            return ['code' => 1204,'msg' => 'cookbook not found.'];
        }
        $cookbook = $this->formatCookbook($cookbook);
        $cookbook = $this->formatCookbookForAPI($cookbook);
        if ($mobile != null && strlen($mobile) != 0) {
            //collection and score
            $query = new Query();
            $query->from('uhkklpUserCookbook')->where(['mobile'=>$mobile, 'isDeleted'=>false, 'accountId' => $accountId]);
            $userCookbook = $query->all();
            $cookbook['collection'] = 'N';
            $cookbook['score'] = 0;
            for ($j=0;$j < count($userCookbook); $j++) {
                if ($userCookbook[$j]['cookbookId'] == $cookbook['cookbookId']) {
                    if (!isset($userCookbook[$j]['collection'])) {
                        $userCookbook[$j]['collection'] = 'N';
                    } else if ($userCookbook[$j]['collection'] != 'N') {
                        $userCookbook[$j]['collection'] = 'Y';
                    }
                    if (!isset($userCookbook[$j]['score'])) {
                        $userCookbook[$j]['score'] = 0;
                    }
                    $cookbook['collection'] = $userCookbook[$j]['collection'];
                    $cookbook['score'] = $userCookbook[$j]['score'];
                    break;
                }
            }
            //samplerecord
            $query = new Query();
            $query->from('uhkklpSamplerecord')->where(['mobile'=>$mobile, 'isDeleted'=>false, 'accountId' => $accountId]);
            $record = $query->all();
            $sample = $cookbook['sample'];
            for ($j=0;$j < count($sample); $j++) {
                for ($k=0; $k < count($record); $k++) {
                    if ($record[$k]['sampleId'] == $sample[$j]['id']) {
                        $sample[$j]['getSampled'] = 'Y';
                        break;
                    }
                }
                if ($k >= count($record)) {
                    $sample[$j]['getSampled'] = 'N';
                }
            }
            $cookbook['sample'] = $sample;
        }
        $result['cookbook'] = $cookbook;

        LogUtil::info('GetOneCookbookById' . ' mobile:' . $mobile,'cookbook-log');
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $result];
    }

    public function actionCountListManageByCategories()
    {
        $accountId = $this->getAccountId();
        $keyword = $this->getParams("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $active = $this->getParams("active",'Y');
        $categories = $this->getParams('categories',[]);
        $restaurantNames = [];
        $types = [];
        for ($i=0; $i < count($categories); $i++) {
            if ($categories[$i]['name'] == '餐廳') {
                $restaurantNames = $categories[$i]['items'];
            } else {
                $types = array_merge($types, $categories[$i]['items']);
            }
        }

        $listCount = 0;
        $query = new Query();
        $query->from('uhkklpCookbook');
        if ($active == 'Y') {
                $query->where(['active' => 'Y'])
                ->andWhere(['startDate' => ['$ne' => null], 'startDate' => ['$lte' => time()]])
                ->andWhere(['endDate' => ['$ne' => null], 'endDate' => ['$gte' => time()]]);
        } else {
            $query->where(['or',['startDate' => null], ['startDate' => ['$gt' => time()]], ['endDate' => null], ['endDate' => ['$lt' => time()]], ['active' => 'N']]);
        }
        $query->andWhere(['isDeleted' => false, 'hasImportImg' => true])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['or',['like','title',$keyword],['like','ingredient.name',$keyword]]);
        if (count($restaurantNames) > 0) {
            $sql = array('like', 'restaurantName', $restaurantNames[0]);
            if (count($restaurantNames) > 1){
                $sql = array('or');
                for ($i=0; $i < count($restaurantNames) ; $i++) {
                    array_push($sql, array('like', 'restaurantName', $restaurantNames[$i]));
                }
            }
            $query->andWhere($sql);
        }
        if (count($types) > 0) {
            $sql = array('like', 'type', $types[0]);
            if (count($types) > 1){
                $sql = array('or');
                for ($i=0; $i < count($types) ; $i++) {
                    array_push($sql, array('like', 'category', $types[$i]));
                }
            }
            $query->andWhere($sql);
        }
        $listCount = $query->count();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK','result' => $listCount];
    }

    public function actionCollectCookbook()
    {
        $accountId = $this->getAccountId();
        $mobile = $this->getParams('mobile','');
        if (strlen($mobile) == 0) {
            return ['code' => 1202, 'msg' => 'mobile is required.'];
        }
        $cookbookId = $this->getParams('cookbookId','');
        if (strlen($cookbookId) == 0) {
            return ['code' => 1202, 'msg' => 'cookbookId is required.'];
        }
        $collection = $this->getParams('collection','N');
        if (strlen($collection) == 0) {
            return ['code' => 1202, 'msg' => 'collection is required.'];
        }
        $userCookbook = UserCookbook::find()->where(['mobile' => $mobile, 'cookbookId' => $cookbookId])->one();
        if ($userCookbook == null) {
            $userCookbook = new UserCookbook();
        }
        $userCookbook->mobile = $mobile;
        $userCookbook->cookbookId = $cookbookId;
        $userCookbook->collection = $collection;
        $userCookbook->accountId = $accountId;
        $userCookbook->save();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200,'msg' => 'OK'];
    }

    public function actionScoreCookbook()
    {
        $accountId = $this->getAccountId();
        $mobile = $this->getParams('mobile','');
        if (strlen($mobile) == 0) {
            return ['code' => 1202, 'msg' => 'mobile is required.'];
        }
        $cookbookId = $this->getParams('cookbookId','');
        if (strlen($cookbookId) == 0) {
            return ['code' => 1202, 'msg' => 'cookbookId is required.'];
        }
        $score = $this->getParams('score',0);
        if (strlen($score) == 0) {
            return ['code' => 1202, 'msg' => 'score is required.'];
        }
        $userCookbook = UserCookbook::find()->where(['mobile' => $mobile, 'cookbookId' => $cookbookId])->one();
        $isScored = true;
        $oldScore = 0;
        if ($userCookbook == null) {
            $userCookbook = new UserCookbook();
            $isScored = false;
        } else {
            $oldScore = $userCookbook->score;
            return ['code' => 1205, 'msg' => 'user has scored this cookbook'];
        }
        $userCookbook->mobile = $mobile;
        $userCookbook->cookbookId = $cookbookId;
        $userCookbook->score = $score;
        $userCookbook->accountId = $accountId;
        $userCookbook->save();

        //average score
        $cookbook = Cookbook::find()->where(['_id' => $cookbookId])->one();
        if ($cookbook == null) {
            return ['code' => 1204, 'msg' => 'cookbook not found.'];
        }
        //score number
        $query = new Query();
        $query->from('uhkklpUserCookbook')
            ->where(['cookbookId' => $cookbookId]);
        $scorerNumber = $query->count();

        $averageScore = 0.0;
        if (!isset($cookbook['averageScore'])) {
            $averageScore = $score;
        } else {
            if ($isScored) {
                $averageScore = ($score - $oldScore + $cookbook['averageScore'] * ($scorerNumber)) / $scorerNumber;
            } else {
                $averageScore = ($score + $cookbook['averageScore'] * ($scorerNumber - 1)) / $scorerNumber;
            }
        }
        $averageScore = round($averageScore,1);
        $cookbook->averageScore = $averageScore;
        $cookbook->save();

        $result['averageScore'] = $averageScore;
        $result['scorerNumber'] = $scorerNumber;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $result];
    }

    public function actionCookbookBatchHandle()
    {
        $accountId = $this->getAccountId();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $operation = $this->getParams('operation', '');
        if (strlen($operation) == 0) {
            return ['code' => 1202, 'msg' => 'operation is required.'];
        }
        $onSaleTime = $this->getParams('onSaleTime', '');
        $cookbookId = $this->getParams('cookbookId', '');
        $tags = $this->getParams('tags','');

        if (count($cookbookId) <= 0) {
            return ['code' => 1202, 'msg' => 'cookbookId is required.'];
        }
        if ($operation == 'on') {
            for ($i=0; $i < count($cookbookId); $i++) {
                $cookbook = Cookbook::findOne($cookbookId[$i]);
                if ($cookbook == null) {
                    return ['code' => 1204, 'msg' => 'cookbook not found.'];
                }
                $cookbook->active = 'Y';
                if ($onSaleTime == ''){
                    $onSaleTime = time();
                } else {
                    $onSaleTime = strlen($onSaleTime) > 0 ? $onSaleTime/1000 : null;
                }
                $cookbook->startDate=$onSaleTime;

                //Sort time
                $activeSortTime = $cookbook->activeSortTime;
                $inactiveSortTime = $cookbook->inactiveSortTime;
                if ($activeSortTime == null) {
                    $activeSortTime = $cookbook->createdDate;
                    $inactiveSortTime = $cookbook->createdDate;
                }

                $activeSortTime = time();
                $inactiveSortTime = $cookbook->endDate;

                $cookbook->activeSortTime = $activeSortTime;
                $cookbook->inactiveSortTime = $inactiveSortTime;

                $cookbook->save();
            }
        } else if ($operation == 'off') {
            for ($i=0; $i < count($cookbookId); $i++) {
                $cookbook = Cookbook::findOne($cookbookId[$i]);
                if ($cookbook == null) {
                    return ['code' => 1204, 'msg' => 'cookbook not found.'];
                }
                $cookbook->active = 'N';

                //Sort time
                $activeSortTime = $cookbook->activeSortTime;
                $inactiveSortTime = $cookbook->inactiveSortTime;
                if ($activeSortTime == null) {
                    $activeSortTime = $cookbook->createdDate;
                    $inactiveSortTime = $cookbook->createdDate;
                }

                $inactiveSortTime = time();
                $activeSortTime = $cookbook->startDate;

                $cookbook->activeSortTime = $activeSortTime;
                $cookbook->inactiveSortTime = $inactiveSortTime;

                $cookbook->save();
            }
        } else if ($operation == 'del') {
            for ($i=0; $i < count($cookbookId); $i++) {
                Cookbook::deleteAll(['_id' => $cookbookId[$i]]);
            }
        } else if ($operation == 'tags') {
            for ($i=0; $i < count($cookbookId); $i++) {
                $cookbook = Cookbook::findOne($cookbookId[$i]);
                if ($cookbook == null) {
                    return ['code' => 1204, 'msg' => 'cookbook not found.'];
                }
                $cookbook->type = array_merge($cookbook->type, $tags);
                $cookbook->save();
            }
        }
        return ['code' => 200,'msg' => 'OK'];
    }

    private function formatCookbookForAPI($cookbook)
    {
        //averageScore
        if (!isset($cookbook['averageScore'])) {
            $cookbook['averageScore'] = 0.0;
        }
        //isNewest
        for ($i=0; $i < count($cookbook['type']); $i++) {
            if ($cookbook['type'][$i] == '最新食譜') {
                $cookbook['isNewest'] = 'Y';
            } else {
                $cookbook['isNewest'] = 'N';
            }
        }
        //collection number
        $cookbookId = $cookbook['cookbookId'];
        $query = new Query();
        $query->from('uhkklpUserCookbook')
            ->where(['cookbookId' => $cookbookId])
            ->andWhere(['collection' => 'Y']);
        $collectionNumber = $query->count();
        $cookbook['collectionNumber'] = $collectionNumber;
        //score number
        $cookbookId = $cookbook['cookbookId'];
        $query = new Query();
        $query->from('uhkklpUserCookbook')
            ->where(['cookbookId' => $cookbookId]);
        $scorerNumber = $query->count();
        $cookbook['scorerNumber'] = $scorerNumber;
        //sample
        $sample = $cookbook['sample'];
        for ($j=0;$j < count($sample); $j++) {
            $querySample = new Query();
            $querySample->from('uhkklpSample')->where(['_id' => $sample[$j]['id']]);
            $sampleDate = $querySample->one();
            $cookbook['sample'][$j]['imgUrl'] = $sampleDate['imgUrl'];
            $cookbook['sample'][$j]['explain'] = $sampleDate['quantity'];
            unset($cookbook['sample'][$j]['quantity']);
        }

        return $cookbook;
    }

    public function actionGetIsActiveList()
    {
        $query = new Query();
        $accountId = $this->getAccountId();

        $records = $query->from('uhkklpActivitySetting')
        ->select(['_id', 'name'])
        ->where(['accountId' => $accountId, 'isDeleted' => false])
        ->andWhere(['IsActive' => true])
        ->orderBy('updatedAt DESC')
        ->all();

        for ($i=0; $i < count($records); $i++) {
            $item = $records[$i];
            $item['id'] = $item['_id']->{'$id'};
            unset($item['_id']);
            $records[$i] = $item;
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records];
    }
}
