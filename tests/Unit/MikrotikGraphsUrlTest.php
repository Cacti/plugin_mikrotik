<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for mikrotik_graphs_url_by_template_hashs() in setup.php.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	mikrotik_test_reset_db_mocks();
});

it('links to a filtered graph list when matching graphs exist', function () {
	mikrotik_test_mock_db('db_fetch_assoc', 'graph_local', array(array('id' => 3), array('id' => 7)));

	$html = mikrotik_graphs_url_by_template_hashs(array('hash1'));

	expect($html)->toContain('graph_list=3,7');
	expect($html)->toContain('View Graphs');
});

it('renders a skipped-graphs placeholder when no hashes are given', function () {
	$html = mikrotik_graphs_url_by_template_hashs(array());

	expect($html)->toContain('Graphs Skipped by Rule');
});

it('renders a skipped-graphs placeholder when no graphs match', function () {
	mikrotik_test_mock_db('db_fetch_assoc', 'graph_local', array());

	$html = mikrotik_graphs_url_by_template_hashs(array('hash1'));

	expect($html)->toContain('Graphs Skipped by Rule');
});
