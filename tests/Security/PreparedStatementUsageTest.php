<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/*
 * Locks in this PR's hardening of mikrotik_users.php's bulk-action handler:
 * a class-blocked unserialize() with an array fallback, and a prepared
 * DELETE for the selected-device IN() list instead of raw interpolation.
 */
describe('prepared statement usage in mikrotik_users', function () {
	$source = file_get_contents(realpath(__DIR__ . '/../../mikrotik_users.php'));

	it('reads mikrotik_users.php', function () use ($source) {
		expect($source)->not->toBeFalse();
	});

	it('deserializes selected_items without permitting objects', function () use ($source) {
		expect(preg_match(
			'/unserialize\s*\(\s*stripslashes\s*\([^)]*\)\s*,\s*\[\s*\'allowed_classes\'\s*=>\s*false\s*\]\s*\)/',
			$source
		))->toBe(1);
	});

	it('falls back to an empty array when the deserialized value is not an array', function () use ($source) {
		expect(preg_match('/if\s*\(\s*!is_array\s*\(\s*\$selected_items\s*\)\s*\)\s*\{\s*\$selected_items\s*=\s*\[\];/', $source))->toBe(1);
	});

	it('uses a prepared DELETE with placeholders for the selected-device IN() list', function () use ($source) {
		expect(preg_match(
			'/db_execute_prepared\s*\(\s*"DELETE FROM plugin_mikrotik_users WHERE name IN \(\$placeholders\)"\s*,\s*\$devices_to_act_on\s*\)/',
			$source
		))->toBe(1);
	});

	it('guards the prepared DELETE so an empty selection issues no query', function () use ($source) {
		expect(preg_match('/if\s*\(\s*!empty\s*\(\s*\$devices_to_act_on\s*\)\s*\)\s*\{\s*\$placeholders/', $source))->toBe(1);
	});

	it('no longer builds the DELETE by interpolating an imploded IN() list', function () use ($source) {
		expect($source)->not->toContain('WHERE name IN (\'" . implode');
	});
});
