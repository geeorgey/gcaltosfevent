# gcaltosfevent
Googleカレンダーの予定をSalesforceの行動(Event)に同期する

こちらのファイルをサーバに保存してcronで回してください。  cronは5分おきに実行される想定になっていますが、もう少し間隔を開けたい場合は  
'updatedMin' => date('c',strtotime( "-5 min" )),  
こちらの設定を適宜変えて下さい。  
  
作業ディレクトリを /root/googleCal/ として作ってありますのでパスは適宜変えて下さい。  
  
・php-soapが必要です。ない場合はインストールしてください。  
・googleAppsUserList.csv というファイルが必要です。形式は  
Salesforce(GoogleApps)の登録E-mail,SalesforceのユーザID  
というカンマ区切りのファイルです。

