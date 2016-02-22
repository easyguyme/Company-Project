<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\utils\ExcelUtil;
use backend\models\Message;
use backend\models\Token;
use backend\modules\uhkklp\models\SampleRecord;
use backend\utils\LogUtil;

class ExportAllSampleRecord
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header'])) {
            ResqueUtil::log(['status' => 'fail to export slotgame prize statistic', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = self::getFile($fileName . date('YmdHis'), 'csv');
        $pIndex = 1;
        $accountId = new \MongoId($args['accountId']);
        $rows = SampleRecord::getAllSampleRecordExcelDate($accountId);
        self::exportCsv($header, $rows, $filePath, $pIndex);
        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            return false;
        }
        return false;
    }

    public static function getFile($fileName, $fileType = 'csv')
    {
        //make sure the file name is unqiue
        $fileName = Yii::$app->getRuntimePath() . '/temp/' . $fileName . '.' . strtolower($fileType);
        $filePath = dirname($fileName);
        if (!is_dir($filePath)) {
            FileHelper::createDirectory($filePath, 0777, true);
        }
        return $fileName;
    }

    public static function exportCsv($header, $rows, $filename, $pIndex, $options = [])
    {
        $options = self::_mergeOptions($options);
        if (file_exists($filename)) {
            $handle = fopen($filename, 'a+');
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $row = ExcelUtil::setRowValue($row, $header, $options);
                    fputcsv($handle, $row);
                    unset($row);
                }
                fclose($handle);
                unset($header, $filename, $pIndex, $options, $handle);
            }
        } else {
            $objPHPExcel = new \PHPExcel();
            if ($options['printHeader']) {
                $index = 0;
                foreach ($header as $item) {
                    $pCoordinate = ExcelUtil::setColumnIndex($index) . $pIndex;
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($pCoordinate, $item);
                    $index++;
                }
                $pIndex = $pIndex + 1;
            }
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $row = ExcelUtil::setRowValue($row, $header, $options);
                    $index = 0;
                    foreach ($header as $key => $value) {
                        $item = isset($row[$key]) ? $row[$key] : '';
                        $pCoordinate = ExcelUtil::setColumnIndex($index) . $pIndex;
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($pCoordinate, $item);
                        $index++;
                    }
                    $pIndex++;
                }
            }

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV')->setDelimiter($options['delimiter'])
                ->setEnclosure($options['quote'])
                ->setLineEnding($options['linefeed'])
                ->setSheetIndex(0)
                ->setUseBOM(true)
                ->save($filename);

            unset($objPHPExcel, $objWriter);
        }
        unset($rows);
    }

    private static function _mergeOptions($options)
    {
        $defaultOptions = [
            'printHeader' => true,
            'delimiter' => ',',
            'quote' => '"',
            'linefeed' => "\r\n",
            'changeTostring' => [],
        ];

        return array_merge($defaultOptions, $options);
    }
}
