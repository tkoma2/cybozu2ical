#!/usr/env/bin php

<?php
require_once('setting.php');

$query_str = '_System=login&_Login=1&LoginMethod=1&_ID=' . LOGIN_ID . '&Password=' . LOGIN_PASS;


//headerにセット
$header = array(
    "Content-Type: application/x-www-form-urlencoded",
    "Content-Length: ".strlen($query_str)
);
$context = array(
    "http" => array(
        "method"  => "POST",
        "header"  => implode("\r\n", $header),
        "content" => $query_str
    )
);


$pattern = '/<div class="eventLink scheduleMarkTitle0" name=".*?">.*?<\/div>/';
$pattern2 = '/<span class="eventLink scheduleMarkTitle0">.*?<\/span>/';
foreach($userLists as $uid => $name) {
	$url = CYBOZU_URL . "?page=ScheduleUserMonth&UID={$uid}";
	$urlp = fopen($url, 'r', false, stream_context_create($context));

	$ical_str = '';
	$preline = '';
	while(!feof($urlp)) {
		$line = fgets($urlp);
		if(strpos($line, 'scheduleMarkTitle0')) {
			$line = mb_convert_encoding(trim($line), "UTF-8", CHAR_CODE);
			$ret = preg_match_all($pattern, $line, $eventLinks, PREG_SET_ORDER)||preg_match_all($pattern2, $line, $eventLinks, PREG_SET_ORDER);
			foreach($eventLinks as $matches) {
				$preline = mb_convert_encoding(trim($preline), "UTF-8", CHAR_CODE);
				$ical_str .= tag2ical($matches[0], $preline, $context);
			}
		}
		$preline = $line;
	}

	if ($ical_str <> '') {
		$fname = "{$uid}_{$name}_" . sha1($uid . ICAL_SEED) . '.ics';
		$fp = fopen(ICAL_PATH . $fname, 'w');
		fwrite($fp, ical_header($name));
		fwrite($fp, $ical_str);
		fwrite($fp, ical_footer());
		fclose($fp);
	}
	fclose($urlp);
}
exit;



