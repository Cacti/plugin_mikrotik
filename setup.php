<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2015 The Cacti Group                                 |
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

function plugin_mikrotik_install () {
	# graph setup all arrays needed for automation
	api_plugin_register_hook('mikrotik', 'config_arrays',         'mikrotik_config_arrays',         'setup.php');
	api_plugin_register_hook('mikrotik', 'config_form',           'mikrotik_config_form',           'setup.php');
	api_plugin_register_hook('mikrotik', 'config_settings',       'mikrotik_config_settings',       'setup.php');
	api_plugin_register_hook('mikrotik', 'draw_navigation_text',  'mikrotik_draw_navigation_text',  'setup.php');
	api_plugin_register_hook('mikrotik', 'poller_bottom',         'mikrotik_poller_bottom',         'setup.php');
	api_plugin_register_hook('mikrotik', 'top_header_tabs',       'mikrotik_show_tab',              'setup.php');
	api_plugin_register_hook('mikrotik', 'top_graph_header_tabs', 'mikrotik_show_tab',              'setup.php');

	api_plugin_register_realm('mikrotik', 'mikrotik.php', 'Plugin -> MikroTik Viewer', 1);
	api_plugin_register_realm('mikrotik', 'mikrotik_users.php', 'Plugin -> MikroTik Admin', 1);

	mikrotik_setup_table ();
}

