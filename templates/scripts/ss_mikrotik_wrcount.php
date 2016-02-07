<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_wrcount", $_SERVER["argv"]);
}

function ss_mikrotik_wrcount($hostid = "") {
	$wrs = db_fetch_cell("SELECT count(*)
		FROM plugin_mikrotik_wireless_registrations
		WHERE host_id=$hostid 
		AND unix_timestamp(last_seen)>unix_timestamp()-1200");

	if ($wrs == '') $wrs = 'U';

	return $wrs;
}

?>
