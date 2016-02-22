<?php
namespace backend\modules\member\job;

use Yii;
use MongoId;
use MongoDate;
use backend\models\Account;
use backend\utils\StringUtil;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberProperty;
use backend\modules\resque\components\ResqueUtil;

class MemberImportCheck
{
    const EXPIRE = 3600;
    const BATCH_COUNT = 1000;
    const MEMBER_FILE_ROWS = 'rows';
    const MEMBER_FILE_COLS = 'clos';
    const MEMBER_FILE_TITLE = 'title';
    const MEMBER_REPEAT_TITLE = 'repeat';
    const MEMBER_FILE_MISS = 'miss';
    const MEMBER_ENGLISH_COLON = ':';
    const MEMBER_CHINESE_COLON = '：';
    const MEMBER_TAGS = 'tag';
    const MEMBER_LINE = '/';
    const MEMBER_ENGLISH_COMMA = ',';
    const MEMBER_CHINESE_COMMA = '，';
    const MEMBER_SC_GENDER = '男';
    const MEMBER_TC_GENDER = '莮';
    const MEMBER_MALE = 'male';
    const MEMBER_C_GENDER = '女';
    const MEMBER_FEMALE = 'female';
    const MEMBER_WEIZHI = '未知';
    const MEMBER_UNKNOW = '不知道';
    const MEMBER_UNKNOWN = 'unknown';
    const MEMBER_WRONG = 'wrong';
    const MEMBER_RIGHT = 'right';
    const MEMBER_FILE_IGNORE = 'ignore';
    const SET_HEAD_INSERT = "insertMember:";
    const SET_HEAD_UPDATE = "updateMember:";
    const INSERT_COUNT = 'insertCount';
    const UPDATE_COUNT = 'updateCount';

    // The state of wrong.
    const MEMBER_NO_DATA = -4;             // no data
    const MEMBER_PROPERTY_REQUIRED = -1;   // required
    const MEMBER_PROPERTY_UNIQUE = -2;     // unique
    const MEMBER_PROPERTY_ERROR = -3;      // format error
    const MEMBER_NO_EXIST = -6;            // Required property is miss
    const MEMBER_PROPERTY_ILLEGAL = -7;    // Illegal
    const MEMBER_TITLE_REPEATED = -8;      // the title is repeated

