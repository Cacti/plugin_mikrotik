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
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/**
 * Create all of this plugin's database tables if they do not already exist.
 *
 * Invoked from plugin_mikrotik_install() and mikrotik_check_upgrade() to
 * provision the schema through api_plugin_db_table_create(), which also records
 * each table in Cacti's plugin_db_changes tracking for a clean uninstall.
 *
 * @global array $config Cacti global configuration array; used to load the database library.
 *
 * @return void
 */
function mikrotik_setup_table(): void {
	global $config;
	require_once($config['library_path'] . '/database.php');

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_system_health', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'HlCoreVoltage', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlThreeDotThreeVoltage', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlFiveVoltage', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlTwelveVoltage', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlSensorTemperature', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlCpuTemperature', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlBoardTemperature', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlVoltage', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlActiveFan', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'HlTemperature', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlProcessorTemperature', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlPower', 'type' => 'double', 'NULL' => true),
			array('name' => 'HlCurrent', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true),
			array('name' => 'HlProcessorFrequency', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true),
			array('name' => 'HlPowerSupplyState', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true),
			array('name' => 'HlBackupPowerSupplyState', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true),
			array('name' => 'HlFanSpeed1', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'HlFanSpeed2', 'type' => 'varchar(20)', 'NULL' => true),
		),
		'primary' => 'host_id',
		'type' => 'InnoDB',
		'comment' => 'Stores MikroTik Health Counters'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_wireless_registrations', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'index', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
			array('name' => 'TxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Strength', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'TxRate', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxRate', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RouterOSVersion', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'Uptime', 'type' => 'varchar(30)', 'default' => ''),
			array('name' => 'SignalToNoise', 'type' => 'int(11)', 'NULL' => true),
			array('name' => 'TxStrengthCh0', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'RxStrengthCh0', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'TxStrengthCh1', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'RxStrengthChl', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'TxStrengthCh2', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'RxStrengthCh2', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'TxStrength', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
		),
		'primary' => 'host_id`,`index',
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik Wireless Registrations'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_wireless_aps', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'index', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'apSSID', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
			array('name' => 'apTxRate', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true),
			array('name' => 'apRxRate', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true),
			array('name' => 'apBSSID', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'apClientCount', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'apFreq', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true),
			array('name' => 'apBand', 'type' => 'varchar(40)', 'NULL' => true),
			array('name' => 'apNoiseFloor', 'type' => 'int(11)', 'NULL' => true),
			array('name' => 'apOverallTxCCQ', 'type' => 'int(11)', 'default' => '0'),
			array('name' => 'apAuthClientCount', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
		),
		'primary' => 'host_id`,`index`,`apSSID',
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik Access Point Definitions'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_system', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'host_status', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'uptime', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'),
			array('name' => 'users', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'cpuPercent', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'numCpus', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'processes', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'maxProcesses', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'memSize', 'type' => 'bigint', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'memUsed', 'type' => 'float', 'NULL' => false, 'default' => '0'),
			array('name' => 'diskSize', 'type' => 'bigint', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'diskUsed', 'type' => 'float', 'NULL' => false, 'default' => '0'),
			array('name' => 'sysDescr', 'type' => 'varchar(255)', 'NULL' => false, 'default' => ''),
			array('name' => 'sysObjectID', 'type' => 'varchar(128)', 'NULL' => false, 'default' => ''),
			array('name' => 'sysUptime', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'sysName', 'type' => 'varchar(64)', 'NULL' => false, 'default' => ''),
			array('name' => 'sysContact', 'type' => 'varchar(128)', 'NULL' => false, 'default' => ''),
			array('name' => 'sysLocation', 'type' => 'varchar(255)', 'NULL' => false, 'default' => ''),
			array('name' => 'firmwareVersion', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
			array('name' => 'firmwareVersionLatest', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
			array('name' => 'licVersion', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
			array('name' => 'softwareID', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
			array('name' => 'serialNumber', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
		),
		'primary' => 'host_id',
		'keys' => array(
			array('name' => 'host_status', 'columns' => 'host_status'),
		),
		'type' => 'InnoDB',
		'comment' => 'Contains all Devices that support MikroTik'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_storage', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'index', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'type', 'type' => 'int(10)', 'unsigned' => true, 'default' => '1'),
			array('name' => 'description', 'type' => 'varchar(255)', 'default' => ''),
			array('name' => 'allocationUnits', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'size', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'used', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'failures', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'default' => '1'),
		),
		'primary' => 'host_id`,`index',
		'keys' => array(
			array('name' => 'description', 'columns' => 'description'),
			array('name' => 'index', 'columns' => 'index'),
		),
		'type' => 'InnoDB',
		'comment' => 'Stores the Storage Information'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_interfaces', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'index', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'name', 'type' => 'varchar(40)', 'NULL' => false, 'default' => ''),
			array('name' => 'RxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxTooShort', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxTo64', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Rx65to127', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Rx128to255', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Rx256to511', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Rx512to1023', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Rx1024to1518', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Rx1519toMax', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxTooLong', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxBroadcast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxPause', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxMulticast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxFCFSError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxAlignError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxFragment', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxOverflow', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxControl', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxUnknownOp', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxLengthError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxCodeError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxCarrierError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxJabber', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'RxDrop', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxTooShort', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxTo64', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Tx65to127', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Tx128to255', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Tx256to511', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Tx512to1023', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Tx1024to1518', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'Tx1519toMax', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxTooLong', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxBroadcast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxPause', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxMulticast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxUnderrun', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxExCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxMultCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxSingCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxExDeferred', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxDeferred', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxLateCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxTotalCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxPauseHonored', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxDrop', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxJabber', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxFCFSError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxControl', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'TxFragment', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxTooShort', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxTo64', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRx65to127', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRx128to255', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRx256to511', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRx512to1023', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRx1024to1518', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRx1519toMax', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxTooLong', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxBroadcast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxPause', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxMulticast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxFCFSError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxAlignError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxFragment', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxOverflow', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxControl', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxUnknownOp', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxLengthError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxCodeError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxCarrierError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxJabber', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curRxDrop', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxTooShort', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxTo64', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTx65to127', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTx128to255', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTx256to511', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTx512to1023', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTx1024to1518', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTx1519toMax', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxTooLong', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxBroadcast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxPause', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxMulticast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxUnderrun', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxExCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxMultCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxSingCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxExDeferred', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxDeferred', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxLateCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxTotalCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxPauseHonored', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxDrop', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxJabber', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxFCFSError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxControl', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curTxFragment', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxTooShort', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxTo64', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRx65to127', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRx128to255', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRx256to511', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRx512to1023', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRx1024to1518', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRx1519toMax', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxTooLong', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxBroadcast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxPause', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxMulticast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxFCFSError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxAlignError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxFragment', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxOverflow', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxControl', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxUnknownOp', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxLengthError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxCodeError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxCarrierError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxJabber', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevRxDrop', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxTooShort', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxTo64', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTx65to127', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTx128to255', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTx256to511', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTx512to1023', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTx1024to1518', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTx1519toMax', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxTooLong', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxBroadcast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxPause', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxMulticast', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxUnderrun', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxExCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxMultCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxSingCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxExDeferred', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxDeferred', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxLateCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxTotalCollision', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxPauseHonored', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxDrop', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxJabber', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxFCFSError', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxControl', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevTxFragment', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
		),
		'primary' => 'host_id`,`name',
		'keys' => array(
			array('name' => 'host_id', 'columns' => 'host_id'),
			array('name' => 'name', 'columns' => 'name'),
			array('name' => 'present', 'columns' => 'present'),
			array('name' => 'index', 'columns' => 'index'),
		),
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik Interface Usage'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_queues', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'index', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'name', 'type' => 'varchar(40)', 'NULL' => false, 'default' => ''),
			array('name' => 'srcAddr', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'srcMask', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'dstAddr', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'dstMask', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'iFace', 'type' => 'varchar(20)', 'NULL' => true),
			array('name' => 'BytesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'BytesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'PacketsIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'PacketsOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'QueuesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'QueuesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'DroppedIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'DroppedOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curBytesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curBytesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curPacketsIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curPacketsOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curQueuesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curQueuesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curDroppedIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curDroppedOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevBytesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevBytesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevPacketsIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevPacketsOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevQueuesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevQueuesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevDroppedIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevDroppedOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
		),
		'primary' => 'host_id`,`name',
		'keys' => array(
			array('name' => 'host_id', 'columns' => 'host_id'),
			array('name' => 'name', 'columns' => 'name'),
			array('name' => 'present', 'columns' => 'present'),
			array('name' => 'index', 'columns' => 'index'),
		),
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik Queue Usage'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_users', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'index', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'userType', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'serverID', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'name', 'type' => 'varchar(32)', 'NULL' => false, 'default' => ''),
			array('name' => 'domain', 'type' => 'varchar(32)', 'NULL' => false, 'default' => ''),
			array('name' => 'ip', 'type' => 'varchar(40)', 'NULL' => false, 'default' => ''),
			array('name' => 'mac', 'type' => 'varchar(20)', 'NULL' => false, 'default' => ''),
			array('name' => 'connectTime', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'validTillTime', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'idleStartTime', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'idleTimeout', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'pingTimeout', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'bytesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'bytesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'packetsIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'packetsOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curBytesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curBytesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curPacketsIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curPacketsOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevBytesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevBytesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevPacketsIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevPacketsOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'limitBytesIn', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'limitBytesOut', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'advertStatus', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'radius', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'blockedByAdvert', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
		),
		'primary' => 'host_id`,`name`,`serverID`,`userType',
		'keys' => array(
			array('name' => 'host_id', 'columns' => 'host_id'),
			array('name' => 'name', 'columns' => 'name'),
			array('name' => 'ip', 'columns' => 'ip'),
			array('name' => 'domain', 'columns' => 'domain'),
			array('name' => 'present', 'columns' => 'present'),
			array('name' => 'index', 'columns' => 'index'),
		),
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik User Usage'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_trees', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'index', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'name', 'type' => 'varchar(32)', 'NULL' => false),
			array('name' => 'flow', 'type' => 'varchar(32)', 'NULL' => false),
			array('name' => 'parentIndex', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'bytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'packets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'HCBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'curHCBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevPackets', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'prevHCBytes', 'type' => 'bigint(20)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
		),
		'primary' => 'host_id`,`name',
		'keys' => array(
			array('name' => 'name', 'columns' => 'name'),
			array('name' => 'host_id', 'columns' => 'host_id'),
			array('name' => 'present', 'columns' => 'present'),
			array('name' => 'index', 'columns' => 'index'),
		),
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik Trees'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_processor', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'index', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'load', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
		),
		'primary' => 'host_id`,`index',
		'keys' => array(
			array('name' => 'index', 'columns' => 'index'),
		),
		'type' => 'InnoDB',
		'comment' => 'Stores Processor Information'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_processes', array(
		'columns' => array(
			array('name' => 'pid', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'taskid', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'started', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
		),
		'primary' => 'pid',
		'type' => 'MEMORY',
		'comment' => 'Running collector processes'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_credentials', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'mediumint(8)', 'unsigned' => true, 'NULL' => false, 'default' => '0'),
			array('name' => 'user', 'type' => 'varchar(20)', 'default' => ''),
			array('name' => 'password', 'type' => 'varchar(40)', 'default' => ''),
		),
		'primary' => 'host_id',
		'type' => 'InnoDB',
		'comment' => 'Stores MikroTik API Credentials'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_dhcp', array(
		'columns' => array(
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'address', 'type' => 'varchar(20)', 'NULL' => false),
			array('name' => 'mac_address', 'type' => 'varchar(20)', 'NULL' => false),
			array('name' => 'client_id', 'type' => 'varchar(64)', 'NULL' => false),
			array('name' => 'address_lists', 'type' => 'varchar(128)', 'default' => ''),
			array('name' => 'server', 'type' => 'varchar(20)', 'default' => ''),
			array('name' => 'dhcp_option', 'type' => 'varchar(128)', 'default' => ''),
			array('name' => 'status', 'type' => 'varchar(20)', 'default' => ''),
			array('name' => 'expires_after', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'last_seen', 'type' => 'int', 'unsigned' => true, 'default' => '0'),
			array('name' => 'active_address', 'type' => 'varchar(20)', 'default' => ''),
			array('name' => 'active_mac_address', 'type' => 'varchar(20)', 'default' => ''),
			array('name' => 'active_client_id', 'type' => 'varchar(64)', 'default' => ''),
			array('name' => 'active_server', 'type' => 'varchar(20)', 'default' => ''),
			array('name' => 'hostname', 'type' => 'varchar(64)', 'default' => ''),
			array('name' => 'radius', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'dynamic', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'blocked', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'disabled', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0'),
			array('name' => 'present', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
			array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
		),
		'primary' => 'host_id`,`mac_address',
		'keys' => array(
			array('name' => 'address', 'columns' => 'address'),
			array('name' => 'status', 'columns' => 'status'),
			array('name' => 'present', 'columns' => 'present'),
		),
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik DHCP Lease Information obtained from the API'
	));

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_lists', array(
		'columns' => array(
			array('name' => 'id', 'type' => 'bigint', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true),
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'dynamic', 'type' => 'varchar(5)', 'NULL' => false),
			array('name' => 'disabled', 'type' => 'varchar(5)', 'NULL' => false),
			array('name' => 'list', 'type' => 'varchar(20)', 'NULL' => false),
			array('name' => 'address', 'type' => 'varchar(60)', 'NULL' => false),
			array('name' => 'created', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'),
			array('name' => 'timeout', 'type' => 'int(11)', 'NULL' => false, 'default' => '-1'),
			array('name' => 'present', 'type' => 'tinyint', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
			array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
		),
		'primary' => 'id',
		'unique_keys' => array(
			array('name' => 'host_id', 'columns' => array('host_id', 'list', 'address')),
		),
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik Address List Information obtained from the API'
	));

	/* api_plugin_db_table_create() cannot express ON UPDATE; restore the auto-touch behavior each run (idempotent). */
	if (db_table_exists('plugin_mikrotik_lists')) {
		db_execute("ALTER TABLE plugin_mikrotik_lists
			MODIFY COLUMN `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP");
	}

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_dns', array(
		'columns' => array(
			array('name' => 'id', 'type' => 'bigint', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true),
			array('name' => 'host_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false),
			array('name' => 'type', 'type' => 'varchar(10)', 'NULL' => false),
			array('name' => 'data', 'type' => 'varchar(128)', 'NULL' => false),
			array('name' => 'name', 'type' => 'varchar(128)', 'NULL' => false),
			array('name' => 'ttl', 'type' => 'int(10)', 'NULL' => false, 'default' => '-1'),
			array('name' => 'static', 'type' => 'varchar(10)', 'NULL' => false, 'default' => ''),
			array('name' => 'present', 'type' => 'tinyint', 'unsigned' => true, 'NULL' => false, 'default' => '1'),
			array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP'),
		),
		'primary' => 'id',
		'unique_keys' => array(
			array('name' => 'host_id', 'columns' => array('host_id', 'type', 'data', 'name')),
		),
		'type' => 'InnoDB',
		'comment' => 'Table of MikroTik DNS Information obtained from the API'
	));

	/* api_plugin_db_table_create() cannot express ON UPDATE; restore the auto-touch behavior each run (idempotent). */
	if (db_table_exists('plugin_mikrotik_dns')) {
		db_execute("ALTER TABLE plugin_mikrotik_dns
			MODIFY COLUMN `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP");
	}

	api_plugin_db_table_create('mikrotik', 'plugin_mikrotik_mac2hostname', array(
		'columns' => array(
			array('name' => 'mac_address', 'type' => 'varchar(20)', 'default' => ''),
			array('name' => 'hostname', 'type' => 'varchar(64)', 'default' => ''),
			array('name' => 'last_updated', 'type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'),
		),
		'primary' => 'mac_address`,`hostname',
		'type' => 'InnoDB',
		'comment' => 'Holds mappings from MAC Address to Hostname'
	));

	/* api_plugin_db_table_create() cannot express ON UPDATE; restore the auto-touch behavior each run (idempotent). */
	if (db_table_exists('plugin_mikrotik_mac2hostname')) {
		db_execute("ALTER TABLE plugin_mikrotik_mac2hostname
			MODIFY COLUMN `last_updated` timestamp default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP");
	}
}
