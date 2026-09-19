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
 * setup.php only defines plugin lifecycle hooks; it never runs top-level
 * script code, so the named functions can be pulled in directly.
 */
beforeEach(function () {
	mikrotik_test_load_functions(dirname(__DIR__, 2) . '/setup.php', [
		'plugin_mikrotik_version',
		'mikrotik_check_dependencies',
	]);
});

describe('plugin_mikrotik_version', function () {
	it('parses the real INFO file into an [info] array', function () {
		$info = plugin_mikrotik_version();

		expect($info)->toBeArray();
		expect($info['name'])->toBe('mikrotik');
		expect($info['longname'])->toBe('MikroTik Switch Tool');
		expect($info['version'])->toMatch('/^\d+(\.\d+)+$/');
	});
});

describe('mikrotik_check_dependencies', function () {
	it('always reports its dependencies as satisfied', function () {
		expect(mikrotik_check_dependencies())->toBeTrue();
	});
});
