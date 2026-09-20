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
 * Empty stand-in for Cacti's lib/database.php.
 *
 * mikrotik_setup_table() in setup.php unconditionally does
 * include_once($config['library_path'] . '/database.php') before creating
 * its tables. The real Cacti lib/database.php (checked out alongside this
 * plugin by CI) defines its own db_execute()/db_fetch_*() functions, which
 * would fatal with "Cannot redeclare" against the test stubs already
 * defined in tests/bootstrap-unit.php. Pointing library_path at this empty
 * file instead keeps that include_once a harmless no-op.
 */
