<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\web\Controller;
use backend\models\Token;
use backend\modules\uhkklp\models\Video;
use backend\models\User;
use backend\utils\LogUtil;
use yii\mongodb\Query;

class VideoController extends BaseController
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
        $data = $request->post();
        $code = '1';
        if (!empty($data['_id'])) {
            $video = Video::findOne($data['_id']['$id']);
            $data['updatedAt'] = new \MongoDate();
            $code = '2';
        }

        if (empty($video)) {
            $video = new Video();

            $accessToken = Token::getToken();
            $user = User::findOne(['_id' => $accessToken->userId]);

            $data['accountId'] = $this->getAccountId();
            $data['creator'] = $user->name;
            $data['createdAt'] = new \MongoDate();
            $data['isDeleted'] = false;
        }

        if (!empty($video->accountId)) {
            unset($data['accountId']);
        }

        $video->attributes = $data;

        $video->save();

        return ['code' => $code];
    }

    public function actionGet($id)
    {
        $this->_setJSONFormat(Yii::$app);
        $video = Video::findOne($id);
        return $video->attributes;
    }

    public function actionGetList()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = Yii::$app->request->post();

        $video = new Video();
        $dataCount = $video->getCount(['accountId' => $this->getAccountId()]);
        $videos = $video->getList($data['currentPage'], $data['pageSize'], ['accountId' => $this->getAccountId()], ['updatedAt' => true]);

        $resData = ['dataCount'=>$dataCount, 'video'=>$videos];

        return $resData;
    }

    public function actionDelete()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = Yii::$app->request->post();
        $id = $data['id'];
        $video = Video::findOne($id);
        $video->isDeleted = true;
        $video->update();
        return ['code' => '-1'];
    }

    //api for get one
    public function actionOne($id)
    {
        $this->_setJSONFormat(Yii::$app);
        $video = Video::findOne($id);
        if (empty($video)) {
            $data['code'] = '1404';
            $data['msg'] = '视频不存在';
        } else {
            $data['code'] = '200';
            $data['msg'] = 'OK';
            $result['title'] = $video->title;
            $result['url'] = $video->url;
            $result['imgUrl'] = $video->imgUrl;
            $data['result'] = $result;
        }
        return $data;
    }

    //api for get all
    public function actionAll()
    {
        $this->_setJSONFormat(Yii::$app);
        $query = new Query();
        $result = $query->from(Video::collectionName())
            ->select(['title', 'url', 'imgUrl'])
            ->where(['accountId' => $this->getAccountId()])
            ->all();
        if (empty($result)) {
            $data['code'] = '1900';
            $data['msg'] = '无记录';
            $data['result'] = [];
        } else {
            for ($i = 0; $i < sizeof($result); $i++) {
                unset($result[$i]['_id']);
            }
            $data['code'] = '200';
            $data['msg'] = 'OK';
            $data['result'] = $result;
        }
        return $data;
    }

    //api for get list
    //params: page, pageSize
    public function actionList()
    {
        $this->_setJSONFormat(Yii::$app);
        $param = Yii::$app->request->get();

        if (!isset($param['page'])) {
            $page = 1;
        } else {
            $page = $param['page'];
        }

        if (!isset($param['pageSize'])) {
            $pageSize = 10;
        } else {
            $pageSize = $param['pageSize'];
        }

        if (!preg_match('/^[1-9]\d*$/', (string)$page)) {
            $data['code'] = '1901';
            $data['msg'] = 'page 非法';
            return $data;
        }

        if (!preg_match('/^[1-9]\d*$/', (string)$pageSize)) {
            $data['code'] = '1902';
            $data['msg'] = 'pageSize 非法';
            return $data;
        }

        $video = new Video();
        $dataCount = $video->getCount(['accountId' => $this->getAccountId()]);
        // $dataCount = $video->getCount();
        $pageCount = floor(((int)$dataCount - 1) / (int)$pageSize) + 1;
        if ((int)$page > $pageCount) {
            $data['code'] = '1903';
            $data['msg'] = 'page 过大, 此次查询一共' . $pageCount . '页记录';
            return $data;
        }

        $videos = $video->getList($page, $pageSize, ['accountId' => $this->getAccountId()], ['updatedAt' => true]);
        // $videos = $video->getList($page, $pageSize);

        foreach ($videos as $video) {
            $resVideos[] = [
                'title' => $video['title'],
                'url' => $video['url'],
                'imgUrl' => $video['imgUrl'],
                'position' => $video['position']
            ];
        }

        $ressult['page'] = $page;
        $ressult['pageSize'] = $pageSize;
        $ressult['dataCount'] = $dataCount;
        $ressult['pageCount'] = $pageCount;
        $ressult['videos'] = $resVideos;

        $data['code'] = '200';
        $data['msg'] = 'OK';
        $data['result'] = $ressult;

        return $data;
    }

}
