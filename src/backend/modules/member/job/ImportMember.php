<?php
namespace backend\modules\member\job;

use Yii;
use MongoId;
use backend\modules\member\models\Member;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\member\models\MemberShipCard;
use backend\modules\member\models\MemberLogs;
use backend\modules\member\models\MemberProperty;
use backend\utils\MongodbUtil;
use backend\models\Qrcode;
use backend\utils\TimeUtil;
use backend\behaviors\MemberBehavior;
use yii\helpers\ArrayHelper;

class ImportMember
{
    // the const of operate`s type.
    const JOB_DELETE_REDIS_CODE = 'deleteRedisCode';
    const JOB_INSERT = 'insert';
    const SET_HEAD_INSERT = "insertMember:";
    const SET_HEAD_UPDATE = "updateMember:";
    const EXPIRE = 3600;
    const MEMBER_WRONG = 'wrong';
    const MEMBER_IMPORT_NO_PROPERTY = 'lack:';
    const INSERT_COUNT = 'insertCount';
    const UPDATE_COUNT = 'updateCount';
    const INSERT_TOTAL_COUNT = 'insertTotalCount';
    const TOTAL_COUNT = 'totalCount';

    const MEMBER_NO_DEFAULT_PROPERTIES = -1;
    const MEMBER_INSERT_SUCCESS = 1;

