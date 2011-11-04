<?php

$no_http_headers = true;

/* display ALL errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_procs", $_SERVER["argv"]);
}

function ss_mikrotik_procs($hostid = "", $summary = "no") {
	if ($hostid == "" || $summary == "yes") {
		$procs = db_fetch_cell("SELECT SUM(processes)
			FROM plugin_mikrotik_system");
	}else{
		$procs = db_fetch_cell("SELECT processes
			FROM plugin_mikrotik_system
			WHERE host_id=$hostid");
	}

	if ($procs == '') $procs = 'U';

	return $procs;
}

?>
