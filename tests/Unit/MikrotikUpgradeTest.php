<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for mikrotik_check_upgrade()'s version-drift path in
 * setup.php, including the upgrade-time manifest prune
 * (mikrotik_prune_files()).
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']    = array();
	$GLOBALS['__test_db_fixtures'] = array();
	unset($GLOBALS['__test_current_page']);
});

it('runs the schema upgrade and prune, then updates plugin_config on a version drift', function () {
	$GLOBALS['__test_current_page'] = 'plugins.php';
	mikrotik_test_mock_db('db_fetch_cell', 'plugin_config', '0.0.0');

	// Sandbox base_path so plugin_mikrotik_version() reads a temp INFO and the
	// upgrade-time prune runs against a temp tree, never the real checkout.
	$restore = $GLOBALS['config']['base_path'];
	$base    = sys_get_temp_dir() . '/mikrotik-upg-' . uniqid();
	mkdir($base . '/plugins/mikrotik', 0777, true);
	file_put_contents($base . '/plugins/mikrotik/INFO', "[info]\nversion = 9.9.9\nname = mikrotik\nlongname = MikroTik\nauthor = x\nhomepage = x\n");
	$GLOBALS['config']['base_path'] = $base;

	try {
		mikrotik_check_upgrade();
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
		unset($GLOBALS['__test_current_page']);
	}

	$updates = array_values(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute_prepared' && stripos($call['sql'], 'UPDATE plugin_config') !== false;
	}));

	expect($updates)->toHaveCount(1);
});
