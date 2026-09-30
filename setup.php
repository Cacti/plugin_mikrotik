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

require_once(__DIR__ . '/includes/database.php');

/**
 * Return the CSP nonce attribute for inline <script> tags, safely across
 * Cacti versions. Newer Cacti releases enforce a Content-Security-Policy that
 * requires a per-request nonce on parser-inserted scripts; older releases lack
 * the CactiSecureHeaders class, so this returns an empty string there.
 *
 * @return string The `nonce="..."` attribute when supported, otherwise ''.
 */
function plugin_mikrotik_csp_nonce(): string {
	if (class_exists('CactiSecureHeaders')) {
		return CactiSecureHeaders::getNonceAttribute();
	}

	return '';
}

/**
 * Plugin install hook: registers all of this plugin's Cacti hooks
 * (config arrays/settings, navigation text, poller_bottom, header
 * tabs, and host edit/save/delete integration), registers its two
 * admin realms, and creates its database tables. Called by Cacti's
 * plugin architecture when the plugin is installed.
 *
 * @return void
 */
function plugin_mikrotik_install(): void {
	// graph setup all arrays needed for automation
	api_plugin_register_hook('mikrotik', 'config_arrays',         'mikrotik_config_arrays',         'setup.php');
	api_plugin_register_hook('mikrotik', 'config_settings',       'mikrotik_config_settings',       'setup.php');
	api_plugin_register_hook('mikrotik', 'draw_navigation_text',  'mikrotik_draw_navigation_text',  'setup.php');
	api_plugin_register_hook('mikrotik', 'poller_bottom',         'mikrotik_poller_bottom',         'setup.php');
	api_plugin_register_hook('mikrotik', 'top_header_tabs',       'mikrotik_show_tab',              'setup.php');
	api_plugin_register_hook('mikrotik', 'top_graph_header_tabs', 'mikrotik_show_tab',              'setup.php');
	api_plugin_register_hook('mikrotik', 'host_edit_top',         'mikrotik_host_top',              'setup.php');
	api_plugin_register_hook('mikrotik', 'host_save',             'mikrotik_host_save',             'setup.php');
	api_plugin_register_hook('mikrotik', 'host_delete',           'mikrotik_host_delete',           'setup.php');

	api_plugin_register_realm('mikrotik', 'mikrotik.php', __('MikroTik Viewer', 'mikrotik'), 1);
	api_plugin_register_realm('mikrotik', 'mikrotik_users.php', __('MikroTik Admin', 'mikrotik'), 1);

	mikrotik_setup_table();
}

/**
 * Plugin uninstall hook: drops all of this plugin's database tables.
 * Called by Cacti's plugin architecture when the plugin is
 * uninstalled.
 *
 * @return void
 */
