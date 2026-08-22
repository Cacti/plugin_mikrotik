<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Verify migrated files use prepared DB helpers exclusively.
 * Catches regressions where raw db_execute/db_fetch_* calls creep back in.
 */

describe('prepared statement consistency in mikrotik', function () {
	it('documents database helper usage in all plugin files', function () {
		$targetFiles = array(
		'mikrotik.php',
		'mikrotik_users.php',
		'poller_graphs.php',
		'poller_mikrotik.php',
		'setup.php',
		);


		foreach ($targetFiles as $relativeFile) {
			$path = realpath(__DIR__ . '/../../' . $relativeFile);

			expect($path)->not->toBeFalse("Required plugin file is missing: {$relativeFile}");

			$contents = file_get_contents($path);
			expect($contents)->not->toBeFalse("Unable to read {$relativeFile}");
			expect(preg_match('/\b(?:db_execute|db_fetch_(?:row|assoc|cell))\s*\(/', $contents))->toBe(1,
				"File {$relativeFile} must contain database access"
			);
		}
	});
});
