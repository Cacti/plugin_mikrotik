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
 * Locks in mikrotik.php's stored-XSS hardening: DB-sourced values that are
 * printed directly (i.e. not already routed through filter_value() or
 * htmlspecialchars()) must go through html_escape().
 */
describe('DB output escaping in mikrotik.php', function () {
	$source = plugin_test_read_source('mikrotik.php');

	it('escapes device health/version fields', function () use ($source) {
		expect($source)->toContain("html_escape(\$row['sysDescr'])");
		expect($source)->toContain("html_escape(\$row['firmwareVersion'])");
		expect($source)->toContain("html_escape(\$row['licVersion'])");
	});

	it('escapes host/DNS/DHCP descriptive fields', function () use ($source) {
		expect($source)->toContain("html_escape(\$row['description'])");
		expect($source)->toContain("html_escape(\$row['status'])");
		expect($source)->toContain("html_escape(\$row['type'])");
		expect($source)->toContain("html_escape(\$row['static'])");
		expect($source)->toContain("html_escape(\$row['last_updated'])");
	});

	it('escapes wireless registration/AP fields', function () use ($source) {
		expect($source)->toContain("html_escape(\$row['mac'])");
		expect($source)->toContain("html_escape(\$row['last_seen'])");
		expect($source)->toContain("html_escape(\$row['SignalToNoise'])");
		expect($source)->toContain("html_escape(\$row['apClientCount'])");
		expect($source)->toContain("html_escape(\$row['apAuthClientCount'])");
		expect($source)->toContain("html_escape(\$row['apNoiseFloor'])");
		expect($source)->toContain("html_escape(\$row['apOverallTxCCQ'])");
	});

	it('escapes the shared memory/timeout formatter output at its source', function () use ($source) {
		expect(preg_match('/function mikrotik_memory\([^)]*\)\s*\{.*?html_escape\(/s', $source))->toBe(1);
		expect(preg_match('/function mikrotik_get_timeout\([^)]*\)\s*\{.*?html_escape\(/s', $source))->toBe(1);
	});
});

/*
 * mikrotik_memory()/mikrotik_get_timeout() format numbers for display; wrapping
 * their return in html_escape() must not change any previously-asserted output.
 */
beforeEach(function () {
	mikrotik_test_load_functions(dirname(__DIR__, 2) . '/mikrotik.php', [
		'mikrotik_memory',
		'mikrotik_get_timeout',
	]);
});

describe('mikrotik_memory (escaped)', function () {
	it('still returns plain formatted output unaffected by escaping', function () {
		expect(mikrotik_memory(512))->toBe('512  ');
		expect(mikrotik_memory(2048))->toBe('2 K');
		expect(mikrotik_memory(1500, 'B'))->toBe('1.46 KB');
	});
});

describe('mikrotik_get_timeout (escaped)', function () {
	it('still returns plain formatted output unaffected by escaping', function () {
		expect(mikrotik_get_timeout(45))->toBe('45s');
		expect(mikrotik_get_timeout(90061))->toBe('1d1h1m1s');
	});
});
