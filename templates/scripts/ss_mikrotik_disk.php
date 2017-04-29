<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../../../include/global.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_mikrotik_disk', $_SERVER['argv']);
}

function ss_mikrotik_disk($host_id = '') {
	$disk = db_fetch_row_prepared('SELECT diskSize, diskUsed*diskSize/100 AS diskUsed
		FROM plugin_mikrotik_system
		WHERE host_id = ?', 
		array($host_id));

	if (sizeof($disk)) {
		if (isset($disk['diskSize']) && $disk['diskSize'] == '') {
			$size = 'U';
		}else{
			$size = $disk['diskSize'];
		}

		if (isset($disk['diskUsed']) && $disk['diskUsed'] == '') {
			$used = 'U';
		}else{
			$used = $disk['diskUsed'];
		}

		return "used:$used size:$size";
	}else{
		return 'used:U size:U';
	}
}

