<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2020 The Cacti Group                                 |
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
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/* display no errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../../../include/cli_check.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_mikrotik_wapcount', $_SERVER['argv']);
}

function ss_mikrotik_wapcount($host_id = '') {
	$waps = db_fetch_cell_prepared('SELECT count(*)
		FROM plugin_mikrotik_wireless_aps
		WHERE host_id = ?
		AND unix_timestamp(last_seen)>unix_timestamp()-1200',
		array($host_id));

	if ($waps == '') $waps = 'U';

	return $waps;
}

