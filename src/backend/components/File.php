<?php
namespace backend\components;

use Yii;
use yii\base\Component;
use backend\utils\LogUtil;
use backend\modules\resque\components\ResqueUtil;

/**
 * File class is used edit file format for uploading file.
 *
 * @author Lydia Li
 */
class File extends Component
{
    public function setCurlFile($file)
    {
        $result = '';
        if (!empty($file)) {
            $result = "@{$file["tmp_name"]};type={$file['type']};filename={$file['name']}";
        }
        return $result;
    }

    /**
     * Read excel and converted into an array.
     * The first row should contain the array keys.
     *
     * Example:
     *
     * @param PHPExcel_Reader_Excel2007 $phpreader
     * @param file $phpexcel
     * @param string $filePath
     * @return array
     */
    public function excelToArray($phpreader, $phpexcel, $filePath)
    {
        $data = [];
        $phpexcel = $phpreader->load($filePath);
        $currentSheet = $phpexcel->getSheet(0);
        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();
        ResqueUtil::log(['allRow:' => $allRow, 'allColumn:' => $allColumn]);

        $allColumnNum = \PHPExcel_Cell::columnIndexFromString($allColumn);
        for ($rowIndex = 1; $rowIndex <= $allRow; $rowIndex++) {
            $columnValue = [];
            for ($columnIndex = 0; $columnIndex < $allColumnNum; $columnIndex++) {
                $columnString =  \PHPExcel_Cell::stringFromColumnIndex($columnIndex);
                $value = trim((string)$currentSheet->getCell($columnString.$rowIndex)->getValue());
                $columnValue[] = $value;
            }
            $data[] = $columnValue;
        }
        return $data;
    }
}
