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

$forbidden = array(
	"LIKE '%\" . get_request_var('filter') . \"%'",
	"value='<?php print get_request_var('filter');?>'",
	"value='<?php print htmlspecialchars(get_request_var('filter'));?>'",
);

foreach ($forbidden as $pattern) {
	if (strpos($contents, $pattern) !== false) {
		fwrite(STDERR, "Raw filter handling remains: {$pattern}\n");
		exit(1);
	}
}

print "OK\n";
