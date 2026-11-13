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

use PHPUnit\Framework\Attributes\DataProvider;

final class ApiTlsTest extends PHPUnit\Framework\TestCase {
	/** @return array<string, array{string, string, bool, bool, bool}> */
	public static function policies(): array {
		$cases = [];

		foreach (['probe', 'client'] as $boundary) {
			$cases[$boundary . ': trusted']        = [$boundary, 'localhost', false, true, true];
			$cases[$boundary . ': untrusted']      = [$boundary, 'localhost', false, false, false];
			$cases[$boundary . ': wrong hostname'] = [$boundary, '127.0.0.1', false, true, false];
			$cases[$boundary . ': compatibility']  = [$boundary, '127.0.0.1', true, false, true];
		}

		return $cases;
	}

	#[DataProvider('policies')]
	public function testCertificatePolicy(string $boundary, string $host, bool $allow, bool $trusted, bool $expected): void {
		$directory = sys_get_temp_dir() . '/mikrotik-tls-' . bin2hex(random_bytes(8));
		$this->assertTrue(mkdir($directory, 0700));
		$server       = null;
		$client       = null;
		$server_pipes = [];
		$client_pipes = [];

		try {
			// Generate certificates at run time so neither private keys nor expiring fixtures are committed.
			$key = openssl_pkey_new(['private_key_bits' => 2048]);
			$this->assertNotFalse($key);
			$csr = openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
			$this->assertNotFalse($csr);
			$certificate = openssl_csr_sign($csr, null, $key, 2, ['digest_alg' => 'sha256']);
			$this->assertNotFalse($certificate);
			$this->assertTrue(openssl_pkey_export($key, $private_key));
			$this->assertTrue(openssl_x509_export($certificate, $public_certificate));
			file_put_contents($directory . '/server.pem', $private_key . $public_certificate);

			$other_key = openssl_pkey_new(['private_key_bits' => 2048]);
			$this->assertNotFalse($other_key);
			$other_csr = openssl_csr_new(['commonName' => 'unrelated-ca'], $other_key);
			$this->assertNotFalse($other_csr);
			$other_certificate = openssl_csr_sign($other_csr, null, $other_key, 2);
			$this->assertNotFalse($other_certificate);
			$this->assertTrue(openssl_x509_export($other_certificate, $other_public));
			file_put_contents($directory . '/ca.pem', $trusted ? $public_certificate : $other_public);

			$fixture     = __DIR__ . '/../fixtures/api-tls.php';
			$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
			$server      = proc_open([PHP_BINARY, $fixture, 'server', $directory . '/server.pem', $boundary], $descriptors, $server_pipes);
			$this->assertIsResource($server);
			fclose($server_pipes[0]);
			stream_set_timeout($server_pipes[1], 15);
			$address = trim((string) fgets($server_pipes[1]));
			$this->assertMatchesRegularExpression('/^127\.0\.0\.1:\d+$/', $address);
			$port = substr(strrchr($address, ':'), 1);

			// openssl.cafile is a startup setting. Use a child so the test never changes the runner's trust store.
			$client = proc_open([PHP_BINARY, '-d', 'openssl.cafile=' . $directory . '/ca.pem', $fixture, 'connect', $boundary, $host, $port, $allow ? 'on' : ''], $descriptors, $client_pipes);
			$this->assertIsResource($client);
			fclose($client_pipes[0]);
			stream_set_timeout($client_pipes[1], 15);
			$output = stream_get_contents($client_pipes[1]);
			$this->assertFalse(stream_get_meta_data($client_pipes[1])['timed_out']);
			$errors = stream_get_contents($client_pipes[2]);
			$this->assertSame('', $errors);
			$this->assertSame(0, proc_close($client));
			$client = null;
			$this->assertSame(['connected' => $expected], json_decode($output, true, 512, JSON_THROW_ON_ERROR));

			$server_output = stream_get_contents($server_pipes[1]);
			$this->assertSame('', stream_get_contents($server_pipes[2]));
			$this->assertSame(0, proc_close($server));
			$server   = null;
			$observed = json_decode($server_output, true, 512, JSON_THROW_ON_ERROR);
			$this->assertSame($expected && $boundary === 'client', $observed['login']);

			// A hostname mismatch can be detected after the server finishes its handshake.
			if ($expected) {
				$this->assertTrue($observed['tls']);
			}
		} finally {
			foreach ([$client, $server] as $process) {
				if (is_resource($process)) {
					proc_terminate($process);
					proc_close($process);
				}
			}

			foreach (array_merge($client_pipes, $server_pipes) as $pipe) {
				if (is_resource($pipe)) {
					fclose($pipe);
				}
			}

			foreach (glob($directory . '/*') as $file) {
				unlink($file);
			}
			rmdir($directory);
		}
	}
}
