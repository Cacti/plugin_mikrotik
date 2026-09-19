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

/**
 * Base class for Mikrotik tests.
 *
 * Pest's functional tests do not need this directly, but any test that
 * prefers a class-based fixture can `uses(TestCase::class)` to get a clean
 * stub-call log per test and a helper for loading plugin source once.
 */
abstract class TestCase extends PHPUnit\Framework\TestCase {
	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['__test_db_calls']    = array();
		$GLOBALS['__test_hook_calls']  = array();
		$GLOBALS['__test_realm_calls'] = array();
	}

	/**
	 * Load a plugin source file once per process.
	 *
	 * @param string $file File name relative to the plugin root.
	 *
	 * @return void
	 */
	protected static function loadPluginSource($file) {
		mikrotik_test_load(dirname(__DIR__) . '/' . $file);
	}

	/**
	 * Isolate and define a single function from a CLI/UI plugin entry point
	 * without executing that file's top-level side effects.
	 *
	 * @param string $file     Plugin file, relative to the plugin root.
	 * @param string $function Name of the function to define.
	 *
	 * @return void
	 */
	protected static function loadPluginFunction($file, $function) {
		mikrotik_test_load_function($file, $function);
	}
}
