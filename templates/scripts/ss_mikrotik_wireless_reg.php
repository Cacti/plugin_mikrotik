<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die('<br><strong>This script is only meant to run at the command line.</strong>');
}

global $config;

$no_http_headers = true;

/* display No errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../../../include/global.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_mikrotik_wireless_reg', $_SERVER['argv']);
}

function ss_mikrotik_wireless_reg($host_id, $cmd, $object = '', $index = '') {
	if ($cmd == 'index') {
		$return_arr = db_fetch_assoc_prepared('SELECT `index` AS macAddress
			FROM plugin_mikrotik_wireless_registrations
			WHERE host_id = ?
			ORDER BY macAddress',
			array($host_id));

		foreach($return_arr as $index) {
			print $index['macAddress'] . "\n";
		}
	} elseif ($cmd == 'num_indexes') {
		$indexes = db_fetch_cell_prepared('SELECT COUNT(*)
			FROM plugin_mikrotik_wireless_registrations
			WHERE host_id = ?',
			array($host_id));

		return $indexes;
	} elseif ($cmd == 'query') {
		if ($object == 'macAddress') {
			$return_arr = db_fetch_assoc_prepared('SELECT `index` AS macAddress
				FROM plugin_mikrotik_wireless_registrations
				WHERE host_id = ?
				ORDER BY macAddress',
				array($host_id));

			if (sizeof($return_arr)) {
				foreach($return_arr as $index) {
					print $index['macAddress'] . '!' . $index['macAddress'] . "\n";
				}
			}
		}
	} elseif ($cmd == 'get') {
		return db_fetch_cell_prepared("SELECT `$object`
			FROM plugin_mikrotik_wireless_registrations
			WHERE host_id = ?
			AND `index` = ?",
			array($host_id, $index));
	}
}

