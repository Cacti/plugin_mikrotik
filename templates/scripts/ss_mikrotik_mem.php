<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../../../include/global.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_mikrotik_mem', $_SERVER['argv']);
}

function ss_mikrotik_mem($host_id = '') {
	$disk = db_fetch_row_prepared('SELECT memSize, memUsed*memSize/100 AS memUsed
		FROM plugin_mikrotik_system
		WHERE host_id = ?', 
		array($host_id));

	if (sizeof($disk)) {
		if (isset($disk['memSize']) && $disk['memSize'] == '') {
			$size = 'U';
		}else{
			$size = $disk['memSize'];
		}

		if ($disk['memUsed'] == '') {
			$used = 'U';
		}else{
			$used = $disk['memUsed'];
		}

		return "used:$used size:$size";
	}else{
		return 'used:U size:U';
	}
}

