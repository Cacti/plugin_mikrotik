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
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/*
 * Test bootstrap.
 *
 * MikroTik's sources expect to be included by Cacti, which has already
 * defined the db_*, request-variable, and logging helpers as plain global
 * functions. Nothing here talks to a database, the network, or an actual
 * MikroTik device: each Cacti function is declared as a stub that records
 * the call in $GLOBALS['__test_db_calls'] and hands back a safe default.
 *
 * The CI workflow checks out a pinned Cacti release next to this plugin so
 * Pest runs against Cacti's own Composer-managed vendor tree (Pest/PHPUnit)
 * instead of a vendor tree local to this plugin. The version check below
 * makes sure that checkout actually matches what tests/.cacti-version
 * expects before any plugin source is loaded.
 *
 * Guarding every declaration with function_exists() keeps this file usable
 * if a future integration suite loads real Cacti first.
 */

$cacti_root = dirname(__DIR__, 3);
$autoload   = $cacti_root . '/include/vendor/autoload.php';
$version    = $cacti_root . '/include/cacti_version';
$expected   = __DIR__ . '/.cacti-version';

if (!is_readable($autoload)) {
	throw new RuntimeException("Cacti Composer autoloader is not readable: $autoload");
}

if (!is_readable($version)) {
	throw new RuntimeException("Cacti version file is not readable: $version");
}

if (!is_readable($expected)) {
	throw new RuntimeException("Expected Cacti version file is not readable: $expected");
}

$cacti_version     = trim((string) file_get_contents($version));
$expected_version  = trim((string) file_get_contents($expected));

if ($cacti_version === '') {
	throw new RuntimeException("Cacti version file is empty: $version");
}

if ($expected_version === '') {
	throw new RuntimeException("Expected Cacti version file is empty: $expected");
}

// The CI workflow tracks a moving branch (1.2.x or develop) rather than a pinned release, so any actual version is accepted.
if (!in_array($expected_version, array('1.2.x', 'develop'), true) && $cacti_version !== $expected_version) {
	throw new RuntimeException("Expected Cacti $expected_version, found $cacti_version in $version");
}

require_once $autoload;
require_once __DIR__ . '/TestCase.php';

/*
 * base_path has to point at the Cacti root two levels above this plugin:
 * mikrotik's source files build include paths from it at runtime.
 */
$GLOBALS['config'] = array(
	'base_path'       => $cacti_root,
	'url_path'        => '/cacti/',
	'library_path'    => $cacti_root . '/lib',
	'cacti_version'   => $cacti_version,
	'cacti_server_os' => 'unix',
);

$GLOBALS['__test_db_calls']    = array();
$GLOBALS['__test_db_fixtures'] = array();

/**
 * Queues a canned result for the next matching stubbed db_* call(s).
 *
 * This is the "mock database" for functional tests: instead of connecting to
 * a real MySQL/MariaDB server, a test seeds the exact rows/values a given
 * db_fetch_ or db_execute stub should hand back when it sees a matching SQL
 * statement, then calls the real plugin function and asserts on behavior.
 *
 * @param string          $fn     Stubbed function name, e.g. 'db_fetch_assoc_prepared'.
 * @param string|callable $match  Substring the SQL must contain, or a callable(string $sql, array $params): bool.
 * @param mixed           $result Value to hand back when matched.
 *
 * @return void
 */
function mikrotik_test_mock_db($fn, $match, $result) {
	$GLOBALS['__test_db_fixtures'][$fn][] = array('match' => $match, 'result' => $result);
}

/**
 * Clears queued db_* fixtures and the call log. Safe to call between tests.
 *
 * @return void
 */
function mikrotik_test_reset_db_mocks() {
	$GLOBALS['__test_db_fixtures']  = array();
	$GLOBALS['__test_db_calls']     = array();
	$GLOBALS['__test_current_page'] = '';
}

