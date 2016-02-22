<?php

namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushUser;
use backend\modules\uhkklp\models\Message;

class PushMessageLog extends ActiveRecord
{
    public static function collectionName()
    {
        return 'uhkklpPushMessageLog';
    }

    public function attributes()
    {
        return [
            '_id',
            'messageId',
            'startTime',
            'endTime',
            'results',
            'accountId'
        ];
    }

    public function rules()
    {
        return [
            [['messageId', 'startTime', 'endTime', 'results', 'accountId'], 'safe'],
        ];
    }

    public static function getResults($messageId)
    {
        $query1 = new Query();
        $data = $query1->from(self::collectionName())
            ->select(['messageId', 'results'])
            ->where(['messageId' => $messageId])
            ->all();

        if (empty($data)) {
            return [];
        }

        $data = $data[0];

        $query2 = new Query();
        $messageContent = $query2->from(Message::collectionName())
            ->select(['content'])
            ->where(['_id' => new \MongoId($messageId)])
            ->all()[0]['content'];

        $results = [];
        foreach ($data['results'] as $key => $value) {
            if (empty($value['mobile'])) {
                $value['mobile'] = '';
            }

            if ($value['type'] == 'iOS') {
                if (empty($value['res']) || $value['res'] != 200) {
                    $value['res'] = '失敗: ' . $value['res'];
                } else {
                    $value['res'] = '成功';
                }
            } else {
                if (!empty($value['res'])) {
                    $jsonObj = json_decode($value['res']);
                    if ($jsonObj->success != null && $jsonObj->success > 0) {
                        $value['res'] = '成功';
                    } else {
                        $value['res'] = '失敗';
                    }
                }
            }

            // $query3 = new Query();
            // $mobile = $query3->from(PushUser::collectionName())
            //     ->select(['mobile'])
            //     ->where(['token' => $value['token']])
            //     ->all();
            // if (empty($mobile)) {
            //     $mobile = '';
            // } else {
            //     $mobile = $mobile[0]['mobile'];
            // }

            $results[] = [
                'messageId' => $messageId,
                'messageContent' => $messageContent,
                'mobile' => $value['mobile'] . ' ',
                'deviceType' => $value['type'],
                'deviceId' => $value['token'],
                'result' => $value['res']
            ];
        }
        return $results;
    }
}
