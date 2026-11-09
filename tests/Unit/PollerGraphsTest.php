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
 * automation (poller_graphs.php).
 *
 * poller_graphs.php is a CLI entry point, so it is loaded with
 * MIKROTIK_POLLER_GRAPHS_NO_MAIN defined: the file returns before its poller
 * body runs and only its function definitions remain. Requiring the real file
 * (rather than isolating a copy) means coverage is attributed to
 * poller_graphs.php itself.
 *
 * The gate mirrors Cacti's own create_save_graph(): when a Graph Template has
 * 'Test Data Source' enabled and the data source returns no valid data for a
 * device, the graph must not be created.
 */

if (!function_exists('test_data_sources')) {
	function test_data_sources($graph_template_id, $host_id, $snmp_query_id = 0, $snmp_index = '', $values = array()) {
		$GLOBALS['__mt_tds_calls'][] = array($graph_template_id, $host_id, $snmp_query_id, $snmp_index);

		return $GLOBALS['__mt_tds_return'];
	}
}

if (!defined('MIKROTIK_POLLER_GRAPHS_NO_MAIN')) {
	define('MIKROTIK_POLLER_GRAPHS_NO_MAIN', true);
}

ob_start();
require_once dirname(__DIR__, 2) . '/poller_graphs.php';
ob_get_clean();

beforeEach(function () {
	mikrotik_test_reset_db_mocks();

	// poller_graphs.php's debug() prints only when the global $debug is set.
	$GLOBALS['debug']           = true;
	$GLOBALS['__mt_tds_calls']  = array();
	$GLOBALS['__mt_tds_return'] = true;
});

describe('mikrotik_gt_graph() Test Data Source gate', function () {
	it('skips the graph when the data source test fails', function () {
		$GLOBALS['__mt_tds_return'] = false;

		ob_start();
		mikrotik_gt_graph(9, 11);
		$out = ob_get_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 9, 0, '')));
		expect($out)->toContain('Skipping Graph Template');
	});
});

describe('mikrotik_dq_graphs() Test Data Source gate', function () {
	it('skips the data query graph when the data source test fails', function () {
		mikrotik_test_mock_db('db_fetch_assoc_prepared', 'host_snmp_cache', array(
			array('field_value' => 'eth0', 'snmp_index' => '7'),
		));

		$GLOBALS['__mt_tds_return'] = false;

		ob_start();
		mikrotik_dq_graphs(9, 3, 11, 5, 'ifName');
		$out = ob_get_clean();

		expect($GLOBALS['__mt_tds_calls'])->toBe(array(array(11, 9, 3, '7')));
		expect($out)->toContain('Skipping Data Query Graph');
	});
});
