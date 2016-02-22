<?php
namespace backend\modules\common\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use backend\utils\ExcelUtil;

class JobController extends BaseController
{
    const STATUS_WAITING = 'waiting';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLETE = 'complete';
    const STATUS_ERROR = 'error';

    public function actionStatus()
    {
        $params = $this->getParams();

        if (empty($params['data'])) {
            throw new BadRequestHttpException('missing params');
        }

        $statusMap = [
            0 => self::STATUS_ERROR,
            1 => self::STATUS_WAITING,
            2 => self::STATUS_RUNNING,
            3 => self::STATUS_FAILED,
            4 => self::STATUS_COMPLETE,
        ];

        $result = [];
        foreach ($params['data'] as $data) {
            $url = '';
            if (!empty($data['jobId']) && !empty($data['key'])) {
                $jobstatus = Yii::$app->job->status($data['jobId']);
                $value = $data['key'] . '.csv';
                if (!empty($data['type'])) {
                    $value = $data['key'] . '.' . $data['type'];
                }
                if (!empty($value) && $jobstatus == 4) {
                    $url = Yii::$app->qiniu->getPrivateUrl($value);
                }
                $result[] = [
                    'status' => $statusMap[$jobstatus],
                    'jobId' => $data['jobId'],
                    'url' => $url,
                ];
            }
        }
        return $result;
    }

    public function actionKlpStatus()
    {
        $params = $this->getParams();

        if (empty($params['data'])) {
            throw new BadRequestHttpException('missing params');
        }

        $statusMap = [
            0 => self::STATUS_ERROR,
            1 => self::STATUS_WAITING,
            2 => self::STATUS_RUNNING,
            3 => self::STATUS_FAILED,
            4 => self::STATUS_COMPLETE,
        ];

        $result = [];
        foreach ($params['data'] as $data) {
            $url = '';
            if (!empty($data['jobId']) && !empty($data['key'])) {
                $jobstatus = Yii::$app->job->status($data['jobId']);
                $value = $data['key'] . '.csv';
                if (!empty($value) && $jobstatus == 4) {
                    $url = '/download/' . $value;
                }
                $result[] = [
                    'status' => $statusMap[$jobstatus],
                    'jobId' => $data['jobId'],
                    'url' => $url,
                ];
            }
        }
        return $result;
    }

    public function actionDownload()
    {
        $params = $this->getQuery();
        if (empty($params['url']) || empty($params['fileName'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        ob_end_clean();
        header("Content-Type: application/force-download;");
        // Force broswer download file, not open file
        header("Content-Transfer-Encoding: binary");
        header("Content-Disposition: attachment; filename='" . $params['fileName'] . "'");
        header("Expires: 0");
        header("Cache-control: private");
        header("Pragma: no-cache"); //不缓存页面
        readfile($params['url']);
    }
}
