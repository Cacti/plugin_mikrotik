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

// mikrotik.php is a UI entry point, so only the specific pure helper
// functions under test are isolated out of it (see
// mikrotik_test_load_function() in tests/bootstrap-unit.php) instead of
// requiring the whole file.
mikrotik_test_load_function('mikrotik.php', 'mikrotik_right');
mikrotik_test_load_function('mikrotik.php', 'mikrotik_format_uptime');
mikrotik_test_load_function('mikrotik.php', 'mikrotik_memory');
mikrotik_test_load_function('mikrotik.php', 'mikrotik_get_timeout');
mikrotik_test_load_function('mikrotik.php', 'mikrotik_get_runtime');
mikrotik_test_load_function('mikrotik.php', 'mikrotik_get_network');

describe('mikrotik_right()', function () {
	it('takes the rightmost characters without stripping', function () {
		expect(mikrotik_right('0005', 2))->toBe('05');
	});

	it('strips leading zeros when asked', function () {
		expect(mikrotik_right('0005', 2, true))->toBe('5');
	});
});

describe('mikrotik_format_uptime()', function () {
	it('omits the days segment when zero', function () {
		expect(mikrotik_format_uptime(0, 5, 9))->toBe('05h 09m');
	});

	it('includes a stripped days segment when nonzero', function () {
		expect(mikrotik_format_uptime(2, 3, 4))->toBe('2d 03h 04m');
	});
});

describe('mikrotik_memory()', function () {
	it('leaves small values as bytes', function () {
		expect(mikrotik_memory(500))->toBe('500  ');
	});

	it('scales to kilobytes', function () {
		expect(mikrotik_memory(2048))->toBe('2 K');
	});

	it('scales to megabytes', function () {
		expect(mikrotik_memory(3145728))->toBe('3 M');
	});

	it('appends a caller-supplied suffix', function () {
		expect(mikrotik_memory(2048, 'b/s'))->toBe('2 Kb/s');
	});
});

describe('mikrotik_get_timeout()', function () {
	it('renders sub-minute values in seconds', function () {
		expect(mikrotik_get_timeout(45))->toBe('45s');
	});

	it('renders minutes and seconds', function () {
		expect(mikrotik_get_timeout(125))->toBe('2m5s');
	});

	it('renders days, hours, minutes, and seconds together', function () {
		expect(mikrotik_get_timeout(90061))->toBe('1d1h1m1s');
	});
});

describe('mikrotik_get_runtime()', function () {
	it('renders days, hours, and minutes', function () {
		expect(mikrotik_get_runtime(90061))->toBe('1:1:1');
	});

	it('renders zero days and hours for short runtimes', function () {
		expect(mikrotik_get_runtime(61))->toBe('0:0:1');
	});
});

describe('mikrotik_get_network()', function () {
	it('derives a /24 from 255.255.255.0', function () {
		expect(mikrotik_get_network('255.255.255.0'))->toBe(24);
	});

	it('derives a /32 from 255.255.255.255', function () {
		expect(mikrotik_get_network('255.255.255.255'))->toBe(32);
	});

	it('derives a /25 from 255.255.255.128', function () {
		expect(mikrotik_get_network('255.255.255.128'))->toBe(25);
	});

	it('derives a /16 from 255.255.0.0', function () {
		expect(mikrotik_get_network('255.255.0.0'))->toBe(16);
	});
});
