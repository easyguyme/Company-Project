<?php

namespace backend\modules\helpdesk\controllers;

use Yii;
use yii\web\ServerErrorHttpException;
use backend\modules\helpdesk\models\Faq;
use backend\modules\helpdesk\models\FaqCategory;

/**
 * Controller class for help desk Faqs.
 **/
class FaqController extends BaseController
{
    public $modelClass = "backend\modules\helpdesk\models\Faq";

    public function beforeAction($action)
    {
        $noAuthActions = [
            'get-faqs',
        ];
        if (in_array($action->id, $noAuthActions)) {
            return true;
        }
        return parent::beforeAction($action);
    }

    public function actionAddFaqCategory()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $faqCategory = new FaqCategory();
        $faqCategory->accountId = $accountId;
        $faqCategory->name = $params['category'];
        if ($faqCategory->save()) {
            return $faqCategory;
        } else {
            throw new ServerErrorHttpException("Save FAQ category failed!");
        }
    }

    public function actionGetCategoryList()
    {
        $accountId = $this->getAccountId();
        $categoryList = FaqCategory::getAll($accountId);
        return $categoryList;
    }

    public function actionRemoveCategory($id)
    {
        $category = FaqCategory::findByPk($id);
        if (empty($category)) {
            throw new ServerErrorHttpException('Remove category failed!');
        }

        $category['isDeleted'] = true;
        $accountId = $category->_id . '';
        $category->save();
        $this->_assignToDefault($accountId);

        return 'ok';
    }

    public function actionGetFaqs()
    {
        FaqCategory::$isFetchFaqs = true;
        $accountId = Yii::$app->request->get('accountId');
        $categoryName = Yii::$app->request->get('category');
        $categoryName = urldecode($categoryName);
        if (empty($accountId)) {
            throw new ServerErrorHttpException('Invalid parameters!');
        }
        $accountId = new \MongoId($accountId);
        if (!empty($categoryName)) {
            $category = FaqCategory::getByName($categoryName, $accountId);
            return $category;
        }

        return FaqCategory::getAll($accountId);
    }


    public function _assignToDefault($categoryId)
    {
        $defaultCategory = FaqCategory::getDefault($this->getAccountId());
        $condition = ['isDeleted' => false, 'faqCategoryId' => $categoryId];
        $results = Faq::find()->where($condition)->all();
        foreach ($results as $result){
            $result['faqCategoryId'] = $defaultCategory->_id . '';
            $result->save();
        }
    }
}

