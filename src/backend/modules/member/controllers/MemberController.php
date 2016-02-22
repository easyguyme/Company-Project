<?php
namespace backend\modules\member\controllers;

use Yii;
use MongoId;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberProperty;
use backend\modules\member\models\MemberShipCard;
use backend\modules\member\models\MemberLogs;
use backend\modules\member\models\ScoreHistory;
use backend\modules\member\models\ScoreRule;
use backend\models\Token;
use backend\models\User;
use backend\models\Qrcode;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\utils\MongodbUtil;
use backend\utils\StringUtil;
use backend\utils\ExcelUtil;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use yii\helpers\ArrayHelper;
use backend\components\Uploader;
use yii\helpers\FileHelper;
use backend\components\Webhook;
use backend\behaviors\MemberBehavior;
use backend\utils\UrlUtil;

/**
 * Controller class for member
 **/
class MemberController extends BaseController
{
    // const of upload file.
    const MAXSIZE = 51200000;/* limit for upload，单位B，default 50MB */
    const PATHFORMAT = '{yyyy}{mm}{dd}/{time}{rand:6}';//file path

    public static $ALLOWFILES = [".csv", ".xlsx", ".xls"];//file type

    //Can not extends backend\components\Controller because of actionIndex currentPage error
    public $modelClass = "backend\modules\member\models\Member";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['update'], $actions['create'], $actions['view'], $actions['delete']);
        return $actions;
    }

    /**
     * Query member list
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/member/members<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying members by propertities.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accounts: string, id of the channel accounts = 54aa7876e9c2fbe9038b4571,54aa7876e9c2fbe9038b4571 <br/>
     *     tags: string, tags = asd,abc<br/>
     *     cards: string, tags = 54a38828e9c2fbec038b4589,54a38828e9c2fbec038b4586<br/>
     *     createdAt: timestamp<br/>
     *     gender: male, female<br/>
     *     country: string<br/>
     *     province: string<br/>
     *     city: string<br/>
     *     searchKey: string<br/>
     *     per-page: int<br/>
     *     page: int<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried channels detail information<br/>
     *     _meta: array, the pagination information.
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * {
     *      "items": [
     *          {
     *              "id": "54a8a24de9c2fbe9038b4569",
     *              "card": {
     *                  "id": "54a38828e9c2fbec038b4589",
     *                  "name": "银卡",
     *                  "poster": "http://aaaa",
     *                  "fontColor": "ffff",
     *                  "privilege": "银卡任命",
     *                  "condition": {
     *                      "minScore": 13,
     *                      "maxScore": 15
     *                  },
     *                  "usageGuide": "aaaaaaaaaaaaaaaa",
     *                  "isEnabled": true,
     *                  "isDefault": false,
     *                  "provideCount": 2,
     *                  "$createdAt": "2014-12-31 13:22:48",
     *                  "$updatedAt": "2014-12-31 13:22:48"
     *              },
     *              "createdAt": "2015-01-04 10:15:41",
     *              "socialAccount": {
     *                  "name": "熊猫Baby",
     *                  "type": "SERVICE_AUTH_ACCOUNT"
     *              },
     *              "socialMember": "vincent",
     *              "properties": [
     *                  {
     *                      "id": "549a73c3e9c2fb8d7c8b4511",
     *                      "type": "input",
     *                      "name": "姓名",
     *                      "value": "sara"
     *                  },
     *                  {
     *                      "id": "549a73c3e9c2fb8d7c8b4511",
     *                      "type": "input",
     *                      "name": "asd",
     *                      "value": "qwe"
     *                  }
     *              ],
     *              "avatar": "http://aaa",
     *              "location": null,
     *              "tags": null,
     *              "score": 100,
     *              "remarks": null,
     *              "cardNumber": null
     *          }
     *      ],
     *      "_links": {
     *          "self": {
     *              "href": "http://wm.com/api/member/members?searchKey=a&per-page=1&page=2"
     *          },
     *          "first": {
     *              "href": "http://wm.com/api/member/members?searchKey=a&per-page=1&page=1"
     *          },
     *          "prev": {
     *              "href": "http://wm.com/api/member/members?searchKey=a&per-page=1&page=1"
     *          }
     *      },
     *      "_meta": {
     *          "totalCount": 2,
     *          "pageCount": 2,
     *          "currentPage": 2,
     *          "perPage": 1
     *      }
     *  }
     * <pre>
     * </pre>
     */
    public function actionIndex()
    {
        $params = $this->getQuery();

        $accountId = $this->getAccountId();

        if (!empty($params['cardStates'])) {
            $params['cardExpiredAt'] = Member::getCardExpiredTime($params);
        }

        return Member::search($params, $accountId);
    }

    /**
     * Query member's card number
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/member/member/card-number<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying member's card number.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     number: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * [
     *  "11000034",
     *  "11000036"
     * ]
     * <pre>
     * </pre>
     */
    public function actionCardNumber()
    {
        $number = $this->getQuery('number');
        $accountId = $this->getAccountId();

        $rows = Member::searchByCardNumber($accountId, $number);

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['cardNumber'];
        }

        return $result;
    }

    /**
     * Query member's name
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/member/member/name<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying member's name.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     name: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * [
     *  "hank",
     *  "hanken"
     * ]
     * <pre>
     * </pre>
     */
    public function actionName()
    {
        $name = $this->getQuery('name');
        $accountId = $this->getAccountId();

        $rows = Member::searchByName($accountId, $name);

        $result = [];
        foreach ($rows as $row) {
            $properties = $row['properties'];
            foreach ($properties as $property) {
                if ($property['name'] == Member::DEFAULT_PROPERTIES_NAME) {
                    $result[] = $property['value'];
                }
            }
        }

        $result = array_unique($result);
        return array_values($result);
    }

    /**
     * Mobile to perfect personal info
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/member/member/personal<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for mobile to update personal info.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     memberId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * </pre>
     */
    public function actionPersonal()
    {
        //this api is just for mobile user to perfect info, mobile user has no permission to update score and card
        $accesstoken = $this->getAccessToken();
        $token = Token::getToken($accesstoken);
        if (empty($token) || $token->role != User::ROLE_MOBILE_ENDUSER) {
            throw new \yii\web\ForbiddenHttpException(\Yii::t('common', 'no_permission'));
        }

        $params = $this->getParams();
        $memberId = new \MongoId($params['memberId']);
        $member = Member::findByPk($memberId);
        unset($params['memberId']);

        $properties = $member->properties;
        $member->load($params, '');

        Member::validateProperty($member);
        $member->properties = $this->_mergeProperties($member, $properties);

        if ($member->save()) {
            $this->attachBehavior('MemberBehavior', new MemberBehavior);
            $this->updateItemByScoreRule($member);
            $member->_id .= '';
            return $member;
        } else {
            throw new ServerErrorHttpException('Fail to update personal information');
        }
    }

    /**
     * Bulk add tags
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/member/member/bulk-add-tags<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for bulk add tags.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accounts: array, <br/>
     *     tags: array,<br/>
     *     cards: array,<br/>
     *     createdAt: timestamp<br/>
     *     gender: male, female<br/>
     *     country: string<br/>
     *     province: string<br/>
     *     city: string<br/>
     *     searchKey: string<br/>
     *     addTags: array<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * </pre>
     */
    public function actionBulkAddTags()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $addTags = $this->getParams('addTags');

        if (empty($addTags)) {
            throw new BadRequestHttpException(\Yii::t('member', 'tags_required'));
        }
        unset($params['addTags']);

        $comma = ',';
        $condition = ['accountId' => $accountId, 'isDeleted' => \backend\components\BaseModel::NOT_DELETED];
        if (!empty($params['accounts'])) {
            $condition = array_merge($condition, ['socialAccountId' => ['$in' => $accounts]]);
        }

        if (!empty($params['cards'])) {
            $cardIds = [];
            foreach ($cards as $card) {
                $cardIds[] = new \MongoId($card);
            }
            $cards = ['$in' => $cardIds];
            $condition = array_merge($condition, ['cardId' => $cards]);
        }

        if (!empty($params['tags'])) {
            $tags = $params['tags'];
            $tags = ['$all' => $tags];
            $condition = array_merge($condition, ['tags' => $tags]);
        }

        if (!empty($params['searchKey'])) {
            $key = trim($params['searchKey']);
            $key = StringUtil::regStrFormat($key);
            $keyReg = new \MongoRegex("/($key)+/");
            $search = [
                '$or' => [
                    ['cardNumber' => ['$regex' => $keyReg]],
                    [
                        'properties.name' => Member::DEFAULT_PROPERTIES_NAME,
                        'properties.value' => ['$regex' => $keyReg]
                    ]
                ]
            ];
            $condition = array_merge($condition, $search);
        }

        if (!empty($params['createdAt'])) {
            $createdAt = ['$gte' => new \MongoDate($params['createdAt']/1000)];//Millisecond to mongoDate
            $condition = array_merge($condition, ['createdAt' => $createdAt]);
        }

        if (!empty($params['gender'])) {
            $gender = [
                'properties.name' => Member::DEFAULT_PROPERTIES_GENDER,
                'properties.value' => strtolower($params['gender'])
            ];
            $condition = array_merge($condition, $gender);
        }

        foreach ($params as $key => $value) {
            if (!empty($value)) {
                if ($key == 'country' || $key == 'province' || $key == 'city') {
                    $key = 'location.' . $key;
                    $condition = array_merge($condition, [$key => $value]);
                }
            }
        }

        $result = Member::updateAll(['$addToSet' => ['tags' => ['$each' => $addTags]]], $condition);
        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Failed to set tags');
        }
    }

    public function actionUpdateStatus()
    {
        $params = $this->getParams();
        if (empty($params['id']) || !isset($params['isDisabled'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        Member::updateAll(
            ['$set' => ['isDisabled' => boolval($params['isDisabled'])]],
            ['_id' => new MongoId($params['id'])]
        );
        return ['message' => 'OK', 'data' => ''];
    }

    /**
     * Bulk add tags by Id
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/member/member/add-tags<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for add tags.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accounts: array, <br/>
     *     tags: array,<br/>
     *     memberIds: array<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * </pre>
     */
    public function actionAddTags()
    {
        $memberIds = $this->getParams('memberIds');
        $tags = $this->getParams('tags');

        if (empty($memberIds)) {
            throw new BadRequestHttpException('missing params');
        }

        foreach ($memberIds as &$memberId) {
            $memberId = new \MongoId($memberId);
        }

        $condition = ['in', '_id', $memberIds];
        if (Member::updateAll(['$addToSet' => ['tags' => ['$each' => $tags]]], $condition)) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Failed to set tags');
        }
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $member = Member::findByPk($id);
        $properties = $member->properties;

        $member->remarks = !isset($params['remarks']) ? $member->remarks : $params['remarks'];
        $member->avatar = empty($params['avatar']) ? $member->avatar : $params['avatar'];
        $member->location = empty($params['location']) ? $member->location : $params['location'];
        $member->tags = !isset($params['tags']) ? $member->tags : $params['tags'];
        //no need to validate properties when update remarks
        if (!empty($params['properties'])) {
            $member->properties = $params['properties'];
            Member::validateProperty($member);
            $member->properties = $this->_mergeProperties($member, $properties);
        }

        if ($member->save()) {
            Member::birthdayReward($member);
            return $member;
        } else {
            throw new ServerErrorHttpException('Fail to update member');
        }
    }

    private function _mergeProperties($member, $properties)
    {
        $newProperties = $member->properties;
        $propertyMap = $member->getPropertyMap();
        foreach ($properties as $property) {
            if (!isset($propertyMap[(string) $property['id']])) {
                $newProperties[] = $property;
            }
        }
        return $newProperties;
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        $member = new Member();
        $member->avatar = empty($params['avatar']) ? '' : $params['avatar'];
        $member->location = empty($params['location']) ? null : $params['location'];
        $member->tags = empty($params['tags']) ? [] : $params['tags'];
        $member->properties = empty($params['properties']) ? [] : $params['properties'];
        $member->accountId = $accountId;
        Member::validateProperty($member);

        $defaultCard = MemberShipCard::getDefault($accountId);
        $member->cardId = $defaultCard->_id;
        $member->cardNumber = Member::generateCardNumber();
        $member->origin = Member::PORTAL;

        if ($member->save()) {
            Member::webhookEvent($member);
            MemberLogs::record($member->_id, $accountId, MemberLogs::OPERATION_VIEWED);
            if (!defined('KLP') || !KLP) {
                Yii::$app->qrcode->create(UrlUtil::getDomain(), Qrcode::TYPE_MEMBER, $member->_id, $accountId);
            }
            Member::birthdayReward($member);
            return $member;
        } else {
            throw new ServerErrorHttpException('Fail to create member');
        }
    }

    public function actionTags()
    {
        $tags = $this->getParams('tags');
        $memberId = $this->getParams('memberId');

        if (empty($memberId)) {
            throw new BadRequestHttpException('missing param');
        }

        $member = Member::findByPk(new \MongoId($memberId));
        $member->tags = $tags;

        if ($member->save(true, ['tags'])) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Save member error');
        }
    }

    public function actionView($id)
    {
        $member = Member::findByPk(new \MongoId($id));
        $accountId = $this->getAccountId();
        if (empty($member)) {
            throw new BadRequestHttpException(Yii::t('member', 'invalid_member_id'));
        }
        $scoreHistory = ScoreHistory::getLastByMemberId($member->_id);
        $qrcode = Qrcode::getByTypeAndAssociated(Qrcode::TYPE_MEMBER, $member->_id, $accountId);
        $member = $member->toArray();
        $properties = ArrayHelper::toArray(MemberProperty::getAllByAccount($accountId));
        $mapPropertyDefault = ArrayHelper::map($properties, 'id', 'isDefault');
        foreach ($member['properties'] as &$property) {
            $property['isDefault'] = empty($mapPropertyDefault[$property['id']]) ? false : $mapPropertyDefault[$property['id']];
        }
        $member['qrcodeUrl'] = empty($qrcode->qiniuKey) ? '' : \Yii::$app->qrcode->getUrl($qrcode->qiniuKey);
        $member['scoreProvideTime'] = empty($scoreHistory->createdAt) ? '' : MongodbUtil::MongoDate2String($scoreHistory->createdAt);
        return $member;
    }

    /**
     * check the member
     */
    public function actionCheckMember()
    {
        $params = $this->getQuery();
        if (empty($params['searchKey'])) {
            throw new BadRequestHttpException('missing params');
        }

        $accountId = $this->getAccountId();
        $result = Member::getByMobile($params['searchKey'], $accountId);
        return $result;
    }

    public function actionExport()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        if (!empty($params['cardStates'])) {
            $params['cardExpiredAt'] = Member::getCardExpiredTime($params);
        }

        $where = Member::getCondition($params, $accountId);
        $condition = $where['condition'];
        //only export enable member
        $condition['isDisabled'] = ['$ne' => true];

        if (!empty($where['cardStatusCondition'])) {
            $condition = ['and', $condition, $where['cardStatusCondition']];
        }
        if (!empty($where['searchCondition'])) {
            $condition = ['and', $condition, $where['searchCondition']];
        }

        $result = Member::find()->where($condition)->one();
        if (!empty($result)) {
            $key = Yii::t('product', 'export_member_file_name') . '_' . date('YmdHis');
            $headerTitle = Yii::t('member', 'member_export_title');
            $headerValue = explode(',', $headerTitle);
            $header = [
                'cardNumber',
                'cardName',
                'score',
                'totalScore',
                'totalScoreAfterZeroed',
                'costScoreAfterZeroed',
                'channel',
                'createdAt',
                'tag'
            ];
            $showHeader = array_combine($header, $headerValue);

            $exportArgs = [
                'key' => $key,
                'header' => $showHeader,
                'accountId' => (string)$accountId,
                'condition' => serialize($condition),
                'description' => 'Direct: export member',
                'params' => $params
            ];
            $jobId = Yii::$app->job->create('backend\modules\member\job\ExportMember', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            throw new BadRequestHttpException(Yii::t('member', 'export_member_failed'));
            //return ['result' => 'member_error', 'message' => 'no datas', 'data' => []];
        }
    }

    /**
     * @param begin=2015-10-01
     * @param end=2015-10-02
     */
    public function actionExportKlpMember()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        unset($params['accesstoken']);

        $condition = ['accountId' => $accountId];
        $key = '';
        if (!empty($params['begin'])) {
            $begin = TimeUtil::ms2sTime($params['begin']);
            $key = date('Y-m-d', $begin);
            $condition['updatedAt']['$gte'] = new \MongoDate($begin);
        }
        if (!empty($params['end'])) {
            $end = TimeUtil::ms2sTime($params['end']) + 24 * 3600;
            $key = !empty($key) ? $key . '_' . date('Y-m-d', $end) : date('Y-m-d', $end);
            $condition['updatedAt']['$lt'] = new \MongoDate($end);
        }

        if (empty($condition['updatedAt'])) {
            $key = date('Y-m-d');
            $condition['updatedAt']['$lt'] = new \MongoDate();
        }
        //only export enable member
        $condition['isDisabled'] = ['$ne' => true];
        $result = Member::findOne($condition);
        if (!empty($result)) {
            $condition = serialize($condition);
            $header = [
                'gender' => 'Title/ Salutation',
                'firstName' => 'First name',
                'lastName' => 'Surname',
                '工作職稱' => 'Job title',
                'email' => 'Email address',
                'tel' => 'Username (prefilled)',
                '密碼' => 'Password',
                'tel_1' => 'Mobile phone number',//is equal with tel
                '餐廳電話' => 'phone number',
                '經營型態' => 'Type of business',
                '每日供餐量' => 'Number of covers per day',
                '訂閱' => 'Opt-in / emailable (y/n)',
                '餐廳名稱' => 'Business name',
                '餐廳地址' => 'Business address',
                'letter_unknow' => 'Street number / Letter',//this field is empty
                '地址' => 'Street name',
                '縣市' => 'City',
                'county' => 'County',
                '郵遞區號' => 'Postcode',
                'country' => 'Country',//this field is empty
                'locations_unkonw' => 'Number of locations',//this field is empty
                '首選經銷商' => 'Wholesaler/ Supplier',
                '廚房廚師人數' => 'Number of kitchen staff',
                'cuisine_unknow' => 'Type of cuisine',//this field is empty
                '感興趣產品' => 'UFS brands',
            ];

            $fields = 'cardId,location,tags,properties,cardNumber,score,remarks,birth,socials,phone';
            $exportArgs = [
                'collection' => 'member',
                'fields' => $fields,
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'description' => 'Direct: export KLP member'
            ];
            $jobId = Yii::$app->job->create('backend\modules\member\job\ExportKlpMember', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    public function actionMerge()
    {
        $params = $this->getParams();
        if (empty($params['main']) || empty($params['others'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if (!is_array($params['others']) || in_array($params['main'], $params['others'])) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        $accountId = $this->getAccountId();

        $mainMember = Member::findByPk(new \MongoId($params['main']), ['accountId' => $accountId]);
        if (empty($mainMember)) {
            throw new InvalidParameterException(Yii::t('member', 'no_member_find'));
        }

        $phone = $mainMember->phone;
        //get mongoIds
        $otherMemberIds = [];
        foreach ($params['others'] as $otherId) {
            $otherMemberIds[] = new \MongoId($otherId);
        }

        $otherMembers = Member::findAll(['_id' => ['$in' => $otherMemberIds] , 'accountId' => $accountId]);
        if (empty($otherMembers) || count($otherMembers) != count($params['others'])) {
            throw new InvalidParameterException(Yii::t('member', 'no_member_find'));
        }

        $mainProperties = [];
        $mainPropertyIds = [];
        foreach ($mainMember->properties as $mainProperty) {
            if (!empty($mainProperty['value'])) {
                $mainPropertyIds[] = $mainProperty['id'];
                $mainProperties[] = $mainProperty;
            }
        }

        $mainMemberLocation = $mainMember->location;
        $otherMemberLocation = [];
        $newMemberTag = empty($mainMember->tags) ? [] : $mainMember->tags;
        foreach ($otherMembers as $otherMember) {
            if (empty($phone)) {
                $phone = $otherMember->phone;
            }
            $mainMember->score += $otherMember->score;
            $mainMember->totalScore += $otherMember->totalScore;
            $mainMember->totalScoreAfterZeroed += $otherMember->totalScoreAfterZeroed;
            $newMemberTag = array_merge($newMemberTag, empty($otherMember->tags) ? [] : $otherMember->tags);
            $location = $otherMember->location;
            if (empty($otherMemberLocation['country']) && !empty($location['country'])) {
                $otherMemberLocation = $location;
            }
            foreach ($otherMember->properties as $otherProperty) {
                if (!in_array($otherProperty['id'], $mainPropertyIds) && !empty($otherProperty['value'])) {
                    $mainPropertyIds[] = $otherProperty['id'];
                    $mainProperties[] = $otherProperty;
                }
            }
        }
        $mainMember->properties = $mainProperties;
        $mainMember->tags = array_values(array_unique($newMemberTag));
        if (empty($mainMemberLocation['country']) && !empty($otherMemberLocation)) {
            $mainMember->location = $otherMemberLocation;
        }
        $mainMember->phone = $phone;

        $updateMember = $mainMember->save(false);
        $deleteMember = Member::deleteAll(['_id' => ['$in' => $otherMemberIds]]);

        if ($updateMember && $deleteMember) {
            $mainMember->upgradeCard();
            $jobArgs = ['mainMember' => serialize($mainMember), 'otherMemberIds' => serialize($otherMemberIds)];
            Yii::$app->job->create('backend\modules\member\job\MergeMember', $jobArgs);
            return ['message' => 'OK', 'data' => ''];
        } else {
            throw new ServerErrorHttpException('Merge member fail');
        }
    }

    public function actionCheckQrcodeHelp()
    {
        $memberId = $this->getParams('memberId');
        if (empty($memberId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        Member::updateAll(['$set' => ['qrcodeViewed' => true]], ['_id' => new \MongoId($memberId)]);
        return ['message' => 'OK', 'data' => ''];
    }

    /*
    * Import member to batch operate member`s message,
    * if the member exists, will update it, add it.
    */
    public function actionImportMembers()
    {
        $params = $this->getParams();
        if (empty($params['filename'])) {
            throw new BadRequestHttpException('missing param filename');
        }

        $accountId = $this->getAccountId();
        $hostInfo = UrlUtil::getDomain();

        $jobArgs = [
            'hostInfo' => $hostInfo,
            'accountId' => $accountId . '',
            'type' => 'insert',
            'filename' => $params['filename'],
            'description' => 'Direct: Import member, if the member is exists, will add is to mongo, update it.'
        ];
        $jobId = Yii::$app->job->create('backend\modules\member\job\ImportMember', $jobArgs);
        return ['message' => 'OK', 'data' => $jobId];
    }

    /*
    * Check member in the excel.
    */
    public function actionCheckMembers()
    {
        $fileKey = 'file';

        if (empty($_FILES[$fileKey])) {
            throw new BadRequestHttpException('missing param '.$fileKey);
        }

        $accountId = $this->getAccountId();

        //upload config
        $config = [
            'maxSize'=> self::MAXSIZE,
            'allowFiles'=>self::$ALLOWFILES,
            'pathFormat'=> self::PATHFORMAT,
            'privateBucket' => true,
        ];
        //upload to qiniu
        $upload = new Uploader($fileKey, $config, 'upload', 1);
        $fileInfo = $upload->getFileInfo();
        $rootPath = Yii::$app->getRuntimePath() . '/code/';
        if (!is_dir($rootPath)) {
            FileHelper::createDirectory($rootPath, 0777, true);
        }
        $fileName = $fileInfo['title'];
        $locationPath = $rootPath . $fileName . $fileInfo['type'];

        if (empty($fileName)) {
            throw new InvalidParameterException($fileInfo['state']);
        }

        $checkArgs = [
            'qiniuBucket' => QINIU_DOMAIN_PRIVATE,
            'accountId' => (string)$accountId,
            'filePath' => Yii::$app->qiniu->getPrivateUrl($fileName),
            'locationPath' => $locationPath,
            'fileName' => $fileName,
            'description' => 'Direct: Check if the file of member has already exists.'
        ];
        $jobId = Yii::$app->job->create('backend\modules\member\job\MemberImportCheck', $checkArgs);
        return ['message' => 'OK', 'data' => ['token'=> $jobId, 'filename'=> $fileName]];
    }

    public function actionImportStatue()
    {
        $params = $this->getQuery();
        if (empty($params['token'])) {
            throw new BadRequestHttpException('param missing');
        }

        $accountId = (string)$this->getAccountId();
        $result = Yii::$app->job->status($params['token']);

        if (!empty($params['filename'])) {
            $filename = $params['filename'];

            $redis = Yii::$app->cache->redis;

            $hashName = md5($accountId . '_' . $filename);
            $cacheSetLackPropertiesKey = 'lack:' . $hashName;
            $cacheInsertTotalCount = 'insertTotalCount' . $hashName;

            $cacheSetLackProperties = $redis->Hget($cacheSetLackPropertiesKey, 'wrong');
            $insertTotalCount = $redis->Hget($cacheInsertTotalCount, 'totalCount');

            $resultStatus =  [
                'message' => 'OK',
                'status' => $result,
                'lack' => $cacheSetLackProperties,
                'totalCount' => $insertTotalCount
            ];

            if ($result == 3 || $result == 4) {
                $redis->del($cacheSetLackPropertiesKey);
                $redis->del($cacheInsertTotalCount);
            }
            return $resultStatus;
        } else {
            return ['message' => 'OK', 'status' => $result, 'wrong' => 0];
        }
    }

    public function actionGetStatus()
    {
        $params = $this->getQuery();
        if (empty($params['token'])) {
            throw new BadRequestHttpException('param missing');
        }
        $accountId = (string)$this->getAccountId();
        $result = Yii::$app->job->status($params['token']);

        if (!empty($params['filename'])) {
            $filename = $params['filename'];

            $redis = Yii::$app->cache->redis;

            $hashName = md5($accountId . '_' . $filename);
            $rowsIndexKey = 'rows' . $hashName;
            $colsIndexKey = 'clos' . $hashName;
            $titleIndexKey = 'title' . $hashName;
            $ignorePropertyKey = 'ignore' . $hashName;
            $missPropertyKey = 'miss' . $hashName;
            $repeatTitleKey = 'repeat' . $hashName;


            $rowsIndex = $redis->Hget($rowsIndexKey, 'wrong');
            $colsIndex = $redis->Hget($colsIndexKey, 'wrong');
            $titleIndex = $redis->Hget($titleIndexKey, 'wrong');
            $ignoreProperty = $redis->smembers($ignorePropertyKey);
            $missProperty = $redis->smembers($missPropertyKey);
            $repeatTitle = $redis->smembers($repeatTitleKey);
            // unserialize
            if (count($ignoreProperty)) {
                $ignoreProperty = unserialize($ignoreProperty[0]);
            }

            if (count($missProperty)) {
                $missProperty = unserialize($missProperty[0]);
            }

            if (count($repeatTitle)) {
                $repeatTitle = unserialize($repeatTitle[0]);
            }
            $wrongValue = $redis->Hget($hashName, 'wrong');
            $rightValue = $redis->Hget($hashName, 'right');

            $resultStatus =  [
                    'message' => 'OK',
                    'status' => $result,
                    'rows' => $rowsIndex,
                    'cols' => $colsIndex,
                    'property' => $titleIndex,
                    'wrong' => $wrongValue,
                    'right' => $rightValue,
                    'ignore' => empty($ignoreProperty) ? null :  $ignoreProperty,
                    'miss' => $missProperty,
                    'repeat' => $repeatTitle,
                ];
            if ($result == 4 || $result == 3) {
                $redis->del($rowsIndexKey);
                $redis->del($colsIndexKey);
                $redis->del($titleIndexKey);
                $redis->del($ignorePropertyKey);
                $redis->del($hashName);
                $redis->del($missPropertyKey);
                $redis->del($repeatTitleKey);
            }
            return $resultStatus;
        } else {
            return [
                    'message' => 'OK',
                    'status' => $result,
                    'wrong' => 0,
                    'right' => 0
                ];
        }
    }

    public function actionClearCache()
    {
        $params = $this->getQuery();
        if (!isset($params['filename'])) {
            throw new BadRequestHttpException('params missing');
        }
        $accountId = $this->getAccountId();

        $deleteArgs = [
            'filename' => $params['filename'],
            'accountId' => $accountId . '',
            'type' => 'deleteRedisCode',
            'description' => 'Direct: Delete member cached in redis'
        ];
        $jobId = Yii::$app->job->create('backend\modules\member\job\ImportMember', $deleteArgs);
        return ['message' => 'OK', 'data' => $jobId];
    }
}
