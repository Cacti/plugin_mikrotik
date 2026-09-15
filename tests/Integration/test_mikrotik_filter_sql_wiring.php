<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

$path = __DIR__ . '/../../mikrotik.php';
$contents = file_get_contents($path);

if ($contents === false) {
	fwrite(STDERR, "Unable to read mikrotik.php\n");
	exit(1);
}

$checks = array(
	"db_qstr('%' . get_request_var('filter') . '%')",
	"html_escape_request_var('filter')",
);

foreach ($checks as $check) {
	if (strpos($contents, $check) === false) {
		fwrite(STDERR, "Missing expected filter hardening: {$check}\n");
		exit(1);
	}
}

print "OK\n";
