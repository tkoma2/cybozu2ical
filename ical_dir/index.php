<?php
$f = explode('_', $_REQUEST['data']);
$file = "{$f['0']}_{$f['1']}_{$f['2']}.ics";


header('Content-Type: text/calendar; charset=utf-8');
readfile($file);
