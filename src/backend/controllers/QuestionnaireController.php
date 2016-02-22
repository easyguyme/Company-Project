<?php
namespace backend\controllers;

use Yii;
use MongoId;
use backend\components\rest\RestController;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\utils\MongodbUtil;
use backend\models\Questionnaire;
use backend\models\QuestionnaireLog;
use yii\web\ServerErrorHttpException;
use backend\exceptions\ApiDataException;
use backend\utils\LogUtil;

class QuestionnaireController extends RestController
{
    public $modelClass = 'backend\models\Questionnaire';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['update'], $actions['view']);
        return $actions;
    }

    public function actionAnswer()
    {
        $params = $this->getParams();
        if (empty($params['questionnaireId']) || empty($params['answers'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $user = [];
        if (!empty($params['user']['channelId']) && !empty($params['user']['openId'])) {
            $channelId = $params['user']['channelId'];
            $openId = $params['user']['openId'];
            $user = $params['user'];
            try {
                $follower = Yii::$app->weConnect->getFollowerByOriginId($openId, $channelId);
                $user['name'] = empty($follower['nickname']) ? '' : $follower['nickname'];
            } catch (ApiDataException $e) {
                LogUtil::error(['message' => 'Answer questionnaire failed to get follower info', 'param' => ['openId' => $openId, 'channelId' => $channelId]]);
            }
        }
        $questionnaireId = new MongoId($params['questionnaireId']);
        $questionnaire = Questionnaire::findByPk($questionnaireId);
        //error questionnaire id or un published questionnaire
        if (empty($questionnaire) || !$questionnaire->isPublished) {
            throw new InvalidParameterException(Yii::t('content', 'invalid_questionnaire'));
        }
        //questionnaire has not begun
        $now = time();
        if (MongodbUtil::MongoDate2TimeStamp($questionnaire->startTime) > $now) {
            throw new InvalidParameterException(Yii::t('content', 'questionnaire_not_began'));
        }
        //questionnaire expired
        if (MongodbUtil::MongoDate2TimeStamp($questionnaire->endTime) < $now) {
            throw new InvalidParameterException(Yii::t('content', 'questionnaire_expired'));
        }

        if (!empty($user)) {
            $questionnaireLog = QuestionnaireLog::getByQuestionnaireAndUser($questionnaireId, $user);
            if (!empty($questionnaireLog)) {
                throw new InvalidParameterException(Yii::t('content', 'user_has_answered_questionnaire'));
            }
        }

        $quertionnaireLog = new QuestionnaireLog();
        $quertionnaireLog->questionnaireId = $questionnaireId;
        $quertionnaireLog->user = $user;
        $quertionnaireLog->answers = $params['answers'];
        $quertionnaireLog->accountId = $questionnaire->accountId;

        if ($quertionnaireLog->save()) {
            return ['message' => 'OK', 'data' => ''];
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
    }

        /**
     * View Questionnaire by questionnaire.
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/questionnaire/{questionnaireId}&channelId={channelId}&openId={openId}br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for viewing questionnaire.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *    id, string<br/>
     *    channelId, string<br/>
     *    openId, string<br/>
     *
     * <b>Response Example:</b><br/>
     *     {
     *           "_id": "55d6cb8be9c2fb022c8b4579",
     *           "name": "name",
     *           "startTime": "1429000112193",
     *           "endTime": "1429000116193",
     *           "description": "good",
     *           "question": [
     *               {
     *                   "id": "55d6cb8be9c2fb022c8b4577",
     *                   "title": "math",
     *                   "type": "radio",
     *                   "order": 0,
     *                   "options": [
     *                       {
     *                           "icon": "support",
     *                           "content": "A option"
     *                       },
     *                       {
     *                           "icon": "support",
     *                           "content": "B option"
     *                       }
     *                   ]
     *               },
     *               {
     *                   "id": "55d6cb8be9c2fb022c8b4577",
     *                   "type": "input",
     *                   "title": "This is a problem",
     *                   "order": 1
     *               }
     *           ],
     *           "isPublished": false,
     *           "answerTime": "2015-08-26 10:28:55",
     *           "isAnswered": false
     *     }
     * <pre>
     * </pre>
     */
    public function actionView($id)
    {
        $channelId = $this->getQuery('channelId');
        $openId = $this->getQuery('openId');
        $isAnswered = false;
        $question = [];
        $answerTime = '';
        $questionnaire = [];
        $user = [
            "channelId" => $channelId,
            "openId" => $openId
        ];

        $questionnaireInfo = Questionnaire::getById(new MongoId($id));
        if (empty($questionnaireInfo)) {
            throw new InvalidParameterException(Yii::t('content', 'questionnaire_no_exist'));
        }

        if (!empty($channelId) && !empty($openId)) {
            $questionnaireLogInfo = QuestionnaireLog::getByQuestionnaireAndUser(new MongoId($id), $user);
            if (!empty($questionnaireLogInfo)) {
                $answerTime = MongodbUtil::MongoDate2String($questionnaireLogInfo->createdAt, 'Y-m-d H:i:s');
                $isAnswered = true;
            }
        }

        $questionnaire = $questionnaireInfo->toArray();
        $questionnaire['answerTime'] = $answerTime;
        $questionnaire['isAnswered'] = $isAnswered;
        return $questionnaire;
    }
}