/**
 * Records a stubbed db_* call and resolves it against any queued fixtures.
 *
 * @param string $fn      Stubbed function name.
 * @param string $sql     SQL text passed to the stub.
 * @param array  $params  Bind parameters passed to the stub (empty for non-prepared calls).
 * @param mixed  $default Value to return when no fixture matches.
 *
 * @return mixed
 */
function mikrotik_test_db_result($fn, $sql, $params, $default) {
	$GLOBALS['__test_db_calls'][] = array('fn' => $fn, 'sql' => $sql, 'params' => $params);

	if (!empty($GLOBALS['__test_db_fixtures'][$fn])) {
		// Most-recently-registered fixture wins, so a test can override a
		// beforeEach() default without it winning by being checked first.
		foreach (array_reverse($GLOBALS['__test_db_fixtures'][$fn]) as $fixture) {
			$matched = is_callable($fixture['match'])
				? $fixture['match']($sql, $params)
				: (strpos($sql, $fixture['match']) !== false);

			if ($matched) {
				return $fixture['result'];
			}
		}
	}

	return $default;
}

if (!function_exists('db_execute')) {
	function db_execute($sql) {
		return mikrotik_test_db_result('db_execute', $sql, array(), true);
	}
}

if (!function_exists('db_execute_prepared')) {
	function db_execute_prepared($sql, $params = array()) {
		return mikrotik_test_db_result('db_execute_prepared', $sql, $params, true);
	}
}

if (!function_exists('db_fetch_assoc')) {
	function db_fetch_assoc($sql) {
		return mikrotik_test_db_result('db_fetch_assoc', $sql, array(), array());
	}
}

if (!function_exists('db_fetch_assoc_prepared')) {
	function db_fetch_assoc_prepared($sql, $params = array()) {
		return mikrotik_test_db_result('db_fetch_assoc_prepared', $sql, $params, array());
	}
}

if (!function_exists('db_fetch_row')) {
	function db_fetch_row($sql) {
		return mikrotik_test_db_result('db_fetch_row', $sql, array(), array());
	}
}

if (!function_exists('db_fetch_row_prepared')) {
	function db_fetch_row_prepared($sql, $params = array()) {
		return mikrotik_test_db_result('db_fetch_row_prepared', $sql, $params, array());
	}
}

if (!function_exists('db_fetch_cell')) {
	function db_fetch_cell($sql) {
		return mikrotik_test_db_result('db_fetch_cell', $sql, array(), '');
	}
}

if (!function_exists('db_fetch_cell_prepared')) {
	function db_fetch_cell_prepared($sql, $params = array()) {
		return mikrotik_test_db_result('db_fetch_cell_prepared', $sql, $params, '');
	}
}

if (!function_exists('db_qstr')) {
	function db_qstr($string) {
		if ($string === null) {
			return 'NULL';
		}

		return "'" . addslashes($string) . "'";
	}
}

if (!function_exists('db_index_exists')) {
	function db_index_exists($table, $index) {
		return false;
	}
}

if (!function_exists('db_column_exists')) {
	function db_column_exists($table, $column) {
		return false;
	}
}

if (!function_exists('db_table_exists')) {
	function db_table_exists($table) {
		return true;
	}
}

if (!function_exists('array_to_sql_or')) {
	function array_to_sql_or($array, $column) {
		if (!cacti_sizeof($array)) {
			return '(1=0)';
		}

		return '(' . $column . ' IN (' . implode(',', (array) $array) . '))';
	}
}

if (!function_exists('api_plugin_db_add_column')) {
	function api_plugin_db_add_column($plugin, $table, $data) {
		return mikrotik_test_db_result('api_plugin_db_add_column', $table, array($plugin, $table, $data), true);
	}
}

if (!function_exists('api_plugin_db_table_create')) {
	function api_plugin_db_table_create($plugin, $table, $data) {
		return mikrotik_test_db_result('api_plugin_db_table_create', $table, array($plugin, $table, $data), true);
	}
}

if (!function_exists('api_plugin_drop_table')) {
	function api_plugin_drop_table($table) {
		return true;
	}
}

