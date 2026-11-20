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
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

// The scripts/ss_mikrotik_*.php backends are CLI entry points, so only their
// functions are isolated out of them.
mikrotik_test_load_functions('scripts/ss_mikrotik_health.php', ['ss_mikrotik_health', 'ss_mikrotik_health_columns', 'ss_mikrotik_health_aliases']);
mikrotik_test_load_functions('scripts/ss_mikrotik_wireless_reg.php', ['ss_mikrotik_wireless_reg', 'ss_mikrotik_wireless_reg_getvalue', 'ss_mikrotik_wireless_reg_uptime', 'ss_mikrotik_wireless_reg_getnames']);
mikrotik_test_load_functions('scripts/ss_mikrotik_interfaces.php', ['ss_mikrotik_interfaces', 'ss_mikrotik_interfaces_getvalue', 'ss_mikrotik_interfaces_getnames', 'ss_mikrotik_interfaces_getinfo']);
mikrotik_test_load_functions('scripts/ss_mikrotik_qtrees.php', ['ss_mikrotik_qtrees', 'ss_mikrotik_qtrees_getvalue', 'ss_mikrotik_qtrees_getnames', 'ss_mikrotik_qtrees_getinfo']);
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_gauge_health_column');

function ss_backends_tables(array $tables) {
	mikrotik_test_mock_db('db_table_exists', function ($table) use ($tables) {
		return in_array($table, $tables, true);
	}, true);
}

function ss_backends_health_mocks(array $sensors, $legacy) {
	mikrotik_test_mock_db('db_fetch_cell_prepared', function ($sql, $params) {
		return strpos($sql, 'plugin_mikrotik_health_sensors') !== false;
	}, function ($sql, $params) use ($sensors) {
		return array_key_exists($params[1], $sensors) ? $sensors[$params[1]] : false;
	});

	mikrotik_test_mock_db('db_fetch_cell_prepared', 'plugin_mikrotik_system_health', $legacy);
}

beforeEach(function () {
	mikrotik_test_reset_db_mocks();
});

test('health is unknown when the plugin is not installed', function () {
	expect(ss_mikrotik_health(1, 'HlVoltage'))->toBe('U');
});

test('health reads the legacy column the graph templates pass on plugins before schema 3.4', function () {
	ss_backends_tables(['plugin_mikrotik_system_health']);
	ss_backends_health_mocks(['voltage' => '99'], '24.1');

	expect(ss_mikrotik_health(1, 'HlVoltage'))->toBe('24.1');
});

test('health prefers the RouterOS 7 gauge sensor from schema 3.4', function () {
	ss_backends_tables(['plugin_mikrotik_system_health', 'plugin_mikrotik_health_sensors']);
	ss_backends_health_mocks(['psu1-voltage' => '23.9'], '24.1');

	expect(ss_mikrotik_health(1, 'HlVoltage'))->toBe('23.9');
});

test('health falls back to the legacy column for pre RouterOS 7 devices', function () {
	ss_backends_tables(['plugin_mikrotik_system_health', 'plugin_mikrotik_health_sensors']);
	ss_backends_health_mocks([], '61');

	expect(ss_mikrotik_health(1, 'HlCpuTemperature'))->toBe('61');
});

test('health is unknown when the device reports no value', function () {
	ss_backends_tables(['plugin_mikrotik_system_health', 'plugin_mikrotik_health_sensors']);
	ss_backends_health_mocks([], null);

	expect(ss_mikrotik_health(1, 'HlTwelveVoltage'))->toBe('U');
});

test('health accepts metric aliases and rejects other columns', function () {
	ss_backends_tables(['plugin_mikrotik_system_health']);
	ss_backends_health_mocks([], '7');

	expect(ss_mikrotik_health(1, 'temperature'))->toBe('7');
	expect(ss_mikrotik_health(1, 'HlActiveFan'))->toBe('U');
	expect(ss_mikrotik_health(1, 'host_id'))->toBe('U');
	expect(ss_mikrotik_health(1, '1; DROP TABLE host'))->toBe('U');
});

test('health aliases keep their RouterOS 7 sensors and legacy fallback column', function () {
	ss_backends_tables(['plugin_mikrotik_system_health', 'plugin_mikrotik_health_sensors']);
	ss_backends_health_mocks(['cpu-temperature' => '48', 'board-temperature' => '39'], '55');

	expect(ss_mikrotik_health(1, 'processorTemperature'))->toBe('48');
	expect(ss_mikrotik_health(1, 'temperature'))->toBe('39');
	expect(ss_mikrotik_health(1, 'HlProcessorTemperature'))->toBe('55');
});