    public function perform()
    {
        # Run task.
        $args = $this->args;
        if (empty($args['locationPath']) || empty($args['accountId']) || empty($args['filePath'])
            || empty($args['qiniuBucket']) || empty($args['fileName'])) {
            ResqueUtil::log(['error' => 'missing params in MemberCheckCode', 'args' => $args]);
        }

        # Get qiniu file.
        $qiniuFile = Yii::$app->curl->get($args['filePath'], [], false);
        if (file_put_contents($args['locationPath'], $qiniuFile) === false) {
            ResqueUtil::log(['message' => 'Fail to get file from qiniu in MemberCheckCode class', 'args' => $args]);
            return false;
        }

        $phpreader = new \PHPExcel_Reader_Excel2007();
        $filePath = $args['locationPath'];

        if (!$phpreader->canRead($filePath)) {
            $phpreader = new \PHPExcel_Reader_Excel5();

            if (!$phpreader->canRead($filePath)) {
                $phpreader= \PHPExcel_IOFactory::createReader('CSV')
                ->setDelimiter(',')
                ->setInputEncoding('UTF-8')
                ->setEnclosure('"')
                ->setLineEnding("\r\n")
                ->setSheetIndex(0);

                if (!$phpreader->canRead($filePath)) {
                    ResqueUtil::log(['error' => 'file can not read  in MemberCheckCode', 'args' => $args]);
                    return false;
                }
            }
        }

        $phpexcel = $phpreader->load($filePath);
        # Excel to array.
        $fileArray = Yii::$app->file->excelToArray($phpreader, $phpexcel, $filePath);

        # Make a key to be called a name for redis hash
        $cacheHash = md5($args['accountId'] . "_" . $args['fileName']);

        # Make a key to be called a name for redis set
        $cacheSetInsert = self::SET_HEAD_INSERT . $cacheHash;
        $cacheSetUpdate = self::SET_HEAD_UPDATE . $cacheHash;

        $insertCount = self::INSERT_COUNT . $cacheHash;
        $updateCount = self::UPDATE_COUNT . $cacheHash;

        # The key for wrong number for store in redis
        $wrongKey = self::MEMBER_WRONG;
        # The key for right number for  store in redis
        $rightKey = self::MEMBER_RIGHT;

        $ignorePropertyKey = self::MEMBER_FILE_IGNORE . $cacheHash;

        $redis = Yii::$app->cache->redis;
        $redis->expire($cacheHash, self::EXPIRE);

        # Check if the data exist.
        if (count($fileArray) < 2) {
            ResqueUtil::log(['error' => 'the file of excel hasn`t data']);
            $array = ['value' => null, 'rows' => null, 'cols' => null, 'property' => null, 'wrongNum' => self::MEMBER_NO_DATA];
            $this->_storeError($redis, $array, $args);
            return false;
        }

        # Validate title.
        $accountId = new MongoId($args['accountId']);
        $condition = [
            'accountId' => $accountId,
            'isDeleted' => false
        ];
        $memberProperty = MemberProperty::findAll($condition);
        # $result = ['titles' => $titles, 'allMemberPropertiesDB' => $allMemberPropertiesDB, 'telId' => $telId, 'memberPropertyArray' => $memberPropertyArray]
        $result = $this->_validateTitle($memberProperty, $fileArray[0], $redis, $args);
        if ($result === false) {
            ResqueUtil::log(['message' => 'Fail to check title', 'data' => $result]);
            return false;
        }

        $titles = $result['titles'];
        $allMemberPropertiesDB = $result['allMemberPropertiesDB'];

        $telId = $result['telId'];
        $memberPropertyArray = $result['memberPropertyArray'];

        # Mapping title to datebase.
        $titleToDatebase = ['name' => 'name', 'mobile' => 'tel', 'gender' => 'gender', 'birthday' => 'birthday', 'email' => 'email'];

        # Traversed to two-dimensional array.
        $mobileCloIndex = array_search('mobile', $titles);
        $ignoreProperties = [];
        $first = 0;
        $mobileResult = [];
        $insertData = [];
        $updateData = [];
        $insertArray = [];
        $updateArray = [];
        $mobilesAll = [];

        for ($col = 0; $col < count($fileArray[0]); $col++) {
            $first++;
            $title = strtolower(trim((string)$fileArray[0][$col]));
            $originalTitle = trim((string)$fileArray[0][$col]);

            # According to mobile, the division of the data into update data and insert data.
            if ($first == 1 && $col == 0) {
                $params = ['fileArray' => $fileArray, 'mobileIndex' => $mobileCloIndex, 'titles' => $titles, 'mobileId' => $telId];
                # ['insertData' => $insertData, 'updateData' => $updateData, 'insertArray' => $insertArray, 'updateArray' => $updateArray, 'mobilesAll' => $mobilesAll]
                $mobileResult =  $this->_divisionDataByMobile($params, $redis, $args);
                if ($mobileResult === false || empty($mobileResult)) {
                    ResqueUtil::log(['message' => 'Fail to check mobile', 'data' => $mobileResult]);
                    return false;
                }
                $insertData = $mobileResult['insertData'];
                $updateData = $mobileResult['updateData'];
                $insertArray = $mobileResult['insertArray'];
                $updateArray = $mobileResult['updateArray'];
                $mobilesAll = $mobileResult['mobilesAll'];
            }

            # Store the properties of member which is ignore.
            if ($title != self::MEMBER_TAGS) {
                if (array_key_exists($title, $titleToDatebase)) {
                    if (!in_array($titleToDatebase[$title], $allMemberPropertiesDB) && !in_array($originalTitle, $ignoreProperties)) {
                        $ignoreProperties[] = $originalTitle;
                        continue;
                    }
                } else {
                    if (!in_array($title, $allMemberPropertiesDB) && !in_array($originalTitle, $ignoreProperties)) {
                        $ignoreProperties[] = $originalTitle;
                        continue;
                    }
                }
            }

            # If the colnum title is mobile, will skip it.
            # If the relate title of value is empty, will skip it.
            if ($col == $mobileCloIndex || empty($originalTitle)) {
                continue;
            }

            # Get value of properties for storing in db.
            if ($title != self::MEMBER_TAGS) {
                $memberPropertyOne = $memberPropertyArray[$title];
                if ($memberPropertyOne['isVisible'] == false && !in_array($title, $ignoreProperties)) {
                    $ignoreProperties[] = $title;
                    continue;
                }
            }

            $property = '';
            $propertyId = '';
            # Verify whether the title is tag.
            if ($title == self::MEMBER_TAGS) {
                # Verify tag and sorting data.
                $paramsTags = ['col' => $col, 'fileArray' => $fileArray, 'mobileCloIndex' => $mobileCloIndex];
                # ['insertData' => $insertData, 'updateData' => $updateData] | false
                $columnResult = ['insertData' => $insertData, 'updateData' => $updateData, 'insertArray' => $insertArray, 'updateArray' => $updateArray, 'mobilesAll' => $mobilesAll];
                $tagResult = $this->_validateTags($columnResult, $paramsTags, $redis, $args);
                if ($tagResult === false) {
                    ResqueUtil::log(['message' => 'The tags is incorrect.', 'data' => $tagResult]);
                    return false;
                }
                $insertData = $tagResult['insertData'];
                $updateData = $tagResult['updateData'];
            } else {
                if ($memberPropertyOne['isDefault'] === false) {
                    $property = $memberPropertyOne['propertyId'];
                } else {
                    $property = $memberPropertyOne['name'];
                }

                $columnParams = ['col' => $col, 'fileArray' => $fileArray, 'mobileCloIndex' => $mobileCloIndex, 'memberProperty' => $memberPropertyOne, 'property' => $property, 'mobilesAll' => $mobilesAll];

                $columnResult = ['insertData' => $insertData, 'updateData' => $updateData, 'insertArray' => $insertArray, 'updateArray' => $updateArray, 'mobilesAll' => $mobilesAll];
                $columnDataResult = $this->_traverseProperty($columnParams, $columnResult, $redis, $args);
                if ($columnDataResult === false) {
                    ResqueUtil::log(['message' => 'Fail to check property', 'data' => $columnDataResult]);
                    return false;
                }
                $insertData = $columnDataResult['insertData'];
                $updateData = $columnDataResult['updateData'];
            }
            $insertData = $insertData;
            $updateData = $updateData;
        }

        if (count($insertData) > 0) {
            $redis->sadd($cacheSetInsert, serialize($insertData));
        }
        if (count($updateData) > 0) {
            $redis->sadd($cacheSetUpdate, serialize($updateData));
        }
        # Store the number of the wrong code and the number of the right code
        $total = count($fileArray) - 1;
        $rightInsert = $redis->Hset($insertCount, self::INSERT_COUNT, count($insertData));
        $rightUpdate = $redis->Hset($updateCount, self::UPDATE_COUNT, count($updateData));

        $right = count($insertData) + count($updateData);
        $wrong = $total - $right;

        $redis->Hset($cacheHash, $wrongKey, $wrong);
        $redis->Hset($cacheHash, $rightKey, $right);

        # Store the ignore of properties.
        if ($wrong == 0 && count($ignoreProperties) > 0) {
            $redis->sadd($ignorePropertyKey, serialize($ignoreProperties));
        } else {
            $redis->sadd($ignorePropertyKey, null);
        }
        unset($ignoreProperties);
        unset($mobileResult);
        unset($insertData);
        unset($updateData);
        unset($insertArray);
        unset($updateArray);
        $this->_deleteFile($args['locationPath'], $args);
        return true;
    }

