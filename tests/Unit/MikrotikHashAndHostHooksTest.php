<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for mikrotik_template_by_hash(), mikrotik_data_query_by_hash(),
 * mikrotik_host_save(), and mikrotik_host_delete() in setup.php.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	mikrotik_test_reset_db_mocks();
	$GLOBALS['__test_db_calls'] = array();
	test_set_request(array());
});

it('looks up a graph template id by its hash', function () {
	mikrotik_test_mock_db('db_fetch_cell', 'graph_templates', '42');

	expect(mikrotik_template_by_hash('abc123'))->toBe('42');
});

it('looks up a data query id by its hash', function () {
	mikrotik_test_mock_db('db_fetch_cell', 'snmp_query', '7');

	expect(mikrotik_data_query_by_hash('abc123'))->toBe('7');
});

it('saves credentials only when the mikrotik_user field was submitted', function () {
	$data = mikrotik_host_save(array('host_id' => 5));

	expect($GLOBALS['__test_db_calls'])->toBeEmpty();
	expect($data)->toBe(array('host_id' => 5));

	set_request_var('mikrotik_user', 'admin');
	set_request_var('mikrotik_password', 'secret');

	mikrotik_host_save(array('host_id' => 5));

	expect($GLOBALS['__test_db_calls'])->toHaveCount(1);
	expect($GLOBALS['__test_db_calls'][0]['sql'])->toContain('REPLACE INTO plugin_mikrotik_credentials');
	expect($GLOBALS['__test_db_calls'][0]['params'])->toBe(array(5, 'admin', 'secret'));
});

it('deletes credentials for every host id passed', function () {
	$data = mikrotik_host_delete(array(3, 4));

	expect($GLOBALS['__test_db_calls'])->toHaveCount(1);
	expect($GLOBALS['__test_db_calls'][0]['sql'])->toContain('WHERE host_id IN(3,4)');
	expect($data)->toBe(array(3, 4));
});
