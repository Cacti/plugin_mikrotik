<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Verify plugin source files do not use syntax newer than PHP 8.2.
 * Cacti 1.2.x plugins must remain compatible with the PHP 8.2 floor.
 *
 * The bundled RouterOS/ directory is third-party vendored code, not
 * plugin logic, so it is excluded from this scan.
 */

	// Discovered recursively so new production PHP files are covered automatically.
	$pluginRoot = realpath(__DIR__ . '/../..');
	$files      = array();

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($pluginRoot, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $file) {
		if ($file->getExtension() !== 'php') {
			continue;
		}

		$relativeFile = ltrim(str_replace($pluginRoot, '', $file->getPathname()), DIRECTORY_SEPARATOR);
		$relativeFile = str_replace(DIRECTORY_SEPARATOR, '/', $relativeFile);

		if (strpos($relativeFile, 'tests/') === 0 || strpos($relativeFile, 'RouterOS/') === 0) {
			continue;
		}

		$files[] = $relativeFile;
	}

	sort($files);

	it('does not declare an entirely readonly class (PHP 8.3)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$contents = file_get_contents(realpath(__DIR__ . '/../../' . $relativeFile));

			if ($contents === false) {
				throw new RuntimeException("Unable to read required plugin source");
			}

			expect(preg_match('/\breadonly\s+class\b/i', $contents))->toBe(0,
				"{$relativeFile} declares an entirely readonly class which requires PHP 8.3"
			);
		}
	});

	it('does not use json_validate() (PHP 8.3)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$contents = file_get_contents(realpath(__DIR__ . '/../../' . $relativeFile));

			if ($contents === false) {
				throw new RuntimeException("Unable to read required plugin source");
			}

			expect(preg_match('/\bjson_validate\s*\(/', $contents))->toBe(0,
				"{$relativeFile} uses json_validate() which requires PHP 8.3"
			);
		}
	});

	it('does not use the #[\\Override] attribute (PHP 8.3)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$contents = file_get_contents(realpath(__DIR__ . '/../../' . $relativeFile));

			if ($contents === false) {
				throw new RuntimeException("Unable to read required plugin source");
			}

			expect(preg_match('/#\[\s*\\\\?Override\s*\]/', $contents))->toBe(0,
				"{$relativeFile} uses the #[Override] attribute which requires PHP 8.3"
			);
		}
	});

	it('does not use asymmetric visibility set-scope modifiers (PHP 8.4)', function () use ($files) {
		foreach ($files as $relativeFile) {
			$contents = file_get_contents(realpath(__DIR__ . '/../../' . $relativeFile));

			if ($contents === false) {
				throw new RuntimeException("Unable to read required plugin source");
			}

			expect(preg_match('/\b(?:public|protected|private)\(set\)/', $contents))->toBe(0,
				"{$relativeFile} uses an asymmetric visibility set-scope modifier which requires PHP 8.4"
			);
		}
	});

	it('does not use the PHP 8.4 array_find/array_any/array_all family', function () use ($files) {
		foreach ($files as $relativeFile) {
			$contents = file_get_contents(realpath(__DIR__ . '/../../' . $relativeFile));

			if ($contents === false) {
				throw new RuntimeException("Unable to read required plugin source");
			}

			expect(preg_match('/\barray_(?:find|any|all)(?:_key)?\s*\(/', $contents))->toBe(0,
				"{$relativeFile} uses an array_find/array_any/array_all helper which requires PHP 8.4"
			);
		}
	});
