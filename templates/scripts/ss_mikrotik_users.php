<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_users", $_SERVER["argv"]);
}

function ss_mikrotik_users($hostid = "") {
	$users = db_fetch_cell("SELECT users
		FROM plugin_mikrotik_system
		WHERE host_id=$hostid");

	if ($users == '') $users = 'U';

	return $users;
}

?>