    /**
     * Verify the tag is right.
     * @param array $mobileResult ['insertData' => $insertData, 'updateData' => $updateData, 'insertArray' => $insertArray, 'updateArray' => $updateArray]
     * @param array $params ['col' => $col, 'fileArray' => $fileArray, 'mobileCloIndex' => $mobileCloIndex, 'memberProperty' => $memberPropertyOne, 'property' => $property, 'mobilesAll' => $mobilesAll]
     * @param object $redis
     * @param array $args
     *
     * @return boolean|array false|[]
     */
    private function _traverseProperty($params, $mobileResult, $redis, $args)
    {
        $memberProperty = $params['memberProperty'];
        $fileArray = $params['fileArray'];
        $col = $params['col'];
        $mobileCloIndex = $params['mobileCloIndex'];
        $property = $params['property'];
        $mobilesAll = $params['mobilesAll'];

        $insertData = $mobileResult['insertData'];
        $updateData = $mobileResult['updateData'];
        $insertArray = $mobileResult['insertArray'];
        $updateArray = $mobileResult['updateArray'];
        $cacheHash = md5($args['accountId'] . "_" . $args['fileName']);
        $mapDBToFile = ['name' => 'Name', 'tel' => 'Mobile', 'gender' => 'Gender', 'birthday' => 'Birthday', 'email' => 'Email'];

        # Check the data is insert or update..
        $insertValue = [];
        $updateValue = [];
        $allValue = [];
        $defaultProertyValue = '';
        $hasValue = [];

        if ($memberProperty['isDefault'] == true) {
            $defaultProertyValue = $mapDBToFile[$property];
        } else {
            $defaultProertyValue = $property;
        }
        for ($row = 1; $row < count($fileArray); $row++) {
            $value = trim((string)$fileArray[$row][$col]);

            $params = ['value' => $value, 'row' => $row + 1, 'col' => $col + 1, 'property' => $defaultProertyValue];
            # Verify if the property is required.
            if ($memberProperty['type'] != MemberProperty::TYPE_RADIO && $memberProperty['isRequired'] == true) {
                $isRequired = $this->_validateIsRequired($params, $redis, $args);
                if ($isRequired === false) {
                    ResqueUtil::log(['message' => 'The property is required.', 'data' => $isRequired]);
                    return false;
                }
            }

            # Verify if the property is right
            $valueResult =  $this->_validateFormat($params, $memberProperty, $redis, $args);
            if ($valueResult === false) {
                ResqueUtil::log(['message' => 'The property is incorrect.', 'data' => $valueResult]);
                return false;
            }

            if (!empty($value) && $memberProperty['type'] != MemberProperty::TYPE_RADIO && $memberProperty['type'] != MemberProperty::TYPE_TEXTAREA
                && $memberProperty['type'] != MemberProperty::TYPE_CHECKBOX && $memberProperty['type'] != MemberProperty::TYPE_DATE && $memberProperty['isUnique'] == true) {
                $paramsUnique = ['value' => $value, 'row' => $row + 1, 'col' => $col + 1, 'property' => $defaultProertyValue];
                # Verify if the mobile is unique in excel.
                $isUniqueResult = $this->_validateUnique($paramsUnique, $redis, $args);
                if ($isUniqueResult === false) {
                    ResqueUtil::log(['message' => 'Fail to check unique which is property in excel', 'data' => $isUniqueResult]);
                    return false;
                }
            }

            if (!empty($value)) {
                $hasValue[] = $value;
            }

            # Search the relate mobile, and put into the collection.
            $originalMobile = trim((string)$fileArray[$row][$mobileCloIndex]);
            $mobile = $this->_getMobileValue($originalMobile);

            // $isInsert = $redis->Hset($cacheHash . $col, $mobile, $col);
            if (in_array($mobile, $insertArray)) {
                $insertData[$mobile][] = [
                    'id' => new MongoId($memberProperty['id']),
                    'name' => $memberProperty['name'],
                    'value' => $valueResult
                ];
                if (!empty($value)) {
                    $insertValue[] = $value;
                }
            } else {
                $updateData[$mobile][] = [
                    'id' => new MongoId($memberProperty['id']),
                    'name' => $memberProperty['name'],
                    'value' => $valueResult
                ];
                if (!empty($valueResult)) {
                    $updateValue[] = $valueResult;
                }
            }

            $allValue[] = $value;
            # Verify if the property is unique in db, and put into the relate collection.
            if ((($row % self::BATCH_COUNT) == 0) || $row == (count($fileArray) - 1)) {
                if (count($hasValue) > 0 && $memberProperty['isUnique'] == true && $memberProperty['type'] != MemberProperty::TYPE_RADIO && $memberProperty['type'] != MemberProperty::TYPE_TEXTAREA
                && $memberProperty['type'] != MemberProperty::TYPE_CHECKBOX && $memberProperty['type'] != MemberProperty::TYPE_DATE) {
                    # Verify the insert data is unique in datebase.
                    if (count($insertArray) > 0) {
                        $paramsInsertUnique = ['mobilesAll' => $mobilesAll, 'value' => $insertValue, 'allValue' => $allValue, 'col' => $col, 'memberProperty' => $memberProperty, 'property' => $property, 'propertyItem' => $defaultProertyValue];
                        $dbUniqueResult = $this->_validateInsertUniqueInDB($paramsInsertUnique, $redis, $args);

                        if ($dbUniqueResult === false) {
                            ResqueUtil::log(['message' => 'Fail to check insert unique which is property in datebase', 'data' => $dbUniqueResult]);
                            return false;
                        }
                    }

                    if (count($updateValue) > 0) {
                        $paramsUpdateUnique = ['value' => $updateValue, 'mobilesAll' => $mobilesAll, 'col' => $col, 'fileArray' => $fileArray, 'memberProperty' => $memberProperty, 'propertyItem' => $defaultProertyValue];
                        $dbUnique = $this->_validateUpdateUniqueInDB($paramsUpdateUnique, $redis, $args);
                        if ($dbUnique === false) {
                            ResqueUtil::log(['message' => 'Fail to check update unique which is property in datebase', 'data' => $dbUnique]);
                            return false;
                        }
                        $updateValue = [];
                    }
                }
            }
        }
        unset($insertValue);
        unset($updateValue);
        return ['insertData' => $insertData, 'updateData' => $updateData];
    }

