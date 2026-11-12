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
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_version_is_seven_plus');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_api_counter_pair');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_api_clamp_uint32');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_api_uptime_dhms');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_gauge_unit_label');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_gauge_scale');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_gauge_health_column');
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_wifi_index');
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

describe('mikrotik_version_is_seven_plus()', function () {
	it('returns true for a v7 version string', function () {
		expect(mikrotik_version_is_seven_plus('7.25.1'))->toBeTrue();
	});

	it('returns true for a major version above 7', function () {
		expect(mikrotik_version_is_seven_plus('8.0.0'))->toBeTrue();
	});

	it('returns false for a pre-v7 version string', function () {
		expect(mikrotik_version_is_seven_plus('6.49.7'))->toBeFalse();
	});

	it('returns false for an empty or null version', function () {
		expect(mikrotik_version_is_seven_plus(''))->toBeFalse();
		expect(mikrotik_version_is_seven_plus(null))->toBeFalse();
	});
});

describe('mikrotik_api_counter_pair()', function () {
	it('splits a tx,rx counter pair into integers', function () {
		expect(mikrotik_api_counter_pair('8058206,30403984'))->toBe([8058206, 30403984]);
	});

	it('defaults a missing rx component to zero', function () {
		expect(mikrotik_api_counter_pair('123'))->toBe([123, 0]);
	});

	it('defaults non-numeric or empty components to zero', function () {
		expect(mikrotik_api_counter_pair(''))->toBe([0, 0]);
		expect(mikrotik_api_counter_pair('abc,def'))->toBe([0, 0]);
	});
});

describe('mikrotik_api_clamp_uint32()', function () {
	it('passes through an in-range value', function () {
		expect(mikrotik_api_clamp_uint32(1201000000))->toBe(1201000000);
	});

	it('clamps a value above the unsigned 32-bit maximum', function () {
		expect(mikrotik_api_clamp_uint32('6000000000'))->toBe(4294967295);
	});

	it('clamps a negative value to zero', function () {
		expect(mikrotik_api_clamp_uint32(-5))->toBe(0);
	});
});

describe('mikrotik_api_uptime_dhms()', function () {
	it('formats a RouterOS uptime string as d:h:m:s', function () {
		expect(mikrotik_api_uptime_dhms('4d16h56m23s'))->toBe('4:16:56:23');
	});

	it('handles a sub-day uptime', function () {
		expect(mikrotik_api_uptime_dhms('13h52m56s'))->toBe('0:13:52:56');
	});

	it('returns zeroes for "never"', function () {
		expect(mikrotik_api_uptime_dhms('never'))->toBe('0:0:0:0');
	});
});

describe('mikrotik_gauge_unit_label()', function () {
    it('maps each mtxrGaugeUnit enum value to its label', function () {
        expect(mikrotik_gauge_unit_label(1))->toBe('C');
        expect(mikrotik_gauge_unit_label(2))->toBe('RPM');
        expect(mikrotik_gauge_unit_label(3))->toBe('V');
        expect(mikrotik_gauge_unit_label(4))->toBe('A');
        expect(mikrotik_gauge_unit_label(5))->toBe('W');
        expect(mikrotik_gauge_unit_label(6))->toBe('status');
    });

    it('returns an empty label for an unknown enum value', function () {
        expect(mikrotik_gauge_unit_label(0))->toBe('');
        expect(mikrotik_gauge_unit_label(99))->toBe('');
    });
});

describe('mikrotik_gauge_scale()', function () {
    it('leaves celsius, rpm, and status values unscaled', function () {
        expect(mikrotik_gauge_scale(59, 1))->toBe(59.0);
        expect(mikrotik_gauge_scale(5654, 2))->toBe(5654.0);
        expect(mikrotik_gauge_scale(1, 6))->toBe(1.0);
    });

    it('divides deci-volt/amp/watt values by ten', function () {
        expect(mikrotik_gauge_scale(121, 3))->toBe(12.1);
        expect(mikrotik_gauge_scale(42, 4))->toBe(4.2);
        expect(mikrotik_gauge_scale(508, 5))->toBe(50.8);
    });
});

describe('mikrotik_gauge_health_column()', function () {
    it('maps well-known sensor names to legacy health columns', function () {
        expect(mikrotik_gauge_health_column('cpu-temperature'))->toBe('HlCpuTemperature');
        expect(mikrotik_gauge_health_column('fan1-speed'))->toBe('HlFanSpeed1');
        expect(mikrotik_gauge_health_column('power-consumption'))->toBe('HlPower');
    });

    it('is case-insensitive', function () {
        expect(mikrotik_gauge_health_column('CPU-Temperature'))->toBe('HlCpuTemperature');
    });

    it('returns null for sensors with no fixed-column equivalent', function () {
        expect(mikrotik_gauge_health_column('sfp-temperature'))->toBeNull();
        expect(mikrotik_gauge_health_column('fan3-speed'))->toBeNull();
    });
});

describe('mikrotik_wifi_index()', function () {
    it('derives a stable unsigned index from the RouterOS .id', function () {
        expect(mikrotik_wifi_index('*2'))->toBe(2);
        expect(mikrotik_wifi_index('*A'))->toBe(10);
        expect(mikrotik_wifi_index('*1E6'))->toBe(486);
    });

    it('gives distinct indexes to distinct interfaces (no collision)', function () {
        // wifi1 and ap1 would both be index 1 under a name-suffix scheme;
        // their distinct .id values keep them apart.
        expect(mikrotik_wifi_index('*3'))->not->toBe(mikrotik_wifi_index('*4'));
    });

    it('returns 0 for a missing or invalid id', function () {
        expect(mikrotik_wifi_index(''))->toBe(0);
        expect(mikrotik_wifi_index('*'))->toBe(0);
        expect(mikrotik_wifi_index('wifi1'))->toBe(0);
    });
});
