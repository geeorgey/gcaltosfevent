# gcaltosfevent
Googleカレンダーの予定をSalesforceの行動(Event)に同期する

こちらのファイルをサーバに保存してcronで回してください。
作業ディレクトリを /root/googleCal/ として作ってありますのでパスは適宜変えて下さい。

・php-soapが必要です。ない場合はインストールしてください。
・googleAppsUserList.csv というファイルが必要です。形式は
Salesforce(GoogleApps)の登録E-mail,SalesforceのユーザID
というカンマ区切りのファイルです。

