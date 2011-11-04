<?php
/*
 ex: set tabstop=4 shiftwidth=4 autoindent:
*/

$no_http_headers = true;

/* display No errors */
error_reporting(E_ALL);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../../../../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_mikrotik_qusers", $_SERVER["argv"]);
}

function ss_mikrotik_qusers($hostid, $cmd = "index", $arg1 = "", $arg2 = "") {
	if ($cmd == "index") {
		$return_arr = ss_mikrotik_qusers_getnames($hostid, $arg1);
		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == "query") {
		$arr_index = ss_mikrotik_qusers_getnames($hostid, $arg1);
		$arr = ss_mikrotik_qusers_getinfo($hostid, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . "!" . $arr[$arr_index[$i]] . "\n";
			}
		}
	}elseif ($cmd == "get") {
		$arg = $arg1;
		$index = $arg2;
		return ss_mikrotik_qusers_getvalue($hostid, $index, $arg);
	}
}

function ss_mikrotik_qusers_getvalue($hostid, $index, $column) {
	global $config;

	switch ($column) {
	case "curBytesIn":
	case "curBytesOut":
	case "curPacketsIn":
	case "curPacketsOut":
	case "connectTime":
		$value = db_fetch_cell("SELECT
			$column AS value
			FROM plugin_mikrotik_users
			WHERE (name='$index'
			AND host_id='$hostid')");

		break;
	case "avgBytesIn":
	case "avgBytesOut":
	case "avgPacketsIn":
	case "avgPacketsOut":
		$column = str_replace("avgB", "b", $column);
		$column = str_replace("avgP", "p", $column);
		$value = db_fetch_cell("SELECT
			IF(connectTime>0,($column/connectTime),0) AS value
			FROM plugin_mikrotik_users
			WHERE (name='$index'
			AND host_id='$hostid')");

		break;
	case "bytesIn":
	case "bytesOut":
	case "packetsIn":
	case "packetsOut":
		$value = db_fetch_cell("SELECT
			$column AS value
			FROM plugin_mikrotik_users
			WHERE (name='$index'
			AND host_id='$hostid')");

		break;
	}

	if (!empty($value)) {
		return $value;
	}else{
		return "0";
	}
}

function ss_mikrotik_qusers_getnames($hostid) {
	$return_arr = array();

	$arr = db_fetch_assoc("SELECT DISTINCT name
		FROM plugin_mikrotik_users
		WHERE host_id='" . $hostid . "' AND name!=''
		ORDER BY name");

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["name"];
	}

	return $return_arr;
}

function ss_mikrotik_qusers_getinfo($hostid, $info_requested) {
	$return_arr = array();

	if ($info_requested == "name") {
		$arr = db_fetch_assoc("SELECT name AS qry_index,
			name AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id ='" . $hostid ."' AND name!=''
			ORDER BY name");
	}elseif ($info_requested == "domain") {
		$arr = db_fetch_assoc("SELECT name AS qry_index,
			domain AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id ='" . $hostid ."' AND name!=''
			ORDER BY name");
	}elseif ($info_requested == "ip") {
		$arr = db_fetch_assoc("SELECT name AS qry_index,
			ip AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id ='" . $hostid ."' AND name!=''
			ORDER BY name");
	}elseif ($info_requested == "mac") {
		$arr = db_fetch_assoc("SELECT name AS qry_index,
			mac AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id ='" . $hostid ."' AND name!=''
			ORDER BY name");
	}

	for ($i=0;($i<sizeof($arr));$i++) {
                $return_arr[$arr[$i]["qry_index"]] = addslashes($arr[$i]["qry_value"]);
	}

	return $return_arr;
}

?>
