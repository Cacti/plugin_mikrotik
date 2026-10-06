<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for mikrotik_get_graph_url() in mikrotik.php.
 *
 * mikrotik.php is a UI entry point, so the function under test is isolated
 * out of it (see mikrotik_test_load_function() in tests/bootstrap-unit.php)
 * rather than requiring the whole file.
 */

beforeAll(function () {
	mikrotik_test_load_function('mikrotik.php', 'mikrotik_get_graph_url');
});

beforeEach(function () {
	mikrotik_test_reset_db_mocks();
});

it('tolerates a null data query (unset Host MIB setting) without a TypeError', function () {
	// read_config_option('mikrotik_dq_host_cpu') returns null when unset;
	// mikrotik_devices() passes that straight through as $data_query.
	$html = mikrotik_get_graph_url(null, 5, '', '4', false);

	expect($html)->toBe('4');
});

it('shows the select-a-data-query hint for a null data query in image mode', function () {
	$html = mikrotik_get_graph_url(null, 5, '', '', true);

	expect($html)->toContain('Please select Data Query first');
});

it('links to the matching graphs when a data query resolves graphs', function () {
	mikrotik_test_mock_db('db_fetch_assoc', 'snmp_query_id=7', array(array('id' => 11), array('id' => 12)));

	$html = mikrotik_get_graph_url(7, 5, '', '', true);

	expect($html)->toContain('graph_add=11,12');
	expect($html)->toContain('View Graphs');
});
