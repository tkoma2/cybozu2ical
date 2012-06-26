<?php
//サイボウズのURL
define('CYBOZU_URL',  'http://cybozu.xxx.jp/cgi-bin/cbag/ag.cgi');
//icalファイルを出力するローカルパス。外部からアクセスができるパスを設定する
define('ICAL_PATH',   '/data/sites/cybozu.xxx.jp/public/cybozu2ical/ical_dir/');
//ログインID(数字) ※ログイン名ではありません
define("LOGIN_ID",    '123');
//上記ログインIDユーザのログインパスワード
define("LOGIN_PASS",  'xxyyzz');
//詳細スケジュールを獲得する期間(日数)
define("DETAIL_TERM_PRE",   '1');   //今日より何日前から詳細スケジュールを取得する
define("DETAIL_TERM_AFTER", '14');  //今日より何日後まで詳細スケジュールを取得する
//スケジュール名のprefix
define("ICAL_PREFIX", '');
//イベントIDを一意にするためのポストフィックス。ドメイン名などを設定する。
define("ICAL_POSTFIX",'xxx.jp');
//icalファイル名作成のためのシード(ランダムな文字列を設定)
define("ICAL_SEED",   'axYlaztwOYx');
//サイボウズの文字コード
define("CHAR_CODE",   'SJIS');

// icalファイルを作成したいユーザリストを連想配列に設定する。
// ID(数字)をキーに設定。値はファイル名に使用される。
$userLists['123']	= 'suzuki';
$userLists['234']	= 'tanaka';