function getDescription($url, $context) {
	$work2 = file_get_contents(CYBOZU_URL . $url, false, stream_context_create($context));
	$work2 = mb_convert_encoding($work2, "UTF-8", CHAR_CODE);
	$work2 = str_replace("\r\n","", $work2);
   	$work2 = str_replace("\n","", $work2);
   	$work2 = str_replace("\r","", $work2);

	$description = '';
	$LOCATION = '';

	// place
	$pattern4desc = '/<th align="left" nowrap>設備<\/th>.*?<td>(.+?)<\/td>/';
	if (preg_match($pattern4desc, $work2, $matches4desc)) {
		$add = $matches4desc[1];
		$add = str_replace(' ', "", $add);
		$add = str_replace('<br>', "\\n\r\n ", $add);
		$add = trim(strip_tags($add));
		if (str_replace(array("\\n", ' '),'', $add) <> '') {
			$description .= "\\n\r\n ============================(設備)\\n\r\n {$add}";
			if (strpos($add, '508')) $LOCATION .= "[ASC]";
			if (strpos($add, '505')) $LOCATION .= "[SNA]";
			if (strpos($add, '506')) $LOCATION .= "[PC]";
			if (strpos($add, '500')) $LOCATION .= "[STG]";
			if (strpos($add, '502')) $LOCATION .= "[YST]";
			if (strpos($add, '503')) $LOCATION .= "[DL]";
			if (strpos($add, '507')) $LOCATION .= "[GLR]";
		}
	}
	// memo
	$pattern4desc = '/<th align="left" nowrap>メモ<\/th>.*?<td>(.*?)<\/td>/';
	if (preg_match($pattern4desc, $work2, $matches4desc)) {
		$add = $matches4desc[1];
#		$add = str_replace(' ', "", $add);
#		$add = str_replace('<br>', "\r\n ", $add);
		$add = str_replace('<br>', "\\n\r\n ", $add);
		$add = trim(strip_tags($add));
#		if ($add <> '') {
#		if (preg_match("/^\w+$/", $add)) {
		if (str_replace(array("\\n", ' '),'', $add) <> '') {
			$description .= "\\n\r\n ============================(メモ)\\n\r\n {$add}";
		}
	}
	// member
	$pattern4desc = '/<td class="participants">(.+?)<\/td>/';
	if (preg_match($pattern4desc, $work2, $matches4desc)) {
		$add = $matches4desc[1];
#		$add = str_replace(' ', "", $add);
		$add = str_replace('<br>', "\\n\r\n ", $add);
		$add = trim(strip_tags($add));
		$add = str_replace('…（↓参加者をすべて表示する）', '', $add);
		$add = str_replace('（↑参加者を隠す）', "", $add);
		$add = trim($add);
#		if (preg_match("/^\w+$/", $add)) {
		if (str_replace(array("\\n", ' '),'', $add) <> '') {
			$description .= "\\n\r\n ============================(メンバー)\\n\r\n {$add}";
		}
	}
	//follow
	$pattern4desc = '/<tr class="followRow" valign="top">.*?<table .*?>.*?<\/table>.*?<\/tr>(.+?)<\/table>/';
	if (preg_match($pattern4desc, $work2, $matches4desc)) {
		$add = $matches4desc[1];
		$add = str_replace(' ', "", $add);
		$add = str_replace("\t", "", $add);
		$add = str_replace('<br>', "\\n\r\n ", $add);
		$add = str_replace('&nbsp;', "\\n\r\n ", $add);
		$add = trim(strip_tags($add));
#		if (preg_match("/^\w+$/", $add)) {
		if (str_replace(array("\\n", ' '),'', $add) <> '') {
			$description .= "\\n\r\n ============================(フォロー)\\n\r\n {$add}";
		}
	}

	$ret = array();
	$ret['location'] = $LOCATION;
	$ret['description'] = $description;
	return $ret;
}


// line break at every 75 octet.
function linebreak($line) {
	$ret = '';
	$width = 75;
	$all_count = mb_strwidth($line);

	for($i = 0; $i < ceil(mb_strwidth($line) / $width); $i++ ) {
		$ret .= mb_strcut($line, $width * $i, $width, 'UTF-8') . "\r\n ";
	}

	return $ret;
}


