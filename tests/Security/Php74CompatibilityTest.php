<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Verify plugin source files do not use PHP 8.0+ syntax.
 * Cacti 1.2.x plugins must remain compatible with PHP 7.4.
 */

	$files = array(
		'mikrotik.php',
		'mikrotik_users.php',
		'poller_graphs.php',
		'poller_mikrotik.php',
		'setup.php',
	);

	it('does not use str_contains (PHP 8.0)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$path = realpath(__DIR__ . '/../../' . $relativeFile);

			if ($path === false) {
			throw new RuntimeException("Unable to resolve required plugin source");
		}

			$contents = file_get_contents($path);

			if ($contents === false) {
			throw new RuntimeException("Unable to read required plugin source");
		}

			expect(preg_match('/\bstr_contains\s*\(/', $contents))->toBe(0,
				"{$relativeFile} uses str_contains() which requires PHP 8.0"
			);
		}
	});

	it('does not use str_starts_with (PHP 8.0)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$path = realpath(__DIR__ . '/../../' . $relativeFile);

			if ($path === false) {
			throw new RuntimeException("Unable to resolve required plugin source");
		}

			$contents = file_get_contents($path);

			if ($contents === false) {
			throw new RuntimeException("Unable to read required plugin source");
		}

			expect(preg_match('/\bstr_starts_with\s*\(/', $contents))->toBe(0,
				"{$relativeFile} uses str_starts_with() which requires PHP 8.0"
			);
		}
	});

	it('does not use str_ends_with (PHP 8.0)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$path = realpath(__DIR__ . '/../../' . $relativeFile);

			if ($path === false) {
			throw new RuntimeException("Unable to resolve required plugin source");
		}

			$contents = file_get_contents($path);

			if ($contents === false) {
			throw new RuntimeException("Unable to read required plugin source");
		}

			expect(preg_match('/\bstr_ends_with\s*\(/', $contents))->toBe(0,
				"{$relativeFile} uses str_ends_with() which requires PHP 8.0"
			);
		}
	});

	it('does not use nullsafe operator (PHP 8.0)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$path = realpath(__DIR__ . '/../../' . $relativeFile);

			if ($path === false) {
			throw new RuntimeException("Unable to resolve required plugin source");
		}

			$contents = file_get_contents($path);

			if ($contents === false) {
			throw new RuntimeException("Unable to read required plugin source");
		}

			expect(preg_match('/\?->/', $contents))->toBe(0,
				"{$relativeFile} uses nullsafe operator which requires PHP 8.0"
			);
		}
	});
