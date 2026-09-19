<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/*
 * setup.php's plugin lifecycle hooks span multiple Cacti subsystems (hook
 * and realm registration, schema creation/migration, plugin_config
 * bookkeeping), so they are exercised together here rather than as
 * isolated Unit tests.
 */
beforeEach(function () {
	mikrotik_test_load_functions(dirname(__DIR__, 2) . '/setup.php', [
		'plugin_mikrotik_install',
		'plugin_mikrotik_uninstall',
		'plugin_mikrotik_version',
		'mikrotik_check_upgrade',
		'mikrotik_setup_table',
	]);
});

describe('plugin_mikrotik_install', function () {
	it('registers every plugin hook, both realms, and creates the plugin schema', function () {
		plugin_mikrotik_install();

		$hooks = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'api_plugin_register_hook';
		});

		$realms = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'api_plugin_register_realm';
		});

		$schema = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_execute' && strpos($call['sql'], 'CREATE TABLE IF NOT EXISTS `plugin_mikrotik_system_health`') !== false;
		});

		expect($hooks)->toHaveCount(9);
		expect($realms)->toHaveCount(2);
		expect($schema)->toHaveCount(1);
	});
});

describe('plugin_mikrotik_uninstall', function () {
	it('drops every plugin_mikrotik_* table it created', function () {
		plugin_mikrotik_uninstall();

		$drops = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_execute' && strpos($call['sql'], 'DROP TABLE IF EXISTS') !== false;
		});

		expect($drops)->toHaveCount(15);

		$expectedTables = [
			'plugin_mikrotik_system', 'plugin_mikrotik_system_health', 'plugin_mikrotik_storage',
			'plugin_mikrotik_users', 'plugin_mikrotik_trees', 'plugin_mikrotik_queues',
			'plugin_mikrotik_interfaces', 'plugin_mikrotik_wireless_aps', 'plugin_mikrotik_wireless_registrations',
			'plugin_mikrotik_processes', 'plugin_mikrotik_processor', 'plugin_mikrotik_credentials',
			'plugin_mikrotik_dhcp', 'plugin_mikrotik_dns', 'plugin_mikrotik_lists',
		];

		foreach ($expectedTables as $table) {
			$dropped = array_filter($drops, function ($call) use ($table) {
				return strpos($call['sql'], "`$table`") !== false;
			});

			expect($dropped)->toHaveCount(1);
		}
	});
});

describe('mikrotik_check_upgrade', function () {
	it('does nothing on a page that is not plugins.php or mikrotik.php', function () {
		$GLOBALS['__test_current_page'] = 'graphs.php';

		mikrotik_check_upgrade();

		expect($GLOBALS['__test_db_calls'])->toBeEmpty();
	});

	it('does nothing when the installed version already matches the plugin version', function () {
		$GLOBALS['__test_current_page'] = 'mikrotik.php';

		$current = plugin_mikrotik_version();
		mikrotik_test_mock_db('db_fetch_cell', "directory='mikrotik'", $current['version']);

		mikrotik_check_upgrade();

		$updates = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_execute_prepared' && strpos($call['sql'], 'UPDATE plugin_config') !== false;
		});

		expect($updates)->toBeEmpty();
	});

	it('migrates the schema and records the new version when the installed version is stale', function () {
		$GLOBALS['__test_current_page'] = 'mikrotik.php';

		mikrotik_test_mock_db('db_fetch_cell', "directory='mikrotik'", '0.1');

		mikrotik_check_upgrade();

		$alters = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_execute' && strpos($call['sql'], 'ALTER TABLE') !== false;
		});

		$updates = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_execute_prepared' && strpos($call['sql'], 'UPDATE plugin_config') !== false;
		});

		expect($alters)->not->toBeEmpty();
		expect($updates)->toHaveCount(1);
	});
});
