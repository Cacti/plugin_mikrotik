<?php

$no_http_headers = true;

/* display ALL errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_cpu", $_SERVER["argv"]);
}

function ss_mikrotik_cpu($hostid = "", $summary = "no") {
	if ($hostid == "" || $summary == "yes") {
		$cpu = db_fetch_row("SELECT AVG(cpuPercent) AS avgCpu, 
			MAX(cpuPercent) AS maxCpu
			FROM plugin_mikrotik_system");

		return 'avg:' . $cpu['avgCpu'] . ' max:' . $cpu['maxCpu'];
	}else{
		$cpu = db_fetch_cell("SELECT cpuPercent
			FROM plugin_mikrotik_system
			WHERE host_id=$hostid");

		return $cpu;
	}
}

?>
