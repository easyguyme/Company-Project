<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use backend\modules\uhkklp\models\CookingType;
use yii\mongodb\Query;
use backend\modules\uhkklp\controllers\BaseController;
use backend\models\User;
use backend\modules\uhkklp\models\Cookbook;

class CookingTypeController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionSave()
    {
        $accountId = $this->getAccountId();
        $adminId = $this->getParams("id");
        $admin = User::findOne($adminId);
        if ($admin == null) {
            return ['code' => 1209,'msg' => 'not login'];
        }
        $name = $this->getParams('name', '');
        if ($name == '') {
            return ['code' => 1202, 'msg' => 'Name is required.'];
        }
        $category = $this->getParams('category', '');
        if ($category == '') {
            return ['code' => 1202, 'msg' => 'Category is required.'];
        }
        $radio = $this->getParams('radio', true);
        $cookingtypeId = $this->getParams('cookingtypeId', '');
        $cookingType = null;
        if ($cookingtypeId != '') {
            $cookingType = CookingType::findOne($cookingtypeId);
            if ($cookingType == null) {
                return ['code' => 1204, 'msg' => 'cooking type not found.'];
            } else {
                $cookingTypes = CookingType::find()->where(['category' => $cookingType->name])->all();
                for ($i=0; $i < count($cookingTypes); $i++) {
                    $cookingTypes[$i]->category = $name;
                    $cookingTypes[$i]->save();
                }
            }
        } else {
            $cookingType = new CookingType();
        }
        $cookingType->name = $name;
        $cookingType->category = $category;
        $cookingType->operator = $admin['name'];
        $cookingType->radio = $radio . '';
        $cookingType->accountId = $accountId;
        $cookingType->save();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'save success'];
    }

    public function actionCountList()
    {
        $accountId = $this->getAccountId();
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $query = new Query();
        $query->from('uhkklpCookingtype')
            ->where(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['or', ['category' => '大類']])
            ->andWhere(['like','name',$keyword]);
        $count = $query->count();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $count];
    }

    public function actionList()
    {
        $accountId = $this->getAccountId();
        CookingType::initCookingType($accountId);
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $query = new Query();
        $query->from('uhkklpCookingtype')
            ->where(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['like','name',$keyword])
            ->orderBy('createdAt DESC');
        $list = $query->all();
        $list = $this->formatCookingTypes($list);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $list];
    }

    public function actionListCategory()
    {
        $keyword = $request = Yii::$app->request->get("keyword",'');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        $page = $request = Yii::$app->request->get("page",1);
        $pageSize = $request = Yii::$app->request->get("pageSize",10);
        $offset = ($page-1)*$pageSize;
        $sortName = Yii::$app->request->get('sortName','updatedAt');
        $sortDesc = Yii::$app->request->get('sortDesc','DESC');
        $sort = $sortName . ' ' . $sortDesc;

        $accountId = $this->getAccountId();
        CookingType::initCookingType($accountId);
        $query = new Query();
        $query->from('uhkklpCookingtype')
            ->where(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->andWhere(['category' => '大類'])
            ->andWhere(['like','name',$keyword])
            ->limit($pageSize)
            ->offset($offset)
            ->orderBy($sort);
        $list = $query->all();
        $list = $this->formatCookingTypes($list);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $list];
    }

    private function formatCookingType($item)
    {
        $item['id'] = $item['_id'] . '';
        if (array_key_exists('updatedAt', $item)) {
            $updatedAt = $item['updatedAt']->sec;
            $item['updatedAt'] = date('Y-m-d H:i:s', $updatedAt);
        }
        if (array_key_exists('createdAt', $item)) {
            $createdAt = $item['createdAt']->sec;
            $item['createdAt'] = date('Y-m-d H:i:s', $createdAt);
        }
        return $item;
    }

    private function formatCookingTypes($list)
    {
        date_default_timezone_set('Asia/Shanghai');
         for ($i = 0; $i < count($list); $i++) {
            $item = $this->formatCookingType($list[$i]);
            $list[$i] = $item;
        }
        return $list;
    }

    public function actionRename()
    {
        $typeId = $this->getParams("id", '');
        $newName = $this->getParams("newName", '');
        if ($newName == '') {
            return ['code' => 1202, 'msg' => 'New name is need'];
        }
        $cookingType = CookingType::findOne($typeId);
        if ($cookingType == null) {
            return ['code' => 1204, 'msg' => 'Type is not exist'];
        }
        $cookingType->name = $newName;
        $cookingType->save();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'Rename types success'];
    }

    public function actionDelete()
    {
        $cookingtypeId = $this->getParams("id", '');
        if ($cookingtypeId != null) {
            $query = new Query();
            $query->from('uhkklpCookingtype')
                ->select(['name'])
                ->where(['_id' => $cookingtypeId]);
            $cookingType = $query->one();
            $cookingTypes = cookingType::find()->where(['category' => $cookingType['name']])->all();
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            for ($i=0; $i < count($cookingTypes); $i++) {
                $cookingTypes[$i]->category = '標簽';
                $cookingTypes[$i]->save();
            }
            CookingType::deleteAll(['_id' => $cookingtypeId]);
            return ['code' => 200, 'msg' => 'Delete types success'];
        } else {
            return ['code' => 1204, 'msg' => 'Type is not exist'];
        }
    }

    public function actionUpdate()
    {
        $categoryId = $this->getParams('categoryId', []);
        $tags = $this->getParams('tags', []);
        $accountId = $this->getAccountId();
        $category = cookingType::findOne($categoryId);
        for ($i=0; $i < count($tags); $i++) {
            $cookingType = cookingType::findOne($tags[$i]['id']);
            if ($tags[$i]['check'] && $cookingType['category'] != $category['name']) {
                $cookingType->category = $category['name'];
                $cookingType->save();
            } else if (!$tags[$i]['check'] && $cookingType['category'] == $category['name']) {
                $cookingType->category = '標簽';
                $cookingType->save();
            }
            unset($cookingType);
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'Update success'];
    }

    public function actionGetById()
    {
        $cookingtypeId = Yii::$app->request->get('cookingtypeId', '');
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($cookingtypeId != '') {
            $query = new Query();
            $query->from('uhkklpCookingtype')
                ->where(['_id' => $cookingtypeId]);
            $cookingType = $query->one();
            return ['code' => 200, 'msg' => 'OK', 'result' => $this->formatCookingType($cookingType)];
        }
        return ['code' => 1204, 'msg' => 'Type is not exist'];
    }

    //API
    public function actionGetTags()
    {
        $accountId = $this->getAccountId();
        $query = new Query();
        $query->from('uhkklpCookingtype')
            ->where(['isDeleted' => false])
            ->andWhere(['accountId' => $accountId])
            ->orderBy('updatedAt DESC');
        $list = $query->all();
        $tags = [];
        for ($i=0; $i < count($list); $i++) {
            if ($list[$i]['category'] == '大類') {
                for ($j=0; $j < count($tags); $j++) {
                    if ($tags[$j]['name'] == $list[$i]['name']) {
                        if (isset($list[$i]['radio'])) {
                            if ($list[$i]['radio'] == 'true') {
                                $tags[$j]['isRadio'] = 'Y';
                            } else {
                                $tags[$j]['isRadio'] = 'N';
                            }
                        } else {
                            $tags[$j]['isRadio'] = 'Y';
                        }
                        break;
                    }
                }
                if ($j >= count($tags)) {
                    $tag = [];
                    $tag['name'] = $list[$i]['name'];
                    $tag['items'] = [];
                    if (isset($list[$i]['radio'])) {
                        if ($list[$i]['radio'] == 'true') {
                            $tag['isRadio'] = 'Y';
                        } else {
                            $tag['isRadio'] = 'N';
                        }
                    } else {
                        $tag['isRadio'] = 'Y';
                    }
                    array_push($tags, $tag);
                }
            }
            if ($list[$i]['category'] != '大類' && $list[$i]['category'] != '固定分類' && $list[$i]['category'] != '標簽') {
                for ($j=0; $j < count($tags); $j++) {
                    if ($tags[$j]['name'] == $list[$i]['category']) {
                        array_push($tags[$j]['items'], $list[$i]['name']);
                        break;
                    }
                }
                if ($j >= count($tags)) {
                    $tag = [];
                    $tag['name'] = $list[$i]['category'];
                    $tag['items'] = [];
                    array_push($tag['items'], $list[$i]['name']);
                    $tag['isRadio'] = 'Y';
                    array_push($tags, $tag);
                }
            }
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'msg' => 'OK', 'result' => $tags];
    }
}