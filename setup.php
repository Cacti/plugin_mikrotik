<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2010 The Cacti Group                                 |
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
	db_execute("DROP TABLE IF EXISTS `plugin_mikrotik_system`");
	db_execute("DROP TABLE IF EXISTS `plugin_mikrotik_storage`");
	db_execute("DROP TABLE IF EXISTS `plugin_mikrotik_users`");
	db_execute("DROP TABLE IF EXISTS `plugin_mikrotik_trees`");
	db_execute("DROP TABLE IF EXISTS `plugin_mikrotik_processes`");
	db_execute("DROP TABLE IF EXISTS `plugin_mikrotik_processor`");
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
	include_once($config["library_path"] . "/database.php");
	include_once($config["library_path"] . "/functions.php");

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
		db_execute("UPDATE plugin_config SET version='$current' WHERE directory='mikrotik'");
		db_execute("UPDATE plugin_config SET " .
				"version='" . $version["version"] . "', " .
				"name='" . $version["longname"] . "', " .
				"author='" . $version["author"] . "', " .
				"webpage='" . $version["url"] . "' " .
				"WHERE directory='" . $version["name"] . "' ");
	}
}

function mikrotik_check_dependencies() {
	return true;
}

function mikrotik_setup_table () {
	global $config, $database_default;
	include_once($config["library_path"] . "/database.php");

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
		PRIMARY KEY  (`host_id`),
		INDEX `host_status` (`host_status`))
		ENGINE=MyISAM
		COMMENT='Contains all Hosts that support MikroTik';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_storage` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`type` int(10) unsigned NOT NULL default '1',
		`description` varchar(255) NOT NULL default '',
		`allocationUnits` int(10) unsigned NOT NULL default '0',
		`size` int(10) unsigned NOT NULL default '0',
		`used` int(10) unsigned NOT NULL default '0',
		`failures` int(10) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY  (`host_id`,`index`),
		INDEX `description` (`description`),
		INDEX `index` (`index`))
		ENGINE=MyISAM
		COMMENT='Stores the Storage Information';");

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_users` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`serverID` int(10) unsigned NOT NULL,
		`name` varchar(32) NOT NULL,
		`domain` varchar(32) NOT NULL,
		`ip` varchar(40) NOT NULL,
		`mac` varchar(20) NOT NULL,
		`connectTime` int(10) unsigned NOT NULL,
		`validTillTime` int(10) unsigned NOT NULL,
		`idleStartTime` int(10) unsigned NOT NULL,
		`idleTimeout` int(10) unsigned NOT NULL,
		`pingTimeout` int(10) unsigned NOT NULL,
		`bytesIn` bigint(20) unsigned NOT NULL,
		`bytesOut` bigint(20) unsigned NOT NULL,
		`packetsIn` bigint(20) unsigned NOT NULL,
		`packetsOut` bigint(20) unsigned NOT NULL,
		`curBytesIn` bigint(20) unsigned NOT NULL,
		`curBytesOut` bigint(20) unsigned NOT NULL,
		`curPacketsIn` bigint(20) unsigned NOT NULL,
		`curPacketsOut` bigint(20) unsigned NOT NULL,
		`prevBytesIn` bigint(20) unsigned NOT NULL,
		`prevBytesOut` bigint(20) unsigned NOT NULL,
		`prevPacketsIn` bigint(20) unsigned NOT NULL,
		`prevPacketsOut` bigint(20) unsigned NOT NULL,
		`limitBytesIn` bigint(20) unsigned NOT NULL,
		`limitBytesOut` bigint(20) unsigned NOT NULL,
		`advertStatus` int(10) unsigned NOT NULL,
		`radius` int(10) unsigned NOT NULL,
		`blockedByAdvert` int(10) unsigned NOT NULL,
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

	db_execute("CREATE TABLE IF NOT EXISTS `plugin_mikrotik_trees` (
		`host_id` int(10) unsigned NOT NULL,
		`index` int(10) unsigned NOT NULL,
		`name` varchar(32) NOT NULL,
		`flow` varchar(32) NOT NULL,
		`parentIndex` int(10) unsigned NOT NULL,
		`bytes` bigint(20) unsigned NOT NULL,
		`packets` bigint(20) unsigned NOT NULL,
		`HCBytes` bigint(20) unsigned NOT NULL,
		`last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`prevBytes` bigint(20) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`host_id`,`name`),
		KEY `name` (`name`),
		KEY `host_id` (`host_id`),
		KEY `present` (`present`),
		KEY `index` (`index`))
		ENGINE=MyISAM
		COMMENT='Table of MikroTik Trees';");

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
	include_once($config["base_path"] . "/lib/poller.php");

	exec_background(read_config_option("path_php_binary"), " -q " . $config["base_path"] . "/plugins/mikrotik/poller_mikrotik.php -M");
}

function mikrotik_config_settings () {
	global $tabs, $settings, $mikrotik_frequencies, $item_rows;

	$tabs['mikrotik'] = 'MikroTik';
	$settings['mikrotik'] = array(
		"mikrotik_header" => array(
			"friendly_name" => "MikroTik General Settings",
			"method" => "spacer",
			),
		"mikrotik_enabled" => array(
			"friendly_name" => "MikroTik Poller Enabled",
			"description" => "Check this box, if you want MikroTik polling to be enabled.  Otherwise, the poller will not function.",
			"method" => "checkbox",
			"default" => ""
			),
		"mikrotik_autodiscovery" => array(
			"friendly_name" => "Automatically Discover Cacti Devices",
			"description" => "Do you wish to automatically scan for and add devices which support the MikroTik MIB from the Cacti host table?",
			"method" => "checkbox",
			"default" => "on"
			),
		"mikrotik_autopurge" => array(
			"friendly_name" => "Automatically Purge Devices",
			"description" => "Do you wish to automatically purge devices that are removed from the Cacti system?",
			"method" => "checkbox",
			"default" => "on"
			),
		"mikrotik_concurrent_processes" => array(
			"friendly_name" => "Maximum Concurrent Collectors",
			"description" => "What is the maximum number of concurrent collector process that you want to run at one time?",
			"method" => "drop_array",
			"default" => "10",
			"array" => array(
				1  => "1 Process",
				2  => "2 Processes",
				3  => "3 Processes",
				4  => "4 Processes",
				5  => "5 Processes",
				10 => "10 Processes",
				20 => "20 Processes",
				30 => "30 Processes",
				40 => "40 Processes",
				50 => "50 Processes")
			),
		//"mikrotik_host_templates" => array(
		//	"friendly_name" => "Host Template Association",
		//	"method" => "spacer",
		//	),
		//"mikrotik_summary_host_template" => array(
		//	"friendly_name" => "MikroTik Summary Device Template",
		//	"description" => "Select the Host Template associated with the MikroTik
		//	summary device.  This device will contain graphs for summary metrics.",
		//	"method" => "drop_sql",
		//	"default" => "",
		//	"none_value" => "N/A",
		//	"sql" => "SELECT id, name FROM host_template ORDER BY name",
		//	),
		"mikrotik_graphs" => array(
			"friendly_name" => "Data Query Associations",
			"method" => "spacer",
			),
		//"mikrotik_dq_trees" => array(
		//	"friendly_name" => "MikroTik Trees Data Query",
		//	"description" => "Select the Data Query associated with the MikroTik Tree aggregation.  This will be
		//	used for action icon placement.",
		//	"method" => "drop_sql",
		//	"default" => "",
		//	"none_value" => "N/A",
		//	"sql" => "SELECT id, name FROM snmp_query ORDER BY name",
		//	),
		"mikrotik_dq_users" => array(
			"friendly_name" => "MikroTik Users Data Query",
			"description" => "Select the Data Query associated with the MikroTik User aggregation.  This will be
			used for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT id, name FROM snmp_query ORDER BY name",
			),
		//"mikrotik_dq_cpu" => array(
		//	"friendly_name" => "MikroTik CPU Usage Data Query",
		//	"description" => "Select the Data Query associated with the MikroTik CPU Utilization.  This will be
		//	used for action icon placement.",
		//	"method" => "drop_sql",
		//	"default" => "",
		//	"none_value" => "N/A",
		//	"sql" => "SELECT id, name FROM snmp_query ORDER BY name",
		//	),
		"mikrotik_templates" => array(
			"friendly_name" => "Graph Template Associations",
			"method" => "spacer",
			),
		"mikrotik_gt_users" => array(
			"friendly_name" => "MikroTik Users Template",
			"description" => "Select the Graph Template associated with the Logged on User Graphs.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_disk" => array(
			"friendly_name" => "MikroTik Disk Template",
			"description" => "Select the Graph Template associated with the Disk Utilization.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_memory" => array(
			"friendly_name" => "MikroTik Memory Template",
			"description" => "Select the Graph Template associated with the Memory Utilization.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_cpu" => array(
			"friendly_name" => "MikroTik CPU Template",
			"description" => "Select the Graph Template associated with the CPU Utilization.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_processes" => array(
			"friendly_name" => "MikroTik Processes Template",
			"description" => "Select the Graph Template associated with the number of Processes.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_arp" => array(
			"friendly_name" => "MikroTik ARP Entries Template",
			"description" => "Select the Graph Template associated with the number ARP Entries.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_routes" => array(
			"friendly_name" => "MikroTik IP Routes Template",
			"description" => "Select the Graph Template associated with the number IP Route Entries.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_ppp" => array(
			"friendly_name" => "MikroTik PPP Active Template",
			"description" => "Select the Graph Template associated with the number PPP Entries.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_gt_uptime" => array(
			"friendly_name" => "MikroTik Uptime Template",
			"description" => "Select the Graph Template associated with the Devices Uptime.  This will be used
			for action icon placement.",
			"method" => "drop_sql",
			"default" => "",
			"none_value" => "N/A",
			"sql" => "SELECT gt.id, gt.name
				FROM graph_templates AS gt
				LEFT JOIN snmp_query_graph AS sqg
				ON gt.id=sqg.graph_template_id
				WHERE sqg.id IS NULL
				ORDER BY name",
			),
		"mikrotik_autodiscovery_header" => array(
			"friendly_name" => "MikroTik Auto Discovery Frequency",
			"method" => "spacer",
			),
		"mikrotik_autodiscovery_freq" => array(
			"friendly_name" => "Auto Discovery Frequency",
			"description" => "How often do you want to look for new Cacti Devices?",
			"method" => "drop_array",
			"default" => "300",
			"array" => $mikrotik_frequencies
			),
		"mikrotik_automation_header" => array(
			"friendly_name" => "Host Graph Automation",
			"method" => "spacer",
			),
		"mikrotik_automation_frequency" => array(
			"friendly_name" => "Automatically Add New Graphs",
			"description" => "How often do you want to check for new objects to graph?",
			"method" => "drop_array",
			"default" => "0",
			"array" => array(
				0    => "Never",
				10   => "10 Minutes",
				20   => "20 Minutes",
				30   => "30 Minutes",
				60   => "1 Hour",
				720  => "12 Hours",
				1440 => "1 Day",
				2880 => "2 Days")
			),
		"mikrotik_frequencies" => array(
			"friendly_name" => "MikroTik Table Collection Frequencies",
			"method" => "spacer",
			),
		"mikrotik_storage_freq" => array(
			"friendly_name" => "Storage Frequency",
			"description" => "How often do you want to scan Storage Statistics?",
			"method" => "drop_array",
			"default" => "300",
			"array" => $mikrotik_frequencies
			),
		"mikrotik_processor_freq" => array(
			"friendly_name" => "Processor Frequency",
			"description" => "How often do you want to scan Device Processor Statistics?",
			"method" => "drop_array",
			"default" => "300",
			"array" => $mikrotik_frequencies
			),
		"mikrotik_users_freq" => array(
			"friendly_name" => "Users Frequency",
			"description" => "How often do you want to scan User Statistics?",
			"method" => "drop_array",
			"default" => "300",
			"array" => $mikrotik_frequencies
			),
		"mikrotik_trees_freq" => array(
			"friendly_name" => "Queue Trees Frequency",
			"description" => "How often do you want to scan the Queue Trees?",
			"method" => "drop_array",
			"default" => "300",
			"array" => $mikrotik_frequencies
			)
		);
}

function mikrotik_config_arrays() {
	global $menu, $messages, $mikrotik_frequencies;
	global $mikrotikSystem, $mikrotikTrees, $mikrotikUsers, $mikrotikProcessor, $mikrotikStorage;

	$menu['Management']['plugins/mikrotik/mikrotik_users.php'] = 'Mikrotik Users';

	$mikrotik_frequencies = array(
		-1    => "Disabled",
		60    => "1 Minute",
		300   => "5 Minutes",
		600   => "10 Minutes",
		1200  => "20 Minutes",
		3600  => "1 Hour",
		7200  => "2 Hours",
		14400 => "4 Hours",
		43200 => "12 Hours",
		86400 => "1 Day"
	);

	$mikrotikSystem = array(
		"baseOID"        => ".1.3.6.1.2.1.25.1.",
		"uptime"         => ".1.3.6.1.2.1.25.1.1.0",
		"date"           => ".1.3.6.1.2.1.25.1.2.0",
		"processes"      => ".1.3.6.1.2.1.1.7.0",
		"memory"         => ".1.3.6.1.2.1.25.2.2.0",
		"sysDescr"       => ".1.3.6.1.2.1.1.1.0",
		"sysObjectID"    => ".1.3.6.1.2.1.1.2.0",
		"sysUptime"      => ".1.3.6.1.2.1.1.3.0",
		"sysContact"     => ".1.3.6.1.2.1.1.4.0",
		"sysName"        => ".1.3.6.1.2.1.1.5.0",
		"sysLocation"    => ".1.3.6.1.2.1.1.6.0"
	);

	$mikrotikStorage = array(
		"baseOID"         => ".1.3.6.1.2.1.25.2.3",
		"index"           => ".1.3.6.1.2.1.25.2.3.1.1",
		"type"            => ".1.3.6.1.2.1.25.2.3.1.2",
		"description"     => ".1.3.6.1.2.1.25.2.3.1.3",
		"allocationUnits" => ".1.3.6.1.2.1.25.2.3.1.4",
		"size"            => ".1.3.6.1.2.1.25.2.3.1.5",
		"used"            => ".1.3.6.1.2.1.25.2.3.1.6",
		"failures"        => ".1.3.6.1.2.1.25.2.3.1.7"
	);

	$mikrotikUsers = array(
		"index"           => ".1.3.6.1.4.1.14988.1.1.5.1.1.1",
		"serverId"        => ".1.3.6.1.4.1.14988.1.1.5.1.1.2",
		"name"            => ".1.3.6.1.4.1.14988.1.1.5.1.1.3",
		"domain"          => ".1.3.6.1.4.1.14988.1.1.5.1.1.4",
		"ip"              => ".1.3.6.1.4.1.14988.1.1.5.1.1.5",
		"mac"             => ".1.3.6.1.4.1.14988.1.1.5.1.1.6",
		"connectTime"     => ".1.3.6.1.4.1.14988.1.1.5.1.1.7",
		"validTillTime"   => ".1.3.6.1.4.1.14988.1.1.5.1.1.8",
		"idleStartTime"   => ".1.3.6.1.4.1.14988.1.1.5.1.1.9",
		"idleTimeout"     => ".1.3.6.1.4.1.14988.1.1.5.1.1.10",
		"pingTimeout"     => ".1.3.6.1.4.1.14988.1.1.5.1.1.11",
		"bytesIn"         => ".1.3.6.1.4.1.14988.1.1.5.1.1.12",
		"bytesOut"        => ".1.3.6.1.4.1.14988.1.1.5.1.1.13",
		"packetsIn"       => ".1.3.6.1.4.1.14988.1.1.5.1.1.14",
		"packetsOut"      => ".1.3.6.1.4.1.14988.1.1.5.1.1.15",
		"limitBytesIn"    => ".1.3.6.1.4.1.14988.1.1.5.1.1.16",
		"limitBytesOut"   => ".1.3.6.1.4.1.14988.1.1.5.1.1.17",
		"advertStatus"    => ".1.3.6.1.4.1.14988.1.1.5.1.1.18",
		"radius"          => ".1.3.6.1.4.1.14988.1.1.5.1.1.19",
		"blockedByAdvert" => ".1.3.6.1.4.1.14988.1.1.5.1.1.20",
	);

	$mikrotikTrees = array(
		"index"       => ".1.3.6.1.4.1.14988.1.1.2.2.1.1",
		"name"        => ".1.3.6.1.4.1.14988.1.1.2.2.1.2",
		"flow"        => ".1.3.6.1.4.1.14988.1.1.2.2.1.3",
		"parentIndex" => ".1.3.6.1.4.1.14988.1.1.2.2.1.4",
		"bytes"       => ".1.3.6.1.4.1.14988.1.1.2.2.1.5",
		"packets"     => ".1.3.6.1.4.1.14988.1.1.2.2.1.6",
		"HCBytes"     => ".1.3.6.1.4.1.14988.1.1.2.2.1.7"
	);

	$mikrotikProcessor = array(
		"baseOID" => ".1.3.6.1.2.1.25.3.3.1",
		"load"    => ".1.3.6.1.2.1.25.3.3.1.2"
	);

	if (isset($_SESSION['mikrotik_message']) && $_SESSION['mikrotik_message'] != '') {
		$messages['mikrotik_message'] = array('message' => $_SESSION['mikrotik_message'], 'type' => 'info');
	}
}

function mikrotik_draw_navigation_text ($nav) {
	$nav["mikrotik.php:"]        = array("title" => "MikroTik Devices", "mapping" => "", "url" => "mikrotik.php", "level" => "0");
	$nav["mikrotik.php:devices"] = array("title" => "MikroTik Devices", "mapping" => "", "url" => "mikrotik.php", "level" => "0");
	$nav["mikrotik.php:trees"]   = array("title" => "MikroTik Trees", "mapping" => "", "url" => "mikrotik.php", "level" => "0");
	$nav["mikrotik.php:users"]   = array("title" => "MikroTik Users", "mapping" => "", "url" => "mikrotik.php", "level" => "0");
	$nav["mikrotik.php:storage"] = array("title" => "MikroTik Storage", "mapping" => "", "url" => "mikrotik.php", "level" => "0");
	$nav["mikrotik.php:graphs"]  = array("title" => "MikroTik Graphs", "mapping" => "", "url" => "mikrotik.php", "level" => "0");

	$nav["mikrotik_users.php:"]  = array("title" => "MikroTik Users", "mapping" => "index.php:", "url" => "mikrotik_user.php", "level" => "1");
	$nav["mikrotik_users.php:edit"] = array("title" => "(Edit)", "mapping" => "index.php:,mikrotik_users.php:", "url" => "", "level" => "2");
	$nav["mikrotik_users.php:actions"] = array("title" => "Actions", "mapping" => "index.php:,mikrotik_users.php:", "url" => "", "level" => "2");

	return $nav;
}

function mikrotik_show_tab() {
	global $config;

	if (api_user_realm_auth('mikrotik.php')) {
		if (substr_count($_SERVER["REQUEST_URI"], "mikrotik.php")) {
			print '<a href="' . $config['url_path'] . 'plugins/mikrotik/mikrotik.php"><img src="' . $config['url_path'] . 'plugins/mikrotik/images/tab_mikrotik_down.gif" alt="MikroTik" align="absmiddle" border="0"></a>';
		}else{
			print '<a href="' . $config['url_path'] . 'plugins/mikrotik/mikrotik.php"><img src="' . $config['url_path'] . 'plugins/mikrotik/images/tab_mikrotik.gif" alt="MikroTik" align="absmiddle" border="0"></a>';
		}
	}
}

?>
