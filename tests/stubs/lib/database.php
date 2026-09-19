<?php
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
