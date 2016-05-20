<?php
//作業フォルダは /root/googleCal です。適宜変更を
//cronで回すのでrequire系は全部フルパスで書いてあります
require_once '/root/googleCal/vendor/autoload.php';


//認証周りはGoogle公式から取得 https://developers.google.com/google-apps/calendar/quickstart/php        
define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', '/root/.credentials/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', '/root/googleCal/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
));     
        
if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}
        
/**       
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */         
function getClient() { 
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfigFile(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {      
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));
                        
    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);
                
    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }   
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);
        
  // Refresh the token if it's expired. 
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}
          
/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}   
    
// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

// GoogleのユーザーIDの一覧を取得する。CSVの形式は 【Salesforceの登録E-mail,SalesforceのユーザID】
$file = new SplFileObject('/root/googleCal/googleAppsUserList.csv');
$file->setFlags(SplFileObject::READ_CSV); 
$uid = 0;
$recid = 0;
$updateEvents = array();
//CSVを一行ずつ取得して処理する
foreach ($file as $line) {
        $calendarId = $line[0];//Salesforceの登録E-mailを取得
        $OwnerId = $line[1];//SalesforceのユーザIDを取得
        if($calendarId){
        $optParams = array(
        //一度に取得するカレンダー情報量を20件に設定(SFに一度に投げられるレコード数上限が200なのでそれを超えないような数値を設定する事(200を超えるやり方があるぽいですが現状理解していない)
          'maxResults' => 20,
        //更新時間順に取得する(古い順)ただし、updateMinの所で過去5分以内の更新のみ取得するように制限してある
          'orderBy' => 'updated',
        //  'orderBy' => 'startTime', //startTimeにすると開始時間を昇順に取得してくれます
        //更新時間5分以内のレコードを取得する
          'updatedMin' => date('c',strtotime( "-5 min" )),
        //開始時間が現在以降の予定のみ取得する
          'timeMin' => date('c'),
        );
        $results = $service->events->listEvents($calendarId, $optParams);
        $uid++ ;

        if (count($results->getItems()) == 0) {
          print "No upcoming events found.\n";
        } else {
          foreach ($results->getItems() as $event) {
            $id = $event->id;
            $email = $calendarId;
            $gcaluID = $email.$id;//メアドとGoogleのイベントIDを組み合わせる理由は、GoogleのイベントIDは、一つの予定で複数人に招待を行った場合に同じものが割り振られる為
            $start = $event->start->dateTime;
            $end = $event->end->dateTime;

            if (empty($start)) {
                //カレンダー削除時は過去に飛ばしてみえなくする処理
                //カレンダーが削除されるとstartとendの時間が消える。
                //本当は消えた場合にSFのレコードを削除したいのだが、deleteをextraIDで実行出来なかったので苦肉の策
                $start = "2000-08-03T08:00:00.000Z";
                $end = "2000-08-04T08:00:00.000Z" ;
            }

//データの入れ方はこちらを参照：https://developer.salesforce.com/page/PHP_Toolkit_13.0_Getting_Started
        $sObject_Event[$recid] = new stdclass(); //必須
        $sObject_Event[$recid]->googleCalEventID2__c = $gcaluID;//Eventにカスタム項目を作成し、外部IDとして利用する・重複なしのフラグを立てる。これをキーにしてデータのupsertを行います
        $sObject_Event[$recid]->OWNERID = $OwnerId;
        $sObject_Event[$recid]->StartDateTime = $start;
        $sObject_Event[$recid]->EndDateTime = $end;
        $sObject_Event[$recid]->Subject = $event->getSummary();
        $sObject_Event[$recid]->Description = $event->description;
        $sObject_Event[$recid]->Location = $event->location;
        $sObject_Event[$recid]->WhatId = '0061000000xxxxxgAAM';//商談か何かに予定を紐付けないとSalesforce側の予定は他人が見えないという仕様なので、ダミー商談に紐付けています。IDは商談のIDに差し替えること
        $recid++ ;
         	}
        }
	}
}
// SFへのUPdate
// PHPのツールキットをダウンロードしてきて配置する：https://developer.salesforce.com/page/PHP_Toolkit
define("SOAP_CLIENT_BASEDIR", "/root/googleCal/Force.com-Toolkit-for-PHP/soapclient");
//SFのouth情報が入ったファイルを配置
require_once ('/root/googleCal/Force.com-Toolkit-for-PHP/outhy.php');
require_once ('/root/googleCal/Force.com-Toolkit-for-PHP/soapclient/SforceEnterpriseClient.php');

    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
    $mySforceConnection->login(USERNAME, PASSWORD);

	$upsertResponse = $mySforceConnection->upsert('googleCalEventID2__c',$sObject_Event, 'Event');

// レスポンスを見たい場合はコメントアウトを外す
// var_dump($upsertResponse);