function plugin_mikrotik_uninstall () {
	// Do any extra Uninstall stuff here
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_system`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_system_health`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_storage`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_users`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_trees`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_queues`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_interfaces`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_wireless_aps`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_wireless_registrations`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_processes`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_processor`');
}

function plugin_mikrotik_check_config () {
	// Here we will check to ensure everything is configured
	mikrotik_check_upgrade ();
	return true;
}

function plugin_mikrotik_upgrade () {
	// Here we will upgrade to the newest version
	mikrotik_check_upgrade ();
	return true;
}

function plugin_mikrotik_version () {
	return mikrotik_version();
}

function mikrotik_check_upgrade () {
	global $config, $database_default;
	include_once($config['library_path'] . '/database.php');
	include_once($config['library_path'] . '/functions.php');

	// Let's only run this check if we are on a page that actually needs the data
	$files = array('plugins.php', 'mikrotik.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$version = mikrotik_version ();
	$current = $version['version'];
	$old     = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='mikrotik'");
	if ($current != $old) {
		if (api_plugin_is_enabled('mikrotik')) {
			api_plugin_enable_hooks('mikrotik');
		}

		db_execute("ALTER TABLE plugin_mikrotik_trees ADD COLUMN prevPackets BIGINT UNSIGNED default NULL AFTER prevBytes");
		db_execute("ALTER TABLE plugin_mikrotik_trees ADD COLUMN prevHCBytes BIGINT UNSIGNED default NULL AFTER prevPackets");
		db_execute("ALTER TABLE plugin_mikrotik_trees ADD COLUMN curBytes BIGINT UNSIGNED default null AFTER HCBytes");
		db_execute("ALTER TABLE plugin_mikrotik_trees ADD COLUMN curPackets BIGINT UNSIGNED default null AFTER curBytes");
		db_execute("ALTER TABLE plugin_mikrotik_trees ADD COLUMN curHCBytes BIGINT UNSIGNED default null AFTER curPackets");
		db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN firmwareVersion varchar(20) NOT NULL default '' AFTER sysLocation");
		db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN licVersion varchar(20) NOT NULL default '' AFTER firmwareVersion");
		db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN softwareID varchar(20) NOT NULL default '' AFTER licVersion");
		db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN serialNumber varchar(20) NOT NULL default '' AFTER softwareID");

		db_execute("UPDATE plugin_config SET version='$current' WHERE directory='mikrotik'");
		db_execute('UPDATE plugin_config SET ' .
			"version='" . $version['version'] . "', " .
			"name='" . $version['longname'] . "', " .
			"author='" . $version['author'] . "', " .
			"webpage='" . $version['url'] . "' " .
			"WHERE directory='" . $version['name'] . "' ");
	}
}

function mikrotik_check_dependencies() {
	return true;
}

function mikrotik_setup_table () {
	global $config, $database_default;
	include_once($config['library_path'] . '/database.php');

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_system_health` (
		`host_id` int(10) unsigned NOT NULL,
		`HlCoreVoltage` double DEFAULT NULL,
		`HlThreeDotThreeVoltage` double DEFAULT NULL,
		`HlFiveVoltage` double DEFAULT NULL,
		`HlTwelveVoltage` double DEFAULT NULL,
		`HlSensorTemperature` double DEFAULT NULL,
		`HlCpuTemperature` double DEFAULT NULL,
		`HlBoardTemperature` double DEFAULT NULL,
		`HlVoltage` double DEFAULT NULL,
		`HlActiveFan` varchar(20) DEFAULT NULL,
		`HlTemperature` double DEFAULT NULL,
		`HlProcessorTemperature` double DEFAULT NULL,
		`HlPower` double DEFAULT NULL,
		`HlCurrent` int(10) unsigned DEFAULT NULL,
		`HlProcessorFrequency` int(10) unsigned DEFAULT NULL,
		`HlPowerSupplyState` int(10) unsigned DEFAULT NULL,
		`HlBackupPowerSupplyState` int(10) unsigned DEFAULT NULL,
		`HlFanSpeed1` varchar(20) DEFAULT NULL,
		`HlFanSpeed2` varchar(20) DEFAULT NULL,
		PRIMARY KEY (`host_id`)) 
		ENGINE=MyISAM 
		COMMENT='Stores Mikrotik Health Counters'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_wireless_registrations` (
		`host_id` int(10) unsigned NOT NULL,
		`index` varchar(20) NOT NULL DEFAULT '',
		`TxBytes` bigint(20) unsigned DEFAULT '0',
		`RxBytes` bigint(20) unsigned DEFAULT '0',
		`TxPackets` bigint(20) unsigned DEFAULT '0',
		`RxPackets` bigint(20) unsigned DEFAULT '0',
		`curRxBytes` bigint(20) unsigned DEFAULT '0',
		`curTxBytes` bigint(20) unsigned DEFAULT '0',
		`curTxPackets` bigint(20) unsigned DEFAULT '0',
		`curRxPackets` bigint(20) unsigned DEFAULT '0',
		`prevTxBytes` bigint(20) unsigned DEFAULT '0',
		`prevRxBytes` bigint(20) unsigned DEFAULT '0',
		`prevTxPackets` bigint(20) unsigned DEFAULT '0',
		`prevRxPackets` bigint(20) unsigned DEFAULT '0',
		`Strength` int(11) DEFAULT '0',
		`TxRate` int(10) unsigned DEFAULT '0',
		`RxRate` int(10) unsigned DEFAULT '0',
		`RouterOSVersion` varchar(20) DEFAULT NULL,
		`Uptime` varchar(30) DEFAULT '',
		`SignalToNoise` int(11) DEFAULT NULL,
		`TxStrengthCh0` int(11) DEFAULT '0',
		`RxStrengthCh0` int(11) DEFAULT '0',
		`TxStrengthCh1` int(11) DEFAULT '0',
		`RxStrengthChl` int(11) DEFAULT '0',
		`TxStrengthCh2` int(11) DEFAULT '0',
		`RxStrengthCh2` int(11) DEFAULT '0',
		`TxStrength` int(11) DEFAULT '0',
		`last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`host_id`,`index`)) 
		ENGINE=MyISAM 
		COMMENT='Table of Mikrotik Wireless Registrations'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_wireless_aps` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`apSSID` varchar(20) NOT NULL DEFAULT '',
		`apTxRate` int(10) unsigned DEFAULT NULL,
		`apRxRate` int(10) unsigned DEFAULT NULL,
		`apBSSID` varchar(20) DEFAULT NULL,
		`apClientCount` int(10) unsigned DEFAULT '0',
		`apFreq` int(10) unsigned DEFAULT NULL,
		`apBand` varchar(40) DEFAULT NULL,
		`apNoiseFloor` int(11) DEFAULT NULL,
		`apOverallTxCCQ` int(11) DEFAULT '0',
		`apAuthClientCount` int(10) unsigned DEFAULT '0',
		`last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`host_id`,`index`,`apSSID`)) 
		ENGINE=MyISAM 
		COMMENT='Table of MikroTik Access Point Definitions'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_system` (
		`host_id` int(10) unsigned NOT NULL,
		`host_status` int(10) unsigned NOT NULL default '0',
		`uptime` int(10) unsigned NOT NULL default '0',
		`date` timestamp NOT NULL default '0000-00-00 00:00:00',
		`users` int(10) unsigned NOT NULL default '0',
		`cpuPercent` int(10) unsigned NOT NULL default '0',
		`numCpus` int(10) unsigned NOT NULL default '0',
		`processes` int(10) unsigned NOT NULL default '0',
		`maxProcesses` int(10) unsigned NOT NULL default '0',
		`memSize` BIGINT unsigned NOT NULL default '0',
		`memUsed` FLOAT NOT NULL default '0',
		`diskSize` BIGINT UNSIGNED NOT NULL default '0',
		`diskUsed` FLOAT NOT NULL default '0',
		`sysDescr` varchar(255) NOT NULL default '',
		`sysObjectID` varchar(128) NOT NULL default '',
		`sysUptime` int(10) unsigned NOT NULL default '0',
		`sysName` varchar(64) NOT NULL default '',
		`sysContact` varchar(128) NOT NULL default '',
		`sysLocation` varchar(255) NOT NULL default '',
		`firmwareVersion` varchar(20) NOT NULL default '',
		`licVersion` varchar(20) NOT NULL default '',
		`softwareID` varchar(20) NOT NULL default '',
		`serialNumber` varchar(20) NOT NULL default '',
		PRIMARY KEY  (`host_id`),
		INDEX `host_status` (`host_status`))
		ENGINE=MyISAM
		COMMENT='Contains all Hosts that support MikroTik';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_storage` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`type` int(10) unsigned default '1',
		`description` varchar(255) default '',
		`allocationUnits` int(10) unsigned default '0',
		`size` int(10) unsigned default '0',
		`used` int(10) unsigned default '0',
		`failures` int(10) unsigned default '0',
		`present` tinyint(3) unsigned DEFAULT '1',
		PRIMARY KEY  (`host_id`,`index`),
		INDEX `description` (`description`),
		INDEX `index` (`index`))
		ENGINE=MyISAM
		COMMENT='Stores the Storage Information';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_interfaces` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`name` varchar(40) DEFAULT NULL,
		`RxBytes` bigint(20) unsigned DEFAULT '0',
		`RxPackets` bigint(20) unsigned DEFAULT '0',
		`RxTooShort` bigint(20) unsigned DEFAULT '0',
		`RxTo64` bigint(20) unsigned DEFAULT '0',
		`Rx65to127` bigint(20) unsigned DEFAULT '0',
		`Rx128to255` bigint(20) unsigned DEFAULT '0',
		`Rx256to511` bigint(20) unsigned DEFAULT '0',
		`Rx512to1023` bigint(20) unsigned DEFAULT '0',
		`Rx1024to1518` bigint(20) unsigned DEFAULT '0',
		`Rx1519toMax` bigint(20) unsigned DEFAULT '0',
		`RxTooLong` bigint(20) unsigned DEFAULT '0',
		`RxBroadcast` bigint(20) unsigned DEFAULT '0',
		`RxPause` bigint(20) unsigned DEFAULT '0',
		`RxMulticast` bigint(20) unsigned DEFAULT '0',
		`RxFCFSError` bigint(20) unsigned DEFAULT '0',
		`RxAlignError` bigint(20) unsigned DEFAULT '0',
		`RxFragment` bigint(20) unsigned DEFAULT '0',
		`RxOverflow` bigint(20) unsigned DEFAULT '0',
		`RxControl` bigint(20) unsigned DEFAULT '0',
		`RxUnknownOp` bigint(20) unsigned DEFAULT '0',
		`RxLengthError` bigint(20) unsigned DEFAULT '0',
		`RxCodeError` bigint(20) unsigned DEFAULT '0',
		`RxCarrierError` bigint(20) unsigned DEFAULT '0',
		`RxJabber` bigint(20) unsigned DEFAULT '0',
		`RxDrop` bigint(20) unsigned DEFAULT '0',
		`TxBytes` bigint(20) unsigned DEFAULT '0',
		`TxPackets` bigint(20) unsigned DEFAULT '0',
		`TxTooShort` bigint(20) unsigned DEFAULT '0',
		`TxTo64` bigint(20) unsigned DEFAULT '0',
		`Tx65to127` bigint(20) unsigned DEFAULT '0',
		`Tx128to255` bigint(20) unsigned DEFAULT '0',
		`Tx256to511` bigint(20) unsigned DEFAULT '0',
		`Tx512to1023` bigint(20) unsigned DEFAULT '0',
		`Tx1024to1518` bigint(20) unsigned DEFAULT '0',
		`Tx1519toMax` bigint(20) unsigned DEFAULT '0',
		`TxTooLong` bigint(20) unsigned DEFAULT '0',
		`TxBroadcast` bigint(20) unsigned DEFAULT '0',
		`TxPause` bigint(20) unsigned DEFAULT '0',
		`TxMulticast` bigint(20) unsigned DEFAULT '0',
		`TxUnderrun` bigint(20) unsigned DEFAULT '0',
		`TxCollision` bigint(20) unsigned DEFAULT '0',
		`TxExCollision` bigint(20) unsigned DEFAULT '0',
		`TxMultCollision` bigint(20) unsigned DEFAULT '0',
		`TxSingCollision` bigint(20) unsigned DEFAULT '0',
		`TxExDeferred` bigint(20) unsigned DEFAULT '0',
		`TxDeferred` bigint(20) unsigned DEFAULT '0',
		`TxLateCollision` bigint(20) unsigned DEFAULT '0',
		`TxTotalCollision` bigint(20) unsigned DEFAULT '0',
		`TxPauseHonored` bigint(20) unsigned DEFAULT '0',
		`TxDrop` bigint(20) unsigned DEFAULT '0',
		`TxJabber` bigint(20) unsigned DEFAULT '0',
		`TxFCFSError` bigint(20) unsigned DEFAULT '0',
		`TxControl` bigint(20) unsigned DEFAULT '0',
		`TxFragment` bigint(20) unsigned DEFAULT '0',
		`curRxBytes` bigint(20) unsigned DEFAULT '0',
		`curRxPackets` bigint(20) unsigned DEFAULT '0',
		`curRxTooShort` bigint(20) unsigned DEFAULT '0',
		`curRxTo64` bigint(20) unsigned DEFAULT '0',
		`curRx65to127` bigint(20) unsigned DEFAULT '0',
		`curRx128to255` bigint(20) unsigned DEFAULT '0',
		`curRx256to511` bigint(20) unsigned DEFAULT '0',
		`curRx512to1023` bigint(20) unsigned DEFAULT '0',
		`curRx1024to1518` bigint(20) unsigned DEFAULT '0',
		`curRx1519toMax` bigint(20) unsigned DEFAULT '0',
		`curRxTooLong` bigint(20) unsigned DEFAULT '0',
		`curRxBroadcast` bigint(20) unsigned DEFAULT '0',
		`curRxPause` bigint(20) unsigned DEFAULT '0',
		`curRxMulticast` bigint(20) unsigned DEFAULT '0',
		`curRxFCFSError` bigint(20) unsigned DEFAULT '0',
		`curRxAlignError` bigint(20) unsigned DEFAULT '0',
		`curRxFragment` bigint(20) unsigned DEFAULT '0',
		`curRxOverflow` bigint(20) unsigned DEFAULT '0',
		`curRxControl` bigint(20) unsigned DEFAULT '0',
		`curRxUnknownOp` bigint(20) unsigned DEFAULT '0',
		`curRxLengthError` bigint(20) unsigned DEFAULT '0',
		`curRxCodeError` bigint(20) unsigned DEFAULT '0',
		`curRxCarrierError` bigint(20) unsigned DEFAULT '0',
		`curRxJabber` bigint(20) unsigned DEFAULT '0',
		`curRxDrop` bigint(20) unsigned DEFAULT '0',
		`curTxBytes` bigint(20) unsigned DEFAULT '0',
		`curTxPackets` bigint(20) unsigned DEFAULT '0',
		`curTxTooShort` bigint(20) unsigned DEFAULT '0',
		`curTxTo64` bigint(20) unsigned DEFAULT '0',
		`curTx65to127` bigint(20) unsigned DEFAULT '0',
		`curTx128to255` bigint(20) unsigned DEFAULT '0',
		`curTx256to511` bigint(20) unsigned DEFAULT '0',
		`curTx512to1023` bigint(20) unsigned DEFAULT '0',
		`curTx1024to1518` bigint(20) unsigned DEFAULT '0',
		`curTx1519toMax` bigint(20) unsigned DEFAULT '0',
		`curTxTooLong` bigint(20) unsigned DEFAULT '0',
		`curTxBroadcast` bigint(20) unsigned DEFAULT '0',
		`curTxPause` bigint(20) unsigned DEFAULT '0',
		`curTxMulticast` bigint(20) unsigned DEFAULT '0',
		`curTxUnderrun` bigint(20) unsigned DEFAULT '0',
		`curTxCollision` bigint(20) unsigned DEFAULT '0',
		`curTxExCollision` bigint(20) unsigned DEFAULT '0',
		`curTxMultCollision` bigint(20) unsigned DEFAULT '0',
		`curTxSingCollision` bigint(20) unsigned DEFAULT '0',
		`curTxExDeferred` bigint(20) unsigned DEFAULT '0',
		`curTxDeferred` bigint(20) unsigned DEFAULT '0',
		`curTxLateCollision` bigint(20) unsigned DEFAULT '0',
		`curTxTotalCollision` bigint(20) unsigned DEFAULT '0',
		`curTxPauseHonored` bigint(20) unsigned DEFAULT '0',
		`curTxDrop` bigint(20) unsigned DEFAULT '0',
		`curTxJabber` bigint(20) unsigned DEFAULT '0',
		`curTxFCFSError` bigint(20) unsigned DEFAULT '0',
		`curTxControl` bigint(20) unsigned DEFAULT '0',
		`curTxFragment` bigint(20) unsigned DEFAULT '0',
		`prevRxBytes` bigint(20) unsigned DEFAULT '0',
		`prevRxPackets` bigint(20) unsigned DEFAULT '0',
		`prevRxTooShort` bigint(20) unsigned DEFAULT '0',
		`prevRxTo64` bigint(20) unsigned DEFAULT '0',
		`prevRx65to127` bigint(20) unsigned DEFAULT '0',
		`prevRx128to255` bigint(20) unsigned DEFAULT '0',
		`prevRx256to511` bigint(20) unsigned DEFAULT '0',
		`prevRx512to1023` bigint(20) unsigned DEFAULT '0',
		`prevRx1024to1518` bigint(20) unsigned DEFAULT '0',
		`prevRx1519toMax` bigint(20) unsigned DEFAULT '0',
		`prevRxTooLong` bigint(20) unsigned DEFAULT '0',
		`prevRxBroadcast` bigint(20) unsigned DEFAULT '0',
		`prevRxPause` bigint(20) unsigned DEFAULT '0',
		`prevRxMulticast` bigint(20) unsigned DEFAULT '0',
		`prevRxFCFSError` bigint(20) unsigned DEFAULT '0',
		`prevRxAlignError` bigint(20) unsigned DEFAULT '0',
		`prevRxFragment` bigint(20) unsigned DEFAULT '0',
		`prevRxOverflow` bigint(20) unsigned DEFAULT '0',
		`prevRxControl` bigint(20) unsigned DEFAULT '0',
		`prevRxUnknownOp` bigint(20) unsigned DEFAULT '0',
		`prevRxLengthError` bigint(20) unsigned DEFAULT '0',
		`prevRxCodeError` bigint(20) unsigned DEFAULT '0',
		`prevRxCarrierError` bigint(20) unsigned DEFAULT '0',
		`prevRxJabber` bigint(20) unsigned DEFAULT '0',
		`prevRxDrop` bigint(20) unsigned DEFAULT '0',
		`prevTxBytes` bigint(20) unsigned DEFAULT '0',
		`prevTxPackets` bigint(20) unsigned DEFAULT '0',
		`prevTxTooShort` bigint(20) unsigned DEFAULT '0',
		`prevTxTo64` bigint(20) unsigned DEFAULT '0',
		`prevTx65to127` bigint(20) unsigned DEFAULT '0',
		`prevTx128to255` bigint(20) unsigned DEFAULT '0',
		`prevTx256to511` bigint(20) unsigned DEFAULT '0',
		`prevTx512to1023` bigint(20) unsigned DEFAULT '0',
		`prevTx1024to1518` bigint(20) unsigned DEFAULT '0',
		`prevTx1519toMax` bigint(20) unsigned DEFAULT '0',
		`prevTxTooLong` bigint(20) unsigned DEFAULT '0',
		`prevTxBroadcast` bigint(20) unsigned DEFAULT '0',
		`prevTxPause` bigint(20) unsigned DEFAULT '0',
		`prevTxMulticast` bigint(20) unsigned DEFAULT '0',
		`prevTxUnderrun` bigint(20) unsigned DEFAULT '0',
		`prevTxCollision` bigint(20) unsigned DEFAULT '0',
		`prevTxExCollision` bigint(20) unsigned DEFAULT '0',
		`prevTxMultCollision` bigint(20) unsigned DEFAULT '0',
		`prevTxSingCollision` bigint(20) unsigned DEFAULT '0',
		`prevTxExDeferred` bigint(20) unsigned DEFAULT '0',
		`prevTxDeferred` bigint(20) unsigned DEFAULT '0',
		`prevTxLateCollision` bigint(20) unsigned DEFAULT '0',
		`prevTxTotalCollision` bigint(20) unsigned DEFAULT '0',
		`prevTxPauseHonored` bigint(20) unsigned DEFAULT '0',
		`prevTxDrop` bigint(20) unsigned DEFAULT '0',
		`prevTxJabber` bigint(20) unsigned DEFAULT '0',
		`prevTxFCFSError` bigint(20) unsigned DEFAULT '0',
		`prevTxControl` bigint(20) unsigned DEFAULT '0',
		`prevTxFragment` bigint(20) unsigned DEFAULT '0',
		`last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`host_id`,`name`),
		KEY `host_id` (`host_id`),
		KEY `name` (`name`),
		KEY `present` (`present`),
		KEY `index` (`index`))
		ENGINE=MyISAM
		COMMENT='Table of MikroTik Interface Usage';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_queues` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`name` varchar(40) NOT NULL default '',
		`srcAddr` varchar(20) DEFAULT NULL,
		`srcMask` varchar(20) DEFAULT NULL,
		`dstAddr` varchar(20) DEFAULT NULL,
		`dstMask` varchar(20) DEFAULT NULL,
		`iFace` varchar(20) DEFAULT NULL,
		`BytesIn` bigint(20) unsigned DEFAULT '0',
		`BytesOut` bigint(20) unsigned DEFAULT '0',
		`PacketsIn` bigint(20) unsigned DEFAULT '0',
		`PacketsOut` bigint(20) unsigned DEFAULT '0',
		`QueuesIn` bigint(20) unsigned DEFAULT '0',
		`QueuesOut` bigint(20) unsigned DEFAULT '0',
		`DroppedIn` bigint(20) unsigned DEFAULT '0',
		`DroppedOut` bigint(20) unsigned DEFAULT '0',
		`curBytesIn` bigint(20) unsigned DEFAULT '0',
		`curBytesOut` bigint(20) unsigned DEFAULT '0',
		`curPacketsIn` bigint(20) unsigned DEFAULT '0',
		`curPacketsOut` bigint(20) unsigned DEFAULT '0',
		`curQueuesIn` bigint(20) unsigned DEFAULT '0',
		`curQueuesOut` bigint(20) unsigned DEFAULT '0',
		`curDroppedIn` bigint(20) unsigned DEFAULT '0',
		`curDroppedOut` bigint(20) unsigned DEFAULT '0',
		`prevBytesIn` bigint(20) unsigned DEFAULT '0',
		`prevBytesOut` bigint(20) unsigned DEFAULT '0',
		`prevPacketsIn` bigint(20) unsigned DEFAULT '0',
		`prevPacketsOut` bigint(20) unsigned DEFAULT '0',
		`prevQueuesIn` bigint(20) unsigned DEFAULT '0',
		`prevQueuesOut` bigint(20) unsigned DEFAULT '0',
		`prevDroppedIn` bigint(20) unsigned DEFAULT '0',
		`prevDroppedOut` bigint(20) unsigned DEFAULT '0',
		`last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`host_id`,`name`),
		KEY `host_id` (`host_id`),
		KEY `name` (`name`),
		KEY `present` (`present`),
		KEY `index` (`index`))
		ENGINE=MyISAM
		COMMENT='Table of MikroTik Queue Usage';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_users` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`serverID` int(10) unsigned DEFAULT NULL, 
		`name` varchar(32) NOT NULL DEFAULT '',
		`domain` varchar(32) NOT NULL DEFAULT '',
		`ip` varchar(40) NOT NULL DEFAULT '',
		`mac` varchar(20) NOT NULL DEFAULT '',
		`connectTime` int(10) unsigned DEFAULT '0',
		`validTillTime` int(10) unsigned DEFAULT '0',
		`idleStartTime` int(10) unsigned DEFAULT '0',
		`idleTimeout` int(10) unsigned DEFAULT '0',
		`pingTimeout` int(10) unsigned DEFAULT '0',
		`bytesIn` bigint(20) unsigned DEFAULT '0',
		`bytesOut` bigint(20) unsigned DEFAULT '0',
		`packetsIn` bigint(20) unsigned DEFAULT '0',
		`packetsOut` bigint(20) unsigned DEFAULT '0',
		`curBytesIn` bigint(20) unsigned DEFAULT '0',
		`curBytesOut` bigint(20) unsigned DEFAULT '0',
		`curPacketsIn` bigint(20) unsigned DEFAULT '0',
		`curPacketsOut` bigint(20) unsigned DEFAULT '0',
		`prevBytesIn` bigint(20) unsigned DEFAULT '0',
		`prevBytesOut` bigint(20) unsigned DEFAULT '0',
		`prevPacketsIn` bigint(20) unsigned DEFAULT '0',
		`prevPacketsOut` bigint(20) unsigned DEFAULT '0',
		`limitBytesIn` bigint(20) unsigned DEFAULT '0',
		`limitBytesOut` bigint(20) unsigned DEFAULT '0',
		`advertStatus` int(10) unsigned DEFAULT '0',
		`radius` int(10) unsigned DEFAULT '0',
		`blockedByAdvert` int(10) unsigned DEFAULT '0',
		`last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`host_id`,`name`,`serverID`),
		KEY `host_id` (`host_id`),
		KEY `name` (`name`),
		KEY `ip` (`ip`),
		KEY `domain` (`domain`),
		KEY `present` (`present`),
		KEY `index` (`index`))
		ENGINE=MyISAM
		COMMENT='Table of MikroTik User Usage';");

	db_execute("CREATE TABLE `plugin_mikrotik_trees` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`name` varchar(32) NOT NULL,
		`flow` varchar(32) NOT NULL,
		`parentIndex` int(10) unsigned DEFAULT '0',
		`bytes` bigint(20) unsigned DEFAULT '0',
		`packets` bigint(20) unsigned DEFAULT '0',
		`HCBytes` bigint(20) unsigned DEFAULT '0',
		`curBytes` bigint(20) unsigned DEFAULT '0',
		`curPackets` bigint(20) unsigned DEFAULT '0',
		`curHCBytes` bigint(20) unsigned DEFAULT '0',
		`prevBytes` bigint(20) unsigned DEFAULT '0',
		`prevPackets` bigint(20) unsigned DEFAULT '0',
		`prevHCBytes` bigint(20) unsigned DEFAULT '0',
		`last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`host_id`,`name`),
		KEY `name` (`name`),
		KEY `host_id` (`host_id`),
		KEY `present` (`present`),
		KEY `index` (`index`)) 
		ENGINE=MyISAM 
		COMMENT='Table of MikroTik Trees'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_processor` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`load` int(10) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL default '1',
		PRIMARY KEY  (`host_id`,`index`),
		INDEX `index` (`index`))
		ENGINE=MyISAM
		COMMENT='Stores Processor Information';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_processes` (
		`pid` int(10) unsigned NOT NULL,
		`taskid` int(10) unsigned NOT NULL,
		`started` timestamp NOT NULL default CURRENT_TIMESTAMP,
		PRIMARY KEY  (`pid`))
		ENGINE=MEMORY
		COMMENT='Running collector processes';");

}

function mikrotik_version () {
	return array(
		'name' 		=> 'mikrotik',
		'version' 	=> '1.0',
		'longname'	=> 'MikroTik Switch Tool',
		'author'	=> 'The Cacti Group',
		'homepage'	=> 'http://www.cacti.net',
		'email'		=> '',
		'url'		=> ''
	);
}

function mikrotik_poller_bottom() {
	global $config;
	include_once($config['base_path'] . '/lib/poller.php');

	exec_background(read_config_option('path_php_binary'), ' -q ' . $config['base_path'] . '/plugins/mikrotik/poller_mikrotik.php -M');
}

function mikrotik_config_settings () {
	global $tabs, $settings, $mikrotik_frequencies, $item_rows;

	$tabs['mikrotik'] = 'MikroTik';
	$settings['mikrotik'] = array(
		'mikrotik_header' => array(
			'friendly_name' => 'MikroTik General Settings',
			'method' => 'spacer',
			),
		'mikrotik_enabled' => array(
			'friendly_name' => 'MikroTik Poller Enabled',
			'description' => 'Check this box, if you want MikroTik polling to be enabled.  Otherwise, the poller will not function.',
			'method' => 'checkbox',
			'default' => ''
			),
		'mikrotik_autodiscovery' => array(
			'friendly_name' => 'Automatically Discover Cacti Devices',
			'description' => 'Do you wish to automatically scan for and add devices which support the MikroTik MIB from the Cacti host table?',
			'method' => 'checkbox',
			'default' => 'on'
			),
		'mikrotik_autopurge' => array(
			'friendly_name' => 'Automatically Purge Devices',
			'description' => 'Do you wish to automatically purge devices that are removed from the Cacti system?',
			'method' => 'checkbox',
			'default' => 'on'
			),
		'mikrotik_concurrent_processes' => array(
			'friendly_name' => 'Maximum Concurrent Collectors',
			'description' => 'What is the maximum number of concurrent collector process that you want to run at one time?',
			'method' => 'drop_array',
			'default' => '10',
			'array' => array(
				1  => '1 Process',
				2  => '2 Processes',
				3  => '3 Processes',
				4  => '4 Processes',
				5  => '5 Processes',
				10 => '10 Processes',
				20 => '20 Processes',
				30 => '30 Processes',
				40 => '40 Processes',
				50 => '50 Processes')
			),
		'mikrotik_autodiscovery_header' => array(
			'friendly_name' => 'MikroTik Auto Discovery Frequency',
			'method' => 'spacer',
			),
		'mikrotik_autodiscovery_freq' => array(
			'friendly_name' => 'Auto Discovery Frequency',
			'description' => 'How often do you want to look for new Cacti Devices?',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_automation_header' => array(
			'friendly_name' => 'Host Graph Automation',
			'method' => 'spacer',
			),
		'mikrotik_automation_frequency' => array(
			'friendly_name' => 'Automatically Add New Graphs',
			'description' => 'How often do you want to check for new objects to graph?',
			'method' => 'drop_array',
			'default' => '0',
			'array' => array(
				0    => 'Never',
				10   => '10 Minutes',
				20   => '20 Minutes',
				30   => '30 Minutes',
				60   => '1 Hour',
				720  => '12 Hours',
				1440 => '1 Day',
				2880 => '2 Days')
			),
		'mikrotik_user_exclusion' => array(
			'friendly_name' => 'Exclude Users RegEx',
			'description' => 'User names that match this regex will not be graphed automatically',
			'method' => 'textbox',
			'default' => '(^T-$)',
			'size' => '40',
			'max_length' => '40',
			),
		'mikrotik_frequencies' => array(
			'friendly_name' => 'MikroTik Device Collection Frequencies',
			'method' => 'spacer',
			),
		'mikrotik_storage_freq' => array(
			'friendly_name' => 'Storage Frequency',
			'description' => 'How often do you want to scan Storage Statistics?',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_processor_freq' => array(
			'friendly_name' => 'Processor Frequency',
			'description' => 'How often do you want to scan Device Processor Statistics?',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_interfaces_freq' => array(
			'friendly_name' => 'Interfaces Frequency',
			'description' => 'How often do you want to scan the Interfaces?',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_queue_tree' => array(
			'friendly_name' => 'MikroTik Queue/Tree Collection Frequencies',
			'method' => 'spacer',
			),
		'mikrotik_queues_freq' => array(
			'friendly_name' => 'Simple Queue Frequency',
			'description' => 'How often do you want to scan Simple Queue Statistics?  Select <b>Disabled</b> to remove this feature.',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_trees_freq' => array(
			'friendly_name' => 'Queue Trees Frequency',
			'description' => 'How often do you want to scan the Queue Trees?  Select <b>Disabled</b> to remove this feature.',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_wireless' => array(
			'friendly_name' => 'MikroTik Wireless Collection Frequencies',
			'method' => 'spacer',
			),
		'mikrotik_users_freq' => array(
			'friendly_name' => 'Wireless HotSpot Users Frequency',
			'description' => 'How often do you want to scan Wireless User Statistics?  Select <b>Disabled</b> to remove this feature.',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_wireless_aps_freq' => array(
			'friendly_name' => 'Wireless Access Point Frequency',
			'description' => 'How often do you want to scan the Wireless Access Points?  Select <b>Disabled</b> to remove this feature.',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_wireless_reg_freq' => array(
			'friendly_name' => 'Wireless Registrations Frequency',
			'description' => 'How often do you want to scan the Wireless Registrations?  Select <b>Disabled</b> to remove this feature.',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			),
		'mikrotik_wireless_sta_freq' => array(
			'friendly_name' => 'Wireless Stations Frequency',
			'description' => 'How often do you want to scan the Wireless Stations?  Select <b>Disabled</b> to remove this feature.',
			'method' => 'drop_array',
			'default' => '300',
			'array' => $mikrotik_frequencies
			)
		);
}

function mikrotik_config_arrays() {
	global $menu, $messages, $mikrotik_frequencies;
	global $mikrotikSystem, $mikrotikTrees, $mikrotikQueueSimpleEntry, $mikrotikUsers;
	global $mikrotikProcessor, $mikrotikStorage, $mikrotikInterfaces, $mikrotikWirelessAps;
	global $mikrotikWirelessRegistrations;
	global $host_template_hashes, $queue_hashes, $tree_hashes, $user_hashes;
	global $wireless_station_hashes, $wirless_reg_hashes, $interface_hashes;
	global $device_hashes, $device_health_hashes, $graph_template_hashes, $device_query_hashes;

	$menu['Management']['plugins/mikrotik/mikrotik_users.php'] = 'Mikrotik Users';

	$queue_hashes = array(
		'2873cd299a639cbdc19320c7c59b76e0',
		'f84afb6764a444799a4fdc6172127703',
		'8fa87e4b89385be56ebc567acefe9895',
		'3c64e6c838a93df2d1f077674e373546',
		'b16ae9022425599a6dc8235258f77246'
	);

	$tree_hashes = array(
		'9cc1e791b12935d5d374cccece6e6e0a',
		'c6e96bdc60197dde8ba470305daf05f5'
	);

	$user_hashes = array(
		'a52861518dd67783a211ae0938cb8ec5',
		'9a7dfd85d24b8320243521300cbfd1d2',
		'75a943d675f2d3e72353df4aa822c535',
		'0e5cd325b4956aaf11fc8e7d813a5e02',
		'a3b1e1488352975428edfb9dcbb29208'
	);

	$wireless_station_hashes = array(
		'0e88ad681dda36417a537c2e06a2add3',
		'8cea2d49a035d5424ff28b9856d78053',
		'0a0e496b94667220dce953cb374cee7c',
		'98ee665dc39e0404a272c87cc4efea2e'
	);

	$wireless_reg_hashes = array(
		'de393e2fe3c31572c0282607ce785335',
		'2c845abc422a651bb298211f6af3d332',
		'ac69d7ecba65c00e19d6db4ebc5132fd',
		'b6089dfa8d9b3638d8ff650e97376c90',
		'8b0b279ce963a63addfc211cb5fea19c'
	);

	$interface_hashes = array(
		'69ccb07edd51939407892ac334812c9a',
		'f4763dd2ab03be32c0afc934c077f34c',
		'42b97712b5edfa5eb54f0f40240dd3e2',
		'a384b1cc265554eb4d6ae4d0094ec4c4',
		'742515def28f84ac787195a3cc1639fa',
		'9266484d98848569fa13fb988699656b',
		'7514b3a58cf1ba6d3306e0dd3ff78928'
	);

	$device_hashes = array(
		'7df474393f58bae8e8d6b85f10efad71',
		'0ece13b90785aa04d1f554a093685948',
		'8856e3943ecc70e5da835072f584d5a0',
		'32bd34d525944127063c2d94e2e8f1de',
		'4396ae857c4f9bc5ed1f26b5361e42d9',
		'47ced1c199d83e8dd79c6ba594c4e3be',
		'e797d967db24fd86341a8aa8c60fa9e0',
		'3d759e4fe07cfe5e0b22dd5a118c9a04',
		'30658723054a27b653af876191e12881',
		'7d8dc3050621a2cb937cac3895bc5d5b',
		'99e37ff13139f586d257ba9a637d7340',
		'b1d124f28ba3242cdcb8767b45bc0a9d',
		'f58edbcb3b6e682bc2332942c37b2652',
		'0c5c6edf53a418032801b9b67eb4ef42'
	);

	$device_health_hashes = array(
		'HlTwelveVoltage'        => '1d877789bec088d883549afd7df9fae5',
		'HlThreeDotThreeVoltage' => '59730f20f092f0b3ac1eab830f8122bf',
		'HlFiveVoltage'          => 'dbed5f0a76bdf28db7778912caa916e0',
		'HlCoreVoltage'          => '5655ad58420de8558f9c389bce041ecf',
		'HlCpuTemperature'       => '5a78c04ce733347648d9abf9de79e5ee',
		'HlCurrent'              => 'd75fd3f8165d7de9108cb9df864f8b6b',
		'HlPower'                => '4667d991a762625ded916a2b53d8ce4f',
		'HlProcessorTemperature' => '05b5e6381976d49dc460418551dc8ccf',
		'HlSensorTemperature'    => 'c8f7d27b3eede759c355dfe075995620',
		'HlTemperature'          => 'bf336909dbede294a0dbfe77082b64f6',
		'HlVoltage'              => 'a8d04f1326b164ca9dd5368ea91dff67'
	);

	$device_query_hashes = array(
		'11a443ebe40073aaa6972f8b357829de',
		'ce63249e6cc3d52bc69659a3f32194fe',
		'b11acd180dad8a955f88fb84237b0350',
		'dff839be04a5844e4d1033567f411c99',
		'7dd90372956af1dc8ec7b859a678f227',
		'25e2a46f8b3e160aed7e1d4ef3504cc3',
	);

	$graph_template_hashes = array(
		'7df474393f58bae8e8d6b85f10efad71',
		'0ece13b90785aa04d1f554a093685948',
		'1d877789bec088d883549afd7df9fae5',
		'59730f20f092f0b3ac1eab830f8122bf',
		'dbed5f0a76bdf28db7778912caa916e0',
		'5655ad58420de8558f9c389bce041ecf',
		'5a78c04ce733347648d9abf9de79e5ee',
		'd75fd3f8165d7de9108cb9df864f8b6b',
		'4667d991a762625ded916a2b53d8ce4f',
		'05b5e6381976d49dc460418551dc8ccf',
		'c8f7d27b3eede759c355dfe075995620',
		'bf336909dbede294a0dbfe77082b64f6',
		'a8d04f1326b164ca9dd5368ea91dff67',
		'8856e3943ecc70e5da835072f584d5a0',
		'32bd34d525944127063c2d94e2e8f1de',
		'4396ae857c4f9bc5ed1f26b5361e42d9',
		'47ced1c199d83e8dd79c6ba594c4e3be',
		'e797d967db24fd86341a8aa8c60fa9e0',
		'3d759e4fe07cfe5e0b22dd5a118c9a04',
		'30658723054a27b653af876191e12881',
		'7d8dc3050621a2cb937cac3895bc5d5b',
		'99e37ff13139f586d257ba9a637d7340',
		'b1d124f28ba3242cdcb8767b45bc0a9d',
		'f58edbcb3b6e682bc2332942c37b2652',
		'0c5c6edf53a418032801b9b67eb4ef42',
		'69ccb07edd51939407892ac334812c9a',
		'f4763dd2ab03be32c0afc934c077f34c',
		'42b97712b5edfa5eb54f0f40240dd3e2',
		'7514b3a58cf1ba6d3306e0dd3ff78928',
		'a384b1cc265554eb4d6ae4d0094ec4c4',
		'742515def28f84ac787195a3cc1639fa',
		'9266484d98848569fa13fb988699656b',
		'2873cd299a639cbdc19320c7c59b76e0',
		'f84afb6764a444799a4fdc6172127703',
		'8fa87e4b89385be56ebc567acefe9895',
		'3c64e6c838a93df2d1f077674e373546',
		'b16ae9022425599a6dc8235258f77246',
		'9cc1e791b12935d5d374cccece6e6e0a',
		'c6e96bdc60197dde8ba470305daf05f5',
		'a52861518dd67783a211ae0938cb8ec5',
		'9a7dfd85d24b8320243521300cbfd1d2',
		'75a943d675f2d3e72353df4aa822c535',
		'0e5cd325b4956aaf11fc8e7d813a5e02',
		'a3b1e1488352975428edfb9dcbb29208',
		'0e88ad681dda36417a537c2e06a2add3',
		'8cea2d49a035d5424ff28b9856d78053',
		'0a0e496b94667220dce953cb374cee7c',
		'98ee665dc39e0404a272c87cc4efea2e',
		'de393e2fe3c31572c0282607ce785335',
		'2c845abc422a651bb298211f6af3d332',
		'ac69d7ecba65c00e19d6db4ebc5132fd',
		'b6089dfa8d9b3638d8ff650e97376c90',
		'8b0b279ce963a63addfc211cb5fea19c',
	);

	$host_template_hashes = array(
		'd364e2b9570f166ab33c8df8bd503887'
	);

	$mikrotik_frequencies = array(
		-1    => 'Disabled',
		60    => '1 Minute',
		300   => '5 Minutes',
		600   => '10 Minutes',
		1200  => '20 Minutes',
		3600  => '1 Hour',
		7200  => '2 Hours',
		14400 => '4 Hours',
		43200 => '12 Hours',
		86400 => '1 Day'
	);

	$mikrotikSystem = array(
		'baseOID'        => '.1.3.6.1.2.1.25.1.',
		'uptime'         => '.1.3.6.1.2.1.25.1.1.0',
		'date'           => '.1.3.6.1.2.1.25.1.2.0',
		'processes'      => '.1.3.6.1.2.1.1.7.0',
		'memory'         => '.1.3.6.1.2.1.25.2.2.0',
		'sysDescr'       => '.1.3.6.1.2.1.1.1.0',
		'sysObjectID'    => '.1.3.6.1.2.1.1.2.0',
		'sysUptime'      => '.1.3.6.1.2.1.1.3.0',
		'sysContact'     => '.1.3.6.1.2.1.1.4.0',
		'sysName'        => '.1.3.6.1.2.1.1.5.0',
		'sysLocation'    => '.1.3.6.1.2.1.1.6.0'
	);

	$mikrotikStorage = array(
		'baseOID'         => '.1.3.6.1.2.1.25.2.3',
		'index'           => '.1.3.6.1.2.1.25.2.3.1.1',
		'type'            => '.1.3.6.1.2.1.25.2.3.1.2',
		'description'     => '.1.3.6.1.2.1.25.2.3.1.3',
		'allocationUnits' => '.1.3.6.1.2.1.25.2.3.1.4',
		'size'            => '.1.3.6.1.2.1.25.2.3.1.5',
		'used'            => '.1.3.6.1.2.1.25.2.3.1.6',
		'failures'        => '.1.3.6.1.2.1.25.2.3.1.7'
	);

	$mikrotikUsers = array(
		'baseOID'         => '.1.3.6.1.4.1.14988.1.1.5.1.1',
		'serverId'        => '.1.3.6.1.4.1.14988.1.1.5.1.1.2',
		'name'            => '.1.3.6.1.4.1.14988.1.1.5.1.1.3',
		'domain'          => '.1.3.6.1.4.1.14988.1.1.5.1.1.4',
		'ip'              => '.1.3.6.1.4.1.14988.1.1.5.1.1.5',
		'mac'             => '.1.3.6.1.4.1.14988.1.1.5.1.1.6',
		'connectTime'     => '.1.3.6.1.4.1.14988.1.1.5.1.1.7',
		'validTillTime'   => '.1.3.6.1.4.1.14988.1.1.5.1.1.8',
		'idleStartTime'   => '.1.3.6.1.4.1.14988.1.1.5.1.1.9',
		'idleTimeout'     => '.1.3.6.1.4.1.14988.1.1.5.1.1.10',
		'pingTimeout'     => '.1.3.6.1.4.1.14988.1.1.5.1.1.11',
		'bytesIn'         => '.1.3.6.1.4.1.14988.1.1.5.1.1.12',
		'bytesOut'        => '.1.3.6.1.4.1.14988.1.1.5.1.1.13',
		'packetsIn'       => '.1.3.6.1.4.1.14988.1.1.5.1.1.14',
		'packetsOut'      => '.1.3.6.1.4.1.14988.1.1.5.1.1.15',
		'limitBytesIn'    => '.1.3.6.1.4.1.14988.1.1.5.1.1.16',
		'limitBytesOut'   => '.1.3.6.1.4.1.14988.1.1.5.1.1.17',
		'advertStatus'    => '.1.3.6.1.4.1.14988.1.1.5.1.1.18',
		'radius'          => '.1.3.6.1.4.1.14988.1.1.5.1.1.19',
		'blockedByAdvert' => '.1.3.6.1.4.1.14988.1.1.5.1.1.20',
	);

	$mikrotikTrees = array(
		'name'        => '.1.3.6.1.4.1.14988.1.1.2.2.1.2',
		'flow'        => '.1.3.6.1.4.1.14988.1.1.2.2.1.3',
		'parentIndex' => '.1.3.6.1.4.1.14988.1.1.2.2.1.4',
		'bytes'       => '.1.3.6.1.4.1.14988.1.1.2.2.1.5',
		'packets'     => '.1.3.6.1.4.1.14988.1.1.2.2.1.6',
		'HCBytes'     => '.1.3.6.1.4.1.14988.1.1.2.2.1.7'
	);

	$mikrotikQueueSimpleEntry = array(
		'name'        => '.1.3.6.1.4.1.14988.1.1.2.1.1.2',
		'srcAddr'     => '.1.3.6.1.4.1.14988.1.1.2.1.1.3',
		'srcMask'     => '.1.3.6.1.4.1.14988.1.1.2.1.1.4',
		'dstAddr'     => '.1.3.6.1.4.1.14988.1.1.2.1.1.5',
		'dstMask'     => '.1.3.6.1.4.1.14988.1.1.2.1.1.6',
		'iFace'       => '.1.3.6.1.4.1.14988.1.1.2.1.1.7',
		'BytesIn'     => '.1.3.6.1.4.1.14988.1.1.2.1.1.8',
		'BytesOut'    => '.1.3.6.1.4.1.14988.1.1.2.1.1.9',
		'PacketsIn'   => '.1.3.6.1.4.1.14988.1.1.2.1.1.10',
		'Packetsout'  => '.1.3.6.1.4.1.14988.1.1.2.1.1.11',
		'QueuesIn'    => '.1.3.6.1.4.1.14988.1.1.2.1.1.12',
		'QueuesOut'   => '.1.3.6.1.4.1.14988.1.1.2.1.1.13',
		'DroppedIn'   => '.1.3.6.1.4.1.14988.1.1.2.1.1.14',
		'DroppedOut'  => '.1.3.6.1.4.1.14988.1.1.2.1.1.15',
	);

	$mikrotikWirelessAps = array(
		'apSSID'            => '.1.3.6.1.4.1.14988.1.1.1.3.1.4',
		'apTxRate'          => '.1.3.6.1.4.1.14988.1.1.1.3.1.2',
		'apRxRate'          => '.1.3.6.1.4.1.14988.1.1.1.3.1.3',
		'apBSSID'           => '.1.3.6.1.4.1.14988.1.1.1.3.1.5',
		'apClientCount'     => '.1.3.6.1.4.1.14988.1.1.1.3.1.6',
		'apFreq'            => '.1.3.6.1.4.1.14988.1.1.1.3.1.7',
		'apBand'            => '.1.3.6.1.4.1.14988.1.1.1.3.1.8',
		'apNoiseFloor'      => '.1.3.6.1.4.1.14988.1.1.1.3.1.9',
		'apOverallTxCCQ'    => '.1.3.6.1.4.1.14988.1.1.1.3.1.10',
		'apAuthClientCount' => '.1.3.6.1.4.1.14988.1.1.1.3.1.11',
	);

	$mikrotikWirelessRegistrations = array(
		'index'             => '.1.3.6.1.4.1.14988.1.1.1.2.1.1',
		'Strength'          => '.1.3.6.1.4.1.14988.1.1.1.2.1.3',
		'TxBytes'           => '.1.3.6.1.4.1.14988.1.1.1.2.1.4',
		'RxBytes'           => '.1.3.6.1.4.1.14988.1.1.1.2.1.5',
		'TxPackets'         => '.1.3.6.1.4.1.14988.1.1.1.2.1.6',
		'RxPackets'         => '.1.3.6.1.4.1.14988.1.1.1.2.1.7',
		'TxRate'            => '.1.3.6.1.4.1.14988.1.1.1.2.1.8',
		'RxRate'            => '.1.3.6.1.4.1.14988.1.1.1.2.1.9',
		'RouterOSVersion'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.10',
		'Uptime'            => '.1.3.6.1.4.1.14988.1.1.1.2.1.11',
		'SignalToNoise'     => '.1.3.6.1.4.1.14988.1.1.1.2.1.12',
		'TxStrengthCh0'     => '.1.3.6.1.4.1.14988.1.1.1.2.1.13',
		'RxStrengthCh0'     => '.1.3.6.1.4.1.14988.1.1.1.2.1.14',
		'TxStrengthCh1'     => '.1.3.6.1.4.1.14988.1.1.1.2.1.15',
		'RxStrengthChl'     => '.1.3.6.1.4.1.14988.1.1.1.2.1.16',
		'TxStrengthCh2'     => '.1.3.6.1.4.1.14988.1.1.1.2.1.17',
		'RxStrengthCh2'     => '.1.3.6.1.4.1.14988.1.1.1.2.1.18',
		'TxStrength'        => '.1.3.6.1.4.1.14988.1.1.1.2.1.19',
	);

	$mikrotikInterfaces = array(
		'name'             => '.1.3.6.1.4.1.14988.1.1.14.1.1.2',
		'RxBytes'          => '.1.3.6.1.4.1.14988.1.1.14.1.1.31',
		'RxPackets'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.32',
		'RxTooShort'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.33',
		'RxTo64'           => '.1.3.6.1.4.1.14988.1.1.14.1.1.34',
		'Rx65To127'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.35',
		'Rx128to255'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.36',
		'Rx256to511'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.37',
		'Rx512to1023'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.38',
		'Rx1024to1518'     => '.1.3.6.1.4.1.14988.1.1.14.1.1.39',
		'Rx1519toMax'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.40',
		'RxTooLong'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.41',
		'RxBroadcast'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.42',
		'RxPause'          => '.1.3.6.1.4.1.14988.1.1.14.1.1.43',
		'RxMulticast'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.44',
		'RxFCFSError'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.45',
		'RxAlignError'     => '.1.3.6.1.4.1.14988.1.1.14.1.1.46',
		'RxFragment'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.47',
		'RxOverflow'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.48',
		'RxControl'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.49',
		'RxUnknownOp'     => '.1.3.6.1.4.1.14988.1.1.14.1.1.50',
		'RxLengthError'    => '.1.3.6.1.4.1.14988.1.1.14.1.1.51',
		'RxCodeError'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.52',
		'RxCarrierError'   => '.1.3.6.1.4.1.14988.1.1.14.1.1.53',
		'RxJabber'         => '.1.3.6.1.4.1.14988.1.1.14.1.1.54',
		'RxDrop'           => '.1.3.6.1.4.1.14988.1.1.14.1.1.55',
		'TxBytes'          => '.1.3.6.1.4.1.14988.1.1.14.1.1.61',
		'TxPackets'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.62',
		'TxTooShort'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.63',
		'TxTo64'           => '.1.3.6.1.4.1.14988.1.1.14.1.1.64',
		'Tx65To127'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.65',
		'Tx128to255'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.66',
		'Tx256to511'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.67',
		'Tx512to1023'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.68',
		'Tx1024to1518'     => '.1.3.6.1.4.1.14988.1.1.14.1.1.69',
		'Tx1519toMax'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.70',
		'TxTooLong'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.71',
		'TxBroadcast'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.72',
		'TxPause'          => '.1.3.6.1.4.1.14988.1.1.14.1.1.73',
		'TxMulticast'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.74',
		'TxUnderrun'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.75',
		'TxCollision'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.76',
		'TxExCollision'    => '.1.3.6.1.4.1.14988.1.1.14.1.1.77',
		'TxMultCollision'  => '.1.3.6.1.4.1.14988.1.1.14.1.1.78',
		'TxSingCollision'  => '.1.3.6.1.4.1.14988.1.1.14.1.1.79',
		'TxExDeferred'     => '.1.3.6.1.4.1.14988.1.1.14.1.1.80',
		'TxDeferred'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.81',
		'TxLateCollision'  => '.1.3.6.1.4.1.14988.1.1.14.1.1.82',
		'TxTotalCollision' => '.1.3.6.1.4.1.14988.1.1.14.1.1.83',
		'TxPauseHonored'   => '.1.3.6.1.4.1.14988.1.1.14.1.1.84',
		'TxDrop'           => '.1.3.6.1.4.1.14988.1.1.14.1.1.85',
		'TxJabber'         => '.1.3.6.1.4.1.14988.1.1.14.1.1.86',
		'TxFCFSError'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.87',
		'TxControl'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.88',
		'TxFragment'       => '.1.3.6.1.4.1.14988.1.1.14.1.1.89',
	);

	$mikrotikProcessor = array(
		'baseOID' => '.1.3.6.1.2.1.25.3.3.1',
		'load'    => '.1.3.6.1.2.1.25.3.3.1.2'
	);

	if (isset($_SESSION['mikrotik_message']) && $_SESSION['mikrotik_message'] != '') {
		$messages['mikrotik_message'] = array('message' => $_SESSION['mikrotik_message'], 'type' => 'info');
	}
}

function mikrotik_draw_navigation_text($nav) {
	$nav['mikrotik.php:']              = array('title' => 'MikroTik', 'mapping' => '', 'url' => 'mikrotik.php', 'level' => '0');
	$nav['mikrotik.php:devices']       = array('title' => 'Devices', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik.php:trees']         = array('title' => 'Trees', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik.php:queues']        = array('title' => 'Simple Queues', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik.php:users']         = array('title' => 'Users', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik.php:interfaces']    = array('title' => 'Interfaces', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik.php:storage']       = array('title' => 'Storage', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik.php:graphs']        = array('title' => 'Graphs', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik.php:wireless_aps']  = array('title' => 'Wireless Aps', 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1');
	$nav['mikrotik_users.php:']        = array('title' => 'MikroTik Users', 'mapping' => 'index.php:', 'url' => 'mikrotik_user.php', 'level' => '1');
	$nav['mikrotik_users.php:edit']    = array('title' => '(Edit)', 'mapping' => 'index.php:,mikrotik_users.php:', 'url' => '', 'level' => '2');
	$nav['mikrotik_users.php:actions'] = array('title' => 'Actions', 'mapping' => 'index.php:,mikrotik_users.php:', 'url' => '', 'level' => '2');

	return $nav;
}

function mikrotik_show_tab() {
	global $config;

	if (api_user_realm_auth('mikrotik.php')) {
		if (substr_count($_SERVER['REQUEST_URI'], 'mikrotik.php')) {
			print '<a href="' . $config['url_path'] . 'plugins/mikrotik/mikrotik.php"><img src="' . $config['url_path'] . 'plugins/mikrotik/images/tab_mikrotik_down.gif" alt="MikroTik" border="0"></a>';
		}else{
			print '<a href="' . $config['url_path'] . 'plugins/mikrotik/mikrotik.php"><img src="' . $config['url_path'] . 'plugins/mikrotik/images/tab_mikrotik.gif" alt="MikroTik" border="0"></a>';
		}
	}
}

function mikrotik_template_by_hash($hash) {
	return db_fetch_cell("SELECT id FROM graph_templates WHERE hash='$hash'");
}

function mikrotik_data_query_by_hash($hash) {
	return db_fetch_cell("SELECT id FROM snmp_query WHERE hash='$hash'");
}

function mikrotik_graphs_url_by_template_hashs($hashes, $host_id = 0, $search = '') {
	global $config;

	$sql_where = '';
	if ($host_id != 0) {
		$sql_where .= " AND gl.host_id=$host_id";
	}

	if ($search != '') {
		$sql_where .= " AND gl.snmp_index LIKE '%$search%'";
	}

	$graphs = array_rekey(db_fetch_assoc("SELECT gl.id 
		FROM graph_local AS gl 
		INNER JOIN graph_templates AS gt 
		ON gl.graph_template_id=gt.id 
		WHERE gt.hash IN ('" . implode("','", $hashes) . "') $sql_where"), 'id', 'id');

	if (sizeof($graphs)) {
		return "<a class='pic' href='" . htmlspecialchars($config['url_path'] . 'plugins/mikrotik/mikrotik.php?action=graphs&reset=1&style=selective&graph_list=' . implode(',', $graphs)) . "'><img src='" . $config['url_path'] . "plugins/mikrotik/images/view_graphs.gif' alt='' title='View Graphs'></a>";
	}else{
		return "<img style='padding:3px;' src='" . $config['url_path'] . "plugins/mikrotik/images/view_graphs_disabled.gif' alt='' title='No Graphs Found'>";
	}
}

?>
