<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../../../include/global.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_mikrotik_qtrees', $_SERVER['argv']);
}

function ss_mikrotik_qtrees($host_id, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_mikrotik_qtrees_getnames($host_id, $arg1);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == 'query') {
		$arr_index = ss_mikrotik_qtrees_getnames($host_id, $arg1);
		$arr = ss_mikrotik_qtrees_getinfo($host_id, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	}elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_mikrotik_qtrees_getvalue($host_id, $index, $arg);
	}
}

function ss_mikrotik_qtrees_getvalue($host_id, $index, $column) {
	$return_arr = array();

	switch ($column) {
	case 'qtBytes':
		$column = 'curHCBytes';
		break;
	case 'qtPackets':
		$column = 'curPackets';
		break;
	}

	$index2 = str_replace('_', ' ', $index);

	$value = db_fetch_cell_prepared("SELECT
		$column AS value
		FROM plugin_mikrotik_trees
		WHERE name IN ('$index', '$index2', '<$index>')
		AND host_id = ?", 
		array($host_id));

	return $value;
}

function ss_mikrotik_qtrees_getnames($host_id) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared("SELECT REPLACE(name, ' ', '_') AS name
		FROM plugin_mikrotik_trees
		WHERE host_id = ?
		ORDER BY name", 
		array($host_id));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['name'];
	}

	return $return_arr;
}

function ss_mikrotik_qtrees_getinfo($host_id, $info_requested) {
	$return_arr = array();

	switch($info_requested) {
	case 'qtName':
		$column = 'name';
		break;
	case 'qtFlow':
		$column = 'flow';
		break;
	case 'qtParent':
		$column = 'parentIndex';
		break;
	}

	$arr = db_fetch_assoc_prepared("SELECT
		REPLACE(name, ' ', '_') AS qry_index,
		$column AS qry_value
		FROM plugin_mikrotik_trees
		WHERE host_id = ?
		ORDER BY name", 
		array($host_id));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = $arr[$i]['qry_value'];
	}

	return $return_arr;
}

