<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\web\Controller;
use backend\models\Token;
use backend\modules\uhkklp\models\CookbookBatch;
use backend\modules\uhkklp\models\Cookbook;
use backend\utils\LogUtil;

class CookbookBatchController extends BaseController
{
    public $enableCsrfValidation = false;

    private function _setJSONFormat($app) {
        $app->request->parsers = [
            'application/json' => 'yii\web\JsonParser',
            'text/json' => 'yii\web\JsonParser',
        ];
        $app->response->format = 'json';
    }

    public function actionGetList()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = Yii::$app->request->post();

        $condition = ['like', 'cookbooks.name', $data['searchKey']];

        $dataCount = CookbookBatch::getCount($condition);
        $result = CookbookBatch::getList($data['currentPage'], $data['pageSize'], $data['sort'], $condition);

        $resData = ['dataCount' => $dataCount, 'result' => $result];
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return $resData;
    }

    public function actionImage()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = Yii::$app->request->post();
        $id = $data['id'];
        $imageKeys = $data['imgKeys'];

        $cookbookBatch = CookBookBatch::findOne([$id]);
        $cookbookBatch->hasImages = true;
        $cookbookBatch->save();

        $cookbooks = $cookbookBatch->cookbooks;
        foreach ($cookbooks as $cb) {
            $cookbookId = $cb['cookbookId'];
            $cookbook = Cookbook::findOne($cookbookId);
            $imgurl = $cb['image'];
            explode('/', $imgurl);
            $array = explode('/', $imgurl);
            $img = $array[count($array) - 1];
            for ($i=0; $i < count($imageKeys); $i++) {
                if (self::getImgName($imageKeys[$i]['name']) == self::getImgName($img)) {
                    $imgurl = str_replace($img,$imageKeys[$i]['key'],$imgurl);
                    $imageKeys[$i]['name'] = '-1';
                    break;
                }
            }
            $cookbook->image = $imgurl;
            $cookbook->hasImportImg = true;
            $cookbook->save();
        }
    }

    private function getImgName($name)
    {
        $name = str_replace('.png','',$name);
        $name = str_replace('.jpg','',$name);
        $name = str_replace('.gif','',$name);
        return $name;
    }

    public function actionTest()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = Yii::$app->request->post();
        $id = $data['id'];
        $imageKeys = $data['imgKeys'];
        $imgurl = 'http://vincenthou.qiniudn.com/R0042781.jpg';
        $array = explode('/', $imgurl);
        $array[count($array) - 1];
        $imgurl = str_replace($imageKeys[0]['name'],$imageKeys[0]['key'],$imgurl);
        return $imgurl;
    }

    public function actionDelete()
    {
        $this->_setJSONFormat(Yii::$app);
        $request = Yii::$app->request;
        $id = $request->post('id');
        $cookbookBatch = CookBookBatch::findOne([$id]);
        $cookbooks = $cookbookBatch->cookbooks;
        foreach ($cookbooks as $cookbook) {
            $cookbookId = $cookbook['cookbookId'];
            $cb = Cookbook::findOne($cookbookId);
            $cb->delete();
        }
        $cookbookBatch->delete();
        return ['code' => '-1'];
    }
}
