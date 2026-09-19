<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

// poller_mikrotik.php is a CLI entry point, so only the specific pure
// parsing helper functions under test are isolated out of it (see
// mikrotik_test_load_function() in tests/bootstrap-unit.php) instead of
// requiring the whole file.
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_dateParse');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_macParse');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_splitBaseIndex');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_parse_ttl');
mikrotik_test_load_function('poller_mikrotik.php', 'uptimeToSeconds');

describe('mikrotik_dateParse()', function () {
	it('normalizes a parseable RouterOS clock value', function () {
		expect(mikrotik_dateParse('2024-01-02,03:04:05'))->toBe('2024-01-02 03:04:05');
	});

	it('drops sub-second precision before parsing', function () {
		expect(mikrotik_dateParse('2024-01-02,03:04:05.678'))->toBe('2024-01-02 03:04:05');
	});

	it('falls back to the current time for unparseable values', function () {
		expect(mikrotik_dateParse('garbage,unparsable.deeper'))->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
	});
});

describe('mikrotik_macParse()', function () {
	it('returns already-hexadecimal values unchanged', function () {
		expect(mikrotik_macParse('aabbccddeeff'))->toBe('aabbccddeeff');
	});

	it('hex-encodes and colon-joins raw binary octets', function () {
		expect(mikrotik_macParse("\x00\x1A\xFF"))->toBe('00:1a:ff');
	});
});

describe('mikrotik_splitBaseIndex()', function () {
	it('splits off the trailing single sub-identifier by default', function () {
		expect(mikrotik_splitBaseIndex('10.20.30.40'))->toBe(array('10.20.30', '40'));
	});

	it('splits off multiple trailing sub-identifiers at a given depth', function () {
		expect(mikrotik_splitBaseIndex('10.20.30.40', 2))->toBe(array('10.20', '30.40'));
	});
});

describe('mikrotik_parse_ttl()', function () {
	it('accumulates days, hours, minutes, and seconds', function () {
		expect(mikrotik_parse_ttl('1d2h3m4s'))->toBe(93784);
	});

	it('accumulates a partial minutes/seconds value', function () {
		expect(mikrotik_parse_ttl('5m10s'))->toBe(310);
	});
});

describe('uptimeToSeconds()', function () {
	it('returns zero for "never"', function () {
		expect(uptimeToSeconds('never'))->toBe(0);
	});

	it('accumulates weeks, days, hours, minutes, and seconds', function () {
		expect(uptimeToSeconds('2w3d4h5m6s'))->toBe(1483506);
	});

	it('accumulates a value with no weeks component', function () {
		expect(uptimeToSeconds('1d2h3m4s'))->toBe(93784);
	});
});