function plugin_mikrotik_uninstall(): void {
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
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_credentials`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_dhcp`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_dns`');
	db_execute('DROP TABLE IF EXISTS `plugin_mikrotik_lists`');
}

/**
 * Plugin config-check hook: ensures the plugin's schema/hooks are up to
 * date by delegating to mikrotik_check_upgrade(). Called by Cacti's
 * plugin architecture on relevant page loads.
 *
 * @return bool Always true.
 */
function plugin_mikrotik_check_config(): bool {
	// Here we will check to ensure everything is configured
	mikrotik_check_upgrade();

	return true;
}

/**
 * Plugin upgrade hook: brings the plugin's schema/hooks up to date by
 * delegating to mikrotik_check_upgrade(). Called by Cacti's plugin
 * architecture when the plugin is upgraded to a new version.
 *
 * @return bool Always true.
 */
function plugin_mikrotik_upgrade(): bool {
	// Here we will upgrade to the newest version
	mikrotik_check_upgrade();

	return true;
}

/**
 * Reads and returns this plugin's version/author/metadata info from its
 * INFO file. Called wherever plugin metadata is needed (e.g.
 * display_version() in the poller scripts, mikrotik_check_upgrade()).
 *
 * @return array The plugin's info array, as parsed from the INFO
 *               file's '[info]' section.
 *
 * @global array $config Cacti global configuration array; used to
 *                       locate the plugin's INFO file.
 */
function plugin_mikrotik_version(): array {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/mikrotik/INFO', true);

	if (!is_array($info) || !isset($info['info']) || !is_array($info['info'])) {
		return [];
	}

	return $info['info'];
}

/**
 * Checks whether the plugin's recorded database version differs from
 * its actual (INFO file) version and, if so, re-enables its hooks,
 * applies a sequence of incremental schema changes (new trend/system
 * columns, a users-table primary key change, and any missing DHCP/DNS/
 * lists tables), updates the plugin_config record, and clears a stale
 * config_form hook registration. Only runs on plugins.php/mikrotik.php
 * page loads. Called from plugin_mikrotik_check_config() and
 * plugin_mikrotik_upgrade().
 *
 * @return void
 *
 * @global array $config           Cacti global configuration array
 *                                (declared but not directly used
 *                                here).
 * @global mixed $database_default Reserved/declared for parity with
 *                                other setup functions; not used
 *                                directly here.
 */
function mikrotik_check_upgrade(): void {
	global $config, $database_default;

	// Let's only run this check if we are on a page that actually needs the data
	$files = ['plugins.php', 'mikrotik.php'];

	if (!in_array(get_current_page(), $files, true)) {
		return;
	}

	$info    = plugin_mikrotik_version();
	$current = $info['version'];
	$old     = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='mikrotik'");

	if ($current != $old) {
		if (api_plugin_is_enabled('mikrotik')) {
			api_plugin_enable_hooks('mikrotik');
		}

		if (!db_column_exists('plugin_mikrotik_trees', 'prevPackets')) {
			db_execute('ALTER TABLE plugin_mikrotik_trees ADD COLUMN prevPackets BIGINT UNSIGNED default NULL AFTER prevBytes');
			db_execute('ALTER TABLE plugin_mikrotik_trees ADD COLUMN prevHCBytes BIGINT UNSIGNED default NULL AFTER prevPackets');
			db_execute('ALTER TABLE plugin_mikrotik_trees ADD COLUMN curBytes BIGINT UNSIGNED default null AFTER HCBytes');
			db_execute('ALTER TABLE plugin_mikrotik_trees ADD COLUMN curPackets BIGINT UNSIGNED default null AFTER curBytes');
			db_execute('ALTER TABLE plugin_mikrotik_trees ADD COLUMN curHCBytes BIGINT UNSIGNED default null AFTER curPackets');
		}

		if (!db_column_exists('plugin_mikrotik_system', 'firmwareVersion')) {
			db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN firmwareVersion varchar(20) NOT NULL default '' AFTER sysLocation");
			db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN firmwareVersionLatest varchar(20) NOT NULL default '' AFTER firmwareVersion");
			db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN licVersion varchar(20) NOT NULL default '' AFTER firmwareVersion");
			db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN softwareID varchar(20) NOT NULL default '' AFTER licVersion");
			db_execute("ALTER TABLE plugin_mikrotik_system ADD COLUMN serialNumber varchar(20) NOT NULL default '' AFTER softwareID");
		}

		if (!db_column_exists('plugin_mikrotik_users', 'userType')) {
			db_execute("ALTER TABLE plugin_mikrotik_users ADD COLUMN userType int unsigned DEFAULT '0' AFTER `index`");
			db_execute('ALTER TABLE plugin_mikrotik_users DROP PRIMARY KEY, ADD PRIMARY KEY (`host_id`,`name`,`serverID`,`userType`)');
		}

		if (!db_table_exists('plugin_mikrotik_dhcp') ||
			!db_table_exists('plugin_mikrotik_dns') ||
			!db_table_exists('plugin_mikrotik_lists')) {
			mikrotik_setup_table();
		}

		// Remove files tombstoned in manifest.json plus the dev-only tests/ tree.
		plugin_mikrotik_prune_files();

		db_execute_prepared('UPDATE plugin_config
			SET version = ?, name = ?, author = ?, webpage = ?
			WHERE directory = ?',
			[
				$info['version'],
				$info['longname'],
				$info['author'],
				$info['homepage'],
				$info['name']
			]
		);

		db_execute('DELETE FROM plugin_hooks WHERE name = "mikrotik" and hook = "config_form"');
	}
}

/**
 * Removes every graph using a given graph template along with any of
 * its data sources that would become orphaned (used by no other
 * graph), including cleanup of data sources left with no remaining
 * graph reference at all. Called during schema migrations/template
 * consolidations that retire a graph template.
 *
 * @param int $graph_template_id The graph_templates id whose graphs
 *                               (and now-orphaned data sources) should
 *                               be removed.
 *
 * @return void
 */
function mikrotik_delete_graphs_and_data_sources_from_hash(int $graph_template_id): void {
	$graphs = array_rekey(
		db_fetch_assoc_prepared('SELECT id
			FROM graph_local
			WHERE graph_template_id = ?',
			[$graph_template_id]),
		'id', 'id'
	);

	if (cacti_sizeof($graphs)) {
		$all_data_sources = array_rekey(db_fetch_assoc('SELECT DISTINCT dtd.local_data_id
			FROM data_template_data AS dtd
			INNER JOIN data_template_rrd AS dtr
			ON dtd.local_data_id=dtr.local_data_id
			INNER JOIN graph_templates_item AS gti
			ON dtr.id=gti.task_item_id
			WHERE ' . array_to_sql_or($graphs, 'gti.local_graph_id') . '
			AND dtd.local_data_id > 0'), 'local_data_id', 'local_data_id');

		$data_sources = array_rekey(db_fetch_assoc('SELECT dtd.local_data_id,
			COUNT(DISTINCT gti.local_graph_id) AS graphs
			FROM data_template_data AS dtd
			INNER JOIN data_template_rrd AS dtr
			ON dtd.local_data_id=dtr.local_data_id
			INNER JOIN graph_templates_item AS gti
			ON dtr.id=gti.task_item_id
			WHERE dtd.local_data_id > 0
			GROUP BY dtd.local_data_id
			HAVING graphs = 1
			AND ' . array_to_sql_or($all_data_sources, 'local_data_id')), 'local_data_id', 'local_data_id');

		if (cacti_sizeof($data_sources)) {
			api_data_source_remove_multi($data_sources);
			api_plugin_hook_function('data_source_remove', $data_sources);
		}

		api_graph_remove_multi($graphs);
		api_plugin_hook_function('graphs_remove', $graphs);

		// Remove orphaned data sources
		$data_sources = array_rekey(db_fetch_assoc('SELECT DISTINCT dtd.local_data_id
			FROM data_template_data AS dtd
			INNER JOIN data_template_rrd AS dtr
			ON dtd.local_data_id=dtr.local_data_id
			LEFT JOIN graph_templates_item AS gti
			ON dtr.id=gti.task_item_id
			WHERE ' . array_to_sql_or($all_data_sources, 'dtd.local_data_id') . '
			AND gti.local_graph_id IS NULL
			AND dtd.local_data_id > 0'), 'local_data_id', 'local_data_id');

		if (cacti_sizeof($data_sources)) {
			api_data_source_remove_multi($data_sources);
			api_plugin_hook_function('data_source_remove', $data_sources);
		}
	}
}

/**
 * Reports whether this plugin's dependencies are satisfied. Called by
 * Cacti's plugin architecture when checking whether the plugin can be
 * enabled.
 *
 * @return bool Always true (this plugin declares no extra
 *              dependencies).
 */
function mikrotik_check_dependencies(): bool {
	return true;
}


/**
 * Poller_bottom hook: launches the main MikroTik poller process
 * (poller_mikrotik.php -M) as a background process at the end of each
 * Cacti polling cycle. Called by Cacti's poller via the
 * 'poller_bottom' hook.
 *
 * @return void
 *
 * @global array $config Cacti global configuration array; used to
 *                       locate the PHP binary and this plugin's poller
 *                       script.
 */
function mikrotik_poller_bottom(): void {
	global $config;
	include_once($config['base_path'] . '/lib/poller.php');

	exec_background(read_config_option('path_php_binary'), ' -q ' . $config['base_path'] . '/plugins/mikrotik/poller_mikrotik.php -M');
}

/**
 * Config_settings hook: registers this plugin's 'MikroTik' settings tab
 * and all of its configuration fields (poller enable toggle, row-count/
 * frequency/retention defaults, and collection intervals). Called by
 * Cacti's settings framework via the 'config_settings' hook.
 *
 * @return void
 *
 * @global array $tabs                  Cacti's settings tabs registry;
 *                                      appended with this plugin's
 *                                      tab.
 * @global array $settings              Cacti's settings fields
 *                                      registry; appended with this
 *                                      plugin's fields.
 * @global array $mikrotik_frequencies  Map of frequency (seconds) =>
 *                                      display label, used as the
 *                                      option list for the frequency
 *                                      drop-downs.
 * @global array $mikrotik_retention    Map of retention period
 *                                      (seconds) => display label,
 *                                      used as the option list for the
 *                                      retention drop-downs.
 * @global array $item_rows             Cacti's standard row-count
 *                                      option list, used for the
 *                                      default row-count setting.
 */
function mikrotik_config_settings(): void {
	global $tabs, $settings, $mikrotik_frequencies, $mikrotik_retention, $item_rows;

	$tabs['mikrotik']     = __('MikroTik', 'mikrotik');
	$settings['mikrotik'] = [
		'mikrotik_header' => [
			'friendly_name' => __('MikroTik General Settings', 'mikrotik'),
			'method'        => 'spacer',
			],
		'mikrotik_enabled' => [
			'friendly_name' => __('MikroTik Poller Enabled', 'mikrotik'),
			'description'   => __('Check this box, if you want MikroTik polling to be enabled.  Otherwise, the poller will not function.', 'mikrotik'),
			'method'        => 'checkbox',
			'default'       => 'on'
			],
		'mikrotik_api_enabled' => [
			'friendly_name' => __('MikroTik API Polling Enabled', 'mikrotik'),
			'description'   => __('Check this box, if you want Cacti to poll various settings using the MikroTik API.  If for some reason you don\'t have access to the MikroTik API, uncheck this option.', 'mikrotik'),
			'method'        => 'checkbox',
			'default'       => 'on'
			],
		'mikrotik_autodiscovery' => [
			'friendly_name' => __('Automatically Discover Cacti Devices', 'mikrotik'),
			'description'   => __('Do you wish to automatically scan for and add devices which support the MikroTik MIB from the Cacti host table?', 'mikrotik'),
			'method'        => 'checkbox',
			'default'       => 'on'
			],
		'mikrotik_autopurge' => [
			'friendly_name' => __('Automatically Purge Devices', 'mikrotik'),
			'description'   => __('Do you wish to automatically purge devices that are removed from the Cacti system?', 'mikrotik'),
			'method'        => 'checkbox',
			'default'       => 'on'
			],
		'mikrotik_concurrent_processes' => [
			'friendly_name' => __('Maximum Concurrent Collectors', 'mikrotik'),
			'description'   => __('What is the maximum number of concurrent collector process that you want to run at one time?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '10',
			'array'         => [
				1  => __('%d Process', 1, 'mikrotik'),
				2  => __('%d Processes', 2, 'mikrotik'),
				3  => __('%d Processes', 3, 'mikrotik'),
				4  => __('%d Processes', 4, 'mikrotik'),
				5  => __('%d Processes', 5, 'mikrotik'),
				10 => __('%d Processes', 10, 'mikrotik'),
				20 => __('%d Processes', 20, 'mikrotik'),
				30 => __('%d Processes', 30, 'mikrotik'),
				40 => __('%d Processes', 40, 'mikrotik'),
				50 => __('%d Processes', 50, 'mikrotik')
				]
			],
		'mikrotik_autodiscovery_header' => [
			'friendly_name' => __('Auto Discovery Settings', 'mikrotik'),
			'method'        => 'spacer',
			],
		'mikrotik_autodiscovery_freq' => [
			'friendly_name' => __('Auto Discovery Frequency', 'mikrotik'),
			'description'   => __('How often do you want to look for new Cacti Devices?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_dhcp_header' => [
			'friendly_name' => __('DHCP/DNS/List Settings', 'mikrotik'),
			'method'        => 'spacer',
			],
		'mikrotik_dhcp_retention' => [
			'friendly_name' => __('DHCP Retention', 'mikrotik'),
			'description'   => __('How long would you like to retain DHCP IP registration history?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '2419200',
			'array'         => $mikrotik_retention
			],
		'mikrotik_dns_retention' => [
			'friendly_name' => __('DNS Retention', 'mikrotik'),
			'description'   => __('How long would you like to retain DNS Cache history?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '2419200',
			'array'         => $mikrotik_retention
			],
		'mikrotik_list_retention' => [
			'friendly_name' => __('Address List Retention', 'mikrotik'),
			'description'   => __('How long would you like to retain Address List history?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '2419200',
			'array'         => $mikrotik_retention
			],
		'mikrotik_automation_header' => [
			'friendly_name' => __('Device Graph Automation', 'mikrotik'),
			'method'        => 'spacer',
			],
		'mikrotik_automation_frequency' => [
			'friendly_name' => __('Automatically Add New Graphs', 'mikrotik'),
			'description'   => __('How often do you want to check for new objects to graph?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '0',
			'array'         => [
				0    => __('Never', 'mikrotik'),
				10   => __('%d Minutes', 10, 'mikrotik'),
				20   => __('%d Minutes', 20, 'mikrotik'),
				30   => __('%d Minutes', 30, 'mikrotik'),
				60   => __('%d Hour', 1, 'mikrotik'),
				720  => __('%d Hours', 12, 'mikrotik'),
				1440 => __('%d Day', 1, 'mikrotik'),
				2880 => __('%d Days', 2, 'mikrotik')
				]
			],
		'mikrotik_user_exclusion' => [
			'friendly_name' => __('Exclude Users RegEx', 'mikrotik'),
			'description'   => __('User names that match this regex will not be graphed automatically', 'mikrotik'),
			'method'        => 'textbox',
			'default'       => '(^T-$)',
			'size'          => '40',
			'max_length'    => '40',
			],
		'mikrotik_user_exclusion_ttl' => [
			'friendly_name' => __('Exclude Users Time to Live', 'mikrotik'),
			'description'   => __('How long should an excluded user\'s data be preserved after they have disconnected.', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '3600',
			'array'         => [
				'1800'  => __('%d Minutes', 30, 'mikrotik'),
				'3600'  => __('%d Hour', 1, 'mikrotik'),
				'7200'  => __('%d Hours', 2, 'mikrotik'),
				'14400' => __('%d Hours', 4, 'mikrotik'),
				'86400' => __('%d Day', 1, 'mikrotik')
				],
			],
		'mikrotik_frequencies' => [
			'friendly_name' => __('MikroTik Device Collection Frequencies', 'mikrotik'),
			'method'        => 'spacer',
			],
		'mikrotik_storage_freq' => [
			'friendly_name' => __('Storage Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan Storage Statistics?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_processor_freq' => [
			'friendly_name' => __('Processor Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan Device Processor Statistics?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_interfaces_freq' => [
			'friendly_name' => __('Interfaces Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan the Interfaces?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_dns_dhcp_list_freq' => [
			'friendly_name' => __('DNS/DHCP/List Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan the DNS/DHCP/Address Lists?', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_queue_tree' => [
			'friendly_name' => __('MikroTik Queue/Tree Collection Frequencies', 'mikrotik'),
			'method'        => 'spacer',
			],
		'mikrotik_queues_freq' => [
			'friendly_name' => __('Simple Queue/PPPoe Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan Simple Queue Statistics?  Select <b>Disabled</b> to remove this feature.', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_trees_freq' => [
			'friendly_name' => __('Queue Trees Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan the Queue Trees?  Select <b>Disabled</b> to remove this feature.', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_wireless' => [
			'friendly_name' => __('MikroTik Wireless Collection Frequencies', 'mikrotik'),
			'method'        => 'spacer',
			],
		'mikrotik_users_freq' => [
			'friendly_name' => __('Wireless HotSpot Users Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan Wireless User Statistics?  Select <b>Disabled</b> to remove this feature.', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_wireless_aps_freq' => [
			'friendly_name' => __('Wireless Access Point Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan the Wireless Access Points?  Select <b>Disabled</b> to remove this feature.', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_wireless_reg_freq' => [
			'friendly_name' => __('Wireless Registrations Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan the Wireless Registrations?  Select <b>Disabled</b> to remove this feature.', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			],
		'mikrotik_wireless_sta_freq' => [
			'friendly_name' => __('Wireless Stations Frequency', 'mikrotik'),
			'description'   => __('How often do you want to scan the Wireless Stations?  Select <b>Disabled</b> to remove this feature.', 'mikrotik'),
			'method'        => 'drop_array',
			'default'       => '300',
			'array'         => $mikrotik_frequencies
			]
		];
}

/**
 * Config_arrays hook: registers the MikroTik Users management menu
 * entry and user-realm role augmentations, initializes the collection
 * frequency/retention option lists, initializes every SNMP OID tree
 * map used by the poller collectors (system, storage, users, trees,
 * queues, wireless APs/registrations, interfaces, processor) and the
 * graph-template/data-query hash lists used to identify this plugin's
 * standard templates, surfaces any pending session message, and
 * triggers a schema/hooks upgrade check. Called by Cacti's plugin
 * framework via the 'config_arrays' hook on every page load.
 *
 * @return void
 *
 * @global array $menu                          Cacti's admin menu
 *                                              registry; appended with
 *                                              this plugin's Users
 *                                              entry.
 * @global array $messages                      Cacti's session-message
 *                                              display registry.
 * @global array $mikrotik_frequencies          Populated here with the
 *                                              map of frequency
 *                                              (seconds) => display
 *                                              label.
 * @global array $mikrotik_retention            Populated here with the
 *                                              map of retention period
 *                                              (seconds) => display
 *                                              label.
 * @global array $mikrotikSystem                Populated here with the
 *                                              system SNMP OID tree
 *                                              map.
 * @global array $mikrotikTrees                 Populated here with the
 *                                              trees SNMP OID tree
 *                                              map.
 * @global array $mikrotikQueueSimpleEntry       Populated here with the
 *                                              simple-queue SNMP OID
 *                                              tree map.
 * @global array $mikrotikUsers                 Populated here with the
 *                                              users SNMP OID tree
 *                                              map.
 * @global array $mikrotikProcessor              Populated here with the
 *                                              processor SNMP OID tree
 *                                              map.
 * @global array $mikrotikStorage                Populated here with the
 *                                              storage SNMP OID tree
 *                                              map.
 * @global array $mikrotikInterfaces             Populated here with the
 *                                              interfaces SNMP OID
 *                                              tree map.
 * @global array $mikrotikWirelessAps            Populated here with the
 *                                              wireless AP SNMP OID
 *                                              tree map.
 * @global array $mikrotikWirelessRegistrations  Populated here with the
 *                                              wireless registration
 *                                              SNMP OID tree map.
 * @global array $host_template_hashes           Populated here with the
 *                                              list of this plugin's
 *                                              host template hashes.
 * @global array $queue_hashes                   Populated here with the
 *                                              list of queue graph
 *                                              template hashes.
 * @global array $tree_hashes                    Populated here with the
 *                                              list of tree graph
 *                                              template hashes.
 * @global array $user_hashes                    Populated here with the
 *                                              list of user graph
 *                                              template hashes.
 * @global array $wireless_station_hashes        Reserved/declared for
 *                                              parity with other
 *                                              functions in this file;
 *                                              not populated directly
 *                                              here.
 * @global array $wirless_reg_hashes             Reserved/declared for
 *                                              parity with other
 *                                              functions in this file;
 *                                              not populated directly
 *                                              here (see local
 *                                              $wireless_reg_hashes).
 * @global array $interface_hashes               Populated here with the
 *                                              list of interface graph
 *                                              template hashes.
 * @global array $device_hashes                  Populated here with the
 *                                              list of standard
 *                                              device-level graph
 *                                              template hashes.
 * @global array $device_health_hashes           Populated here with the
 *                                              map of health column
 *                                              name => graph template
 *                                              hash.
 * @global array $graph_template_hashes          Populated here with the
 *                                              full combined list of
 *                                              this plugin's graph
 *                                              template hashes.
 * @global array $device_query_hashes            Populated here with the
 *                                              list of standard data
 *                                              query hashes.
 */
function mikrotik_config_arrays(): void {
	global $menu, $messages, $mikrotik_frequencies, $mikrotik_retention;
	global $mikrotikSystem, $mikrotikTrees, $mikrotikQueueSimpleEntry, $mikrotikUsers;
	global $mikrotikProcessor, $mikrotikStorage, $mikrotikInterfaces, $mikrotikWirelessAps;
	global $mikrotikWirelessRegistrations;
	global $host_template_hashes, $queue_hashes, $tree_hashes, $user_hashes;
	global $wireless_station_hashes, $wirless_reg_hashes, $interface_hashes;
	global $device_hashes, $device_health_hashes, $graph_template_hashes, $device_query_hashes;

	$menu[__('Management')]['plugins/mikrotik/mikrotik_users.php'] = __('MikroTik Users', 'mikrotik');

	if (function_exists('auth_augment_roles')) {
		auth_augment_roles(__('Normal User'), ['mikrotik.php']);
		auth_augment_roles(__('General Administration'), ['mikrotik_users.php']);
	}

	$queue_hashes = [
		'2873cd299a639cbdc19320c7c59b76e0',
		'f84afb6764a444799a4fdc6172127703',
		'8fa87e4b89385be56ebc567acefe9895',
		'3c64e6c838a93df2d1f077674e373546',
		'b16ae9022425599a6dc8235258f77246'
	];

	$tree_hashes = [
		'9cc1e791b12935d5d374cccece6e6e0a',
		'c6e96bdc60197dde8ba470305daf05f5'
	];

	$user_hashes = [
		'a52861518dd67783a211ae0938cb8ec5',
		'9a7dfd85d24b8320243521300cbfd1d2',
		'75a943d675f2d3e72353df4aa822c535',
		'0e5cd325b4956aaf11fc8e7d813a5e02',
		'a3b1e1488352975428edfb9dcbb29208'
	];

	$wireless_reg_hashes = [
		'de393e2fe3c31572c0282607ce785335',
		'2c845abc422a651bb298211f6af3d332',
		'ac69d7ecba65c00e19d6db4ebc5132fd',
		'b6089dfa8d9b3638d8ff650e97376c90',
		'8b0b279ce963a63addfc211cb5fea19c'
	];

	$interface_hashes = [
		'69ccb07edd51939407892ac334812c9a',
		'f4763dd2ab03be32c0afc934c077f34c',
		'42b97712b5edfa5eb54f0f40240dd3e2',
		'a384b1cc265554eb4d6ae4d0094ec4c4',
		'742515def28f84ac787195a3cc1639fa',
		'9266484d98848569fa13fb988699656b',
		'7514b3a58cf1ba6d3306e0dd3ff78928'
	];

	$device_hashes = [
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
	];

	$device_health_hashes = [
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
	];

	$device_query_hashes = [
		'11a443ebe40073aaa6972f8b357829de',
		'ce63249e6cc3d52bc69659a3f32194fe',
		'b11acd180dad8a955f88fb84237b0350',
		'dff839be04a5844e4d1033567f411c99',
		'7dd90372956af1dc8ec7b859a678f227',
		'25e2a46f8b3e160aed7e1d4ef3504cc3',
	];

	$graph_template_hashes = [
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
	];

	$host_template_hashes = [
		'd364e2b9570f166ab33c8df8bd503887'
	];

	$mikrotik_frequencies = [
		-1    => __('Disabled', 'mikrotik'),
		60    => __('%d Minute', 1, 'mikrotik'),
		300   => __('%d Minutes', 5, 'mikrotik'),
		600   => __('%d Minutes', 10, 'mikrotik'),
		1200  => __('%d Minutes', 20, 'mikrotik'),
		3600  => __('%d Hour', 1, 'mikrotik'),
		7200  => __('%d Hours', 2, 'mikrotik'),
		14400 => __('%d Hours', 4, 'mikrotik'),
		43200 => __('%d Hours', 12, 'mikrotik'),
		86400 => __('%d Day', 1, 'mikrotik')
	];

	$mikrotik_retention = [
		-1       => __('Indefinite', 'mikrotik'),
		86400    => __('%d Day',    1, 'mikrotik'),
		172800   => __('%d Days',   2, 'mikrotik'),
		259200   => __('%d Days',   3, 'mikrotik'),
		604800   => __('%d Week',   1, 'mikrotik'),
		1209600  => __('%d Weeks',  2, 'mikrotik'),
		2419200  => __('%d Weeks',  4, 'mikrotik'),
		5184000  => __('%d Months', 2, 'mikrotik'),
		15552000 => __('%d Months', 6, 'mikrotik'),
		31536000 => __('%d Year',   1, 'mikrotik')
	];

	$mikrotikSystem = [
		'baseOID'     => '.1.3.6.1.2.1.25.1.',
		'uptime'      => '.1.3.6.1.2.1.25.1.1.0',
		'date'        => '.1.3.6.1.2.1.25.1.2.0',
		'processes'   => '.1.3.6.1.2.1.1.7.0',
		'memory'      => '.1.3.6.1.2.1.25.2.2.0',
		'sysDescr'    => '.1.3.6.1.2.1.1.1.0',
		'sysObjectID' => '.1.3.6.1.2.1.1.2.0',
		'sysUptime'   => '.1.3.6.1.2.1.1.3.0',
		'sysContact'  => '.1.3.6.1.2.1.1.4.0',
		'sysName'     => '.1.3.6.1.2.1.1.5.0',
		'sysLocation' => '.1.3.6.1.2.1.1.6.0'
	];

	$mikrotikStorage = [
		'baseOID'         => '.1.3.6.1.2.1.25.2.3',
		'index'           => '.1.3.6.1.2.1.25.2.3.1.1',
		'type'            => '.1.3.6.1.2.1.25.2.3.1.2',
		'description'     => '.1.3.6.1.2.1.25.2.3.1.3',
		'allocationUnits' => '.1.3.6.1.2.1.25.2.3.1.4',
		'size'            => '.1.3.6.1.2.1.25.2.3.1.5',
		'used'            => '.1.3.6.1.2.1.25.2.3.1.6',
		'failures'        => '.1.3.6.1.2.1.25.2.3.1.7'
	];

	$mikrotikUsers = [
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
	];

	$mikrotikTrees = [
		'name'        => '.1.3.6.1.4.1.14988.1.1.2.2.1.2',
		'flow'        => '.1.3.6.1.4.1.14988.1.1.2.2.1.3',
		'parentIndex' => '.1.3.6.1.4.1.14988.1.1.2.2.1.4',
		'bytes'       => '.1.3.6.1.4.1.14988.1.1.2.2.1.5',
		'packets'     => '.1.3.6.1.4.1.14988.1.1.2.2.1.6',
		'HCBytes'     => '.1.3.6.1.4.1.14988.1.1.2.2.1.7'
	];

	$mikrotikQueueSimpleEntry = [
		'name'       => '.1.3.6.1.4.1.14988.1.1.2.1.1.2',
		'srcAddr'    => '.1.3.6.1.4.1.14988.1.1.2.1.1.3',
		'srcMask'    => '.1.3.6.1.4.1.14988.1.1.2.1.1.4',
		'dstAddr'    => '.1.3.6.1.4.1.14988.1.1.2.1.1.5',
		'dstMask'    => '.1.3.6.1.4.1.14988.1.1.2.1.1.6',
		'iFace'      => '.1.3.6.1.4.1.14988.1.1.2.1.1.7',
		'BytesIn'    => '.1.3.6.1.4.1.14988.1.1.2.1.1.8',
		'BytesOut'   => '.1.3.6.1.4.1.14988.1.1.2.1.1.9',
		'PacketsIn'  => '.1.3.6.1.4.1.14988.1.1.2.1.1.10',
		'Packetsout' => '.1.3.6.1.4.1.14988.1.1.2.1.1.11',
		'QueuesIn'   => '.1.3.6.1.4.1.14988.1.1.2.1.1.12',
		'QueuesOut'  => '.1.3.6.1.4.1.14988.1.1.2.1.1.13',
		'DroppedIn'  => '.1.3.6.1.4.1.14988.1.1.2.1.1.14',
		'DroppedOut' => '.1.3.6.1.4.1.14988.1.1.2.1.1.15',
	];

	$mikrotikWirelessAps = [
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
	];

	$mikrotikWirelessRegistrations = [
		'index'           => '.1.3.6.1.4.1.14988.1.1.1.2.1.1',
		'Strength'        => '.1.3.6.1.4.1.14988.1.1.1.2.1.3',
		'TxBytes'         => '.1.3.6.1.4.1.14988.1.1.1.2.1.4',
		'RxBytes'         => '.1.3.6.1.4.1.14988.1.1.1.2.1.5',
		'TxPackets'       => '.1.3.6.1.4.1.14988.1.1.1.2.1.6',
		'RxPackets'       => '.1.3.6.1.4.1.14988.1.1.1.2.1.7',
		'TxRate'          => '.1.3.6.1.4.1.14988.1.1.1.2.1.8',
		'RxRate'          => '.1.3.6.1.4.1.14988.1.1.1.2.1.9',
		'RouterOSVersion' => '.1.3.6.1.4.1.14988.1.1.1.2.1.10',
		'Uptime'          => '.1.3.6.1.4.1.14988.1.1.1.2.1.11',
		'SignalToNoise'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.12',
		'TxStrengthCh0'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.13',
		'RxStrengthCh0'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.14',
		'TxStrengthCh1'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.15',
		'RxStrengthChl'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.16',
		'TxStrengthCh2'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.17',
		'RxStrengthCh2'   => '.1.3.6.1.4.1.14988.1.1.1.2.1.18',
		'TxStrength'      => '.1.3.6.1.4.1.14988.1.1.1.2.1.19',
	];

	$mikrotikInterfaces = [
		'name'             => '.1.3.6.1.4.1.14988.1.1.14.1.1.2',
		'RxBytes'          => '.1.3.6.1.4.1.14988.1.1.14.1.1.31',
		'RxPackets'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.12',
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
		'RxUnknownOp'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.50',
		'RxLengthError'    => '.1.3.6.1.4.1.14988.1.1.14.1.1.51',
		'RxCodeError'      => '.1.3.6.1.4.1.14988.1.1.14.1.1.52',
		'RxCarrierError'   => '.1.3.6.1.4.1.14988.1.1.14.1.1.53',
		'RxJabber'         => '.1.3.6.1.4.1.14988.1.1.14.1.1.54',
		'RxDrop'           => '.1.3.6.1.4.1.14988.1.1.14.1.1.55',
		'TxBytes'          => '.1.3.6.1.4.1.14988.1.1.14.1.1.61',
		'TxPackets'        => '.1.3.6.1.4.1.14988.1.1.14.1.1.14',
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
	];

	$mikrotikProcessor = [
		'baseOID' => '.1.3.6.1.2.1.25.3.3.1',
		'load'    => '.1.3.6.1.2.1.25.3.3.1.2'
	];

	if (isset($_SESSION['mikrotik_message']) && $_SESSION['mikrotik_message'] != '') {
		$messages['mikrotik_message'] = ['message' => $_SESSION['mikrotik_message'], 'type' => 'info'];
	}

	mikrotik_check_upgrade();
}

/**
 * Draw_navigation_text hook: registers the breadcrumb/navigation title
 * entries for this plugin's mikrotik.php and mikrotik_users.php pages
 * and their sub-views. Called by Cacti's navigation framework via the
 * 'draw_navigation_text' hook.
 *
 * @param array $nav The navigation entries array being built up.
 *
 * @return array The $nav array with this plugin's entries added.
 */
function mikrotik_draw_navigation_text($nav): array {
	$nav['mikrotik.php:']              = ['title' => __('MikroTik', 'mikrotik'), 'mapping' => '', 'url' => 'mikrotik.php', 'level' => '0'];
	$nav['mikrotik.php:devices']       = ['title' => __('Devices', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik.php:trees']         = ['title' => __('Trees', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik.php:queues']        = ['title' => __('Simple Queues', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik.php:users']         = ['title' => __('Users', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik.php:interfaces']    = ['title' => __('Interfaces', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik.php:storage']       = ['title' => __('Storage', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik.php:graphs']        = ['title' => __('Graphs', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik.php:wireless_aps']  = ['title' => __('Wireless Aps', 'mikrotik'), 'mapping' => 'mikrotik.php:', 'url' => 'mikrotik.php', 'level' => '1'];
	$nav['mikrotik_users.php:']        = ['title' => __('MikroTik Users', 'mikrotik'), 'mapping' => 'index.php:', 'url' => 'mikrotik_users.php', 'level' => '1'];
	$nav['mikrotik_users.php:edit']    = ['title' => __('(edit)', 'mikrotik'), 'mapping' => 'index.php:,mikrotik_users.php:', 'url' => '', 'level' => '2'];
	$nav['mikrotik_users.php:actions'] = ['title' => __('Actions', 'mikrotik'), 'mapping' => 'index.php:,mikrotik_users.php:', 'url' => '', 'level' => '2'];

	return $nav;
}

/**
 * Top_header_tabs/top_graph_header_tabs hook: prints the MikroTik tab
 * icon/link in Cacti's page header, using the 'down' (active) icon when
 * currently viewing mikrotik.php. Called by Cacti's header rendering
 * via the 'top_header_tabs'/'top_graph_header_tabs' hooks.
 *
 * @return void
 *
 * @global array $config Cacti global configuration array; used to
 *                       build the tab's URL and image paths.
 */
function mikrotik_show_tab(): void {
	global $config;

	if (api_user_realm_auth('mikrotik.php')) {
		if (substr_count($_SERVER['REQUEST_URI'], 'mikrotik.php')) {
			print '<a href="' . $config['url_path'] . 'plugins/mikrotik/mikrotik.php"><img src="' . $config['url_path'] . 'plugins/mikrotik/images/tab_mikrotik_down.gif" alt="' . __('MikroTik', 'mikrotik') . '"></a>';
		} else {
			print '<a href="' . $config['url_path'] . 'plugins/mikrotik/mikrotik.php"><img src="' . $config['url_path'] . 'plugins/mikrotik/images/tab_mikrotik.gif" alt="' . __('MikroTik', 'mikrotik') . '"></a>';
		}
	}
}

/**
 * Looks up a graph template's id by its unique hash. Called throughout
 * the poller/UI code to resolve this plugin's known standard graph
 * templates.
 *
 * @param string $hash The graph template's hash to look up.
 *
 * @return int|null The matching graph_templates id, or null if not
 *                  found.
 */
function mikrotik_template_by_hash(string $hash) {
	return db_fetch_cell("SELECT id FROM graph_templates WHERE hash='$hash'");
}

/**
 * Looks up a data query's id by its unique hash. Called throughout the
 * poller/UI code to resolve this plugin's known standard data queries.
 *
 * @param string $hash The data query's hash to look up.
 *
 * @return int|null The matching snmp_query id, or null if not found.
 */
function mikrotik_data_query_by_hash(string $hash) {
	return db_fetch_cell("SELECT id FROM snmp_query WHERE hash='$hash'");
}

/**
 * Builds a 'view graphs' icon link to Cacti's selective graphs view for
 * all graphs using any of a set of graph template hashes, optionally
 * restricted to a specific device and/or SNMP index search string.
 * Called from mikrotik.php's list views to render each row's graph
 * link.
 *
 * @param array  $hashes  The list of graph template hashes to include.
 * @param int    $host_id Optional host id to restrict the search to a
 *                        single device.
 * @param string $search  Optional SNMP index substring to restrict the
 *                        search to.
 *
 * @return string An HTML anchor linking to the matching graphs, or a
 *                disabled-looking placeholder link if none are found.
 *
 * @global array $config Cacti global configuration array; used to
 *                       build the link URL.
 */
function mikrotik_graphs_url_by_template_hashs(array $hashes, int $host_id = 0, string $search = ''): string {
	global $config;

	$sql_where = '';

	if ($host_id != 0) {
		$sql_where .= " AND gl.host_id=$host_id";
	}

	if ($search != '') {
		$sql_where .= " AND gl.snmp_index LIKE '%$search%'";
	}

	if (cacti_sizeof($hashes)) {
		$graphs = array_rekey(db_fetch_assoc("SELECT gl.id
			FROM graph_local AS gl
			INNER JOIN graph_templates AS gt
			ON gl.graph_template_id=gt.id
			WHERE gt.hash IN ('" . implode("','", $hashes) . "') $sql_where"), 'id', 'id');

		if (cacti_sizeof($graphs)) {
			return "<a class='pic' href='" . htmlspecialchars($config['url_path'] . 'plugins/mikrotik/mikrotik.php?action=graphs&reset=1&style=selective&graph_list=' . implode(',', $graphs)) . "'><i class='fas fa-chart-line' style='color:orange;' title='" . __esc('View Graphs', 'mikrotik') . "'></i></a>";
		} else {
			return "<a class='pic' href='#'><i class='fas fa-chart-line' title='" . __esc('Graphs Skipped by Rule, or Not Created', 'mikrotik') . "'></i></a>";
		}
	} else {
		return "<a class='pic' href='#'><i class='fas fa-chart-line' title='" . __esc('Graphs Skipped by Rule, or Not Created', 'mikrotik') . "'></i></a>";
	}
}

/**
 * Host_edit_top hook: when editing a device using this plugin's
 * MikroTik host template, adds credential fields (read-only
 * username/password) to the host edit form and, if credentials are
 * already set, attempts a live API connection to report success/
 * failure. Called by Cacti's host edit page via the 'host_edit_top'
 * hook.
 *
 * @return void
 *
 * @global array $fields_host_edit The host edit form's field
 *                                definitions array; appended with this
 *                                plugin's credential fields when
 *                                applicable.
 */
function mikrotik_host_top(): void {
	global $fields_host_edit;

	$id = get_filter_request_var('id');

	$template_id = db_fetch_cell('SELECT id FROM host_template WHERE hash="d364e2b9570f166ab33c8df8bd503887"');

	$is_tik = db_fetch_row_prepared('SELECT pmc.*, host.hostname
		FROM host
		LEFT JOIN plugin_mikrotik_credentials AS pmc
		ON host.id=pmc.host_id
		WHERE host_template_id = ? AND host.id = ?', [$template_id, $id]);

	if (is_array($is_tik) && cacti_sizeof($is_tik)) {
		$fields_host_edit += [
			'mikrotik_head' => [
				'method'        => 'spacer',
				'collapsible'   => 'true',
				'friendly_name' => __('MikroTik Credentials', 'mikrotik')
			],
			'mikrotik_user' => [
				'method'        => 'textbox',
				'friendly_name' => __('Read Only User', 'mikrotik'),
				'description'   => __('Provide a read only username for the MikroTik.', 'mikrotik'),
				'value'         => $is_tik['user'],
				'max_length'    => '40',
				'size'          => '20'
			],
			'mikrotik_password' => [
				'method'        => 'textbox',
				'friendly_name' => __('Password', 'mikrotik'),
				'description'   => __('Provide the read only username password for this MikroTik.', 'mikrotik'),
				'value'         => $is_tik['password'],
				'max_length'    => '40',
				'size'          => '30',
			]
		];

		if ($is_tik['user'] != '') {
			include_once('./plugins/mikrotik/RouterOS/routeros_api.class.php');

			$api = new RouterosAPI();

			$api->debug = false;

			if ($api->connect($is_tik['hostname'], $is_tik['user'], $is_tik['password'])) {
				$api->disconnect();

				$fields_host_edit += [
					'mikrotik_result' => [
						'method'        => 'other',
						'friendly_name' => __('Connection Result', 'mikrotik'),
						'description'   => __('Ok if Cacti can connect to the MikroTik over its API port.', 'mikrotik'),
						'value'         => 'Connected Successfully'
					]
				];
			} else {
				$fields_host_edit += [
					'mikrotik_result' => [
						'method'        => 'other',
						'friendly_name' => __('Connection Result', 'mikrotik'),
						'description'   => __('Ok if Cacti can connect to the MikroTik over its API port.', 'mikrotik'),
						'value'         => __('Connection Failed', 'mikrotik')
					]
				];
			}
		}
	}
}

/**
 * Host_save hook: persists a submitted MikroTik read-only
 * username/password into plugin_mikrotik_credentials for the saved
 * host. Called by Cacti's host edit page via the 'host_save' hook.
 *
 * @param array $data The host save-hook data, including 'host_id'.
 *
 * @return array The unmodified $data array.
 */
function mikrotik_host_save(array $data): array {
	$id = $data['host_id'];

	if (isset_request_var('mikrotik_user')) {
		db_execute_prepared('REPLACE INTO plugin_mikrotik_credentials (host_id, user, password) VALUES (?,?,?)', [$id, get_nfilter_request_var('mikrotik_user'), get_nfilter_request_var('mikrotik_password')]);
	}

	return $data;
}

/**
 * Host_delete hook: removes this plugin's stored credentials for one
 * or more deleted hosts. Called by Cacti's host admin via the
 * 'host_delete' hook.
 *
 * @param array $data The list of deleted host ids.
 *
 * @return array The unmodified $data array.
 */
function mikrotik_host_delete(array $data): array {
	db_execute('DELETE FROM plugin_mikrotik_credentials WHERE host_id IN(' . implode(',', $data) . ')');

	return $data;
}

/**
 * Removes files and directories that a previous version of this plugin
 * shipped but that have since moved or been deleted, using the tombstone
 * and whitelist lists in manifest.json. Whitelisted (user-data) paths and
 * any VCS metadata (.git*) are never touched; the dev-only tests/ tree is
 * removed. Any path that resolves outside the plugin directory (a tampered
 * manifest.json) is refused, and any file/directory that cannot be removed
 * (e.g. read-only) is reported to the Cacti log. Any top-level entry that is
 * neither expected nor a tombstone nor whitelisted is logged to the Cacti
 * log and left in place. Called on a plugin version change.
 *
 * @return void
 *
 * @global array $config Cacti global configuration array; used to resolve
 *                       the plugin directory.
 */
function plugin_mikrotik_prune_files(): void {
	global $config;

	$plugin_dir    = $config['base_path'] . '/plugins/mikrotik';
	$manifest_path = $plugin_dir . '/manifest.json';

	if (!is_readable($manifest_path)) {
		return;
	}

	$manifest = json_decode((string) file_get_contents($manifest_path), true);

	if (!is_array($manifest)) {
		cacti_log('WARNING: mikrotik manifest.json could not be parsed; skipping file prune', false, 'MIKROTIK');

		return;
	}

	$tombstones = isset($manifest['tombstones']) && is_array($manifest['tombstones']) ? $manifest['tombstones'] : [];
	$expected   = isset($manifest['expected'])   && is_array($manifest['expected'])   ? $manifest['expected']   : [];
	$whitelist  = isset($manifest['whitelist'])  && is_array($manifest['whitelist'])  ? $manifest['whitelist']  : [];

	$protected = function (string $rel) use ($whitelist): bool {
		if (strncmp($rel, '.git', 4) === 0 || strncmp($rel, '.md', 3) === 0) {
			return true;
		}

		foreach ($whitelist as $entry) {
			$entry = trim((string) $entry, '/');

			if ($entry !== '' && ($rel === $entry || strncmp($rel, $entry . '/', strlen($entry) + 1) === 0)) {
				return true;
			}
		}

		return false;
	};

	// Security: resolve the plugin directory so a tampered manifest.json
	// cannot steer the prune outside of it.
	$plugin_real = realpath($plugin_dir);

	// Remove tombstoned (moved/deleted) paths plus the dev-only tests/
	// tree and the phpunit.xml test configuration.
	$remove   = $tombstones;
	$remove[] = 'tests/';
	$remove[] = 'phpunit.xml';

	foreach ($remove as $rel) {
		$rel = trim((string) $rel, '/');

		if ($rel === '' || $protected($rel)) {
			continue;
		}

		$path = $plugin_dir . '/' . $rel;

		if (!is_link($path) && !file_exists($path)) {
			continue;
		}

		// Refuse any path that, after resolving symlinks and ../ segments,
		// escapes the plugin directory (protects user data from a tampered
		// manifest.json).
		$anchor = is_link($path) ? dirname($path) : $path;
		$real   = realpath($anchor);

		if ($real === false || ($real !== $plugin_real && strncmp($real, $plugin_real . DIRECTORY_SEPARATOR, strlen((string) $plugin_real) + 1) !== 0)) {
			cacti_log(sprintf('WARNING: mikrotik prune refused to remove %s: path resolves outside the plugin directory (tampered manifest.json?)', $rel), false, 'MIKROTIK');

			continue;
		}

		if (is_dir($path) && !is_link($path)) {
			$removed = plugin_mikrotik_rmtree($path);
		} else {
			$removed = @unlink($path);
		}

		if (!$removed) {
			cacti_log(sprintf('WARNING: mikrotik upgrade could not remove %s (check file/directory permissions)', $rel), false, 'MIKROTIK');
		}
	}

	// Surface any top-level entry the manifest does not account for.
	$known = [];

	foreach (array_merge($expected, $tombstones) as $entry) {
		$top = explode('/', trim((string) $entry, '/'))[0];

		if ($top !== '') {
			$known[$top] = true;
		}
	}

	$entries = scandir($plugin_dir);

	foreach (($entries !== false ? $entries : []) as $entry) {
		if ($entry === '.' || $entry === '..' || $entry === 'tests' || $entry === 'phpunit.xml' || $protected($entry) || isset($known[$entry])) {
			continue;
		}

		cacti_log(sprintf('WARNING: mikrotik upgrade found a file/directory not described in manifest.json: %s (left in place)', $entry), false, 'MIKROTIK');
	}
}

/**
 * Recursively deletes a directory and its contents. Symlinks are removed
 * without being followed. Helper for plugin_mikrotik_prune_files().
 *
 * @param string $dir Absolute path to the directory to remove.
 *
 * @return bool True if the directory and everything under it was removed;
 *              false if any entry could not be deleted.
 */
function plugin_mikrotik_rmtree(string $dir): bool {
	$entries = scandir($dir);
	$ok      = true;

	foreach (($entries !== false ? $entries : []) as $entry) {
		if ($entry === '.' || $entry === '..') {
			continue;
		}

		$path = $dir . '/' . $entry;

		if (is_dir($path) && !is_link($path)) {
			if (!plugin_mikrotik_rmtree($path)) {
				$ok = false;
			}
		} elseif (!@unlink($path)) {
			$ok = false;
		}
	}

	if (!@rmdir($dir)) {
		$ok = false;
	}

	return $ok;
}
