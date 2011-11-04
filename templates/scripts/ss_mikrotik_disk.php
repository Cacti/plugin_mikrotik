<?php

$no_http_headers = true;

/* display ALL errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_disk", $_SERVER["argv"]);
}

function ss_mikrotik_disk($hostid = "") {
	$disk = db_fetch_row("SELECT diskSize, diskUsed*diskSize AS diskUsed
		FROM plugin_mikrotik_system");

	if ($disk['diskSize'] == '') {
		$size = 'U';
	}else{
		$size = $disk['diskSize'];
	}

	if ($disk['diskUsed'] == '') {
		$used = 'U';
	}else{
		$used = $disk['diskUsed'];
	}

	return "used:$used size:$size";
}

?>
