### Export CSV
Steps are as follows:

>  The format of the exported file is only supported by CSV, Because CSV is a kind of pure text file that is separated by special symbols. For example:Tabs, comma and so on. This format is easy to read and write;

## Verify the title of excel
First of all, We have to verify that the parameters are correct, to determine the title of the excel document and to support the international standard.

```
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
```

## Defines job
The job class that defines the export file.The reference method is as follows:

```
$exportArgs = [
    'key' => $key,
    'header' => $showHeader,
    'accountId' => (string)$accountId,
    'condition' => serialize($condition),
    'description' => 'Direct: export member',
    'params' => $params
];
$jobId = Yii::$app->job->create('backend\modules\member\job\ExportMember', $exportArgs);
```

## Write job
Define job class. Determine the current international language of the system. Query data according to the conditions;

## Create a temporary file
Call the following method. Create a temporary file. Determine the file name on the qiniu cloud is unique. Returns the path of the saved file；

```
$filePath = ExcelUtil::getFile($fileName, 'csv');
```

## Export data
Export data from a database and store it in a excel document in a temporary file.
The path to a temporary file is stored as:
 /home/user/aug-marketing/src/backend/runtime/temp/

* The key steps to export the data are as follows：
* 1. Defines the callback function.The callback function is used to prepare the data which queried according to the conditions.

```
$classFunction = '\backend\modules\member\models\Member::preProcessMemberData';
```

* 2. The callback function is invoked, Call once, return a piece of data to write the document after.

```
ExcelUtil::processMultiData($header, $filePath, $base, $condition, $object, $classFunction, [], $orderBy);
```

## Upload qiniu
```
$hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
```

## delete temporary file
```
@unlink($filePath);
```