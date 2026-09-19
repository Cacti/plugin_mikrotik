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
 * mikrotik.php is a UI entry point (chdir()s, includes Cacti's auth.php and
 * runs an action switch at the top of the file), so it cannot be require()d
 * directly in a test process. mikrotik_test_load_functions() pulls just the
 * named pure helper functions out of the file via its real source, without
 * executing any of that top-level script code.
 */
beforeEach(function () {
	mikrotik_test_load_functions(dirname(__DIR__, 2) . '/mikrotik.php', [
		'mikrotik_get_network',
		'mikrotik_format_uptime',
		'mikrotik_right',
		'mikrotik_memory',
		'mikrotik_get_runtime',
		'mikrotik_get_timeout',
		'mikrotik_get_device_status_url',
	]);

	$GLOBALS['config']['url_path'] = '/cacti/';
});

describe('mikrotik_get_network', function () {
	it('counts the number of network bits in a dotted-quad netmask', function () {
		expect(mikrotik_get_network('255.255.255.0'))->toBe(24);
		expect(mikrotik_get_network('255.255.255.255'))->toBe(32);
	});

	it('returns 0 for an all-zero mask', function () {
		expect(mikrotik_get_network('0.0.0.0'))->toBe(0);
	});
});

describe('mikrotik_right', function () {
	it('returns the rightmost N characters of a string', function () {
		expect(mikrotik_right('0007', 2))->toBe('07');
		expect(mikrotik_right('12345', 3))->toBe('345');
	});

	it('strips leading zeros from the result when asked', function () {
		expect(mikrotik_right('0007', 3, true))->toBe('7');
	});
});

describe('mikrotik_format_uptime', function () {
	it('includes a days segment only when days is greater than zero', function () {
		expect(mikrotik_format_uptime(2, 3, 4))->toBe('2d 03h 04m');
		expect(mikrotik_format_uptime(0, 5, 30))->toBe('05h 30m');
	});
});

describe('mikrotik_memory', function () {
	it('leaves small values unscaled', function () {
		expect(mikrotik_memory(512))->toBe('512  ');
	});

	it('scales up through K/M suffixes as the value grows', function () {
		expect(mikrotik_memory(2048))->toBe('2 K');
		expect(mikrotik_memory(1048576))->toBe('1 M');
	});

	it('appends the caller-supplied suffix after the scale letter', function () {
		expect(mikrotik_memory(1500, 'B'))->toBe('1.46 KB');
	});
});

describe('mikrotik_get_runtime', function () {
	it('formats seconds as days:hours:minutes', function () {
		expect(mikrotik_get_runtime(90061))->toBe('1:1:1');
	});

	it('returns all zeroes for a runtime under a minute', function () {
		expect(mikrotik_get_runtime(59))->toBe('0:0:0');
	});
});

describe('mikrotik_get_timeout', function () {
	it('renders sub-minute values in plain seconds', function () {
		expect(mikrotik_get_timeout(45))->toBe('45s');
	});

	it('renders larger values as a compact d/h/m/s string', function () {
		expect(mikrotik_get_timeout(90061))->toBe('1d1h1m1s');
	});
});

describe('mikrotik_get_device_status_url', function () {
	it('links the count to a filtered device list when greater than zero', function () {
		$result = mikrotik_get_device_status_url(5, 'up');

		expect($result)->toBe(
			"<a href='/cacti/plugins/mikrotik/mikrotik.php?action=devices&amp;reset=1&amp;status=up' title='View Hosts'>5</a>"
		);
	});

	it('returns the bare count when it is zero', function () {
		expect(mikrotik_get_device_status_url(0, 'up'))->toBe(0);
	});
});
