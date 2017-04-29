<?php
/*
 ex: set tabstop=4 shiftwidth=4 autoindent:
*/

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../../../include/global.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_mikrotik_qusers', $_SERVER['argv']);
}

function ss_mikrotik_qusers($host_id, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_mikrotik_qusers_getnames($host_id, $arg1);
		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == 'query') {
		$arr_index = ss_mikrotik_qusers_getnames($host_id, $arg1);
		$arr = ss_mikrotik_qusers_getinfo($host_id, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	}elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_mikrotik_qusers_getvalue($host_id, $index, $arg);
	}
}

function ss_mikrotik_qusers_getvalue($host_id, $index, $column) {
	switch ($column) {
	case 'curBytesIn':
	case 'curBytesOut':
	case 'curPacketsIn':
	case 'curPacketsOut':
	case 'connectTime':
		$value = db_fetch_cell_prepared("SELECT
			$column AS value
			FROM plugin_mikrotik_users
			WHERE name = ?
			AND host_id = ?", 
			array($index, $host_id));

		break;
	case 'avgBytesIn':
	case 'avgBytesOut':
	case 'avgPacketsIn':
	case 'avgPacketsOut':
		$column = str_replace('avgB', 'b', $column);
		$column = str_replace('avgP', 'p', $column);
		$value = db_fetch_cell_prepared("SELECT
			IF(connectTime>0,($column/connectTime),0) AS value
			FROM plugin_mikrotik_users
			WHERE name = ?
			AND host_id = ?", 
			array($index, $host_id));

		break;
	case 'bytesIn':
	case 'bytesOut':
	case 'packetsIn':
	case 'packetsOut':
		$value = db_fetch_cell_prepared("SELECT
			$column AS value
			FROM plugin_mikrotik_users
			WHERE name = ?
			AND host_id = ?", 
			array($index, $host_id));

		break;
	}

	if (!empty($value)) {
		return $value;
	}else{
		return '0';
	}
}

function ss_mikrotik_qusers_getnames($host_id) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared("SELECT DISTINCT name
		FROM plugin_mikrotik_users
		WHERE host_id = ? 
		AND name!=''
		ORDER BY name", 
		array($host_id));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['name'];
	}

	return $return_arr;
}

function ss_mikrotik_qusers_getinfo($host_id, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'name') {
		$arr = db_fetch_assoc_prepared("SELECT name AS qry_index,
			name AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id = ?
			AND name!=''
			ORDER BY name", 
			array($host_id));
	}elseif ($info_requested == 'domain') {
		$arr = db_fetch_assoc_prepared("SELECT name AS qry_index,
			domain AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id = ? 
			AND name!=''
			ORDER BY name", 
			array($host_id));
	}elseif ($info_requested == 'ip') {
		$arr = db_fetch_assoc_prepared("SELECT name AS qry_index,
			ip AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id = ? 
			AND name!=''
			ORDER BY name", 
			array($host_id));
	}elseif ($info_requested == 'mac') {
		$arr = db_fetch_assoc_prepared("SELECT name AS qry_index,
			mac AS qry_value
			FROM plugin_mikrotik_users
			WHERE host_id = ? 
			AND name!=''
			ORDER BY name", 
			array($host_id));
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = $arr[$i]['qry_value'];
	}

	return $return_arr;
}

