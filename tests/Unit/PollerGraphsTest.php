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
 * poller_graphs.php is a CLI poller entry point (chdir()s, includes real
 * Cacti core libraries, and exit()s before its helpers are defined), so
 * only the named pure/DB-driven helpers are pulled out via the tokenizer
 * based loader; the top-level automation script never runs.
 *
 * Every scenario below keeps the mocked "graph_local"/"host_graph" lookups
 * truthy (already exists) so mikrotik_gt_graph()/mikrotik_dq_graphs() never
 * reach their execute_automation()/exec() branch - that helper shells out
 * to a real add_graphs.php CLI invocation, which must not run in a test
 * process.
 */
beforeEach(function () {
	mikrotik_test_load_functions(dirname(__DIR__, 2) . '/poller_graphs.php', [
		'mikrotik_gt_graph',
		'mikrotik_dq_graphs',
	]);

	// Graph already exists in graph_local, so neither helper below will
	// ever call execute_automation()/exec().
	mikrotik_test_mock_db('db_fetch_cell_prepared', 'FROM graph_local', 1);
});

describe('mikrotik_gt_graph', function () {
	it('associates a graph template with the host when no association exists yet', function () {
		mikrotik_test_mock_db('db_fetch_cell_prepared', 'FROM host_graph', 0);

		mikrotik_gt_graph(5, 42);

		$inserts = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_execute_prepared' && strpos($call['sql'], 'INSERT INTO host_graph') !== false;
		});

		expect($inserts)->toHaveCount(1);
		expect(array_values($inserts)[0]['params'])->toBe([5, 42]);
	});

	it('does not re-associate a graph template that is already linked to the host', function () {
		mikrotik_test_mock_db('db_fetch_cell_prepared', 'FROM host_graph', 1);

		mikrotik_gt_graph(5, 42);

		$inserts = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_execute_prepared' && strpos($call['sql'], 'INSERT INTO host_graph') !== false;
		});

		expect($inserts)->toBeEmpty();
	});
});

describe('mikrotik_dq_graphs', function () {
	it('derives the sort field from host_snmp_query when none is supplied', function () {
		mikrotik_test_mock_db('db_fetch_assoc_prepared', 'FROM host_snmp_cache', []);

		mikrotik_dq_graphs(5, 10, 42, 7);

		$sortFieldLookups = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_fetch_cell_prepared' && strpos($call['sql'], 'FROM host_snmp_query') !== false;
		});

		expect($sortFieldLookups)->toHaveCount(1);
	});

	it('skips an item entirely once an exclusion regex matches its field value', function () {
		mikrotik_test_mock_db('db_fetch_assoc_prepared', 'FROM host_snmp_cache', [
			['field_value' => 'guest-wifi', 'snmp_index' => '1'],
		]);

		mikrotik_dq_graphs(5, 10, 42, 7, 'userName', 'guest-.*', false);

		$existsChecks = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_fetch_cell_prepared' && strpos($call['sql'], 'FROM graph_local') !== false;
		});

		expect($existsChecks)->toBeEmpty();
	});

	it('checks for an existing graph once an item passes the regex filter', function () {
		mikrotik_test_mock_db('db_fetch_assoc_prepared', 'FROM host_snmp_cache', [
			['field_value' => 'staff-wifi', 'snmp_index' => '2'],
		]);

		mikrotik_dq_graphs(5, 10, 42, 7, 'userName', 'guest-.*', false);

		$existsChecks = array_filter($GLOBALS['__test_db_calls'], function ($call) {
			return $call['fn'] === 'db_fetch_cell_prepared' && strpos($call['sql'], 'FROM graph_local') !== false;
		});

		expect($existsChecks)->toHaveCount(1);
	});
});