function tag2ical($tag, $preline, $context) {
	$ICSDATA = '';
	$DTSTAMP = date('Ymd') . 'T' . date('His') . "Z";
	$matches = array();
	$pattern2 = '/<a class="event" href="ag.cgi?(.*?)".+<span class="eventTitle">(.+?)<\/span>/';
	$pattern22 = '/<a class="bannerevent" href="ag.cgi?(.*?)".+title="(.+?)">/';
	$pattern3 = '/&Date=da.([0-9,.]+?)&.*&sEID=(\d+?)&/';

	if (preg_match($pattern2, $tag, $matches)||preg_match($pattern22, $tag, $matches)) {
		$url4description = $matches[1];
		$eventTitle = $matches[2];
#print $url."\n";
		if (preg_match($pattern3, $url4description, $matches2)) {
			$thistime = strtotime(str_replace('.', '/', $matches2[1]));
			$Ymd = date('Ymd', $thistime);
			$eid = $Ymd . '-' . $matches2[2];

			$eventTitleTmp = explode('&nbsp;', $eventTitle);
			if (count($eventTitleTmp) > 1) {
				$summary = $eventTitleTmp[1];
				list($from, $to) = explode('-',$eventTitleTmp[0]);

				if (strpos($from, ':')){
					list($m,$s) = explode(':', $from);
					$dtstart = "DTSTART;TZID=Asia/Tokyo:{$Ymd}T" . sprintf("%02d", $m) . sprintf("%02d", $s) . '00';
				} else {
					$dtstart = "DTSTART;TZID=Asia/Tokyo:{$Ymd}T000000";
				}
				if (strpos($to, ':')){
					list($m,$s) = explode(':', $to);
					$dtend = "DTEND;TZID=Asia/Tokyo:{$Ymd}T" . sprintf("%02d", $m) . sprintf("%02d", $s) . '00';
				} else {
					$dtend = "DTEND;TZID=Asia/Tokyo:{$Ymd}T240000";
				}
			}
			else{
				$summary = $eventTitleTmp[0];

				$Ymd_tommorow = date('Ymd', $thistime+24*60*60);

				$dtstart = "DTSTART;VALUE=DATE:".$Ymd;
				$dtend   = "DTEND;VALUE=DATE:".$Ymd_tommorow;

				//for banner event.
				if(strpos($tag, 'bannerevent')) {
					if(preg_match('/colspan="(\d+?)"/', $preline, $matches)) {
						$Ymd_tommorow = date('Ymd', $thistime+24*60*60*intval($matches[1]));
						$dtend   = "DTEND;VALUE=DATE:".$Ymd_tommorow;
						
					}
				}
			}

			// 詳細取得 昨日以降、DETAIL_TERM_AFTER 日先までの予定は詳細を取得する。
			$LOCATION = '';
			$description = $summary;
			if ($thistime >= strtotime('Today')-24*60*60*DETAIL_TERM_PRE && $thistime <= strtotime('Today')+24*60*60*DETAIL_TERM_AFTER) {
				$descs = getDescription($url4description, $context);
				$LOCATION		= $descs['location'];
				$description	= $descs['description'];
			}

			$ICSDATA  = 'BEGIN:VEVENT'. "\r\n";
			$ICSDATA .= 'UID:' . $eid . "@" . ICAL_POSTFIX . "\r\n";
			$ICSDATA .= 'SUMMARY:'. trim($summary). "\r\n";
			$ICSDATA .= 'DESCRIPTION:' . $description . "\r\n";
			$ICSDATA .= 'LOCATION:' . $LOCATION . "\r\n";
			$ICSDATA .= $dtstart . "\r\n";
			$ICSDATA .= $dtend . "\r\n";
			$ICSDATA .= "DTSTAMP:{$DTSTAMP}\r\n";
			$ICSDATA .= 'END:VEVENT'. "\r\n";
		}
	}
	return $ICSDATA;
}



function ical_header($name) {
$ICSDATA_H  = '';
$ICSDATA_H .= 'BEGIN:VCALENDAR'. "\r\n";
$ICSDATA_H .= 'PRODID:' . ICAL_PREFIX. "{$name}\r\n";
$ICSDATA_H .= 'VERSION:2.0'. "\r\n";
$ICSDATA_H .= 'METHOD:PUBLISH'. "\r\n";
$ICSDATA_H .= 'CALSCALE:GREGORIAN'. "\r\n";
$ICSDATA_H .= 'X-WR-CALNAME:' . ICAL_PREFIX . "{$name}\r\n";
$ICSDATA_H .= 'X-WR-CALDESC:' . ICAL_PREFIX . "{$name}\r\n";
$ICSDATA_H .= 'X-WR-TIMEZONE:Asia/Tokyo'. "\r\n";
return $ICSDATA_H;
}

function ical_footer() {
$ICSDATA_F  = 'BEGIN:VTIMEZONE'. "\r\n";
$ICSDATA_F .= 'TZID:Asia/Tokyo'. "\r\n";
$ICSDATA_F .= 'BEGIN:STANDARD'. "\r\n";
$ICSDATA_F .= 'DTSTART:19700101T000000'. "\r\n";
$ICSDATA_F .= 'TZOFFSETFROM:+0900'. "\r\n";
$ICSDATA_F .= 'TZOFFSETTO:+0900'. "\r\n";
$ICSDATA_F .= 'END:STANDARD'. "\r\n";
$ICSDATA_F .= 'END:VTIMEZONE'. "\r\n";
$ICSDATA_F .= 'END:VCALENDAR'. "\r\n";
return $ICSDATA_F;
}




