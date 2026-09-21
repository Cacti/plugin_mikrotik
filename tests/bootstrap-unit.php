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
 * Test bootstrap.
 *
 * Mikrotik's sources expect to be included by Cacti, which has already
 * defined the db_*, request-variable, and logging helpers as plain global
 * functions. Nothing here talks to a database or a network: each Cacti
 * function is declared as a stub that records the call in
 * $GLOBALS['__test_db_calls'] and hands back a safe default.
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

$cacti_version    = trim((string) file_get_contents($version));
$expected_version = trim((string) file_get_contents($expected));

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

/*
 * base_path has to point at the Cacti root two levels above this plugin:
 * mikrotik's source files build include paths from it at runtime.
 */
$GLOBALS['config'] = array(
	'base_path'       => $cacti_root,
	'library_path'    => __DIR__ . '/stubs/lib',
	'url_path'        => '/cacti/',
	'cacti_version'   => $cacti_version,
	'cacti_server_os' => 'unix',
);

$GLOBALS['__test_db_calls']   = array();
$GLOBALS['__test_hook_calls'] = array();
$GLOBALS['__test_realm_calls'] = array();

if (!function_exists('db_execute')) {
	function db_execute($sql) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'db_execute', 'sql' => $sql, 'params' => array());
		return true;
	}
}

if (!function_exists('db_execute_prepared')) {
	function db_execute_prepared($sql, $params = array()) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'db_execute_prepared', 'sql' => $sql, 'params' => $params);
		return true;
	}
}

if (!function_exists('db_fetch_assoc')) {
	function db_fetch_assoc($sql) {
		return array();
	}
}

if (!function_exists('db_fetch_assoc_prepared')) {
	function db_fetch_assoc_prepared($sql, $params = array()) {
		return array();
	}
}

if (!function_exists('db_fetch_row')) {
	function db_fetch_row($sql) {
		return array();
	}
}

if (!function_exists('db_fetch_row_prepared')) {
	function db_fetch_row_prepared($sql, $params = array()) {
		return array();
	}
}

if (!function_exists('db_fetch_cell')) {
	function db_fetch_cell($sql) {
		return '';
	}
}

if (!function_exists('db_fetch_cell_prepared')) {
	function db_fetch_cell_prepared($sql, $params = array()) {
		return '';
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
		return false;
	}
}

if (!function_exists('api_plugin_db_add_column')) {
	function api_plugin_db_add_column($plugin, $table, $data) {
		return true;
	}
}

if (!function_exists('api_plugin_db_table_create')) {
	function api_plugin_db_table_create($plugin, $table, $data) {
		return true;
	}
}

if (!function_exists('api_plugin_register_hook')) {
	function api_plugin_register_hook($plugin, $hook, $function, $file, $subtype = '') {
		$GLOBALS['__test_hook_calls'][] = array('plugin' => $plugin, 'hook' => $hook, 'function' => $function, 'file' => $file);
		return true;
	}
}

