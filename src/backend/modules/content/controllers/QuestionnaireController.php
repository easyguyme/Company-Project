<?php
namespace backend\modules\content\controllers;

use Yii;
use MongoId;
use MongoDate;
use backend\models\Questionnaire;
use backend\models\Question;
use backend\models\User;
use backend\models\Token;
use backend\utils\TimeUtil;
use backend\utils\MongodbUtil;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use yii\helpers\ArrayHelper;
use backend\models\QuestionnaireLog;
use backend\models\StatsQuestionnaireAnswerDaily;

class QuestionnaireController extends BaseController
{
    public $modelClass = 'backend\models\Questionnaire';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['delete'], $actions['update'], $actions['view']);
        return $actions;
    }

    /**
     * Create Questionnaire.
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/content/questionnaires<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for creating questionnaire.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     name: string<br/>
     *     startTime: string, startTime = "1429000112193"<br/>
     *     endTime: string, endTime = "1429000112193"<br/>
     *     description: string<br/>
     *     question:Array, question = [{"title": "math","type": "radio","order": 0,"options": [{"icon":
     *              "support","content": "A option" },{"icon": "support","content": "B option"}]},{"type":
     *              "input","order": 1,"title": "This is a problem"}]<br/>
     *     isPublished: boolean<br/>
     *
     * <b>Response Params:</b><br/>
     *     {
     *           "name": "name",
     *           "startTime": "1429000112193",
     *           "endTime": "1429000116193",
     *           "description": "good",
     *           "question": [
     *               {
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
     *                   "type": "input",
     *                   "order": 1,
     *                   "title": "This is a problem"
     *               }
     *           ],
     *           "isPublished": false
     *       }
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * {
     *     "message": "OK",
     *     "data": ""
     * }
     * <pre>
     * </pre>
     */
    public function actionCreate()
    {
        $params = $this->getParams();

        if (!isset($params['name']) || empty($params['startTime']) || empty($params['endTime']) || !isset($params['isPublished'])) {
            throw new InvalidParameterException(Yii::t('common', 'parameters_missing'));
        }
        Questionnaire::isNameExist($params['name']);

        $params['startTime'] = new \MongoDate(TimeUtil::ms2sTime($params['startTime']));
        $params['endTime'] = new \MongoDate(TimeUtil::ms2sTime($params['endTime']));

        $token = $this->getAccessToken();
        $tokenInfo = Token::getToken($token);
        $accountId = $tokenInfo['userId'];
        $params['accountId'] = $tokenInfo['accountId'];

        $condition = [];
        $question = [];
        $questionIds = [];
        $options = [];
        $questionTitles = [];

        if (!empty($params['question']) && count($params['question']) > 0) {
            foreach ($params['question'] as $questionInfo) {
                $questionId = new MongoId();
                $questionIds[] = $questionId;
                Question::checkTitle($questionInfo['title']);
                $question = [
                    '_id' => $questionId,
                    'type' => $questionInfo['type'],
                    'title' => $questionInfo['title'],
                    'order' => $questionInfo['order'],
                    'createdAt' => new \MongoDate(),
                    'accountId' => $accountId,
                ];
                if (strcasecmp($questionInfo['type'], Question::TYPE_INPUT) != 0) {
                    if (is_array($questionInfo['options'])) {
                        if (Question::isQuestionOptionRepeat($questionInfo['options']) != null) {
                            $question['options'] = $questionInfo['options'];
                        }
                    }
                }

                if (in_array($question['title'], $questionTitles)) {
                    throw new InvalidParameterException(Yii::t('content', 'question_incorrect'));
                }
                $questionTitles[] = $question['title'];
                $condition[] = $question;
            }
            $isSaveQuestions = Question::saveQuestions($condition);

            if (!$isSaveQuestions) {
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        }

        $account = User::findOne(['_id' => new MongoId($accountId)]);
        $questionnaire = new Questionnaire();
        $questionnaire->name = $params['name'];
        $questionnaire->startTime = $params['startTime'];
        $questionnaire->endTime = $params['endTime'];
        $questionnaire->description = !isset($params['description']) ? '' : $params['description'];
        $questionnaire->creator = [
            'id' => $account['_id'],
            'name' => !isset($account['name']) ? '' : $account['name'],
        ];
        $questionnaire->questions = $questionIds;
        $questionnaire->accountId = $params['accountId'];
        $questionnaire->isPublished = $params['isPublished'];
        $questionnaire->createdAt = new \MongoDate();

        if (!$questionnaire->save()) {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        } else {
            return ["message"=>"OK", "data" => ""];
        }
    }

    /**
     * Delete Questionnaire.
     *
     * <b>Request Type</b>: DELETE<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/content/questionnaire/{product_id_list}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for deleting questionnaire.
     * <br/><br/>
     *
     * <pre>
     * </pre>
     */
    public function actionDelete($id)
    {
        $idList = explode(',', $id);
        $ids = [];
        foreach ($idList as $perId) {
            $ids[] = new MongoId($perId);
        }

        $questionnaireMessage = Questionnaire::getByQuestionnaireIds($ids);
        if (empty($questionnaireMessage)) {
            throw new BadRequestHttpException(Yii::t('content', 'questionnaire_is_deleted'));
        }

        $questionIds = [];
        if (is_array($questionnaireMessage)) {
            foreach ($questionnaireMessage as $questionnaireInfo) {
                $questionIds = array_merge($questionIds, $questionnaireInfo['questions']);
            }
        }

        $isDeQuestionnaire = Questionnaire::deleteAll(['_id' => ['$in' => $ids]]);
        $isDeQuestion = Question::deleteAll(['_id' => ['$in' => $questionIds]]);

        if (!$isDeQuestionnaire && !$isDeQuestion) {
            throw new ServerErrorHttpException(Yii::t('content', 'delete_fail'));
        }
    }

    /**
     * Get question name list
     * @throws InvalidParameterException
     * @return array, [{"id": "55dafffdd6f97f1a5e8b4568", "title": "Who are you"}]
     */
    public function actionQuestionNames()
    {
        $questionnaireId = $this->getQuery('questionnaireId');
        $questionnaire = Questionnaire::findByPk(new MongoId($questionnaireId));
        if (empty($questionnaire)) {
            throw new InvalidParameterException(Yii::t('content', 'questionnaire_no_exist'));
        }
        //only get stats question, checkbox and radio
        $questions = Question::getByIds($questionnaire->questions, true);
        $questionNameList = [];
        foreach ($questions as $question) {
            $questionNameList[] = [
                'id' => (string) $question->_id,
                'title' => $question->title
            ];
        }
        return $questionNameList;
    }

     /**
     * Update Questionnaire.
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/content/questionnaire/{questionnaireId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for updating questionnaire.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     name: string<br/>
     *     startTime: string, startTime = "1429000112193"<br/>
     *     endTime: string, endTime = "1429000112193"<br/>
     *     description: string<br/>
     *     question:Array, question = [{"id": "55d6cb8be9c2fb022c8b4577","title": "math","type": "radio",
     *              "order": 0,"options": [{"icon": "support","content": "A option"},{"icon": "support",
     *              "content": "B option"}]},{"id": "55d6cb8be9c2fb022c8b4577","type": "input","title":
     *              "This is a problem","order": 1}]<br/>
     *     isPublished: boolean<br/>
     *
     * <b>Response Params:</b><br/>
     *     {
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
     *           "isPublished": false
     *     }
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * {
     *     "message": "OK",
     *     "data": ""
     * }
     * <pre>
     * </pre>
     */
    public function actionUpdate($id)
    {
        $id = new MongoId($id);
        $params = $this->getParams();
        $questionnaire = Questionnaire::getById($id);
        $question = [];
        $questionTitles = [];
        $questionExistIds = [];

        if (empty($questionnaire)) {
            throw new BadRequestHttpException(Yii::t('content', 'questionnaire_missing'));
        }

        if (!empty($params['startTime'])) {
            $params['startTime'] = new \MongoDate(TimeUtil::ms2sTime($params['startTime']));
        }

        if (!empty($params['endTime'])) {
            $params['endTime'] = new \MongoDate(TimeUtil::ms2sTime($params['endTime']));
        }

        $questionsItems = Question::getByIds($questionnaire->questions);

        if (!empty($params['question']) && count($params['question']) > 0) {
            foreach ($params['question'] as $questionInfo) {
                Question::checkTitle($questionInfo['title']);
                $question = [
                    '_id' => empty($questionInfo['_id']) ? new MongoId() : new MongoId($questionInfo['_id']),
                    'title' => $questionInfo['title'],
                    'type' => $questionInfo['type'],
                    'order' => $questionInfo['order'],
                ];
                if (strcasecmp($questionInfo['type'], Question::TYPE_INPUT) != 0) {
                    if (is_array($questionInfo['options'])) {
                        if (Question::isQuestionOptionRepeat($questionInfo['options']) != null) {
                            $question['options'] = $questionInfo['options'];
                        }
                    }
                }

                if (in_array($question['title'], $questionTitles)) {
                    throw new InvalidParameterException(Yii::t('content', 'question_incorrect'));
                }

                $questionTitles[] = $question['title'];
                if (empty($questionInfo['id'])) {
                    $questionOption = new Question();
                    $questionOption->_id = new MongoId();
                } else {
                    $questionOption = Question::findByPk(new MongoId($questionInfo['id']));
                }
                $questionOption->title = $questionInfo['title'];
                $questionOption->type = $questionInfo['type'];
                $questionOption->order = $questionInfo['order'];
                $questionOption->options = empty($questionInfo['options']) ? [] : $questionInfo['options'];
                $questionOption->createdAt = new \MongoDate();

                if (!$questionOption->save()) {
                    throw new ServerErrorHttpException(Yii::t('content', 'update_fail'));
                }
                $questionExistIds[] = $questionOption->_id;
            }
            if (!empty($questionExistIds) && count($questionnaire->questions) >= count($questionExistIds)) {
                $isDeleteQuestion = array_diff($questionnaire->questions, $questionExistIds);
                if (count($isDeleteQuestion) > 0) {
                    $isDeQuestion = Question::deleteAll(['_id' => ['$in' => $isDeleteQuestion]]);
                    if (!$isDeQuestion) {
                        throw new ServerErrorHttpException(Yii::t('content', 'update_fail'));
                    }
                }
            }
        }

        if (!empty($params['name']) && !empty($params['startTime']) && !empty($params['endTime'])) {
            $questionnaire->name = $params['name'];
            $questionnaire->startTime = $params['startTime'];
            $questionnaire->endTime = $params['endTime'];
            $questionnaire->description = !isset($params['description']) ? '' : $params['description'];
        }
        $questionnaire->isPublished = $params['isPublished'];
        $questionnaire->createdAt = new \MongoDate();
        if (!empty($params['question']) && count($params['question']) > 0) {
            $questionnaire->questions = $questionExistIds;
        }
        unset($questionnaire->createdAt);
        if (!$questionnaire->save()) {
            throw new ServerErrorHttpException(Yii::t('common', 'update_fail'));
        } else {
            return ["message"=>"OK", "data" => ""];
        }
    }

    public function actionView($id)
    {
        $questionnaireId = new MongoId($id);
        $questionnaire = Questionnaire::findByPk($questionnaireId);
        if (empty($questionnaire)) {
            throw new InvalidParameterException(Yii::t('common', 'invalid_questionnaire'));
        }
        $questionnaire = $questionnaire->toArray();
        $questionnaire['userCount'] = QuestionnaireLog::countByQuestionnaireId($questionnaireId);
        foreach ($questionnaire['questions'] as &$question) {
            $questionId = new MongoId($question['id']);
            $stats = StatsQuestionnaireAnswerDaily::getQuestionOptionStats($questionId);
            $statsMap = ArrayHelper::map($stats, 'option', 'count');
            if ($question['type'] !== Question::TYPE_INPUT) {
                foreach ($question['options'] as &$option) {
                    $option['count'] = empty($statsMap[$option['content']]) ? 0 : $statsMap[$option['content']];
                }
            }

            if ($question['type'] == Question::TYPE_INPUT) {
                $question['count'] = QuestionnaireLog::countByQuestionnaireIdAndQuestionId($questionnaireId, $questionId);
            }
        }
        return $questionnaire;
    }

    public function actionUnexpiredQuestionnaire()
    {
        $now = new MongoDate();
        $accountId = $this->getAccountId();
        return Questionnaire::find()->where(['endTime' => ['$gte' => $now], 'isPublished' => true, 'accountId' => $accountId])->orderBy(['createdAt' => SORT_DESC])->all();
    }
}
