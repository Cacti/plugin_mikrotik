<?php

$no_http_headers = true;

/* display ALL errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_uptime", $_SERVER["argv"]);
}

function ss_mikrotik_uptime($hostid = "") {
	$cpu = db_fetch_cell("SELECT uptime/86400/100
		FROM plugin_mikrotik_system
		WHERE host_id=$hostid");

	return $cpu;
}

?>
