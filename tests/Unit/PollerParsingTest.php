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

// poller_mikrotik.php is a CLI entry point, so only the specific pure
// parsing helper functions under test are isolated out of it (see
// mikrotik_test_load_function() in tests/bootstrap-unit.php) instead of
// requiring the whole file.
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_dateParse');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_macParse');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_splitBaseIndex');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_parse_ttl');
mikrotik_test_load_function('poller_mikrotik.php', 'uptimeToSeconds');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_api_apply_ssl');
mikrotik_test_load_function('poller_mikrotik.php', 'collectHostIndexedOid');

if (!defined('SNMP_WEBUI')) {
	define('SNMP_WEBUI', 1);
}

if (!function_exists('cacti_snmp_walk')) {
	function cacti_snmp_walk(...$args) {
		return $GLOBALS['__mikrotik_snmp_walks'][$args[2]] ?? [];
	}
}

if (!function_exists('debug')) {
	function debug($message) {
	}
}

if (!function_exists('db_qstr')) {
	function db_qstr($value) {
		return "'" . addslashes((string) $value) . "'";
	}
}

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

describe('mikrotik_api_apply_ssl()', function () {
	it('enables SSL and selects the api-ssl port when the credential ssl flag is on', function () {
		$api = new stdClass();
		mikrotik_api_apply_ssl($api, array('ssl' => 'on'));

		expect($api->ssl)->toBeTrue();
		expect($api->port)->toBe(8729);
	});

	it('defaults to no SSL and the plaintext api port when ssl is unset', function () {
		$api = new stdClass();
		mikrotik_api_apply_ssl($api, array());

		expect($api->ssl)->toBeFalse();
		expect($api->port)->toBe(8728);
	});

	it('treats any non-"on" ssl value as disabled', function () {
		$api = new stdClass();
		mikrotik_api_apply_ssl($api, array('ssl' => ''));

		expect($api->ssl)->toBeFalse();
		expect($api->port)->toBe(8728);
	});
});

describe('collectHostIndexedOid()', function () {
	beforeEach(function () {
		$GLOBALS['__test_db_calls'] = [];
		$GLOBALS['__mikrotik_snmp_walks'] = [];
	});

	it('keeps the previous rows visible when a required walk is empty', function () {
		$GLOBALS['__mikrotik_snmp_walks'] = [
			'.1.3.6.1.4.1.14988.1.1.2.2.1.2' => [],
			'.1.3.6.1.4.1.14988.1.1.2.2.1.3' => [
				['oid' => '.1.3.6.1.4.1.14988.1.1.2.2.1.3.7', 'value' => 'some-flow'],
			],
		];

		$host = [
			'id' => 8, 'description' => 'router', 'hostname' => '192.0.2.1',
			'snmp_community' => 'public', 'snmp_version' => 2, 'snmp_username' => '',
			'snmp_password' => '', 'snmp_auth_protocol' => '', 'snmp_priv_passphrase' => '',
			'snmp_priv_protocol' => '', 'snmp_context' => '', 'snmp_port' => 161,
			'snmp_timeout' => 500, 'max_oids' => 10, 'snmp_engine_id' => '',
		];

		collectHostIndexedOid(
			$host,
			[
				'name' => '.1.3.6.1.4.1.14988.1.1.2.2.1.2',
				'flow' => '.1.3.6.1.4.1.14988.1.1.2.2.1.3',
			],
			'plugin_mikrotik_trees',
			'trees',
			true
		);

		expect($GLOBALS['__test_db_calls'])->toBeEmpty();
	});

	it('marks prior rows absent only after required walks succeed', function () {
		$GLOBALS['__mikrotik_snmp_walks'] = [
			'.1.3.6.1.4.1.14988.1.1.2.2.1.2' => [
				['oid' => '.1.3.6.1.4.1.14988.1.1.2.2.1.2.7', 'value' => 'queue-a'],
			],
			'.1.3.6.1.4.1.14988.1.1.2.2.1.3' => [],
		];

		$host = [
			'id' => 8, 'description' => 'router', 'hostname' => '192.0.2.1',
			'snmp_community' => 'public', 'snmp_version' => 2, 'snmp_username' => '',
			'snmp_password' => '', 'snmp_auth_protocol' => '', 'snmp_priv_passphrase' => '',
			'snmp_priv_protocol' => '', 'snmp_context' => '', 'snmp_port' => 161,
			'snmp_timeout' => 500, 'max_oids' => 10, 'snmp_engine_id' => '',
		];

		collectHostIndexedOid(
			$host,
			[
				'name' => '.1.3.6.1.4.1.14988.1.1.2.2.1.2',
				'flow' => '.1.3.6.1.4.1.14988.1.1.2.2.1.3',
			],
			'plugin_mikrotik_trees',
			'trees',
			true
		);

		expect($GLOBALS['__test_db_calls'])->not->toBeEmpty();
		expect($GLOBALS['__test_db_calls'][0]['sql'])->toContain('SET present=0');
	});
});
