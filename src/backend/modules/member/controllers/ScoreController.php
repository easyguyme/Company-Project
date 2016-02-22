<?php
namespace backend\modules\member\controllers;

use Yii;
use MongoId;
use backend\modules\member\models\Member;
use backend\modules\member\models\ScoreHistory;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use backend\models\User;
use backend\utils\LogUtil;

/**
 * Controller class for score
 */
class ScoreController extends BaseController
{
    public $modelClass = "backend\modules\member\models\ScoreHistory";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        return $actions;
    }

    /**
     * Give points to member
     *
     * <b>Request Type: </b>POST<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/member/score/give<br/>
     * <b>Content-type: </b>application/json<br/>
     * <b>Summary: </b>This api is for give scores to specific members or conditions to filter member.<br/>
     *
     * <b>Request Parameters: </b>
     *     scores: int, the value of the scores to give, required<br/>
     *     filterType: string, the type of the filter, "name" or "number" or "tag", required<br/>
     *     names: array<string>, the array of the names for filter, required only if the filterType is "name"<br/>
     *     numbers: array<string>, the array of the numbers to filter members, required only if the filterType is "number"<br/>
     *     tags: array<string>, the array of the tags fo filter members, required only if the filterType is "tag"<br/>
     *     description: string, the description. optional. <br/>
     *
     * <b>Request Example</b><br/>
     * <pre>
     * {"score":100, "filterType":"name", "names":["Zhang San", "Li Si"]}
     * </pre>
     *
     */
    public function actionGive()
    {
        $filterType = $this->getParams('filterType');
        $score = $this->getParams('score');
        $description = $this->getParams('description');

        if (empty($filterType) || empty($score)) {
            throw new BadRequestHttpException('Missing required parameters');
        }

        $userId = $this->getUserId();
        $user = User::findByPk($userId);
        $user = [
            'id' => $userId,
            'name' => $user->name
        ];

        $filterKey = $filterType . 's';
        $filterValue = $this->getParams($filterKey);

        if (empty($filterValue)) {
            throw new BadRequestHttpException("Missing required parameters");
        }

        $function = "giveScoreBy" . $filterKey;

        $memberList = Member::$function($score, $filterValue);
        if (empty($memberList)) {
            throw new InvalidParameterException(['member-' . $filterType => \Yii::t('member', 'no_member_find')]);
        }

        if ($memberList) {
            foreach ($memberList as $member) {
                $scoreHistory = new ScoreHistory;
                $scoreHistory->assigner = ScoreHistory::ASSIGNER_ADMIN;
                $scoreHistory->increment = $score;
                $scoreHistory->memberId = $member;
                $scoreHistory->brief = ($score >= 0) ? ScoreHistory::ASSIGNER_ADMIN_ISSUE_SCORE : ScoreHistory::ASSIGNER_ADMIN_DEDUCT_SCORE;
                $scoreHistory->description = $description;
                $scoreHistory->channel = ['origin' => ScoreHistory::PORTAL];
                $scoreHistory->user = $user;
                $scoreHistory->accountId = $this->getAccountId();

                if (!$scoreHistory->save()) {
                    LogUtil::error(['message' => 'save scoreHistory failed', 'data' => $scoreHistory->toArray()], 'member');
                }
            }

            return ['status' => 'ok'];
        }
    }

    /**
     * Get the overview of the score history
     *
     * <b>Request Type: </b>GET<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/member/score/overview<br/>
     * <b>Summary: </b>This api is for the statistics of the score history.
     * <b>Response Example: </b>
     * <pre>
     * {"memberToday": 2, "scoreToday": 600, "scoreYesterday": 300}
     * </pre>
     */
    public function actionOverview()
    {
        $accountId = $this->getAccountId();

        //query the member count
        $memberCountToday = ScoreHistory::getMemberCountToDay($accountId);
        $totalScoreToday = ScoreHistory::getTotalScoreToday($accountId);
        $totalScoreYesterday = ScoreHistory::getTotalScoreYesterday($accountId);

        return ['memberToday' => $memberCountToday, 'scoreToday' => $totalScoreToday, 'scoreYesterday' => $totalScoreYesterday];
    }

    /**
     * Query the score history of a specific member
     *
     * <b>Request Type: </b>GET<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/scores?memberId={memberId}&per-page={pageSize}&page={pageNum}
     * <b>Summary: </b>This api is for query the score history for a specific member
     *
     * <b>Response Example: </b>
     * <pre>
     * {
     * "items": [
     *   {
     *       "id": "54aa2e1fdb4c0ec6048b4570",
     *       "assigner": "admin",
     *       "increment": 100,
     *       "description": "abc123",
     *       "createdAt": "2015-01-05 14:24:31"
     *   },
     *   {
     *       "id": "54aa2df3db4c0ec5048b4570",
     *       "assigner": "admin",
     *       "increment": 100,
     *       "description": "",
     *       "createdAt": "2015-01-05 14:23:47"
     *   }
     * ],
     * "_links": {
     *   "self": {
     *       "href": "http://dev.cp.augmarketing.cn/api/member/scores?memberId=54a8f557ff64ee5203bede18&page=1"
     *   }
     * },
     * "_meta": {
     *   "totalCount": 12,
     *   "pageCount": 1,
     *  "currentPage": 1,
     *   "perPage": 20
     * }
     *}
     * </pre>
     */
    public function actionIndex()
    {
        $params = $this->getQuery();
        if (!empty($params['memberId'])) {
            $params['memberId'] = new MongoId($params['memberId']);
        }
        $accountId = $this->getAccountId();

        //build the query
        return ScoreHistory::search($params, $accountId);
    }
}
