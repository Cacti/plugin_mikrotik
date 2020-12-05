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
	print call_user_func_array('ss_mikrotik_mem', $_SERVER['argv']);
}

function ss_mikrotik_mem($host_id = '') {
	$disk = db_fetch_row_prepared('SELECT memSize, memUsed*memSize/100 AS memUsed
		FROM plugin_mikrotik_system
		WHERE host_id = ?',
		array($host_id));

	if (cacti_sizeof($disk)) {
		if (isset($disk['memSize']) && $disk['memSize'] == '') {
			$size = 'U';
		} else {
			$size = $disk['memSize'];
		}

		if ($disk['memUsed'] == '') {
			$used = 'U';
		} else {
			$used = $disk['memUsed'];
		}

		return "used:$used size:$size";
	} else {
		return 'used:U size:U';
	}
}

