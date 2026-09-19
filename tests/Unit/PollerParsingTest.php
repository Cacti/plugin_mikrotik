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
 * poller_mikrotik.php is the CLI poller entry point: it chdir()s, includes
 * real Cacti core libraries, parses argv and exit()s before its helper
 * functions are even defined. As with mikrotik.php, only the named pure
 * parsing helpers are pulled out of the real source via the tokenizer-based
 * loader, so the top-level script never runs.
 */
beforeEach(function () {
	mikrotik_test_load_functions(dirname(__DIR__, 2) . '/poller_mikrotik.php', [
		'mikrotik_dateParse',
		'mikrotik_macParse',
		'mikrotik_splitBaseIndex',
		'mikrotik_parse_ttl',
		'uptimeToSeconds',
	]);
});

describe('mikrotik_dateParse', function () {
	it('normalizes a RouterOS date/time pair into "Y-m-d H:i:s"', function () {
		expect(mikrotik_dateParse('2024-01-15,12:34:56.789'))->toBe('2024-01-15 12:34:56');
		expect(mikrotik_dateParse('2024-01-15,12:34:56'))->toBe('2024-01-15 12:34:56');
	});

	it('falls back to the current date/time when the value cannot be parsed', function () {
		expect(mikrotik_dateParse('not-a-date'))->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
	});
});

describe('mikrotik_macParse', function () {
	it('returns an already-hexadecimal MAC unchanged', function () {
		expect(mikrotik_macParse('0011223344AA'))->toBe('0011223344AA');
	});

	it('hex-encodes and colon-separates a raw binary MAC', function () {
		expect(mikrotik_macParse("\x00\x11\x22\x33\x44\xAA"))->toBe('00:11:22:33:44:aa');
	});
});

describe('mikrotik_splitBaseIndex', function () {
	it('splits a trailing OID segment into base and index at the given depth', function () {
		expect(mikrotik_splitBaseIndex('1.2.3.4.5', 1))->toBe(['1.2.3.4', '5']);
		expect(mikrotik_splitBaseIndex('1.2.3.4.5', 2))->toBe(['1.2.3', '4.5']);
	});
});

describe('mikrotik_parse_ttl', function () {
	it('sums d/h/m/s components into total seconds', function () {
		expect(mikrotik_parse_ttl('1d2h3m4s'))->toBe(93784);
		expect(mikrotik_parse_ttl('5m30s'))->toBe(330);
	});
});

describe('uptimeToSeconds', function () {
	it('treats "never" as zero uptime', function () {
		expect(uptimeToSeconds('never'))->toBe(0);
	});

	it('sums w/d/h/m/s components into total seconds', function () {
		expect(uptimeToSeconds('2w3d4h5m6s'))->toBe(1483506);
		expect(uptimeToSeconds('5m30s'))->toBe(330);
	});
});
