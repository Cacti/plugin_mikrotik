<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

// display no errors
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(__DIR__ . '/../include/cli_check.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_mikrotik_health', $_SERVER['argv']);
}

function ss_mikrotik_health(mixed $host_id = 0, string $column = 'no') : string {
	// The graph templates pass a friendly metric name. Map each to the
	// RouterOS 7 mtxrGaugeTable sensor name(s) in plugin_mikrotik_health_sensors
	// (preferred) and to the legacy plugin_mikrotik_system_health column used
	// on pre-v7 devices (fallback).
	$map = [
		'voltage'              => ['sensors' => ['voltage', 'psu1-voltage'],          'column' => 'HlVoltage'],
		'temperature'          => ['sensors' => ['temperature', 'board-temperature'], 'column' => 'HlTemperature'],
		'processorTemperature' => ['sensors' => ['cpu-temperature'],                  'column' => 'HlProcessorTemperature'],
		'current'              => ['sensors' => ['current', 'psu1-current'],          'column' => 'HlCurrent'],
		'power'                => ['sensors' => ['power-consumption', 'power'],        'column' => 'HlPower'],
		'fanSpeed'             => ['sensors' => ['fan1-speed', 'fan1'],               'column' => 'HlFanSpeed1'],
		'cpuFrequency'         => ['sensors' => ['cpu-frequency'],                    'column' => 'HlProcessorFrequency'],
	];

	if (!isset($map[$column])) {
		return 'U';
	}

	$host_id = (int) $host_id;

	// Prefer the RouterOS 7 gauge sensor table when present.
	if (db_table_exists('plugin_mikrotik_health_sensors')) {
		foreach ($map[$column]['sensors'] as $sensor) {
			$value = db_fetch_cell_prepared('SELECT value
				FROM plugin_mikrotik_health_sensors
				WHERE host_id = ?
				AND name = ?',
				[$host_id, $sensor]);

			if ($value !== false && $value !== null && $value !== '') {
				return $value;
			}
		}
	}

	// Fall back to the legacy fixed health column.
	$value = db_fetch_cell_prepared('SELECT `' . $map[$column]['column'] . '`
		FROM plugin_mikrotik_system_health
		WHERE host_id = ?',
		[$host_id]);

	if ($value === false || $value === null || $value === '') {
		return 'U';
	}

	return $value;
}