test('health sensors match the poller gauge to column mapping', function () {
	$mapped = [];

	foreach (ss_mikrotik_health_columns() as $column => $sensors) {
		foreach ($sensors as $sensor) {
			expect(mikrotik_gauge_health_column($sensor))->toBe($column);

			$mapped[] = $sensor;
		}
	}

	foreach (['cpu-temperature', 'board-temperature1', '12-voltage', 'psu1-current', 'fan2', 'psu2-state'] as $sensor) {
		expect(in_array($sensor, $mapped, true))->toBeTrue();
	}
});

test('wireless registrations map the RxStrengthCh1 field to its column', function () {
	ss_backends_tables(['plugin_mikrotik_wireless_registrations']);
	mikrotik_test_mock_db('db_fetch_cell_prepared', '`RxStrengthChl`', '-61');

	expect(ss_mikrotik_wireless_reg(1, 'get', 'RxStrengthCh1', 'AA:BB:CC:DD:EE:FF'))->toBe('-61');
	expect(ss_mikrotik_wireless_reg(1, 'get', 'apTxRate', 'AA:BB:CC:DD:EE:FF'))->toBe('U');
});

test('wireless registration uptime is graphed in seconds', function () {
	expect(ss_mikrotik_wireless_reg_uptime('1:2:3:4'))->toBe('93784');
	expect(ss_mikrotik_wireless_reg_uptime('02:03:04.50'))->toBe('7384');
	expect(ss_mikrotik_wireless_reg_uptime('12050'))->toBe('120');
	expect(ss_mikrotik_wireless_reg_uptime(''))->toBe('U');
	expect(ss_mikrotik_wireless_reg_uptime(false))->toBe('U');
});

test('data query backends return unknown when the plugin is not installed', function () {
	expect(ss_mikrotik_wireless_reg(1, 'get', 'TxBytes', 'AA:BB:CC:DD:EE:FF'))->toBe('U');
	expect(ss_mikrotik_wireless_reg(1, 'num_indexes'))->toBe('0');
	expect(ss_mikrotik_interfaces(1, 'get', 'RxBytes', '3'))->toBe('U');
	expect(ss_mikrotik_interfaces(1, 'num_indexes'))->toBe('0');
});

test('interfaces are indexed by interface index and return the index count', function () {
	ss_backends_tables(['plugin_mikrotik_interfaces']);
	mikrotik_test_mock_db('db_fetch_assoc_prepared', 'plugin_mikrotik_interfaces', [
		['index' => 3, 'name' => 'ether1'],
		['index' => 7, 'name' => 'ether2'],
	]);

	expect(ss_mikrotik_interfaces(1, 'num_indexes'))->toBe('2');
	expect(ss_mikrotik_interfaces_getinfo(1, 'intName'))->toBe([3 => 'ether1', 7 => 'ether2']);
});

test('interface counters come from the cur column of an allowed field only', function () {
	ss_backends_tables(['plugin_mikrotik_interfaces']);
	mikrotik_test_mock_db('db_fetch_cell_prepared', function ($sql, $params) {
		return strpos($sql, '`curRxBytes`') !== false && $params[0] === '3';
	}, '1500');

	expect(ss_mikrotik_interfaces(1, 'get', 'RxBytes', '3'))->toBe('1500');
	expect(ss_mikrotik_interfaces(1, 'get', 'RxBytes', '9'))->toBe('U');
	expect(ss_mikrotik_interfaces(1, 'get', 'InOctets', '3'))->toBe('U');
	expect(ss_mikrotik_interfaces(1, 'get', 'x`y', '3'))->toBe('U');
});

test('interface counters still resolve name indexes and prefer the present row', function () {
	ss_backends_tables(['plugin_mikrotik_interfaces']);
	mikrotik_test_mock_db('db_fetch_cell_prepared', function ($sql, $params) {
		return strpos($sql, 'name IN (?, ?)') !== false && strpos($sql, 'ORDER BY present DESC') !== false
			&& $params === ['sfp_sfpplus1', 'sfp sfpplus1', 1];
	}, '900');

	expect(ss_mikrotik_interfaces(1, 'get', 'TxBytes', 'sfp_sfpplus1'))->toBe('900');
});

test('queue trees are indexed without spaces and resolve every stored name form', function () {
	ss_backends_tables(['plugin_mikrotik_trees']);
	mikrotik_test_mock_db('db_fetch_cell_prepared', function ($sql, $params) {
		return $params === ['customer_tree', 'customer tree', '<customer_tree>', '<customer tree>', 1];
	}, '42');

	expect(ss_mikrotik_qtrees(1, 'get', 'qtBytes', 'customer_tree'))->toBe('42');
	expect(ss_mikrotik_qtrees(1, 'get', 'qtBytes', 'gone'))->toBe('U');
	expect(ss_mikrotik_qtrees(1, 'get', 'qtFoo', 'customer_tree'))->toBe('U');
});