if (!function_exists('api_plugin_register_hook')) {
	function api_plugin_register_hook($plugin, $hook, $function, $file, $realtime = 0) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'api_plugin_register_hook', 'sql' => $hook, 'params' => array($plugin, $hook, $function, $file));

		return true;
	}
}

if (!function_exists('api_plugin_register_realm')) {
	function api_plugin_register_realm($plugin, $files, $name, $enabled = 0) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'api_plugin_register_realm', 'sql' => $files, 'params' => array($plugin, $files, $name, $enabled));

		return true;
	}
}

if (!function_exists('api_plugin_is_enabled')) {
	function api_plugin_is_enabled($plugin) {
		return true;
	}
}

if (!function_exists('api_plugin_enable_hooks')) {
	function api_plugin_enable_hooks($plugin) {
	}
}

if (!function_exists('api_user_realm_auth')) {
	function api_user_realm_auth($file) {
		return true;
	}
}

if (!function_exists('get_current_page')) {
	function get_current_page() {
		return $GLOBALS['__test_current_page'] ?? '';
	}
}

if (!function_exists('is_hexadecimal')) {
	function is_hexadecimal($value) {
		return (bool) preg_match('/^[0-9a-fA-F]+$/', (string) $value);
	}
}

if (!function_exists('read_config_option')) {
	function read_config_option($name, $force = false) {
		return '';
	}
}

if (!function_exists('set_config_option')) {
	function set_config_option($name, $value) {
	}
}

