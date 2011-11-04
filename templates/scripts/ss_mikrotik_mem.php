<?php

$no_http_headers = true;

/* display ALL errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_mem", $_SERVER["argv"]);
}

function ss_mikrotik_mem($hostid = "") {
	$disk = db_fetch_row("SELECT memSize, memUsed*memSize AS memUsed
		FROM plugin_mikrotik_system");

	if ($disk['memSize'] == '') {
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
}

?>