    /**
     * Verify if it is right.
     * @param array $params ['value' => $value, 'row' => $row + 1, 'col' => $col + 1, 'property' => $defaultProertyValue]
     * @param object $memberProperty
     * @param object $redis
     * @param array $args
     *
     * @return boolean|string false|$value
     */
    private function _validateFormat($params, $memberProperty, $redis, $args)
    {
        $value = $params['value'];
        $row = $params['row'];
        $col = $params['col'];
        $property = $params['property'];

        $paramsFormat = ['value' => $value, 'row' => $row, 'col' => $col, 'property' => $property];

        switch ($memberProperty['type']) {
            case Member::DEFAULT_PROPERTIES_EMAIL:
                $emailResult = $this->_validateEmail($paramsFormat, $redis, $args);
                if ($emailResult === false) {
                    ResqueUtil::log(['message' => 'The email is incorrect', 'data' => $emailResult]);
                    return false;
                }
                return $emailResult;
                break;
            case MemberProperty::TYPE_DATE:
                $dateResult = $this->_validateDate($paramsFormat, $redis, $args, $memberProperty);
                if ($dateResult === false) {
                    ResqueUtil::log(['message' => 'The date is incorrect', 'data' => $dateResult]);
                    return false;
                }
                return $dateResult;
                break;
            case MemberProperty::TYPE_CHECKBOX:
                $checkboxResult = $this->_validateCheckbox($paramsFormat, $memberProperty, $redis, $args);
                if ($checkboxResult === false) {
                    ResqueUtil::log(['message' => 'The checkbox is incorrect', 'data' => $checkboxResult]);
                    return false;
                }
                return $checkboxResult;
                break;
            case MemberProperty::TYPE_RADIO:
                $radioResult = $this->_validateRadio($paramsFormat, $memberProperty, $redis, $args);
                if ($radioResult === false) {
                    ResqueUtil::log(['message' => 'The radio is incorrect', 'data' => $radioResult]);
                    return false;
                }
                return $radioResult;
                break;
            case MemberProperty::TYPE_TEXTAREA:
                $textareaValue = str_replace("\\n", PHP_EOL, $value);
                return $textareaValue;
                break;
            default:
                if ($memberProperty['name'] == Member::DEFAULT_PROPERTIES_NAME) {
                    $nameResult = $this->_validateName($paramsFormat, $redis, $args);
                    if ($nameResult === false) {
                        ResqueUtil::log(['message' => 'The name is incorrect', 'data' => $nameResult]);
                        return false;
                    }
                    return $nameResult;
                } else {
                    return $value;
                }
                break;
        }
    }

    /**
     * Verify if the email is correct.
     * @param array $paramsFormat ['value' => $value, 'row' => $row, 'col' => $col, 'property' => $property]
     * @param object $redis
     * @param array $args
     *
     * @return boolean|string false|$value
     */
    private function _validateName($paramsFormat, $redis, $args)
    {
        $value = $paramsFormat['value'];
        $rowIndex = $paramsFormat['row'];
        $colIndex = $paramsFormat['col'];
        $property = $paramsFormat['property'];

        $valueLen = mb_strlen($value, 'utf-8');
        if (!empty($value)) {
            if ($valueLen < 2 || $valueLen > 30) {
                $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
                $this->_storeError($redis, $array, $args);
                return false;
            }
        }
        return $value;
    }

    /**
     * Verify if the email is correct.
     * @param array $paramsFormat ['value' => $value, 'row' => $row, 'col' => $col, 'property' => $property]
     * @param object $memberProperty
     * @param object $redis
     * @param array $args
     *
     * @return boolean|string true|$value
     */
    private function _validateRadio($paramsFormat, $memberProperty, $redis, $args)
    {
        $value = $paramsFormat['value'];
        $rowIndex = $paramsFormat['row'];
        $colIndex = $paramsFormat['col'];
        $property = $paramsFormat['property'];

        if ($memberProperty['name'] != Member::DEFAULT_PROPERTIES_GENDER) {
            if (!empty($value) && !in_array($value, $memberProperty['options'])) {
                $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
                $this->_storeError($redis, $array, $args);
                return false;
            }
            $radioValue = empty($value) ? $memberProperty['options'][0] : $value;
            return $radioValue;
        } else {
            if (!empty($value) && $value == self::MEMBER_SC_GENDER || $value == self::MEMBER_TC_GENDER || strcasecmp($value, self::MEMBER_MALE) == 0) {
                $genderValue = self::MEMBER_MALE;
            } else if (!empty($value) && $value == self::MEMBER_C_GENDER || strcasecmp($value, self::MEMBER_FEMALE) == 0) {
                $genderValue = self::MEMBER_FEMALE;
            } else if (empty($value)) {
                $genderValue = self::MEMBER_MALE;
            } else {
                $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
                $this->_storeError($redis, $array, $args);
                return false;
            }
            return $genderValue;
        }
    }

    /**
     * Verify if the email is correct.
     * @param array $paramsFormat ['value' => $value, 'row' => $row + 1, 'col' => $col + 1, 'property' => $property]
     * @param object $memberProperty
     * @param object $redis
     * @param array $args
     *
     * @return boolean|string false|$checkboxValue
     */
    private function _validateCheckbox($paramsFormat, $memberProperty, $redis, $args)
    {
        $value = $paramsFormat['value'];
        $rowIndex = $paramsFormat['row'];
        $colIndex = $paramsFormat['col'];
        $property = $paramsFormat['property'];

        $checkboxValue = [];
        if (!empty($value) && strstr($value, self::MEMBER_ENGLISH_COMMA) && !strstr($value, self::MEMBER_CHINESE_COMMA)) {//english
            $checkboxValue = explode(self::MEMBER_ENGLISH_COMMA, $value);
            $checkboxValue = array_values(array_unique($checkboxValue));
        }

        if (!empty($value) && strstr($value, self::MEMBER_CHINESE_COMMA) && !strstr($value, self::MEMBER_ENGLISH_COMMA)) {// chinese
            $checkboxValue = explode(self::MEMBER_CHINESE_COMMA, $value);
            $checkboxValue = array_values(array_unique($checkboxValue));
        }

        if (!empty($value) && strstr($value, self::MEMBER_ENGLISH_COMMA) && strstr($value, self::MEMBER_CHINESE_COMMA)) {
            $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
            $this->_storeError($redis, $array, $args);
            return false;
        }
        # Only one value
        if (!empty($value) && !strstr($value, self::MEMBER_ENGLISH_COMMA) && !strstr($value, self::MEMBER_CHINESE_COMMA)) {
            $checkboxValue[] = $value;
        }

        if (count($checkboxValue) > 0) {
            foreach ($checkboxValue as $item) {
                if (!in_array($item, $memberProperty['options'])) {
                    $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
                    $this->_storeError($redis, $array, $args);
                    return false;
                }
                continue;
            }
        }
        return $checkboxValue;
    }

