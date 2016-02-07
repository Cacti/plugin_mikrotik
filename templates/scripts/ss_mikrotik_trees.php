<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_trees", $_SERVER["argv"]);
}

function ss_mikrotik_trees($hostid = "") {
	$trees = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_trees
		WHERE host_id=$hostid");

	if ($trees == '') $trees = 'U';

	return $trees;
}

?>
