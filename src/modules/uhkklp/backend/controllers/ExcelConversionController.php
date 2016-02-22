<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use backend\utils\LogUtil;
use backend\models\Token;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\Cookbook;
use backend\modules\uhkklp\models\Product;
use backend\modules\uhkklp\models\CookbookBatch;
use backend\models\User;
use backend\modules\uhkklp\models\KlpAccountSetting;


class ExcelConversionController extends BaseController
{
    public $enableCsrfValidation = false;

    private function _checkData($data)
    {
        /*foreach ($data as $phone) {
            if (!preg_match("/^09[0-9]{8}$/", (string)$phone)) {
                $wrongNums[] = $phone;
            }
        }
        if (!empty($wrongNums)) {
            return [
                'numError' => true,
                'wrongNums' => $wrongNums
            ];
        }*/
        return [];
    }

    public function actionToJson()
    {

        sleep(3);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $fileB64 = $request->post('fileB64');

        $file = base64_decode(substr($fileB64, strpos($fileB64,";base64,") + 8));

        $filePath = Yii::getAlias('@runtime') . '/namelist' . date('his');
        file_put_contents($filePath, $file);

        // $phpExcel = new \PHPExcel();
        $phpReader = new \PHPExcel_Reader_Excel2007();
        if (!$phpReader->canRead($filePath)){
            $phpReader = new \PHPExcel_Reader_Excel5();
            if (!$phpReader->canRead($filePath)){
                $phpReader = new \PHPExcel_Reader_CSV();
                if (!$phpReader->canRead($filePath)) {
                    unlink($filePath);
                    return ['fileError'=>true];
                }
            }
        }

        $phpExcel = $phpReader->load($filePath);
        $sheet = $phpExcel->getActiveSheet();
        $colCount = $sheet->getHighestColumn();
        $rowCount = $sheet->getHighestRow();

        $data = [];
        for($row = 1; $row <= $rowCount; $row++){
            for ($col = 'A'; $col <= $colCount; $col++) {
                $val = $sheet->getCellByColumnAndRow(ord($col) - 65, $row)->getValue();
                $val = (string)$val;
                if ($val) {
                    $accountId = $this->getAccountId();
                    $site = KlpAccountSetting::getAccountSite($accountId);
                    if ($site == 'TW') {
                        if ($val[0] !== '0') {
                            $val = '0' . $val;
                        }
                    }
                    $data[] = $val;
                }
            }
        }

        unlink($filePath);
        $numError = $this->_checkData($data);
        if (!empty($numError)) {
            return $numError;
        }
        return array_unique($data);
    }

