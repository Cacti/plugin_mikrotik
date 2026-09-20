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

describe('PHP 8.2 compatibility in mikrotik', function () {
	$files = array(
		'mikrotik.php',
		'mikrotik_users.php',
		'poller_graphs.php',
		'poller_mikrotik.php',
		'setup.php',
	);

	// The plugin's PHP floor is 8.2, so 8.0/8.1 syntax (str_contains, match,
	// nullsafe, constructor promotion, union types) is allowed. These checks
	// instead guard against syntax/functions that require PHP 8.3 or 8.4,
	// which would break the 8.2 leg of the CI matrix, plus functions removed
	// well before 8.2 that would break every leg.
	it('does not use each() (removed in PHP 8.0)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\beach\s*\(/', $c))->toBe(0, "{$f} uses each() (removed in PHP 8.0)");
		}
	});

	it('does not use create_function() (removed in PHP 8.0)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\bcreate_function\s*\(/', $c))->toBe(0, "{$f} uses create_function() (removed in PHP 8.0)");
		}
	});

	it('does not use curly-brace string offset access (removed in PHP 8.0)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\$\w+\s*\{\s*\d+\s*\}/', $c))->toBe(0, "{$f} uses curly-brace string offset access (removed in PHP 8.0)");
		}
	});

	it('does not use json_validate() (PHP 8.3)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\bjson_validate\s*\(/', $c))->toBe(0, "{$f} uses json_validate() (PHP 8.3)");
		}
	});

	it('does not use dynamic class constant fetch syntax (PHP 8.3)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\w+::\{.+\}/', $c))->toBe(0, "{$f} uses dynamic class constant fetch (PHP 8.3)");
		}
	});

	it('does not use typed class constants (PHP 8.3)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\b(?:public|private|protected|final)\s+const\s+\??[\w|]+\s+\w+\s*=/', $c))->toBe(0,
				"{$f} uses typed class constants (PHP 8.3)"
			);
		}
	});

	it('does not use the #[Override] attribute (PHP 8.4)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/#\[\s*\\\\?Override\s*\]/', $c))->toBe(0, "{$f} uses #[Override] attribute (PHP 8.4)");
		}
	});

	it('does not use asymmetric visibility (PHP 8.4)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\b(?:public|protected)\s*\(\s*set\s*\)/', $c))->toBe(0,
				"{$f} uses asymmetric visibility (PHP 8.4)"
			);
		}
	});

	it('does not use property hooks (PHP 8.4)', function () use ($files) {
		foreach ($files as $f) {
			$p = realpath(__DIR__ . '/../../' . $f);
			if ($p === false) continue;
			$c = file_get_contents($p);
			if ($c === false) continue;
			expect(preg_match('/\b(?:get|set)\s*\{/', $c))->toBe(0, "{$f} uses property hooks (PHP 8.4)");
		}
	});
});