    /**
     * Verify if the email is correct.
     * @param array $paramsFormat ['value' => $value, 'row' => $row + 1, 'col' => $col + 1, 'property' => $property]
     * @param object $redis
     * @param array $args
     *
     * @return boolean|string false|$date
     */
    private function _validateDate($paramsFormat, $redis, $args, $memberProperty)
    {
        $value = $paramsFormat['value'];
        $rowIndex = $paramsFormat['row'];
        $colIndex = $paramsFormat['col'];
        $property = $paramsFormat['property'];
        $time = '';

        if (!empty($value)) {
            if (strstr($value, self::MEMBER_LINE)) {
                $str = explode(self::MEMBER_LINE, $value);
                if (count($str) == 3) {
                    $time = $str[2] . '-' . $str[0] . '-' . $str[1];
                } else {
                    $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
                    $this->_storeError($redis, $array, $args);
                    return false;
                }
            } else {
                $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
                $this->_storeError($redis, $array, $args);
                return false;
            }
        }

        $unixTime = strtotime($time);
        if (!empty($value) && (date("Y-m-d", $unixTime) == $time) === false) {
            $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
            $this->_storeError($redis, $array, $args);
            return false;
        }

        if (!empty($value)) {
            $str = explode(self::MEMBER_LINE, $value);
            $dayStr = $str[2] . '-' . $str[0] . '-' . $str[1];
            $date = (int)(strtotime($dayStr)) * 1000;
        } else {
            $date = null;
        }

        if ($memberProperty['name'] == 'birthday') {
            if (!empty($value) && $date >= time() * 1000) {
                $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
                $this->_storeError($redis, $array, $args);
                return false;
            }
        }
        return $date;
    }

    /**
     * Verify if the email is correct.
     * @param array $paramsEmail ['value' => $value, 'row' => $row + 1, 'col' => $col + 1, 'property' => $property]
     * @param object $redis
     * @param array $args
     *
     * @return boolean|string false|$value
     */
    private function _validateEmail($paramsEmail, $redis, $args)
    {
        $value = $paramsEmail['value'];
        $rowIndex = $paramsEmail['row'];
        $colIndex = $paramsEmail['col'];
        $property = $paramsEmail['property'];

        if (!empty($value) && StringUtil::isEmail($value) === false) {
            $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
            $this->_storeError($redis, $array, $args);
            return false;
        }
        return $value;
    }

