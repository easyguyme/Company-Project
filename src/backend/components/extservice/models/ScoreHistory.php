<?php
namespace backend\components\extservice\models;

use MongoId;
use backend\modules\member\models\ScoreHistory as ModelScoreHistory;
use yii\web\BadRequestHttpException;

/**
 * ScoreHistory for extension
 */
class ScoreHistory extends BaseComponent
{
    public function all($conditions)
    {
        if (empty($conditions['accountId'])) {
            $conditions['accountId'] = $this->accountId;
        }
        return ModelScoreHistory::findAll($conditions);
    }

    /**
     * Query member`s score history
     * @param  array    $conditions
     * @param  int      $page
     * @param  int      $pageSize
     * @return array
     */
    public function search($conditions, $page = 1, $pageSize = 10)
    {
        //build the query
        $result = ModelScoreHistory::search($conditions, $this->accountId);
        return $this->formatPageResult($result, $page, $pageSize);
    }

    /**
     * Get the overview of the score history
     * @param array $pipeline
     * @return array
     */
    public function aggregate($pipeline)
    {
        if (empty($pipeline[0]['$match'])) {
            array_unshift($pipeline, ['$match' => ['accountId' => $this->accountId]]);
        } else if (empty($pipeline[0]['$match']['accountId'])) {
            $pipeline[0]['$match']['accountId'] = $this->accountId;
        }
        return ModelScoreHistory::getCollection()->aggregate($pipeline);
    }
}
