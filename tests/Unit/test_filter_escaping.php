<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

$payload = '\'" OR 1=1 -- <script>alert(1)</script>';
$escaped = htmlspecialchars($payload, ENT_QUOTES, 'UTF-8');

if (strpos($escaped, '<script>') === false && strpos($escaped, '&lt;script&gt;') !== false) {
	print "OK\n";
	exit(0);
}

fwrite(STDERR, "Expected filter payload to be escaped for HTML output\n");
exit(1);
