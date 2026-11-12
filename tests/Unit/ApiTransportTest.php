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
*/

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class ApiTransportTest extends PHPUnit\Framework\TestCase {
	/**
	 * @return array<string, array{string, bool, bool}>
	 */
	public static function connections(): array {
		return [
			'TLS unavailable'       => ['on', false, false],
			'TLS login rejected'    => ['on', false, true],
			'TLS success'           => ['on', true, true],
			'configured plaintext'  => ['', true, true],
			'plaintext unavailable' => ['', false, false],
		];
	}

	/**
	 * @param string $ssl              Stored transport preference.
	 * @param bool   $connected        Connection result.
	 * @param bool   $socket_connected Whether the transport was established.
	 *
	 * @return void
	 */
	#[DataProvider('connections')]
	#[RunInSeparateProcess]
	public function testConnectionKeepsTheConfiguredTransport(string $ssl, bool $connected, bool $socket_connected): void {
		class_alias(MikrotikApiTransportStub::class, 'RouterosAPI');
		MikrotikApiTransportStub::$connected                      = $connected;
		MikrotikApiTransportStub::$transport_connected            = $socket_connected;
		$GLOBALS['__test_config_options']['mikrotik_api_enabled'] = 'on';
		$GLOBALS['__test_db_calls']                               = [];
		mikrotik_test_mock_db('db_fetch_row_prepared', 'plugin_mikrotik_credentials', ['user' => 'test-user', 'password' => 'test-value', 'ssl' => $ssl]);
		mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_api_apply_ssl');
		mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_api_open');

		$result = mikrotik_api_open(['id' => 8, 'hostname' => 'router.example']);

		$this->assertSame($connected, $result !== false);
		if ($result !== false) {
			$this->assertFalse($result->ssl_allow_self_signed);
		}
		$this->assertSame([[$ssl === 'on', $ssl === 'on' ? 8729 : 8728]], MikrotikApiTransportStub::$attempted);
		$this->assertSame([], array_values(array_filter($GLOBALS['__test_db_calls'], static fn ($call) => $call['fn'] === 'db_execute_prepared')));
	}
}

/** Records transport selection at the RouterOS client boundary. */
final class MikrotikApiTransportStub {
	public bool $ssl                        = false;
	public bool $ssl_allow_self_signed      = true;
	public bool $debug                      = false;
	public bool $socket_connected           = false;
	public int $port                        = 8728;
	public int $attempts                    = 5;
	public static bool $connected           = false;
	public static bool $transport_connected = false;
	public static array $attempted          = [];

	/**
	 * @param string $host     Device address.
	 * @param string $user     Test account.
	 * @param string $password Test credential.
	 *
	 * @return bool Configured connection result.
	 */
	public function connect($host, $user, $password): bool {
		self::$attempted[]      = [$this->ssl, $this->port];
		$this->socket_connected = self::$transport_connected;

		return self::$connected;
	}
}