if (!function_exists('api_plugin_register_realm')) {
	function api_plugin_register_realm($plugin, $file, $name, $type = 0) {
		$GLOBALS['__test_realm_calls'][] = array('plugin' => $plugin, 'file' => $file, 'name' => $name, 'type' => $type);
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
		return true;
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
		return htmlspecialchars((string) $string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('__')) {
	function __($text, $domain = '') {
		return $text;
	}
}

if (!function_exists('__esc')) {
	function __esc($text, $domain = '') {
		return htmlspecialchars((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

if (!function_exists('is_realm_allowed')) {
	function is_realm_allowed($realm) {
		return true;
	}
}

if (!function_exists('get_current_page')) {
	function get_current_page() {
		return '';
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

if (!function_exists('isset_request_var')) {
	function isset_request_var($name) {
		return false;
	}
}

if (!function_exists('set_request_var')) {
	function set_request_var($name, $value) {
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

if (!function_exists('sanitize_search_string')) {
	function sanitize_search_string($string) {
		return $string;
	}
}

if (!function_exists('array_to_sql_or')) {
	function array_to_sql_or($array, $sql_column) {
		return '1=1';
	}
}

if (!function_exists('sql_save')) {
	function sql_save($array, $table, $key = 'id') {
		return isset($array['id']) ? $array['id'] : 1;
	}
}

if (!function_exists('is_hexadecimal')) {
	function is_hexadecimal($value) {
		return (bool) preg_match('/^[0-9a-fA-F]+$/', (string) $value);
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

if (!function_exists('plugin_test_read_source')) {
	function plugin_test_read_source($relative_file) {
		$path = realpath(__DIR__ . '/../' . $relative_file);
		if ($path === false) {
			throw new RuntimeException("Unable to resolve required file: {$relative_file}");
		}

		$contents = file_get_contents($path);
		if ($contents === false) {
			throw new RuntimeException("Unable to read required file: {$relative_file}");
		}

		return $contents;
	}
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
 * Load a single named function out of a plugin source file without
 * executing that file.
 *
 * mikrotik.php, poller_mikrotik.php, and poller_graphs.php are CLI/UI entry
 * points: requiring them runs top-level code (chdir(), include() of real
 * Cacti files, exit()) that has no place in a unit test process. The pure
 * helper functions they define (string/number formatting, OID parsing,
 * etc.) are still worth covering directly, so this tokenizes the file,
 * isolates the source text of exactly one `function name(...) { ... }`
 * declaration, and eval()s only that isolated declaration. No user input
 * ever reaches this eval(); the input is always this plugin's own
 * version-controlled source read from disk.
 *
 * @param string $relative_file Plugin file, relative to the plugin root.
 * @param string $function_name Name of the function to isolate and define.
 *
 * @return void
 */
function mikrotik_test_load_function($relative_file, $function_name) {
	static $loaded = array();

	$key = $relative_file . '::' . $function_name;
	if (isset($loaded[$key]) || function_exists($function_name)) {
		$loaded[$key] = true;
		return;
	}

	$path = realpath(dirname(__DIR__) . '/' . $relative_file);
	if ($path === false) {
		throw new RuntimeException("Unable to resolve required plugin source: {$relative_file}");
	}

	$source = file_get_contents($path);
	if ($source === false) {
		throw new RuntimeException("Unable to read required plugin source: {$relative_file}");
	}

	$tokens = token_get_all($source);
	$count  = count($tokens);
	$start  = null;

	for ($i = 0; $i < $count; $i++) {
		if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
			continue;
		}

		for ($j = $i + 1; $j < $count; $j++) {
			if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
				continue;
			}

			if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING && $tokens[$j][1] === $function_name) {
				$start = $i;
			}

			break;
		}

		if ($start !== null) {
			break;
		}
	}

	if ($start === null) {
		throw new RuntimeException("Unable to locate function {$function_name}() in {$relative_file}");
	}

	$depth      = 0;
	$seen_brace = false;
	$buffer     = '';

	for ($i = $start; $i < $count; $i++) {
		$text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
		$buffer .= $text;

		if ($text === '{') {
			$depth++;
			$seen_brace = true;
		} elseif ($text === '}') {
			$depth--;
		}

		if ($seen_brace && $depth === 0) {
			break;
		}
	}

	eval($buffer);

	if (!function_exists($function_name)) {
		throw new RuntimeException("Failed to define function {$function_name}() from {$relative_file}");
	}

	$loaded[$key] = true;
}

/**
 * Convenience wrapper around mikrotik_test_load_function() to isolate and
 * define several functions from the same plugin source file in one call.
 *
 * @param string $file           Absolute or plugin-root-relative path to the source file.
 * @param array  $function_names Names of the functions to isolate and define.
 *
 * @return void
 */
function mikrotik_test_load_functions($file, array $function_names) {
	$plugin_root = realpath(dirname(__DIR__));
	$absolute    = realpath($file) ?: realpath($plugin_root . '/' . $file);

	if ($absolute === false) {
		throw new RuntimeException("Unable to resolve required plugin source: {$file}");
	}

	$relative_file = ltrim(str_replace($plugin_root, '', $absolute), DIRECTORY_SEPARATOR);

	foreach ($function_names as $function_name) {
		mikrotik_test_load_function($relative_file, $function_name);
	}
}
