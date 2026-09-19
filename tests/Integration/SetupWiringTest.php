<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

// setup.php only defines functions/hook registrations; it has no top-level
// side effects, so it is safe to load in full (unlike the CLI/UI entry
// points covered by tests/Unit).
mikrotik_test_load(realpath(__DIR__ . '/../../setup.php'));

beforeEach(function () {
	$GLOBALS['__test_db_calls']    = array();
	$GLOBALS['__test_hook_calls']  = array();
	$GLOBALS['__test_realm_calls'] = array();
});

describe('plugin_mikrotik_install() wiring', function () {
	it('registers the expected hooks', function () {
		plugin_mikrotik_install();

		$hooks = array_column($GLOBALS['__test_hook_calls'], 'hook');

		expect($hooks)->toContain('config_arrays');
		expect($hooks)->toContain('config_settings');
		expect($hooks)->toContain('draw_navigation_text');
		expect($hooks)->toContain('poller_bottom');
		expect($hooks)->toContain('top_header_tabs');
		expect($hooks)->toContain('top_graph_header_tabs');
		expect($hooks)->toContain('host_edit_top');
		expect($hooks)->toContain('host_save');
		expect($hooks)->toContain('host_delete');
	});

	it('registers every hook against the mikrotik plugin', function () {
		plugin_mikrotik_install();

		foreach ($GLOBALS['__test_hook_calls'] as $call) {
			expect($call['plugin'])->toBe('mikrotik');
		}
	});

	it('registers the viewer and admin realms', function () {
		plugin_mikrotik_install();

		$files = array_column($GLOBALS['__test_realm_calls'], 'file');

		expect($files)->toContain('mikrotik.php');
		expect($files)->toContain('mikrotik_users.php');
	});
});

describe('plugin_mikrotik_check_config() / plugin_mikrotik_upgrade()', function () {
	it('both report success', function () {
		expect(plugin_mikrotik_check_config())->toBeTrue();
		expect(plugin_mikrotik_upgrade())->toBeTrue();
	});
});

describe('plugin_mikrotik_version()', function () {
	it('reads name and version from INFO', function () {
		$info = plugin_mikrotik_version();

		expect($info)->toHaveKey('name');
		expect($info['name'])->toBe('mikrotik');
		expect($info)->toHaveKey('version');
	});
});
