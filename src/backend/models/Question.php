<?php
namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use Yii;

/**
 * Model class for question
 *
 * @property MongoId    $_id
 * @property string     $title
 * @property string     $type
 * @property array      $options
 * @property int        $order
 * @property MongoId    $accountId
 * @property MongoDate  $createdAt
 * @author Rex Chen
 */
class Question extends PlainModel
{
    const TYPE_INPUT = 'input';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIO = 'radio';
    const SCENARIO_QUESTION = 'question';

    public static function collectionName()
    {
        return 'question';
    }

    /**
     * Returns the list of all attribute names of question.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'title', 'type', 'options', 'order'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'title', 'type', 'options', 'order'
            ]
        );
    }

    /**
     * Returns the list of all rules of question.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['type', 'title', 'order'], 'required'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into question.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'title','type',
                'options' => function () {
                    return empty($this->options)? [] : $this->options;
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                'order'
            ]
        );
    }

    /**
    * Save questions.
    * @param $account MongoId
    * @param $condition Object
    */
    public static function saveQuestions($condition)
    {
        if (count($condition) > 0) {
            return Question::batchInsert($condition);
        }
    }

    /**
    * Get all the question by question id.
    * @param $questionIds Array
    * @param $onlyStatsQuestion bool
    */
    public static function getByIds($questionIds, $onlyStatsQuestion = false)
    {
        $condition = ['_id' => ['$in' => $questionIds]];
        if ($onlyStatsQuestion) {
            $condition['type'] = ['$in' => [self::TYPE_CHECKBOX, self::TYPE_RADIO]];
        }
        return self::find()->where($condition)->orderBy(['order' => SORT_ASC])->all();
    }

    /**
    * Verify whether the two options are same.
    * @param $options Array
    */
    public static function isQuestionOptionRepeat($options)
    {
        $optionItem = [];
        if (empty($options)) {
            throw new BadRequestHttpException(Yii::t('content', 'question_option_correct'));
        }

        if (count($options) < 2) {
            throw new BadRequestHttpException(Yii::t('content', 'question_option'));
        }

        for ($i = 0; $i < count($options); $i++) {
            for ($j = 0; $j < count($options); $j++) {
                if (empty($options[$j]['content'])) {
                    throw new BadRequestHttpException(Yii::t('content', 'question_fill_option'));
                }
            }
        }
        return $options;
    }

    /**
    * Validate title.
    * @param $questionIds Array
    */
    public static function checkTitle($title)
    {
        if (empty($title)) {
            throw new BadRequestHttpException(Yii::t('content', 'question_fill_title'));
        }
    }
}
