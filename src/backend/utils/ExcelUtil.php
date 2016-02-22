<?php
namespace backend\utils;

use Yii;
use yii\helpers\FileHelper;
use backend\utils\LogUtil;
use backend\utils\StringUtil;
use yii\helpers\Json;
use MongoId;
use MongoRegex;
use MongoDate;

class ExcelUtil
{
    const HASH_TABLE_NAME = 'wm_export_file_hashs';
    const BATCH_COUNT = 1000;
    const FILE_NAME_TAG = ":fileName";//to flag the key for stote filename
    const FILE_SEPARATOR = '_';//used to separate a unique string and file name to make sure the file name is unique

    public static function exportCsv($header, $rows, $filename, $pIndex, $options = [])
    {
        if (empty($rows)) {
            return true;
        }
        $options = self::_mergeOptions($options);

        if (file_exists($filename)) {
            $handle = fopen($filename, 'a+');
            foreach ($rows as $row) {
                $row = self::setRowValue($row, $header, $options);
                fputcsv($handle, $row);
                unset($row);
            }
            fclose($handle);
            unset($header, $filename, $pIndex, $options, $handle);
        } else {
            LogUtil::info(['message' => 'Begin to create the first data', 'fileName' => $filename], 'resque');

            $objPHPExcel = new \PHPExcel();
            if ($options['printHeader']) {
                $index = 0;
                foreach ($header as $item) {
                    $pCoordinate = self::setColumnIndex($index) . $pIndex;
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($pCoordinate, $item);
                    $index++;
                }
                $pIndex = 2;
            }
            foreach ($rows as $row) {
                $row = self::setRowValue($row, $header, $options);
                $index = 0;
                foreach ($header as $key => $value) {
                    $item = isset($row[$key]) ? $row[$key] : '';
                    $pCoordinate = self::setColumnIndex($index) . $pIndex;
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($pCoordinate, $item);
                    $index++;
                }
                $pIndex++;
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

    /**
    * deal with every cell in a row to conver to what type you want
    * @param $row, array,to write in the file
    * @param $header, array, write in  the file to use header
    * @param $option,array, to define whitch cell to conver
    */
    public static function setRowValue($row, $header, $options)
    {
        $data = [];
        foreach ($header as $key => $value) {
            if (empty($options['changeTostring'])) {
                $options['changeTostring'] = ['month'];
            } else {
                $options['changeTostring'][] = 'month';
            }
            //change the number type to string
            if (in_array($key, $options['changeTostring']) && !empty($row[$key])) {
                $row[$key] = "'" . $row[$key];
            }
            $data[$key] = isset($row[$key]) ? $row[$key] : '';
            if (is_array($data[$key])) {
                $data[$key] = implode(',', array_values($data[$key]));
            }
        }
        unset($row, $header, $options);
        return $data;
    }

    /**
     * set column index for excel
     * @param $index,int
     */
    public static function setColumnIndex($index)
    {
        if ($index < 26) {
            $columnIndex = chr(65 + $index);
        } elseif ($index < 702) {
            $columnIndex = chr(64 + ($index / 26)) . chr(65 + $index % 26);
        } else {
            $columnIndex = chr(64 + (($index - 26) / 676))
            . chr(65 + ((($index - 26) % 676) / 26))
            . chr(65 + $index % 26);
        }
        return $columnIndex;
    }

    /**
     * @return string. file path
     * @param $fileName, string, file name
     * @param $fileType, string, file type
     */
    public static function getFile($fileName, $fileType = 'csv')
    {
        //make sure the file name is unqiue
        $fileName = StringUtil::uuid() . self::FILE_SEPARATOR . $fileName;
        $fileName = Yii::$app->getRuntimePath() . '/temp/' . $fileName . '.' . strtolower($fileType);
        $filePath = dirname($fileName);
        if (!is_dir($filePath)) {
            FileHelper::createDirectory($filePath, 0777, true);
        }
        return $fileName;
    }

    /**
     * get new file for write different from the orign file
     * @return string
     * @param $filePath, string, file path
     */
    public static function getDownloadFile($filePath)
    {
        return dirname($filePath) . '/download_' . basename($filePath);
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

    /**
     * set the key to upload file to qiniu
     * check the content for file whether or not to change ,
     * if change i upload file to qiniu,
     * otherwise i will check the file whether or not in qiniu,
     * if the file is deleted in qiniu,i will upload file again,
     * otherwise i just delete file in locality
     * @param $filePath,string,file path
     * @param $key,string,file key used as filename
     */
    public static function setQiniuKey($filePath, $key, $type = 'csv')
    {
        if (!file_exists($filePath)) {
            LogUtil::error(['message' => 'File not exists', 'fileName' => $filePath], 'qiniu');
            return false;
        }

        LogUtil::info(['message' => 'Begin to check file to qiniu', 'fileName' => $filePath], 'resque');

        $uploadFile = $key . '.csv';
        if (!empty($type)) {
            $uploadFile = $key . '.' . $type;
        }

        Yii::$app->qiniu->change2Private();
        list($result, $error) = Yii::$app->qiniu->getFileInfo($uploadFile);

        LogUtil::info(['message' => 'End to check file to qiniu', 'fileName' => $filePath], 'resque');

        $redis = Yii::$app->cache->redis;
        $fileHash = md5_file($filePath);
        $fileValue = $redis->HGET(self::HASH_TABLE_NAME, $uploadFile);

        if (empty($error) && $fileValue == $fileHash) {
            LogUtil::info(['message' => 'File is exists in qiniu,no need to upload file again', 'fileName' => $filePath], 'resque');
            //file is exist in qiniu,so need to check the content whether or not to change
            $result = true;
        } else {
            LogUtil::info(['message' => 'Begin to upload file to qiniu', 'fileName' => $filePath], 'resque');

            $result = Yii::$app->qiniu->upload($filePath, $uploadFile, true);
            if (!empty($result->Err)) {
                LogUtil::error(['message' => 'fail to upload file to qiniu', 'result' => json_encode($result), 'fileName' => $filePath], 'qiniu');
                $result = false;
            } else {
                $redis->HSET(self::HASH_TABLE_NAME, $uploadFile, $fileHash);
            }

            LogUtil::info(['message' => 'End to upload file to qiniu', 'fileName' => $filePath], 'resque');
        }
        @unlink($filePath);
        return $result;
    }
    /**
     * add other data into the source data
     * @param $header,array,the value from header is uesed to header of excel and key is used to write data
     * @param $filePath,string,file path
     * @param $args,array,add to source data
     * @param $condition,array,condition for search
     * @param $object,object,the modole object
     * @param $classFunction,string,the method to be called
     * @param $options,array,the option for csv
     */
    public static function processMultiData($header, $filePath, $args, $condition, $object, $classFunction, $options = [], $order = ['createdAt' => SORT_ASC])
    {
        LogUtil::info(['message' => 'Begin to prepare data in model', 'fileName' => $filePath], 'resque');

        $query = $object->where($condition)->orderBy($order);
        $offset = 0;
        $query->offset($offset)->limit(self::BATCH_COUNT);
        $data = $query->all();

        $pIndex = 1;
        while (!empty($data)) {
            $data = call_user_func_array($classFunction, [$data, $args]);
            self::exportCsv($header, $data, $filePath, $pIndex, $options);
            unset($data);
            $offset += self::BATCH_COUNT;
            $pIndex = $offset + 2;
            $query->offset($offset)->limit(self::BATCH_COUNT);
            $data = $query->all();
        }

        LogUtil::info(['message' => 'End to prepare data in model and end to write file', 'fileName' => $filePath], 'resque');
    }

    public static function processData($header, $filePath, $classFunction, $condition)
    {
        LogUtil::info(['message' => 'Begin to prepare data in model', 'fileName' => $filePath], 'resque');

        $data = call_user_func_array($classFunction, [$condition]);
        self::exportCsv($header, $data, $filePath, 1);

        LogUtil::info(['message' => 'End to prepare data in model and end to write file', 'fileName' => $filePath], 'resque');
        unset($data, $header, $filePath);
    }

    /**
     * process every rows data whitch read from file
     * @param $header, array, write use csv header title
     * @param $filePath, string, file path
     * @param $classFunction, string, recall functuion
     * @param $params, array, pass to recall function as argments
     */
    public static function processRowsData($header, $filePath, $classFunction, $params = [])
    {
        LogUtil::info(['message' => 'Begin to read csv file', 'fileName' => $filePath], 'resque');

        $fileInfos = fopen($filePath, "r");
        $newFilePath = self::getDownloadFile($filePath);
        while (!feof($fileInfos)) {
            $fileInfo = Json::decode(fgets($fileInfos), true);
            if (!empty($fileInfo)) {
                $data = call_user_func_array($classFunction, [$fileInfo, $params]);
                //fputcsv($handle, $data);
                if (!isset($data[0])) {
                    $data = [$data];
                }
                ExcelUtil::exportCsv($header, $data, $newFilePath, 1);
                unset($data);
            }
        }
        fclose($fileInfos);

        LogUtil::info(['message' => 'End to read csv file and end to write file', 'fileName' => $filePath], 'resque');
    }

    /**
     * use mongoexport to export data,this function will export a file in filePath whitch you specified
     * you can (mongoexport --help) get more infomations
     * @param $collection, string, collection name
     * @param $field, string, export field name, comma separated list of field names (required for exporting CSV) e.g. -f "name,age"
     * @param $filePath, string, file path,you must define the file extension
     * @param $condition, array, query filter, as a JSON string, e.g., '{x:{$gt:1}}'
     * @param $sort, as a JSON string, e.g., '{x:{$gt:1}}'
     */
    public static function exportWithMongo($collection, $fields, $filePath, $condition, $sort = [])
    {
        $mongoHost = MONGO_HOST . ':' . MONGO_PORT;
        $mongoUser = MONGO_USER_NAME;
        $mongoPass = MONGO_USER_PASSWORD;
        $mongoDB = MONGO_DATABASE;

        $command = "mongoexport -h '$mongoHost' -u '$mongoUser' -p '$mongoPass' -d '$mongoDB' -c '$collection' -f '$fields' -o '$filePath' -q '$condition'";
        //note:if the data is too large,you need to drop to sort the data,otherwise the mongoexport will throw a exception
        if (!empty($sort)) {
            $sort = self::processSort($sort);
            $command .= " --sort '$sort'";
        }
        LogUtil::info(['message' => 'Begin to export data use mongoexport', 'condition' => $collection, 'command' => $command, 'fileName' => $filePath], 'resque');
        system($command);
        LogUtil::info(['message' => 'End to export data use mongoexport', 'condition' => $collection, 'command' => $command, 'fileName' => $filePath], 'resque');

        //test user,if no define ower,this file need root authority
        if (CURRENT_ENV == 'local') {
            LogUtil::info(['message' => 'change file ower and group'], 'resque');
            chown($filePath, 'user');
            chgrp($filePath, 'user');
        }
    }

    /**
     * process condition for mongoexport
     * @return string
     * @param $condition, array
     */
    public static function processCondition($conditions)
    {
        //conver the first tag
        $query = '{';
        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                if (MongoId::isValid($value)) {
                    //check mongoId
                    $query .= self::converMongoIdToObjectId([$key => $value]);
                } else if ($value instanceof MongoRegex) {
                    $query .= self::converMongoRegexToRegex([$key => $value]);
                } else if ($value instanceof MongoDate) {
                    //check mongo date
                    $query .= self::converMongoDateToDate([$key => $value]);
                    $query = rtrim($query, ',') . '},';
                } else {
                    $query .= '"' . $key . '":' . Json::encode($value) . ',';
                }
            }
            $query = rtrim($query, ',') . '}';
        }
        //conver the mongo type with recursion
        //replace $id to objectId
        $query = self::converMongoIdToObjectId($query);
        //replace mongodate to time
        $query = self::converMongoDateToDate($query);
        //replace regex to MongoRegex
        $query = self::converMongoRegexToRegex($query);
        LogUtil::info(['message' => 'mongo condition', 'query' => $query], 'resque');

        return $query;
    }

    private static function converMongoRegexToRegex($query)
    {
        if (is_array($query)) {
            $key = array_keys($query)[0];
            $value = array_values($query)[0];

            $flags = isset($value->flags) ? $value->flags . ',' : ',';
            $data = '"' . $key . '":/' . $value->regex . '/' . $flags;
        } else {
            preg_match_all('/\{"([^"]+)":(\{"regex":".*?","flags":".*?"\})\}/', $query, $matches);

            $replaces = isset($matches[0]) ? $matches[0] : [];
            $names = isset($matches[1]) ? $matches[1] : [];
            $values = isset($matches[2]) ? $matches[2] : [];

            foreach ($replaces as $key => $search) {
                $value = Json::decode($values[$key], true);
                $replace = '{"'. $names[$key] .'":/' . $value["regex"] . '/' . $value["flags"] . '}';
                $query = str_replace($search, $replace, $query);
            }
            $data = $query;
        }
        return $data;
    }

    private static function converMongoDateToDate($query)
    {
        $data = '';
        if (is_array($query)) {
            $key = array_keys($query)[0];
            $value = array_values($query)[0];

            $indexs = array_keys($value);
            $data = '"' . $key . '":{';
            foreach ($indexs as $index) {
                $time = $value[$index]->sec * 1000 + $value[$index]->usec / 1000;
                $data .= '"' . $index . '":new Date(' . $time . '),';
            }
        } else {
            preg_match_all('/\{"sec":(\d{10}),"usec":(\d{1,6})\}/', $query, $matches);
            if (!empty($matches)) {
                $replace = [];
                foreach ($matches[1] as $k => $v) {
                    $usec = intval($matches[2][$k]);
                    $v = intval($v) * 1000 + $usec / 1000;
                    $replace[] = 'new Date('. $v .')';
                }
                $query = str_replace($matches[0], $replace, $query);
            }
            $data = $query;
        }
        return $data;
    }

    private static function converMongoIdToObjectId($query)
    {
        $data = '';
        if (is_array($query)) {
            $keys = array_keys($query)[0];
            $values = array_values($query)[0];
            $data = '"' . $keys . '":ObjectId("' . (string)$values . '"),';
        } else {
            preg_match_all('/(\{"\$id":"(\w{24})"\})/', $query, $matches);

            if (!empty($matches)) {
                $replace = [];
                foreach ($matches[2] as $v) {
                    $replace[] = 'ObjectId("' . $v . '")';
                }
                $query = str_replace($matches[1], $replace, $query);
            }
            $data = $query;
        }
        return $data;
    }

    /**
     * process sort
     * @return string
     * @param $sort, array
     */
    public static function processSort($sort)
    {
        return Json::encode($sort);
    }
}
