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

/*
 * Unit coverage for the Graph Template 'Test Data Source' gate in the graph
 * automation (poller_graphs.php). poller_graphs.php is a CLI entry point, so
 * only mikrotik_gt_graph() is isolated out of it (see
 * mikrotik_test_load_function() in tests/bootstrap-unit.php).
 *
 * The gate mirrors Cacti's own create_save_graph(): when a Graph Template has
 * 'Test Data Source' enabled and the data source returns no valid data for a
 * device, the graph must not be created.
 */

if (!function_exists('debug')) {
	function debug($message) {
		$GLOBALS['__mt_debug'][] = (string) $message;
	}
}

if (!function_exists('execute_automation')) {
	function execute_automation($command, $type, $field_value = '') {
		$GLOBALS['__mt_automation'][] = array($command, $type, $field_value);
	}
}

if (!function_exists('cacti_escapeshellarg')) {
	function cacti_escapeshellarg($string) {
		return escapeshellarg((string) $string);
	}
}

if (!function_exists('test_data_sources')) {
	function test_data_sources($graph_template_id, $host_id, $snmp_query_id = 0, $snmp_index = '', $values = array()) {
		$GLOBALS['__mt_tds_calls'][] = array($graph_template_id, $host_id, $snmp_query_id, $snmp_index);

		return $GLOBALS['__mt_tds_return'];
	}
}

mikrotik_test_load_function('poller_graphs.php', 'mikrotik_gt_graph');
mikrotik_test_load_function('poller_graphs.php', 'mikrotik_dq_graphs');
mikrotik_test_load_function('poller_graphs.php', 'add_summary_graphs');

beforeEach(function () {
	mikrotik_test_reset_db_mocks();

	$GLOBALS['__mt_debug']        = array();
	$GLOBALS['__mt_automation']   = array();
	$GLOBALS['__mt_tds_calls']    = array();
	$GLOBALS['__mt_tds_return']   = true;
});

function mikrotik_test_automation_types() {
	return array_map(function ($call) {
		return $call[1];
	}, $GLOBALS['__mt_automation']);
}

describe('mikrotik_gt_graph() Test Data Source gate', function () {
	it('creates the graph when the data source test passes', function () {
		$GLOBALS['__mt_tds_return'] = true;

		ob_start();
		mikrotik_gt_graph(9, 11);
		ob_end_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 9, 0, '')));
		expect($GLOBALS['__mt_automation'])->toHaveCount(1);
		expect($GLOBALS['__mt_automation'][0][1])->toBe('Template Graph');
	});

	it('skips the graph when the data source test fails', function () {
		$GLOBALS['__mt_tds_return'] = false;

		ob_start();
		mikrotik_gt_graph(9, 11);
		ob_end_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 9, 0, '')));
		expect($GLOBALS['__mt_automation'])->toBe(array());
		expect(implode("\n", $GLOBALS['__mt_debug']))->toContain('Skipping Graph Template');
	});
});

describe('mikrotik_dq_graphs() Test Data Source gate', function () {
	beforeEach(function () {
		mikrotik_test_mock_db('db_fetch_assoc_prepared', 'host_snmp_cache', array(
			array('field_value' => 'eth0', 'snmp_index' => '7'),
		));
	});

	it('creates the data query graph when the data source test passes', function () {
		$GLOBALS['__mt_tds_return'] = true;

		ob_start();
		mikrotik_dq_graphs(9, 3, 11, 5, 'ifName');
		ob_end_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 9, 3, '7')));
		expect(mikrotik_test_automation_types())->toBe(array('Data Query Graph'));
	});

	it('skips the data query graph when the data source test fails', function () {
		$GLOBALS['__mt_tds_return'] = false;

		ob_start();
		mikrotik_dq_graphs(9, 3, 11, 5, 'ifName');
		ob_end_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 9, 3, '7')));
		expect($GLOBALS['__mt_automation'])->toBe(array());
		expect(implode("\n", $GLOBALS['__mt_debug']))->toContain('Skipping Data Query Graph');
	});
});

describe('add_summary_graphs() Test Data Source gate', function () {
	beforeEach(function () {
		// No data-query graphs; one directly-assigned host_graph template.
		mikrotik_test_mock_db('db_fetch_assoc_prepared', function ($sql, $params) {
			return strpos($sql, 'FROM host_graph') !== false;
		}, array(
			array('graph_template_id' => 11),
		));
	});

	it('creates the summary template graph when the data source test passes', function () {
		$GLOBALS['__mt_tds_return'] = true;

		ob_start();
		add_summary_graphs(42, 5);
		ob_end_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 42, 0, '')));
		expect(mikrotik_test_automation_types())->toContain('Template Graphs');
	});

	it('skips the summary template graph when the data source test fails', function () {
		$GLOBALS['__mt_tds_return'] = false;

		ob_start();
		add_summary_graphs(42, 5);
		ob_end_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 42, 0, '')));
		expect(mikrotik_test_automation_types())->not->toContain('Template Graphs');
		expect(implode("\n", $GLOBALS['__mt_debug']))->toContain('Skipping Graph Template');
	});
});
