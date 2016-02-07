<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_health", $_SERVER["argv"]);
}

function ss_mikrotik_health($hostid = "", $column = "no") {
	$value = db_fetch_cell("SELECT $column
		FROM plugin_mikrotik_system_health
		WHERE host_id=$hostid");

	if ($value == '') $value = 'U';

	return $value;
}

?>
