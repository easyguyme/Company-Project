<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\web\Controller;
use backend\models\Token;
use backend\modules\uhkklp\models\Product;
use backend\models\User;
use backend\utils\LogUtil;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\KlpAccountSetting;

class ProductController extends BaseController
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
            $product = Product::findOne($data['_id']['$id']);
            $data['updatedAt'] = new \MongoDate();
            $code = '2';
        }

        if (empty($product)) {
            $product = new Product();

            $accessToken = Token::getToken();
            $user = User::findOne(['_id' => $accessToken->userId]);

            $data['accountId'] = $this->getAccountId();
            $data['creator'] = $user->name;
            $data['createdAt'] = new \MongoDate();
            $data['isDeleted'] = false;
        }

        if (!empty($product->accountId)) {
            unset($data['accountId']);
        }

        $product->attributes = $data;

        $product->save();

        return ['code' => $code];
    }

    public function actionGet($id)
    {
        $this->_setJSONFormat(Yii::$app);
        $product = Product::findOne($id);
        return $product->attributes;
    }

    public function actionGetList()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = Yii::$app->request->post();
        $currentPage = $data['currentPage'];
        $pageSize = $data['pageSize'];
        $offset = ($currentPage - 1) * $pageSize;
        // $offSetRight = $currentPage * $pageSize - 1;

        // $product = new Product();
        // $dataCount = $product->getCount(['accountId' => $this->getAccountId()]);
        // $products = $product->getList($data['currentPage'], $data['pageSize'], ['accountId' => $this->getAccountId()]);

        // $products = [
        //     // ['name' => 'abc', 'tag' => 'a', 'url' => 'http://aa'],
        //     // ['name' => 'abc', 'tag' => 'b', 'url' => 'http://aa'],
        //     // ['name' => 'abc', 'tag' => 'c', 'url' => 'http://cc']
        // ];

        $site = KlpAccountSetting::getAccountSite($this->getAccountId());
        if ($site == 'TW') {
            $allProducts = require(dirname(__FILE__). '/../' . 'ProductTW.php');
        } else {
            $allProducts = require(dirname(__FILE__). '/../' . 'ProductHK.php');
        }

        $products = array_slice($allProducts, $offset, $pageSize);

        $dataCount = sizeof($allProducts);

        $resData = ['dataCount'=>$dataCount, 'product'=>$products];

        return $resData;
    }

    public function actionDelete()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = Yii::$app->request->post();
        $id = $data['id'];
        $product = Product::findOne($id);
        $product->isDeleted = true;
        $product->update();
        return ['code' => '-1'];
    }

}
