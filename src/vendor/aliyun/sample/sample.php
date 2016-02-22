<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

require_once realpath(dirname(__FILE__) . '/../Sls_Autoload.php');

function putLogs(Aliyun_Sls_Client $client, $project, $logstore) {
    $topic = 'TestTopic';
    
    $contents = array( // key-value pair
        'TestKey'=>'TestContent'
    );
    $logItem = new Aliyun_Sls_Models_LogItem();
    $logItem->setTime(time());
    $logItem->setContents($contents);
    $logitems = array($logItem);
    $request = new Aliyun_Sls_Models_PutLogsRequest($project, $logstore, 
            $topic, null, $logitems);
    
    try {
        $response = $client->putLogs($request);
        var_dump($response);
    } catch (Aliyun_Sls_Exception $ex) {
        var_dump($ex);
    } catch (Exception $ex) {
        var_dump($ex);
    }
}

function listLogstores(Aliyun_Sls_Client $client, $project) {
    try{
        $request = new Aliyun_Sls_Models_ListLogstoresRequest($project);
        $response = $client->listLogstores($request);
        var_dump($response);
    } catch (Aliyun_Sls_Exception $ex) {
        var_dump($ex);
    } catch (Exception $ex) {
        var_dump($ex);
    }
}


function listTopics(Aliyun_Sls_Client $client, $project, $logstore) {
    $request = new Aliyun_Sls_Models_ListTopicsRequest($project, $logstore);
    
    try {
        $response = $client->listTopics($request);
        var_dump($response);
    } catch (Aliyun_Sls_Exception $ex) {
        var_dump($ex);
    } catch (Exception $ex) {
        var_dump($ex);
    }
}

function getLogs(Aliyun_Sls_Client $client, $project, $logstore) {
    $topic = 'TestTopic';
    $from = time()-3600;
    $to = time();
    $request = new Aliyun_Sls_Models_GetLogsRequest($project, $logstore, $from, $to, $topic, '', 100, 0, False);
    
    try {
        $response = $client->getLogs($request);
        var_dump($response);
    } catch (Aliyun_Sls_Exception $ex) {
        var_dump($ex);
    } catch (Exception $ex) {
        var_dump($ex);
    }
}

function getHistograms(Aliyun_Sls_Client $client, $project, $logstore) {
    $topic = 'TestTopic';
    $from = time()-3600;
    $to = time();
    $request = new Aliyun_Sls_Models_GetHistogramsRequest($project, $logstore, $from, $to, $topic, '');
    
    try {
        $response = $client->getHistograms($request);
        var_dump($response);
    } catch (Aliyun_Sls_Exception $ex) {
        var_dump($ex);
    } catch (Exception $ex) {
        var_dump($ex);
    }
}

$endpoint = '<sls_region_endpoint>';
$accessKeyId = '<your_access_key_id>';
$accessKey = '<your_access_key>';
$project = '<your_project_name>';
$logstore = '<your_logstore_name>';

$client = new Aliyun_Sls_Client($endpoint, $accessKeyId, $accessKey);
putLogs($client, $project, $logstore);
listLogstores($client, $project);
listTopics($client, $project, $logstore);
getHistograms($client, $project, $logstore);
getLogs($client, $project, $logstore);
