<?php

$no_http_headers = true;

/* display ALL errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_users", $_SERVER["argv"]);
}

function ss_mikrotik_users($hostid = "", $summary = "no") {
	if ($hostid == "" || $summary == "yes") {
		$users = db_fetch_cell("SELECT SUM(users)
			FROM plugin_mikrotik_system");
	}else{
		$users = db_fetch_cell("SELECT users
			FROM plugin_mikrotik_system
			WHERE host_id=$hostid");
	}

	if ($users == '') $users = 'U';

	return $users;
}

?>
