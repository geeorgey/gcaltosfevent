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
  
Composerが必要です。インストールしてください。  
初回起動時にGoogleのアクセス認証が走ります。画面に出たURLをブラウザで開き、そこに現れるコードをコマンドラインに入力してください。  
  
その他の事など：https://geeorgey.com/archives/3307

update
2016.11.25
-全日予定の同期に対応