    public function perform()
    {
        # Run task
        $args = $this->args;
        # The key for wrong number for store in redis
        $wrongKey = self::MEMBER_WRONG;

        switch ($args['type']) {
            case self::JOB_DELETE_REDIS_CODE:
                if (!isset($args['filename']) || !isset($args['accountId'])) {
                    ResqueUtil::log(['error' => 'missing param', 'param' => $args]);
                    return false;
                }
                $redis = \Yii::$app->cache->redis;

                $cacheSetKey = md5($args['accountId'] . "_" . $args['filename']);

                $cacheSetInsert = self::SET_HEAD_INSERT . $cacheSetKey;
                $cacheSetUpdate = self::SET_HEAD_UPDATE . $cacheSetKey;

                $insertCount = self::INSERT_COUNT . $cacheSetKey;
                $updateCount = self::UPDATE_COUNT . $cacheSetKey;

                $redis->del($cacheSetInsert);
                $redis->del($cacheSetUpdate);
                $redis->del($insertCount);
                $redis->del($updateCount);

                break;
            case self::JOB_INSERT:
                if (!isset($args['accountId']) || empty($args['filename']) || empty($args['hostInfo'])) {
                    ResqueUtil::log(['error' => 'missing params', 'param' => $args]);
                }

                $redis = Yii::$app->cache->redis;
                $cacheKey = $args['accountId'] . "_" . $args['filename'];
                $createdAt =  new \MongoDate(time());
                $redis->expire($cacheKey, self::EXPIRE);

                $cacheSetInsert = self::SET_HEAD_INSERT . md5($cacheKey);
                $cacheSetUpdate = self::SET_HEAD_UPDATE . md5($cacheKey);

                $insertCount = self::INSERT_COUNT . md5($cacheKey);
                $updateCount = self::UPDATE_COUNT . md5($cacheKey);

                $cacheSetLackProperties = self::MEMBER_IMPORT_NO_PROPERTY . md5($cacheKey);
                $insertTotalCount = self::INSERT_TOTAL_COUNT . md5($cacheKey);

                # Get total of excel.
                $totalInsert = $redis->Hget($insertCount, self::INSERT_COUNT);
                $totalUpdate = $redis->Hget($updateCount, self::UPDATE_COUNT);

                ResqueUtil::log(['insert data' => $totalInsert, 'update data' => $totalUpdate]);

                # Gatch insert member.
                $memberInsert = [];
                $keyInsert = $redis->smembers($cacheSetInsert);
                # Update member.
                $keyUpdate = $redis->smembers($cacheSetUpdate);

                # insert member
                if ($totalInsert > 0) {
                    $defaultCard = MemberShipCard::getDefault(new MongoId($args['accountId']));

                    if (empty($defaultCard)) {
                        ResqueUtil::log('Lack of default MemberShip Card');  // 0
                        $redis->Hset($cacheSetLackProperties, $wrongKey, self::MEMBER_NO_DEFAULT_PROPERTIES);
                        return false;
                    }
                    $cardId = $defaultCard->_id;
                    $origin = Member::PORTAL;
                    $memberIds = [];

                    # Unserialize.
                    $memberKey = unserialize($keyInsert[0]);

                    # Get all memberProperty which the type is radio.
                    $dbProperties = MemberProperty::getRadioProperty(new MongoId($args['accountId']));

                    foreach ($memberKey as $key => $value) {
                        $cardNumber = Member::generateCardNumber();
                        $properties = [];
                        $memberTag = [];
                        $birthday = '';
                        $birth = '';
                        $phone = '';

                        foreach ($value as $property) {
                            if (!empty($property['name'])) {
                                if ($property['name'] !== Member::DEFAULT_PROPERTIES_MOBILE) {
                                    $properties[] = $property;
                                }
                                if ($property['name'] == Member::DEFAULT_PROPERTIES_BIRTHDAY) {
                                    $birthday = $property['value'];
                                }
                                if ($property['name'] == Member::DEFAULT_PROPERTIES_MOBILE) {
                                    $phone = $property['value'];
                                }
                            } else {
                                $memberTag[] = $property['tags'];
                            }
                        }

                        if (!empty($birthday)) {
                            $birth = Member::setMemberBirth($birthday);
                        }

                        $properties = self::_mergeRadioProperties($dbProperties, $properties);

                        $memberMessage = [
                            "_id" => new MongoId(),
                            "phone" => $phone,
                            "properties" => $properties,
                            "tags" => $memberTag,
                            "cardId" => $cardId,
                            "origin" => $origin,
                            "cardNumber" => empty($cardNumber) ? 0 : $cardNumber,
                            "location" => ["country" => "", "province" => "", "city" => "", "detail" => ""],
                            "avatar" => Yii::$app->params['defaultAvatar'],
                            "score" => 0,
                            "totalScore" => 0,
                            "socials" => [],
                            "birth" => $birth,
                            "qrcodeViewed" => false,
                            "isDisabled" => false,
                            "totalScoreAfterZeroed" => 0,
                            "accountId" => new MongoId($args['accountId']),
                            "cardProvideTime" => new \MongoDate(),
                        ];

                        $memberInsert[] = $memberMessage;
                        $memberIds[] = $memberMessage['_id'];
                    }

                    # Batch insert member.
                    $insertMemberResult = Member::batchInsert($memberInsert, ['continueOnError' => true]);
                    $memberAllInsert = Member::findAll(['_id' => ['$in' => $memberIds], 'accountId' => new MongoId($args['accountId'])]);

                    if ($insertMemberResult && $totalInsert == count($memberAllInsert)) {
                        ResqueUtil::log(['ok' => 'Batch insert member is success', 'data' => $memberInsert]);
                        if ($totalUpdate == 0) {
                            $redis->Hset($cacheSetLackProperties, $wrongKey, self::MEMBER_INSERT_SUCCESS);
                            $redis->Hset($insertTotalCount, self::TOTAL_COUNT, $totalInsert);
                        }
                    } else {
                        ResqueUtil::log(['error' => 'Batch insert member is fail', 'data' => $memberInsert]);
                        $redis->Hset($cacheSetLackProperties, $wrongKey, self::MEMBER_NO_DEFAULT_PROPERTIES);
                    }

                    # Create qrcode.

                    if (!empty($memberAllInsert)) {
                        foreach ($memberAllInsert as $member) {
                            Member::webhookEvent($member);
                            MemberLogs::record($member->_id, new MongoId($args['accountId']), MemberLogs::OPERATION_VIEWED);
                            if (!defined('KLP') || !KLP) {
                                $a = Yii::$app->qrcode->create($args['hostInfo'], Qrcode::TYPE_MEMBER, $member->_id, new MongoId($args['accountId']));
                            }
                            # Create score rule.
                            Member::birthdayReward($member);
                        }
                    }
                    return true;
                }

                # Update member
                if ($totalUpdate > 0) {
                    $memberKey = unserialize($keyUpdate[0]);
                    $UpdateMemberScoreRule = [];

                    foreach ($memberKey as $key => $value) {
                        $updateData = [];
                        $memberUpdate = [];
                        $memberTag = '';
                        $mobile = '';
                        $id = '';
                        $updateBirthday = '';

                        foreach ($value as $property) {
                            if (!empty($property['name'])) {
                                if ($property['name'] == Member::DEFAULT_PROPERTIES_MOBILE) {
                                    $mobile = $property['value'];
                                    $id = (string)$property['id'];
                                }
                                $memberUpdate[] = $property;

                                if ($property['name'] == Member::DEFAULT_PROPERTIES_BIRTHDAY) {
                                    $updateBirthday = $property['value'];
                                }
                            } else {
                                $memberTag = $property['tags'];
                            }
                        }
                        $param = [
                            'phone' => $mobile,
                            'accountId' => new MongoId($args['accountId'])
                        ];

                        $member = Member::findOne($param);
                        $memberProperties = $this->_mergeProperties($member, $memberUpdate);
                        //remove tel property
                        $memberProperties = ArrayHelper::index($memberProperties, 'name');
                        unset($memberProperties[Member::DEFAULT_PROPERTIES_MOBILE]);
                        $memberProperties = array_values($memberProperties);
                        $updateData['$set'] = [
                            'properties' => $memberProperties
                        ];
                        if (!empty($memberTag)) {
                            $updateData['$addToSet'] = ['tags' => $memberTag];
                        }

                        if (!empty($updateBirthday)) {
                            $birth = Member::setMemberBirth($updateBirthday);
                            $updateData['$set']['birth'] = $birth;
                        }

                        $updateMemberResult = Member::updateAll($updateData, $param);
                        if ($updateMemberResult) {
                            $UpdateMemberScoreRule[] = $member;
                            ResqueUtil::log(['ok' => 'Update member is success', 'data' => $updateData]);
                        } else {
                            ResqueUtil::log(['error' => 'Update member is fail', 'data' => $updateData]);
                            $redis->Hset($cacheSetLackProperties, $wrongKey, self::MEMBER_NO_DEFAULT_PROPERTIES);
                        }
                    }
                    $redis->Hset($cacheSetLackProperties, $wrongKey, self::MEMBER_INSERT_SUCCESS);
                    $redis->Hset($insertTotalCount, self::TOTAL_COUNT, ($totalInsert + $totalUpdate));
                    # Create score rule.
                    foreach ($UpdateMemberScoreRule as $member) {
                        Member::birthdayReward($member);
                    }
                    unset($UpdateMemberScoreRule);
                }
                break;
            default:
                break;
        }
    }

    private function _mergeProperties($member, $propertiesUpdate)
    {
        $propertyMap = $this->getPropertyMap($member->properties);
        foreach ($propertiesUpdate as $propertyUpdate) {
            $propertyMap[(string)$propertyUpdate['id']] = $propertyUpdate;
        }
        return array_values($propertyMap);
    }

    private function _mergeRadioProperties($dbProperties, $fileProperties)
    {
        $fileProperties = $this->getPropertyMap($fileProperties);
        foreach ($dbProperties as $dbProperty) {
            $flag = 0;
            foreach ($fileProperties as $key => $value) {
                if ($key == (string)$dbProperty['id'] && $value['name'] == $dbProperty['name']) {
                    $flag = 1;
                    $fileProperties[$key] = $value;
                }
                continue;
            }

            if ($flag == 0) {
                $fileProperties[(string)$dbProperty['id']] = $dbProperty;
            }
        }
        return array_values($fileProperties);
    }

    private function getPropertyMap($properties)
    {
        $memberPropertiesMap = [];
        foreach ($properties as $property) {
            $memberPropertiesMap[(string)$property['id']] = $property;
        }
        return $memberPropertiesMap;
    }
}
