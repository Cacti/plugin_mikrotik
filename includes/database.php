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

/**
 * Creates (if not already present) all of this plugin's database
 * tables (system/system_health, storage, users, trees, queues,
 * interfaces, wireless APs/registrations, processes, processor,
 * credentials, DHCP, DNS, lists). Called from
 * plugin_mikrotik_install() and mikrotik_check_upgrade().
 *
 * @return void
 *
 * @global array $config           Cacti global configuration array;
 *                                used to include the database library.
 * @global mixed $database_default Reserved/declared for parity with
 *                                other setup functions; not used
 *                                directly here.
 */
function mikrotik_setup_table(): void {
	global $config, $database_default;
	require_once($config['library_path'] . '/database.php');

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
		ENGINE=InnoDB
		COMMENT='Stores MikroTik Health Counters'");

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
		ENGINE=InnoDB
		COMMENT='Table of MikroTik Wireless Registrations'");

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
		ENGINE=InnoDB
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
		`firmwareVersionLatest` varchar(20) NOT NULL default '',
		`licVersion` varchar(20) NOT NULL default '',
		`softwareID` varchar(20) NOT NULL default '',
		`serialNumber` varchar(20) NOT NULL default '',
		PRIMARY KEY  (`host_id`),
		INDEX `host_status` (`host_status`))
		ENGINE=InnoDB
		COMMENT='Contains all Devices that support MikroTik';");

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
		ENGINE=InnoDB
		COMMENT='Stores the Storage Information';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_interfaces` (
		`host_id` int(10) unsigned NOT NULL DEFAULT '0',
		`index` int(10) unsigned NOT NULL,
		`name` varchar(40) NOT NULL DEFAULT '',
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
		ENGINE=InnoDB
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
		ENGINE=InnoDB
		COMMENT='Table of MikroTik Queue Usage';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_users` (
		`host_id` int(10) unsigned NOT NULL DEFAULT '0',
		`index` int(10) unsigned NOT NULL,
		`userType` int(10) unsigned NOT NULL DEFAULT '0',
		`serverID` int(10) unsigned NOT NULL DEFAULT '0',
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
		PRIMARY KEY (`host_id`,`name`,`serverID`,`userType`),
		KEY `host_id` (`host_id`),
		KEY `name` (`name`),
		KEY `ip` (`ip`),
		KEY `domain` (`domain`),
		KEY `present` (`present`),
		KEY `index` (`index`))
		ENGINE=InnoDB
		COMMENT='Table of MikroTik User Usage';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_trees` (
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
		ENGINE=InnoDB
		COMMENT='Table of MikroTik Trees'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_processor` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`load` int(10) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL default '1',
		PRIMARY KEY  (`host_id`,`index`),
		INDEX `index` (`index`))
		ENGINE=InnoDB
		COMMENT='Stores Processor Information';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_processes` (
		`pid` int(10) unsigned NOT NULL,
		`taskid` int(10) unsigned NOT NULL,
		`started` timestamp NOT NULL default CURRENT_TIMESTAMP,
		PRIMARY KEY  (`pid`))
		ENGINE=MEMORY
		COMMENT='Running collector processes';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_credentials` (
		`host_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
		`user` varchar(20) DEFAULT '',
		`password` varchar(40) DEFAULT '',
		PRIMARY KEY (`host_id`))
		ENGINE=InnoDB
		COMMENT='Stores MikroTik API Credentials'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_dhcp` (
		`host_id` int(10) unsigned NOT NULL,
		`address` varchar(20) NOT NULL,
		`mac_address` varchar(20) NOT NULL,
		`client_id` varchar(64) NOT NULL,
		`address_lists` varchar(128) DEFAULT '',
		`server` varchar(20) DEFAULT '',
		`dhcp_option` varchar(128) DEFAULT '',
		`status` varchar(20) DEFAULT '',
		`expires_after` int(10) unsigned DEFAULT '0',
		`last_seen` int unsigned DEFAULT '0',
		`active_address` varchar(20) DEFAULT '',
		`active_mac_address` varchar(20) DEFAULT '',
		`active_client_id` varchar(64) DEFAULT '',
		`active_server` varchar(20) DEFAULT '',
		`hostname` varchar(64) DEFAULT '',
		`radius` int(10) unsigned DEFAULT '0',
		`dynamic` int(10) unsigned DEFAULT '0',
		`blocked` int(10) unsigned DEFAULT '0',
		`disabled` int(10) unsigned DEFAULT '0',
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`host_id`,`mac_address`),
		KEY `address` (`address`),
		KEY `status` (`status`),
		KEY `present` (`present`))
		ENGINE=InnoDB
		COMMENT='Table of MikroTik DHCP Lease Information obtained from the API'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_lists` (
		`id` bigint unsigned NOT NULL auto_increment,
		`host_id` int(10) unsigned NOT NULL,
		`dynamic` varchar(5) NOT NULL,
		`disabled` varchar(5) NOT NULL,
		`list` varchar(20) NOT NULL,
		`address` varchar(60) NOT NULL,
		`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`timeout` int(11) NOT NULL default '-1',
		`present` tinyint unsigned NOT NULL default '1',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`host_id`, `list`, `address`))
		ENGINE=InnoDB
		COMMENT='Table of MikroTik Address List Information obtained from the API'");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_dns` (
		`id` bigint unsigned NOT NULL auto_increment,
		`host_id` int(10) unsigned NOT NULL,
		`type` varchar(10) NOT NULL,
		`data` varchar(128) NOT NULL,
		`name` varchar(128) NOT NULL,
		`ttl` int(10) NOT NULL default '-1',
		`static` varchar(10) NOT NULL default '',
		`present` tinyint unsigned NOT NULL default '1',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`host_id`, `type`, `data`, `name`))
		ENGINE=InnoDB
		COMMENT='Table of MikroTik DNS Information obtained from the API'");

	db_execute("CREATE TABLE IF NOT EXISTS plugin_mikrotik_mac2hostname (
		`mac_address` varchar(20) default '',
		`hostname` varchar(64) default '',
		`last_updated` timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`mac_address`, `hostname`))
		ENGINE=InnoDB
		COMMENT='Holds mappings from MAC Address to Hostname'");
}
