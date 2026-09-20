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
 * Locks in mikrotik.php's filter-field hardening: every "filter" search box
 * must render through html_escape_request_var(), and every LIKE clause
 * built from it must go through db_qstr() rather than raw interpolation.
 */
describe('filter SQL/output hardening in mikrotik', function () {
	$source = plugin_test_read_source('mikrotik.php');

	it('escapes the filter value before rendering it back into the search box', function () use ($source) {
		expect($source)->toContain("html_escape_request_var('filter')");
	});

	it('builds every filter LIKE clause through db_qstr()', function () use ($source) {
		expect($source)->toContain("db_qstr('%' . get_request_var('filter') . '%')");

		// The check above only proves db_qstr() is used *somewhere*; walk
		// every line that builds a LIKE clause from the filter and make
		// sure each one wraps the value in db_qstr(), so a regression that
		// reintroduces raw interpolation on any single line still fails.
		foreach (explode("\n", $source) as $line) {
			if (stripos($line, 'LIKE') !== false && strpos($line, "get_request_var('filter')") !== false) {
				expect($line)->toMatch("/db_qstr\('%'\s*\.\s*get_request_var\('filter'\)\s*\.\s*'%'\)/");
			}
		}
	});

	it('does not print the raw filter value directly', function () use ($source) {
		expect($source)->not->toContain("value='<?php print get_request_var('filter');?>'");
		expect($source)->not->toContain("value='<?php print htmlspecialchars(get_request_var('filter'));?>'");
	});

	it('does not interpolate the raw filter value into a LIKE clause', function () use ($source) {
		expect(preg_match('/LIKE\s*\'%"\s*\.\s*get_request_var\(\'filter\'\)/', $source))->toBe(0);
	});
});
