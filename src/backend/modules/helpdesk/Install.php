<?php
namespace backend\modules\helpdesk;

use Yii;
use yii\web\ServerErrorHttpException;
use backend\modules\helpdesk\models\FaqCategory;
use backend\components\BaseInstall;


class Install extends BaseInstall
{
    const DEFAULT_CATEGORY = "默认分类";
    /**
     * Create default FAQ category
     * @param  \MongoId $accountId
     * @throws ServerErrorHttpException when save the record for default FAQ category failed.
     */
    private function _createDefaultCategory(\MongoId $accountId)
    {
        $defaultCategory = FaqCategory::getDefault($accountId);

        if (empty($defaultCategory)) {
            $category = new FaqCategory;
            $category->name = self::DEFAULT_CATEGORY;
            $category->isDefault = true;
            $category->accountId = $accountId;

            if (!$category->save()) {
                throw new ServerErrorHttpException("create default FAQ category failed");
            }
        }
    }

    public function run($accountId)
    {
        parent::run($accountId);

        $this->_createDefaultCategory($accountId);
    }

}
