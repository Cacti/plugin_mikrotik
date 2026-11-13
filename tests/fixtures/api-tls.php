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

// The test runner starts this fixture with synthetic credentials and ephemeral ports.
if (PHP_SAPI !== 'cli') {
	exit(1);
}

if ($argv[1] === 'server') {
	$context = stream_context_create(['ssl' => ['local_cert' => $argv[2]]]);
	$server  = stream_socket_server('tcp://127.0.0.1:0', $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

	if ($server === false) {
		throw new RuntimeException($error);
	}

	print stream_socket_get_name($server, false) . "\n";
	flush();
	$peer = stream_socket_accept($server, 10);

	if ($peer === false) {
		throw new RuntimeException('TLS test client did not connect');
	}

	stream_set_timeout($peer, 5);
	// Rejected certificates intentionally cause the server handshake to fail.
	$tls   = @stream_socket_enable_crypto($peer, true, STREAM_CRYPTO_METHOD_TLS_SERVER);
	$login = false;

	if ($tls && $argv[3] === 'client') {
		$data = '';

		while (strlen($data) < 4096 && !str_ends_with($data, "\0")) {
			$part = fread($peer, 1024);

			if ($part === false || $part === '') {
				break;
			}
			$data .= $part;
		}
		$login = str_contains($data, '/login') && str_contains($data, '=password=test-value');

		if ($login) {
			fwrite($peer, "\x05!done\0");
		}
	}

	fclose($peer);
	fclose($server);
	print json_encode(['tls' => $tls === true, 'login' => $login], JSON_THROW_ON_ERROR);
	exit(0);
}

require __DIR__ . '/../bootstrap-unit.php';
require __DIR__ . '/../../RouterOS/routeros_api.class.php';
mikrotik_test_load_function('poller_mikrotik.php', 'mikrotik_api_apply_ssl');
mikrotik_test_load_function('setup.php', 'mikrotik_ssl_port_available');
$GLOBALS['__test_config_options']['mikrotik_api_allow_unverified_tls'] = $argv[5];

if ($argv[2] === 'probe') {
	$connected = mikrotik_ssl_port_available($argv[3], (int) $argv[4], 3);
} else {
	$api = new RouterosAPI();
	mikrotik_api_apply_ssl($api, ['ssl' => 'on']);
	$api->port     = (int) $argv[4];
	$api->attempts = 1;
	$api->delay    = 0;
	$connected     = $api->connect($argv[3], 'test-user', 'test-value');
	$api->disconnect();
}

print json_encode(['connected' => $connected], JSON_THROW_ON_ERROR);
