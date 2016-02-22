<?php

namespace backend\modules\chat\controllers;

use Yii;
use yii\web\ServerErrorHttpException;
use backend\modules\helpdesk\models\Faq;
use backend\modules\helpdesk\models\FaqCategory;

/**
 * Controller class for help desk Faqs.
 **/
class FaqController extends RestController
{
    public $modelClass = "backend\modules\helpdesk\models\Faq";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        return $actions;
    }

    public function actionIndex()
    {
        $accountId = $this->getAccountId();
        $params = $this->getQuery();
        $condition = [
            'accountId' => $accountId,
            'isDeleted' => Faq::NOT_DELETED,
        ];
        if (!empty($params['faqCategoryId'])) {
            $condition['faqCategoryId'] = $params['faqCategoryId'];
        }

        $totalCount = Faq::count($condition);
        $currentPage = $params['page'];
        $perPage = $params['per-page'];
        $offset = ($currentPage - 1) * $perPage;
        $pageCount = (int)(($totalCount - 1) / $perPage + 1);
        $faqs = Faq::search($condition, $offset, $perPage);

        return [
            'totalCount' => $totalCount,
            'currentPage' => $currentPage,
            'perPage' => $perPage,
            'pageCount' => $pageCount,
            'faqs' => $faqs,
        ];
    }

    public function actionGetCategoryList()
    {
        $accountId = $this->getAccountId();
        $categoryList = FaqCategory::getAll($accountId);
        return $categoryList;
    }
}