    public function actionExportPushResult() {
        $messageId = Yii::$app->request->get('id');
        if (empty($this->getAccountId())) {
            return;
        }
        $accountId = (string)$this->getAccountId();
        $key = '推播结果' . date('YmdHis');
        $args = [
            'key' => $key,
            'messageId' => $messageId,
            'accoutnId' => $accountId
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportPushResult', $args);
        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionToCookbook()
    {
        sleep(2);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $fileB64 = $request->post('fileB64');

        $file = base64_decode(substr($fileB64, strpos($fileB64,";base64,") + 8));

        $filePath = Yii::getAlias('@runtime') . '/cookbook' . date('his');
        file_put_contents($filePath, $file);

        $phpReader = new \PHPExcel_Reader_Excel2007();
        if (!$phpReader->canRead($filePath)){
            $phpReader = new \PHPExcel_Reader_Excel5();
            if (!$phpReader->canRead($filePath)){
                $phpReader = new \PHPExcel_Reader_CSV();
                if (!$phpReader->canRead($filePath)) {
                    unlink($filePath);
                    return ['fileError'=> true];
                }
            }
        }

        $phpExcel = $phpReader->load($filePath);

        $sheets = $phpExcel->getAllSheets();

        $cookbookTitles = [];
        for ($si = 0; $si < sizeof($sheets); $si++) {
            $sheet = $sheets[$si];

            $rowTemp = [];
            $cowTemp = [];
            $ingredientFinished = false;

            $rowCount = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $colCount = ord($highestCol) - 65;
            $cookbook = [];

            //There has a bug
            //When the 'cuisineType' row does not exist, the $rowCount will be infinity
            //The code blow can avoid this bug
            $rowCount = $rowCount > 100 ? 100 : $rowCount;

            for($row = 1; $row <= $rowCount; $row++){
                for ($col = 0; $col <= $colCount; $col++) {
                    $val = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                    $val = trim((string)$val);
                    if ($val === '') {
                        continue;
                    }

                    // Fill title and image
                    if (!isset($cookbook['title'])) {
                       $arr = explode('-', $val, 2);
                       if (empty($arr) || sizeof($arr) < 2) {
                           unlink($filePath);
                           LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: title is required');
                           return ['contentError' => true];
                       }
                       if (mb_strlen(trim(trim($arr[1])), 'utf-8') > 30) {
                           unlink($filePath);
                           LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: title should less than 30 words');
                           return ['titleLengthError' => true];
                       }

                       $cookbook['image'] = Yii::$app->qiniu->domain . '/' . trim($arr[0]) . '.jpg';
                       $cookbook['title'] = trim(trim($arr[1]));
                       unset($arr);
                       continue;
                    }

                    // Find category row
                    if (!isset($rowTemp['category'])) {
                        if (!preg_match('/^category$/i', $val)) {
                            unlink($filePath);
                            LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: category is required');
                            return ['contentError' => true];
                        }
                        $rowTemp['category'] = $row;
                        continue;
                    }
                    // Fill category
                    if ($rowTemp['category'] === $row) {

                        //The first sheet's category row will leads to a bug
                        if ($si == 0) {
                            $firstCate = $val;
                        }

                        $arr = $this->_spiltByComma($val);

                        // $arr = preg_split('/[,，]/', $val);
                        $cookbook['category'] = [];
                        foreach ($arr as $v) {
                            $v = trim($v);
                            if ($v != '') {
                                $cookbook['category'][] = trim($v);
                            }
                        }
                        $row++;
                        $col = -1;
                        unset($arr);
                        continue;
                    }

                    // Find subCategory row
                    if (!isset($rowTemp['subCategory'])) {
                        if (!preg_match('/^sub[\s\n]*category$/i', $val)) {
                            unlink($filePath);
                            LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: subCategory is required');
                            return ['contentError' => true];
                        }
                        $rowTemp['subCategory'] = $row;
                        continue;
                    }
                    // Fill subCategory
                    if ($rowTemp['subCategory'] === $row) {
                        // $arr = preg_split('/[,，]/', $val);
                        $arr = $this->_spiltByComma($val);
                        $cookbook['subCategory'] = [];
                        foreach ($arr as $v) {
                            $v = trim($v);
                            if ($v != '') {
                                $cookbook['subCategory'][] = trim($v);
                            }
                        }
                        $row++;
                        $col = -1;
                        unset($arr);
                        continue;
                    }

                     // Find cuisineType row
                    if (!isset($rowTemp['cuisineType'])) {
                        if (preg_match('/^cuisine[\s\n]*type$/i', $val)) {
                            $rowTemp['cuisineType'] = $row;
                            continue;
                        } else {
                            $rowTemp['cuisineType'] = '';
                        }
                    }
                    // Fill cuisineType
                    if ($rowTemp['cuisineType'] === $row) {
                        // $arr = preg_split('/[,，]/', $val);
                        $arr = $this->_spiltByComma($val);
                        $cookbook['cuisineType'] = [];
                        foreach ($arr as $v) {
                            $v = trim($v);
                            if ($v != '') {
                                $cookbook['cuisineType'][] = trim($v);
                            }
                        }
                        $row++;
                        $col = -1;
                        unset($arr);
                        continue;
                    }

                    // Find yield row
                    if (!isset($rowTemp['yield'])) {
                        if (!preg_match('/^yield$/i', $val)) {
                            unlink($filePath);
                            LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: yield is required');
                            return ['contentError' => true];
                        }
                        $rowTemp['yield'] = $row;
                        continue;
                    }
                    // Fill yield
                    if ($rowTemp['yield'] === $row) {
                        if (!isset($cookbook['yield'])) {
                            $cookbook['yield'] = [];
                            $cookbook['yield']['Quantity'] = $val;
                        } else {
                            $cookbook['yield']['unit'] = $val;
                        }
                        continue;
                    }

                    // Find portionSize row
                    if (!isset($rowTemp['portionSize'])) {
                        if (!preg_match('/^portion[\s\n]*size$/i', $val)) {
                            unlink($filePath);
                            LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: portionSize is required');
                            return ['contentError' => true];
                        }
                        $rowTemp['portionSize'] = $row;
                        continue;
                    }
                    // Fill portionSize
                    if ($rowTemp['portionSize'] === $row) {
                        $cookbook['portionSize'] = $val;
                        $row++;
                        $col = -1;
                        continue;
                    }

                    //Find ingredient quantity colume
                    if (!isset($colTemp['idtQuantity'])) {
                        if (!preg_match('/^quantity$/i', $val)) {
                            unlink($filePath);
                            LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: ingredient quantity is required');
                            return ['contentError' => true];
                        }
                        $colTemp['idtQuantity'] = $col;
                        continue;
                    }

                    //Find ingredient unit colume
                    if (!isset($colTemp['idtUnit'])) {
                        if (!preg_match('/^unit$/i', $val)) {
                            unlink($filePath);
                            LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: ingredient unit is required');
                            return ['contentError' => true];
                        }
                        $colTemp['idtUnit'] = $col;
                        continue;
                    }

                    //Find ingredient name colume
                    if (!isset($colTemp['idtName'])) {
                        if (!preg_match('/^ingredient[\s\n]*name$/i', $val)) {
                            unlink($filePath);
                            LogUtil::error(date('Y-m-d h:i:s') . ' ukkklp parse excel to cookbook error: ingredient name is required');
                            return ['contentError' => true];
                        }
                        $colTemp['idtName'] = $col;
                        continue;
                    }

                    //Fill ingredient
                    if (!isset($cookbook['ingredient'])) {
                        $cookbook['ingredient'] = [];
                    }

                    if (!$ingredientFinished) {
                        // Fill ingredient quantity
                        if ($col === $colTemp['idtQuantity']) {
                            $cookbook['ingredient'][$row]['quantity'] = $val;
                        }

                        // Fill ingredient unit
                        if ($col === $colTemp['idtUnit']) {
                            $cookbook['ingredient'][$row]['unit'] = $val;
                        }

                        // Fill ingredient name
                        if ($col === $colTemp['idtName']) {
                            $cookbook['ingredient'][$row]['name'] = $val;
                        }
                        $ingredientFinished = preg_match('/^preparation[\s\n]*method$/i', $val);
                        if ($ingredientFinished) {
                            array_pop($cookbook['ingredient']);
                        }
                        continue;
                    }

                    // Find preparation method description colume
                    if (!isset($colTemp['ptnDescription'])) {
                        if (preg_match('/^description$/i', $val)) {
                            $colTemp['ptnDescription'] = $col;
                        }
                        continue;
                    }

                    //Fill preparation method
                    if (!isset($cookbook['preparationMethod'])) {
                        $cookbook['preparationMethod'] = [];
                    }

                    // Fill preparation method description
                    if ($col === $colTemp['ptnDescription']) {
                        $cookbook['preparationMethod'][$row]['description'] = [];
                        $cookbook['preparationMethod'][$row]['description']['step'] = $val;
                        /*$arr = preg_split('/靈感來源\s*or\s*貼心小提示/i', $val);
                        if (empty($arr) || sizeof($arr) !== 2) {
                            $cookbook['preparationMethod'][$row]['description']['step'] = $val;
                            $cookbook['preparationMethod'][$row]['description']['creativeExperience'] = '';
                        } else {
                            $cookbook['preparationMethod'][$row]['description']['step'] = trim($arr[0]);
                            $cookbook['preparationMethod'][$row]['description']['creativeExperience'] = trim($arr[1]);
                        }
                        unset($arr);*/
                        $row = $row + 2;
                        $col = -1;
                        continue;
                    }

                     // Find creativeExperience
                    if (!isset($cookbook['creativeExperience'])) {
                        if (preg_match('/^creative[\s\n]*experience$/i', $val)) {
                            $rowTemp['creativeExperience'] = $row;
                            continue;
                        }
                    }

                    //Fill creativeExperience
                    if (isset($rowTemp['creativeExperience'])) {
                        if ($rowTemp['creativeExperience'] == $row) {
                            $cookbook['creativeExperience'] = $val;
                            $row++;
                            $col = -1;
                            continue;
                        }
                    }

                    // Find deliciousSecret
                    if (!isset($cookbook['deliciousSecret'])) {
                        if (preg_match('/^delicious[\s\n]*secret$/i', $val)) {
                            $rowTemp['deliciousSecret'] = $row;
                            continue;
                        }
                    }

                    //Fill deliciousSecret
                    if (isset($rowTemp['deliciousSecret'])) {
                        if ($rowTemp['deliciousSecret'] == $row) {
                            $cookbook['deliciousSecret'] = $val;
                            $row++;
                            $col = -1;
                            continue;
                        }
                    }

                    // Find restaurantName
                    if (!isset($cookbook['restaurantName'])) {
                        if (preg_match('/^restaurant[\s\n]*name$/i', $val)) {
                            $rowTemp['restaurantName'] = $row;
                            continue;
                        }
                    }
                    
                    //Fill restaurantName
                    if (isset($rowTemp['restaurantName'])) {
                        if ($rowTemp['restaurantName'] == $row) {
                            $cookbook['restaurantName'] = $val;
                            $row++;
                            $col = -1;
                            continue;
                        }
                    }

                    // Find cookName
                    if (!isset($cookbook['cookName'])) {
                        if (preg_match('/^cook[\s\n]*name$/i', $val)) {
                            $rowTemp['cookName'] = $row;
                            continue;
                        }
                    }
                    
                    //Fill cookName
                    if (isset($rowTemp['cookName'])) {
                        if ($rowTemp['cookName'] == $row) {
                            $cookbook['cookName'] = $val;
                            $row++;
                            $col = -1;
                            continue;
                        }
                    }
                }
            }
            if (!isset($cookbook['ingredient']) || !isset($cookbook['preparationMethod'])) {
                unlink($filePath);
                return ['contentError' => true];
            }
            $cookbook['ingredient'] = array_values($cookbook['ingredient']);

            $tmpInfo = $this->_findProductUrlAndSave($cookbook['ingredient']);
            $cookbook['ingredient'] = $tmpInfo['ingredients'];
            unset($tmpInfo);

            $cookbook['preparationMethod'] = array_values($cookbook['preparationMethod']);

            for ($i=0; $i < sizeof($cookbook['ingredient']); $i++) {
                $cookbook['ingredient'][$i]['id'] = $this->_getRandomId();
            }
            $cookbook['content'] = $cookbook['preparationMethod'][0]['description']['step'];
            //$cookbook['creativeExperience'] = $cookbook['preparationMethod'][0]['description']['creativeExperience'];

            $cookbooks[] = $cookbook;
            unset($rowTemp);
            unset($colTemp);
            unset($cookbook);
        }

        unlink($filePath);

        if (empty($cookbooks)) {
            return [];
        }

        $results = Cookbook::saveImportedCookbooks($cookbooks, $this->getAccountId());

        $cookbookBatch = new CookbookBatch();
        $accessToken = Token::getToken();
        $user = User::findOne(['_id' => $accessToken->userId]);
        $cookbookBatch->operator = $user->name;
        $cookbookBatch->cookbooks = $results;
        $cookbookBatch->hasImages = false;
        $cookbookBatch->accountId = $this->getAccountId();
        $cookbookBatch->createdTime = new \MongoDate();
        $cookbookBatch->insert();

        return sizeof($results);
    }

    private function _getRandomId()
    {
        $s = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        $id = '';
        for ($i=0; $i < 10; $i++) {
            $n = rand(0, strlen($s) - 1);
            $id = $id . $s[$n];
        }
        return $id;
    }

    private function _spiltByComma($str)
    {
        $arr = [];
        $arrA = explode('，', $str);
        foreach ($arrA as $strA) {
            $arrB = explode(',', $strA);
            $arr = array_merge($arr, $arrB);
        }
        return $arr;
    }

    private function _findProductUrlAndSave($ingredients)
    {
        $data = [];
        for ($i = 0; $i < sizeof($ingredients); $i++) {
            $product = Product::getProductByName($ingredients[$i]['name'], $this->getAccountId());
            if (!empty($product)) {
                $ingredients[$i]['url'] = $product['url'];
            }
            unset($product);
        }
        $data['ingredients'] = $ingredients;
        return $data;
    }

}
