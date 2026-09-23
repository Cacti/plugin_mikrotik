<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the plugin lifecycle functions in setup.php not
 * already exercised by tests/Integration/SetupWiringTest.php:
 * plugin_mikrotik_uninstall() and mikrotik_check_dependencies().
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls'] = array();
});

it('drops every table it owns on uninstall', function () {
	plugin_mikrotik_uninstall();

	$drops = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute' && stripos($call['sql'], 'DROP TABLE') !== false;
	});

	expect(count($drops))->toBe(15);
});

it('reports that its dependencies are always satisfied', function () {
	expect(mikrotik_check_dependencies())->toBeTrue();
});
