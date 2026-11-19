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
 * A host set to SSL must stay on SSL: a failed TLS connection in the poller
 * may neither retry over the plaintext api port nor rewrite the stored
 * transport, since both would send the credentials in cleartext.
 */
describe('SSL transport policy in the poller', function () {
	$source = plugin_test_read_source('poller_mikrotik.php');

	$start = strpos($source, 'function mikrotik_api_open(');
	$body  = substr($source, $start, strpos($source, "\n}\n", $start) - $start);

	it('never rewrites the stored SSL setting', function () use ($source) {
		expect($source)->not->toMatch('/UPDATE\s+plugin_mikrotik_credentials/i');
	});

	it('does not retry a failed SSL connection without SSL', function () use ($body) {
		expect($body)->not->toContain('->ssl  = false')
			->and($body)->not->toContain('->ssl = false')
			->and(substr_count($body, '->connect('))->toBe(1);
	});
});