if (!function_exists('html_escape')) {
	function html_escape($string) {
		return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('__')) {
	function __($text, $domain = '') {
		return $text;
	}
}

if (!function_exists('__esc')) {
	function __esc($text, $domain = '') {
		return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('cacti_log')) {
	function cacti_log($message, $also_print = false, $log_type = '', $level = 0) {
	}
}

if (!function_exists('cacti_sizeof')) {
	function cacti_sizeof($array) {
		return is_array($array) ? count($array) : 0;
	}
}

if (!function_exists('array_rekey')) {
	function array_rekey($array, $index, $columns = null) {
		$result = array();

		foreach ((array) $array as $row) {
			$key = is_array($index) ? implode(':', array_intersect_key($row, array_flip($index))) : $row[$index];

			if ($columns === null) {
				$result[$key] = $row;
			} elseif (is_array($columns)) {
				$result[$key] = array_intersect_key($row, array_flip($columns));
			} else {
				$result[$key] = $row[$columns];
			}
		}

		return $result;
	}
}

if (!function_exists('is_realm_allowed')) {
	function is_realm_allowed($realm) {
		return true;
	}
}

if (!function_exists('raise_message')) {
	function raise_message($id, $text = '', $level = 0) {
	}
}

if (!function_exists('get_request_var')) {
	function get_request_var($name) {
		return '';
	}
}

if (!function_exists('get_nfilter_request_var')) {
	function get_nfilter_request_var($name) {
		return '';
	}
}

if (!function_exists('get_filter_request_var')) {
	function get_filter_request_var($name) {
		return '';
	}
}

if (!function_exists('form_input_validate')) {
	function form_input_validate($value, $name, $regex, $optional, $error) {
		return $value;
	}
}

if (!function_exists('is_error_message')) {
	function is_error_message() {
		return false;
	}
}

if (!function_exists('sql_save')) {
	function sql_save($array, $table, $key = 'id') {
		return isset($array['id']) ? $array['id'] : 1;
	}
}

if (!function_exists('cacti_escapeshellcmd')) {
	function cacti_escapeshellcmd($command) {
		return escapeshellcmd($command);
	}
}

if (!function_exists('cacti_escapeshellarg')) {
	function cacti_escapeshellarg($arg) {
		return escapeshellarg($arg);
	}
}

if (!function_exists('exec_background')) {
	function exec_background($command, $args = '') {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'exec_background', 'sql' => trim($command . ' ' . $args), 'params' => array());

		return true;
	}
}

if (!defined('CACTI_PATH_BASE')) {
	define('CACTI_PATH_BASE', $GLOBALS['config']['base_path']);
}

if (!defined('POLLER_VERBOSITY_LOW')) {
	define('POLLER_VERBOSITY_LOW', 2);
}

if (!defined('POLLER_VERBOSITY_MEDIUM')) {
	define('POLLER_VERBOSITY_MEDIUM', 3);
}

if (!defined('POLLER_VERBOSITY_DEBUG')) {
	define('POLLER_VERBOSITY_DEBUG', 5);
}

if (!defined('POLLER_VERBOSITY_NONE')) {
	define('POLLER_VERBOSITY_NONE', 6);
}

if (!defined('MESSAGE_LEVEL_ERROR')) {
	define('MESSAGE_LEVEL_ERROR', 1);
}

if (!defined('MESSAGE_LEVEL_INFO')) {
	define('MESSAGE_LEVEL_INFO', 4);
}

/**
 * Load a plugin source file at global scope.
 *
 * Some plugin files define data as file-scope variables that the rest of
 * the plugin reads as globals, and they read $config while doing so.
 * Requiring them from inside a method would make both halves of that
 * method-local, so the require happens here and any variable the file
 * introduced is published to $GLOBALS.
 *
 * @param string $path Absolute path to the file.
 *
 * @return void
 */
function mikrotik_test_load($path) {
	global $config;

	$__before = get_defined_vars();

	require_once $path;

	foreach (get_defined_vars() as $__name => $__value) {
		if (!array_key_exists($__name, $__before) && strncmp($__name, '__', 2) !== 0) {
			$GLOBALS[$__name] = $__value;
		}
	}
}

/**
 * Defines named top-level functions from a plugin script without running
 * the script itself.
 *
 * mikrotik.php, mikrotik_users.php, poller_mikrotik.php and poller_graphs.php
 * are entry points: they chdir(), include real Cacti core files, parse CLI
 * arguments or the request, and (for the CLI ones) exit() before their
 * helper functions are even defined. None of that is safe to run in a test
 * process. Rather than re-implement each helper (and risk testing a copy
 * that drifts from the real code), this walks the token stream, isolates
 * the exact source text of each requested top-level `function name(...) {
 * ... }` block by tracking brace depth, and eval()s only those blocks.
 *
 * @param string        $path  Absolute path to the plugin file.
 * @param string[]|null $names Function names to define; null defines every
 *                             top-level function in the file.
 *
 * @return void
 */
function mikrotik_test_load_functions($path, array $names = null) {
	static $done = array();

	$cacheKey = $path . '|' . ($names !== null ? implode(',', $names) : '*');

	if (isset($done[$cacheKey])) {
		return;
	}

	$done[$cacheKey] = true;

	$source = file_get_contents($path);

	if ($source === false) {
		throw new RuntimeException("Unable to read plugin source: $path");
	}

	$tokens = token_get_all($source);
	$total  = count($tokens);

	for ($i = 0; $i < $total; $i++) {
		if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
			continue;
		}

		$nameIndex = $i + 1;

		while ($nameIndex < $total && !(is_array($tokens[$nameIndex]) && $tokens[$nameIndex][0] === T_STRING)) {
			$nameIndex++;
		}

		$name = $nameIndex < $total ? $tokens[$nameIndex][1] : null;

		if ($name === null) {
			continue;
		}

		if ($names !== null && !in_array($name, $names, true)) {
			continue;
		}

		if (function_exists($name)) {
			continue;
		}

		$braceIndex = $nameIndex;

		while ($braceIndex < $total && $tokens[$braceIndex] !== '{') {
			$braceIndex++;
		}

		$depth = 0;
		$end   = $braceIndex;

		for (; $end < $total; $end++) {
			if ($tokens[$end] === '{') {
				$depth++;
			} elseif ($tokens[$end] === '}') {
				$depth--;

				if ($depth === 0) {
					break;
				}
			}
		}

		$body = '';

		for ($m = $i; $m <= $end; $m++) {
			$body .= is_array($tokens[$m]) ? $tokens[$m][1] : $tokens[$m];
		}

		eval($body);

		$i = $end;
	}
}
