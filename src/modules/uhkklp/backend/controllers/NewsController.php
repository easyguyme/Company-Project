<?php
namespace backend\modules\uhkklp\controllers;

use backend\modules\uhkklp\models\News;
use backend\modules\uhkklp\models\ReadNewsRecord;
use Yii;

class NewsController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionList()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return ['data' => 401, 'detail ' => 'No info found with this account Id.', 'event' => [], 'success' => 0];
        }

        $deviceId = Yii::$app->request->get('device_id');
        if (!isset($deviceId)) {
            return ['data' => 403, 'detail ' => 'Missing device id.', 'event' => [], 'success' => 0];
        }

        $newsList = new News();
        $newsList = $newsList->getList(['accountId' => $accountId, 'isDeleted' => false]);
        $top = [];
        $list = [];

        if ($deviceId == '0') {
             foreach ($newsList as &$value) {
                $newsId = (string)$value['_id'];
                $isReaded = $this->isRead($deviceId, $newsId);
                $item = ['begin' => $value['begin'], 'icon' => $value['icon'], 'is_read' => $isReaded, 'news_id' => $newsId, 'title' => $value['title'], 'thumbnail' => $value['thumbnail']];
                array_push($list, $item);

                if (isset($value['isLatest']) && $value['isLatest'] == 'Y') {
                    $item = ['begin' => $value['begin'], 'icon' => 0, 'is_read' => $isReaded, 'news_id' => $newsId, 'title' => $value['title'], 'thumbnail' => $value['thumbnail']];
                    array_push($list, $item);
                }
             }
        } else {
            foreach ($newsList as &$value) {
                $newsId = (string)$value['_id'];
                $isReaded = $this->isRead($deviceId, $newsId);
                $startDate = intval($value['begin']);
                list($tmp1, $tmp2) = explode(' ', microtime());
                $currentDate = (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);

                if ($startDate > $currentDate) {
                    continue;
                }

                $item = ['begin' => $value['begin'], 'icon' => $value['icon'], 'is_read' => $isReaded, 'news_id' => $newsId, 'title' => $value['title'], 'thumbnail' => $value['thumbnail']];

                array_push($list, $item);

                /*
                if ($value['isTop'] == 'Y') {
                    array_push($top, $item);
                } else {
                    array_push($list, $item);
                }
                */

                if (isset($value['isLatest']) && $value['isLatest'] == 'Y') {
                    $item = ['begin' => $value['begin'], 'icon' => 0, 'is_read' => $isReaded, 'news_id' => $newsId, 'title' => $value['title'], 'thumbnail' => $value['thumbnail']];
                    array_push($list, $item);
                }
            }
        }
        unset($value);
        unset($newsList);

        $results = [
            'data' => ['top' => $top, 'list' => $list],
            'detail ' => '資料取得完成',
            'event' => [],
            'success' => 1
        ];

        return $results;
    }

    public function actionGetNews() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return ['data' => 401, 'detail ' => 'No info found with this account Id.', 'event' => [], 'success' => 0];
        }

        $deviceId = (string)Yii::$app->request->get('device_id');
        if (!isset($deviceId)) {
            return ['data' => 403, 'detail ' => 'Missing device id.', 'event' => [], 'success' => 0];
        }

        $newsId = Yii::$app->request->get('news_id');
        if (empty($newsId)) {
            return ['data' => 404, 'detail ' => 'Missing news id.', 'event' => [], 'success' => 0];
        }

        $news = new News();
        $item = $news->getItem(['accountId' => $accountId, '_id' => $newsId]);
        if ($deviceId != '0') {
            $this->readNews($deviceId, $newsId);
        }

        $results = [
            'data' => ['begin' => $item['begin'], 'content' => $item['content'], 'icon' => $item['icon'], 'thumbnail' => $item['thumbnail'], 'isTop' => $item['isTop'], 'image' => $item['imgUrl'], 'is_video' => $item['isVideo'], 'is_Latest' => $item['isLatest'], 'title' => $item['title'], 'share_url' => $item['shareUrl'], 'youtube_url' => $item['youtubeUrl'], 'share_btn_txt' => $item['shareBtnTxt'], 'more_info' => $item['moreInfo']],
            'success' => 1
        ];

        unset($news);
        unset($item);
        return $results;
    }

    public function actionSave() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $params = $this->getParams();
        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return ['data' => 401, 'detail ' => 'No info found with this account Id.', 'event' => [], 'success' => 0];
        }

        if (!isset($params['begin'])) {
            return ['data' => 402, 'detail ' => 'No begin time specified.', 'event' => [], 'success' => 0];
        }

        $begin = $params['begin'];
        $icon = $params['icon'];
        $title = $params['title'];
        $thumbnail = $params['thumbnail'];
        $isTop = $params['isTop'];
        $content = $params['content'];
        $imgUrl = $params['imgUrl'];
        $isVideo = $params['isVideo'];
        $isLatest = $params['isLatest'];
        $youtubeUrl = $params['youtubeUrl'];
        $shareUrl = $params['shareUrl'];
        $shareBtnTxt = $params['shareBtnTxt'];
        $moreInfo = $params['moreInfo'];

        $newsId = $params['newsId'];

        $news = new News();
        if ($newsId != 0) {
            $news = $news->getItem(['accountId' => $accountId, '_id' => $newsId]);
        }

        $news->accountId = $accountId;
        $news->begin = $begin;
        $news->icon = $icon;
        $news->title = $title;
        $news->thumbnail = $thumbnail;
        $news->isTop = $isTop;
        $news->content = $content;
        $news->imgUrl = $imgUrl;
        $news->isVideo = $isVideo;
        $news->isLatest = $isLatest;
        $news->youtubeUrl = $youtubeUrl;
        $news->shareUrl = $shareUrl;
        $news->moreInfo = $moreInfo;
        $news->shareBtnTxt = $shareBtnTxt;
        $news->isDeleted = false;
        $news->save();

        return ['code'=>200];
    }

    public function actionDelete($id) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return ['data' => 401, 'detail ' => 'No info found with this account Id.', 'event' => [], 'success' => 0];
        }

        $news = new News();
        $news->deleteItem(['accountId' => $accountId, '_id' => $id]);

        $results = [
            'code' => 200,
            'success' => 1
        ];

        unset($news);
        unset($item);
        return $results;
    }

    private function isRead($deviceId, $id) {
        $isRead = 'N';
        $newsRecord = new ReadNewsRecord();
        $accountId = $this->getAccountId();
        $readNewsRecord = $newsRecord->getItem(['accountId' => $accountId, 'deviceId' => $deviceId]);
        if (empty($readNewsRecord)) {
        } else {
            $idList = $readNewsRecord['readedNewsId'];
            foreach ($idList as &$value) {
                if ((string)$value == $id) {
                    $isRead = 'Y';
                    break;
                }
            }
        }
        unset($newsRecord);

        return $isRead;
    }

    private function readNews($deviceId, $id) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return ['data' => 401, 'detail ' => 'No info found with this account Id.', 'event' => [], 'success' => 0];
        }

        $newsRecord = new ReadNewsRecord();
        $readNewsRecord = $newsRecord->getItem(['accountId' => $accountId, 'deviceId' => $deviceId]);
        if (empty($readNewsRecord)) {
            $record = new ReadNewsRecord();
            $record->accountId = $accountId;
            $record->deviceId = $deviceId;
            $record->readedNewsId = [$id];
            $record->save();
            unset($record);
        } else {
            $hasSame = false;
            $idList = $readNewsRecord['readedNewsId'];
            foreach ($idList as &$value) {
                if ((string)$value == $id) {
                    $hasSame = true;
                    break;
                }
            }
            if (!$hasSame) {
                array_push($idList, $id);
                $newsRecord->updateItem(['accountId' => $accountId, 'deviceId' => $deviceId], $idList);
            }
        }
        unset($newsRecord);

        return ['code'=>200];
    }
}
