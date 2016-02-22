<?php

namespace backend\modules\microsite\controllers;

use backend\modules\microsite\models\ArticleChannel;
use backend\modules\microsite\models\Article;
use backend\utils\LogUtil;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use Yii;

class ArticleChannelController extends BaseController
{
    public $modelClass = "backend\modules\microsite\models\ArticleChannel";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['update'], $actions['delete'],  $actions['create']);
        return $actions;
    }

    /**
     * create article channe;l
     */
    public function actionCreate()
    {
        $params = $this->getParams();
        $params['accountId'] = $this->getAccountId();
        if (empty($params['name'])) {
            throw new BadRequestHttpException(Yii::t('microSite', 'channel_name_missing'));
        }
        if (!empty($params['fields'])) {
            foreach ($params['fields'] as $field) {
                if (empty($field['type']) || empty($field['name'])) {
                    throw new BadRequestHttpException(Yii::t('microSite', 'custom_field_missing'));
                }
            }
        }
        $articleChannel = new ArticleChannel;
        $articleChannel->load($params, '');
        if (false === $articleChannel->save()) {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
        return $articleChannel;
    }

    /**
     * Update article channel
     */
    public function actionUpdate($id)
    {
        $channelId = new \MongoId($id);
        $channel = ArticleChannel::findByPk($channelId);

        if (empty($channel)) {
            throw new BadRequestHttpException('Incorrect channel id');
        }

        if ($channel->isDefault) {
            throw new BadRequestHttpException(Yii::t('microSite', 'default_channel_protected'));
        }
        $params = $this->getParams();
        //check the field to make sure the type and name is exists
        if (empty($params['name'])) {
            throw new BadRequestHttpException(Yii::t('microSite', 'channel_name_missing'));
        }
        if (!empty($params['fields'])) {
            foreach ($params['fields'] as $field) {
                if (empty($field['type']) || empty($field['name'])) {
                    throw new BadRequestHttpException(Yii::t('microSite', 'custom_field_missing'));
                }
            }
        }
        $channel->load($params, '');

        if ($channel->save() === false && $channel->hasErrors()) {
            LogUtil::error(['message' => 'save article channel error', 'errors' => $channel->getErrors()], 'microsite');
            throw new ServerErrorHttpException("Save article channel failed for unknown reasons");
        }

        //update the articles for the channel
        $articles = Article::findByChannel($channelId);

        foreach ($articles as $article) {
            $oldFields = $article->fields;
            $newFields = [];

            foreach ($channel->fields as $field) {
                $field['content'] = '';

                foreach ($oldFields as $oldField) {
                    if ($field['id'] === $oldField['id']) {
                        $field['name'] = $oldField['name'];
                        if ($field['type'] === $oldField['type']) {
                            $field['content'] = empty($oldField['content']) ? '' : $oldField['content'];
                        }
                    }
                }

                $newFields[] = $field;
            }

            $article->fields = $newFields;

            if (!$article->save()) {
                LogUtil::error(['message' => 'save article error', 'errors' => $article->getErrors()], 'microsite');
                throw new ServerErrorHttpException('Save article failed for unknown reasons');
            }
        }

        return $channel;
    }

    /**
     * Delete channel
     */
    public function actionDelete($id)
    {
        $channelId = new \MongoId($id);

        $articleCount = Article::countByChannel($channelId);

        if ($articleCount > 0) {
            return $articleCount;
        }

        if (!ArticleChannel::deleteAll(['_id' => $channelId])) {
            throw new ServerErrorHttpException("failed to delete article channel for unknown reason");
        }
        Yii::$app->getResponse()->setStatusCode(204);
    }
}
