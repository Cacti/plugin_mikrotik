<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_cpu", $_SERVER["argv"]);
}

function ss_mikrotik_cpu($hostid = "") {
	$cpu = db_fetch_row("SELECT AVG(`load`) AS avgCPU, 
		MAX(`load`) AS maxCPU
		FROM plugin_mikrotik_processor
		WHERE host_id=$hostid");

	return 'cpu:' . $cpu['avgCPU'] . ' max:' . $cpu['maxCPU'];
}

?>
