<?php

namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;
use backend\models\Token;
use backend\models\User;
use backend\modules\uhkklp\models\CookingType;
use backend\utils\LogUtil;
use yii\base\ErrorException;

class Cookbook extends BaseModel
{
    const ACTIVE_ON = 'Y';
    const ACTIVE_OFF = 'N';
    const SAMPLE_OPEN = 'Y';
    const SAMPLE_CLOSE = 'N';

    public static function collectionName()
    {
        return 'uhkklpCookbook';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['_id', 'title', 'image', 'content', 'ingredient', 'startDate', 'endDate', 'shareUrl',
            'isSampleOpen', 'sample', 'active', 'createdDate', 'updatedDate', 'operator', 'type',
            'video', 'restaurantName', 'cookName', 'category', 'subCategory', 'portionSize',
            'preparationMethod', 'yield', 'tip', 'creativeExperience', 'deliciousSecret',
            'hasImportImg', 'activeSortTime', 'inactiveSortTime', 'cuisineType', 'averageScore',
            'shareDescription', 'activitySettingId', 'activitySettingName']
        );
    }

    public function safeAttributes()
    {
        return array_merge(parent::safeAttributes(), self::attributes());
    }

    public static function saveImportedCookbooks($datas, $accountId)
    {
        $results = [];
        $accessToken = Token::getToken();
        $userId = $accessToken->userId;
        $user = User::findOne(['_id' => $userId]);

        for ($i = 0; $i < sizeof($datas); $i++) {
            $results[] = self::_saveSingleCookbook($datas[$i], $user, $accountId);
        }

        return $results;
    }

    private function _saveSingleCookbook($data, $user, $accountId)
    {

        $data['type'] = [];
        if (isset($data['category']) && sizeof($data['category']) > 0) {
            array_unique($data['category']);
            $data['type'] = array_merge($data['type'], $data['category']);
        }
        if (isset($data['subCategory']) && sizeof($data['subCategory']) > 0) {
            array_unique($data['subCategory']);
            $data['type'] = array_merge($data['type'], $data['subCategory']);
        }
        if (isset($data['cuisineType']) && sizeof($data['cuisineType']) > 0) {
            array_unique($data['cuisineType']);
            $data['type'] = array_merge($data['type'], $data['cuisineType']);
        }

        if (sizeof($data['type'] > 0)) {
            array_unique($data['type']);
        }

        $cookbook = new Cookbook();
        $now = time();
        $cookbook->attributes = $data;
        $cookbook->operator = $user->name;
        $cookbook->createdDate = $now;
        $cookbook->updatedDate = $now;
        $cookbook->startDate = $now;
        $cookbook->endDate = mktime(0, 0, 0, 12, 31, 2016);
        $cookbook->activeSortTime = $cookbook->createdDate;
        $cookbook->inactiveSortTime = $cookbook->createdDate;
        $cookbook->sample = [];
        $cookbook->isSampleOpen = self::SAMPLE_CLOSE;
        $cookbook->active = self::ACTIVE_OFF;
        $cookbook->accountId = $accountId;
        $cookbook->hasImportImg = false;
        $cookbook->save();
        CookingType::saveByCookbook($cookbook, $cookbook->accountId, $user);

        return ['cookbookId' => $cookbook->_id, 'name' => $cookbook->title, 'image'=> $cookbook->image];
    }
}