    /**
     * Verify if it is required.
     * @param array $paramsRequired ['value' => $value, 'row' => $row + 1, 'col' => $col + 1, 'property' => 'Email']
     * @param object $redis
     * @param array $args
     *
     * @return boolean true|false
     */
    private function _validateIsRequired($paramsRequired, $redis, $args)
    {
        $value = $paramsRequired['value'];
        $rowIndex = $paramsRequired['row'];
        $colIndex = $paramsRequired['col'];
        $property = $paramsRequired['property'];

        if (empty($value)) {
            $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_REQUIRED];
            $this->_storeError($redis, $array, $args);
            return false;
        }
        return true;
    }

    /**
     * Conversion format.
     * @param array ['xx,xx,xx','xx，xx，xx']
     *
     * @return array [['xx','xx','xx'], ['xx，xx，xx']]
     */
    private function _transformCheckbox($properties)
    {
        $checkboxValue = [];
        if (count($properties) > 0) {
            foreach ($properties as $value) {
                if (!empty($value) && strstr($value, self::MEMBER_ENGLISH_COMMA) && !strstr($value, self::MEMBER_CHINESE_COMMA)) {//english
                    $checkboxValue = explode(self::MEMBER_ENGLISH_COMMA, $value);
                    $checkboxValue = array_values(array_unique($checkboxValue));
                }

                if (!empty($value) && strstr($value, self::MEMBER_CHINESE_COMMA) && !strstr($value, self::MEMBER_ENGLISH_COMMA)) {// chinese
                    $checkboxValue = explode(self::MEMBER_CHINESE_COMMA, $value);
                    $checkboxValue = array_values(array_unique($checkboxValue));
                }
                # Only one value
                if (!empty($value) && !strstr($value, self::MEMBER_ENGLISH_COMMA) && !strstr($value, self::MEMBER_CHINESE_COMMA)) {
                    $checkboxValue[] = $value;
                }

                if (empty($value)) {
                    $checkboxValue[] = $value;
                }
            }
        }
        return $checkboxValue;
    }

    /**
     * Check insert unique which is email in datebase.
     * @param array $paramsInsertUnique ['mobilesAll' => $mobilesAll, 'value' => $insertValue, 'allValue' => $allValue, 'col' => $col, 'memberProperty' => $memberProperty, 'property' => $property, 'propertyItem' => $defaultProertyValue];
     * @param object $redis
     * @param array $args
     *
     * @return boolean true|false
     */
    private function _validateInsertUniqueInDB($paramsInsertUnique, $redis, $args)
    {
        $mobilesAll = $paramsInsertUnique['mobilesAll'];
        $value = $paramsInsertUnique['value'];
        $allValue = $paramsInsertUnique['allValue'];
        $col = $paramsInsertUnique['col'];
        $memberProperty = $paramsInsertUnique['memberProperty'];
        $property = $paramsInsertUnique['property'];
        $propertyItemValue = $paramsInsertUnique['propertyItem'];
        $rowIndex = 0;

        $where = [
            'properties' => [
                '$elemMatch' =>[
                    'value' => ['$in' => $value],
                    'id' => new MongoId($memberProperty['id'])
                ]
            ],
            'isDeleted' => false,
            'accountId' => new MongoId($args['accountId'])
        ];
        $isExistProperties = Member::findAll($where);
        if (!empty($isExistProperties)) {
            $proertyValue = '';

            if (!empty($isExistProperties[0])) {
                foreach ($isExistProperties[0]['properties'] as $propertyItem) {
                    if ((string)$propertyItem['id'] == (string)$memberProperty['id']) {
                        $proertyValue = $propertyItem['value'];
                    }
                }
                $rowIndex = array_search($proertyValue, $allValue) + 2;
                $array = ['value' => null, 'rows' => $rowIndex, 'cols' => $col + 1, 'property' => $propertyItemValue, 'wrongNum' => self::MEMBER_PROPERTY_UNIQUE];
                $this->_storeError($redis, $array, $args);
                return false;
            }
        }
        return true;
    }

    /**
     * Check update unique which is email in datebase
     * @param array $paramsUpdateUnique ['value' => $updateValue, 'mobilesAll' => $mobilesAll, 'col' => $col, 'fileArray' => $fileArray, 'memberProperty' => $memberProperty, 'propertyItem' => $defaultProertyValue]
     * @param object $redis
     * @param array $args
     *
     * @return boolean true|false
     */
    private function _validateUpdateUniqueInDB($paramsUpdateUnique, $redis, $args)
    {
        $values = $paramsUpdateUnique['value'];
        $mobilesAll = $paramsUpdateUnique['mobilesAll'];
        $col = $paramsUpdateUnique['col'];
        $fileArray = $paramsUpdateUnique['fileArray'];
        $memberProperty = $paramsUpdateUnique['memberProperty'];
        $propertyItem = $paramsUpdateUnique['propertyItem'];

        $members = Member::findMembersByValues($values, new MongoId($memberProperty['id']), new MongoId($args['accountId']));

        if (!empty($members)) {
            # feach the column list in excel.
            $colArray = array_column($fileArray, $col);
            unset($colArray[0]);

            foreach ($members as $member) {
                $dbMobileValue = '';
                $dbPropertyValue = '';
                $rowIndex = 0;
                foreach ($member['properties'] as $property) {
                    if ((string)$memberProperty['id'] == (string)$property['id']) {
                        $dbPropertyValue = $property['value'];
                    }

                    if ($property['name'] == Member::DEFAULT_PROPERTIES_MOBILE) {
                        $dbMobileValue = $property['value'];
                    }
                }

                $rowIndex = array_keys($colArray, $dbPropertyValue);
                foreach ($rowIndex as $index) {
                    $fileMobile = $mobilesAll[($index - 1)];
                    if ($dbMobileValue != $fileMobile) {
                        $array = ['value' => null, 'rows' => $index + 1, 'cols' => $col + 1, 'property' => $propertyItem, 'wrongNum' => self::MEMBER_PROPERTY_UNIQUE];
                        $this->_storeError($redis, $array, $args);
                        return false;
                    }
                }
            }
        }
    }

    /**
     * Verify the tag is right.
     * @param array $mobileResult ['insertData' => $insertData, 'updateData' => $updateData, 'insertArray' => $insertArray, 'updateArray' => $updateArray]
     * @param array $paramsTags ['col' => $col, 'fileArray' => $fileArray, 'mobileCloIndex' => $mobileCloIndex]
     * @param object $redis
     * @param array $args
     *
     * @return boolean|array false|['tags' => '']
     */
    private function _validateTags($mobileResult, $paramsTags, $redis, $args)
    {
        $fileArray = $paramsTags['fileArray'];
        $col = $paramsTags['col'];
        $mobileCloIndex = $paramsTags['mobileCloIndex'];
        $insertData = $mobileResult['insertData'];
        $updateData = $mobileResult['updateData'];
        $insertArray = $mobileResult['insertArray'];
        $updateArray = $mobileResult['updateArray'];
        $cacheHash = md5($args['accountId'] . "_" . $args['fileName']);

        # Get all the tags in datebase.
        $tagsAll = Account::getAllTags(new MongoId($args['accountId']));
        $tags = empty($tagsAll) ? [] : $tagsAll;
        $propertyTip = $fileArray[0][$col];

        for ($row = 1; $row < count($fileArray); $row++) {
            $value = trim((string)$fileArray[$row][$col]);

            if (!empty($value) && !in_array($value, $tags)) {
                $array = ['value' => $value, 'rows' => $row + 1, 'cols' => $col + 1, 'property' => $propertyTip, 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
                $this->_storeError($redis, $array, $args);
                return false;
            }

            # Search the relate mobile, and put into the collection.
            $originalMobile = trim((string)$fileArray[$row][$mobileCloIndex]);
            $mobile = $this->_getMobileValue($originalMobile);

            if (in_array($mobile, $insertArray)) {
                $insertData[$mobile][] = ['tags' => $value];
            } else {
                $updateData[$mobile][] = ['tags' => $value];
            }
        }
        return ['insertData' => $insertData, 'updateData' => $updateData];
    }

    /**
     * Verify the tag is right.
     * @param string $mobile
     *
     * @return string $mobile.
     */
    private function _getMobileValue($mobile)
    {
        $mobileValue = '';
        if (strstr($mobile, self::MEMBER_ENGLISH_COLON)) { // english colon
            $mobileStr = explode(self::MEMBER_ENGLISH_COLON, $mobile);
            $mobileValue = $mobileStr[1];

        } else if (strstr($mobile, self::MEMBER_CHINESE_COLON)) { // chinese colon.
            $mobileStr = explode(self::MEMBER_CHINESE_COLON, $mobile);
            $mobileValue = $mobileStr[1];
        }
        return $mobileValue;
    }

    /**
     * Validate title of excel.
     * @param object $memberProperty
     * @param array $fileArray
     * @param object $redis
     * @param array $args
     *
     * @return array $title
     */
    private function _validateTitle($memberProperty, $fileTitles, $redis, $args)
    {
        $requiredProperties = [];
        $missRequiredProperties = [];
        $repeatTitle = [];
        $titles = [];
        $cacheHash = md5($args['accountId'] . "_" . $args['fileName']);
        $repeatTitleKey = self::MEMBER_REPEAT_TITLE . $cacheHash;
        $missPropertyKey = self::MEMBER_FILE_MISS . $cacheHash;
        $allMemberPropertiesDB = [];
        $telId = '';
        $memberPropertyArray = [];

        # Mapping database to title.
        $dbToTitles = ['name' => 'Name', 'tel' => 'Mobile', 'gender' => 'Gender', 'birthday' => 'Birthday', 'email' => 'Email'];

        # Get all the properties which is required.
        foreach ($memberProperty as $property) {
            if ($property['isDefault'] === true && $property['isRequired'] === true && $property['isVisible'] === true) {
                $requiredProperties[] = strtolower($property['name']);
            }

            if ($property['isDefault'] === false && $property['isRequired'] === true && $property['isVisible'] === true) {
                $requiredProperties[] = strtolower($property['propertyId']);
            }

            if ($property['isDefault'] === true) {
                $allMemberPropertiesDB[] = $property['name'];
                $memberPropertyArray[$property['name']] = $property->toArray();
            } else {
                $allMemberPropertiesDB[] = strtolower($property['propertyId']);
                $memberPropertyArray[strtolower($property['propertyId'])] = $property->toArray();
            }

            if ($property['name'] == 'tel') {
                $telId = (string)$property['_id'];
            }
        }

        # Handle title.
        foreach ($fileTitles as $fileTitle) {
            $title = strtolower(trim((string)$fileTitle));
            if (!in_array($title, $titles)) {
                $titles[] = $title;
            } else {
                if (!in_array($title, $repeatTitle)) {
                    $repeatTitle[] = $title;
                }
                continue;
            }
        }
        # Repeat title.
        if (count($repeatTitle) > 0) {
            ResqueUtil::log(['error' => 'the title is repeated', 'args' => ['repeatTitle' => $repeatTitle]]);
            $array = ['value' => null, 'rows' => null, 'cols' => null, 'property' => null, 'wrongNum' => self::MEMBER_TITLE_REPEATED];
            $this->_storeError($redis, $array, $args);
            $redis->sadd($repeatTitleKey, serialize($repeatTitle));
            unset($repeatTitle);
            return false;
        }

        # Required title is missing.
        foreach ($requiredProperties as $property) {
            if (array_key_exists($property, $dbToTitles)) {
                if (!in_array(strtolower($dbToTitles[$property]), $titles) && !in_array($dbToTitles[$property], $missRequiredProperties)) {
                    $missRequiredProperties[] = $dbToTitles[$property];
                }
                continue;
            } else {
                if (!in_array($property, $titles) && !in_array($property, $missRequiredProperties)) {
                    $missRequiredProperties[] = $property;
                }
            }
        }
        if (count($missRequiredProperties) > 0) {
            ResqueUtil::log(['error' => 'Required property is missing', 'args' => ['missRequiredProperties' => $missRequiredProperties]]);
            $array = ['value' => null, 'rows' => null, 'cols' => null, 'property' => null, 'wrongNum' => self::MEMBER_NO_EXIST];
            $this->_storeError($redis, $array, $args);
            $redis->sadd($missPropertyKey, serialize($missRequiredProperties));
            unset($missRequiredProperties);
            return false;
        }

        $result = ['titles' => $titles, 'allMemberPropertiesDB' => $allMemberPropertiesDB, 'telId' => $telId, 'memberPropertyArray' => $memberPropertyArray];
        return  $result;
    }

    /**
     * Validate mobile if it is right.
     * @param array $params ['value' => $mobileValue, 'row' => $row, 'col' => $mobileCloIndex, 'property' => $titles[$mobileCloIndex]]
     * @param object @redis
     * @param array $args
     *
     * @return string
     */
    private function _validateMobile($params, $redis, $args)
    {
        $mobile = $params['value'];
        $mobileValue = '';

        if (empty($mobile)) {
            $array = ['value' => $mobile, 'rows' => $params['row'] + 1, 'cols' => $params['col'] + 1, 'property' => $params['property'], 'wrongNum' => self::MEMBER_PROPERTY_REQUIRED];
            $this->_storeError($redis, $array, $args);
            return false;
        }

        # Check mobile. Example: T:XXXXXXXXXXX;
        if (strstr($mobile, self::MEMBER_ENGLISH_COLON)) { // english colon
            $mobileStr = explode(self::MEMBER_ENGLISH_COLON, $mobile);
            if (count($mobileStr) != 2) {
                $array = ['value' => $mobile, 'rows' => $params['row'] + 1, 'cols' => $params['col'] + 1, 'property' => $params['property'], 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
                $this->_storeError($redis, $array, $args);
                return false;
            }

            if ($mobileStr[0] != 'T') {
                $array = ['value' => $mobile, 'rows' => $params['row'] + 1, 'cols' => $params['col'] + 1, 'property' => $params['property'], 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
                $this->_storeError($redis, $array, $args);
                return false;
            }
            $mobileValue = $mobileStr[1];

        } else if (strstr($mobile, self::MEMBER_CHINESE_COLON)) { // chinese colon.
            $mobileStr = explode(self::MEMBER_CHINESE_COLON, $mobile);
            if (count($mobileStr) != 2) {
                $array = ['value' => $mobile, 'rows' => $params['row'] + 1, 'cols' => $params['col'] + 1, 'property' => $params['property'], 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
                $this->_storeError($redis, $array, $args);
                return false;
            }

            if ($mobileStr[0] != 'T') {
                $array = ['value' => $mobile, 'rows' => $params['row'] + 1, 'cols' => $params['col'] + 1, 'property' => $params['property'], 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
                $this->_storeError($redis, $array, $args);
                return false;
            }
            $mobileValue = $mobileStr[1];
        } else {
            $array = ['value' => $mobile, 'rows' => $params['row'] + 1, 'cols' => $params['col'] + 1, 'property' => $params['property'], 'wrongNum' => self::MEMBER_PROPERTY_ERROR];
            $this->_storeError($redis, $array, $args);
            return false;
        }

        if (!empty($mobileValue) && StringUtil::isMobile($mobileValue) === false) {
            $array = ['value' => $mobile, 'rows' => $params['row'] + 1, 'cols' => $params['col'] + 1, 'property' => $params['property'], 'wrongNum' => self::MEMBER_PROPERTY_ILLEGAL];
            $this->_storeError($redis, $array, $args);
            return false;
        }
        return $mobileValue;
    }

    /**
     * According to mobile, the division of the data into update data and insert data.
     * @param array $params $params = ['fileArray' => $fileArray, 'mobileIndex' => $mobileCloIndex, 'titles' => $titles, 'mobileId' => $telId];
     * @param object $redis
     * @param array $args
     *
     * @return array|boolean ['insert' = [], 'update' = []]|false
     */
    private function _divisionDataByMobile($params, $redis, $args)
    {
        $fileArray = $params['fileArray'];
        $mobileCloIndex = $params['mobileIndex'];
        $titles = $params['titles'];
        $mobileId = $params['mobileId'];
        $insertData = [];
        $updateData = [];
        $insertArray = [];
        $updateArray = [];
        $mobiles = [];
        $mobilesAll = [];

        for ($row = 1; $row < count($fileArray); $row++) {
            # Verify that mobile, to distinguish insert data or update data.
            $mobileValue = trim((string)$fileArray[$row][$mobileCloIndex]);
            $paramsError = ['value' => $mobileValue, 'row' => $row, 'col' => $mobileCloIndex, 'property' => $titles[$mobileCloIndex]];

            $mobile = $this->_validateMobile($paramsError, $redis, $args);
            if ($mobile === false || empty($mobile)) {
                ResqueUtil::log(['message' => 'Fail to check mobile', 'data' => $mobile]);
                return false;
            }

            # Verify if the mobile is unique in excel.
            $paramsUnique = ['value' => $mobile, 'row' => $row + 1, 'col' => $mobileCloIndex + 1, 'property' => 'Mobile'];
            $isUniqueResult = $this->_validateUnique($paramsUnique, $redis, $args);
            if ($isUniqueResult === false) {
                ResqueUtil::log(['message' => 'Fail to check unique which is mobile in excel', 'data' => $isUniqueResult]);
                return false;
            }

            $mobiles[] = $mobile;

            # Query phone number In batches, if it is exist, will store update date, Instead will store insert data.
            if ((($row % self::BATCH_COUNT) == 0) || $row == (count($fileArray) - 1)) {
                $condition = [
                    'properties' => [
                        '$elemMatch' => [
                            'value' => ['$in' => $mobiles],
                            'name' => 'tel'
                        ]
                    ],
                    'isDeleted' => false,
                    'accountId' => new MongoId($args['accountId'])
                ];
                $members = Member::findAll($condition);

                # store data in collection of updateDate.
                foreach ($members as $member) {
                    foreach ($member['properties'] as $property) {
                        if ($property['name'] == "tel") {
                            $updateData[$property['value']][] = [
                                'id' => $property['id'],
                                'name' => $property['name'],
                                'value' => $property['value']
                            ];
                            $updateArray[] = $property['value'];
                            $deleteMobileIndex = array_search($property['value'], $mobiles);
                            unset($mobiles[$deleteMobileIndex]);
                        }
                        continue;
                    }
                }

                # store data in collection of insertDate.
                foreach ($mobiles as $mobileValue) {
                    $insertData[$mobileValue][] = [
                        'id' => new MongoId($mobileId),
                        'name' => 'tel',
                        'value' => $mobileValue
                    ];
                    $insertArray[] = $mobileValue;
                }
                $mobiles = [];
            }
            $mobilesAll[] = $mobile;
        }
        return ['insertData' => $insertData, 'updateData' => $updateData, 'insertArray' => $insertArray, 'updateArray' => $updateArray, 'mobilesAll' => $mobilesAll];
    }

    /**
     * Validate title of excel.
     * @param array $paramsUnique ['value' => $mobile, 'row' => $row + 1, 'col' => $mobileCloIndex + 1, 'property' => 'Mobile'];
     * @param object $redis
     * @param array $args
     *
     * @return boolean true|false
     */
    private function _validateUnique($paramsUnique, $redis, $args)
    {
        $value = $paramsUnique['value'];
        $rowIndex = $paramsUnique['row'];
        $colIndex = $paramsUnique['col'];
        $property = $paramsUnique['property'];
        $cacheHash = md5($args['accountId'] . "_" . $args['fileName']);
        $isInsert = $redis->Hset($cacheHash . $colIndex, $value, $colIndex);
        if ($isInsert == 0) {
            $array = ['value' => $value, 'rows' => $rowIndex, 'cols' => $colIndex, 'property' => $property, 'wrongNum' => self::MEMBER_PROPERTY_UNIQUE];
            $this->_storeError($redis, $array, $args);
            return false;
        }
        return true;
    }

    /**
     * Validate title of excel.
     * @param object $redis
     * @param array $array
     * @param array $args
     *
     * @return array
     */
    private function _storeError($redis, $array, $args)
    {
        # $array = [value, rows, cols, property, wrongNum];
        $cacheHash = md5($args['accountId'] . "_" . $args['fileName']);
        $rowsIndexKey = self::MEMBER_FILE_ROWS . $cacheHash;
        $colsIndexKey = self::MEMBER_FILE_COLS . $cacheHash;
        $titleIndexKey = self::MEMBER_FILE_TITLE . $cacheHash;
        if (!empty($array['value'])) {
            ResqueUtil::log(['error' => $array['rows'] . ' rows ' . $array['cols'] . ' cols ' . 'the member`s ' . $array['property'] . ' has error', 'args' => ['value' => $array['value']]]);
        }
        $redis->Hset($rowsIndexKey, 'wrong', $array['rows']); // rows
        $redis->Hset($colsIndexKey, 'wrong', $array['cols']); // cols
        $redis->Hset($titleIndexKey, 'wrong', $array['property']); //property
        $redis->Hset($cacheHash, 'wrong', $array['wrongNum']);
        $redis->Hset($cacheHash, 'right', 0);
        $this->_deleteFile($args['locationPath'], $args);
    }

    /**
     * Validate title of excel.
     * @param string $filePath
     * @param array $args
     *
     */
    private function _deleteFile($filePath, $args)
    {
        try {
            @unlink($filePath);
            Yii::$app->qiniu->deleteFile($args['fileName'], $args['qiniuBucket']);
        } catch (Exception $e) {
            ResqueUtil::log(['error' => $e->getMessage()]);
        }
    }
}
