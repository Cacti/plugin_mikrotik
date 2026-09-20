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

/*
 * Smoke test that migrated files still perform their database access through
 * the shared db_* helper functions (prepared or unprepared). This does not
 * assert that a file uses prepared statements exclusively; it only catches
 * regressions where the db_* helper calls are removed or renamed entirely.
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
			expect(preg_match('/\b(?:db_execute|db_fetch_(?:row|assoc|cell))(?:_prepared)?\s*\(/', $contents))->toBe(1,
				"File {$relativeFile} must contain database access via db_* helpers (prepared or unprepared)"
			);
		}
	});
});
