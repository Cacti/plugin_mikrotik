#!/usr/bin/php -q
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2019 The Cacti Group                                 |
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

$no_http_headers = true;

chdir(dirname(__FILE__));
chdir('../..');
include('./include/global.php');
include_once('./lib/poller.php');
include_once('./lib/utility.php');
include_once('./lib/data_query.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_tree.php');
include_once('./lib/api_data_source.php');
include_once('./lib/api_aggregate.php');

ini_set('memory_limit', '256M');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug, $start, $seed, $forcerun;

$debug    = false;
$forcerun = false;
$start    = time();

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}
	switch ($arg) {
	case '--debug':
	case '-d':
		$debug = true;
		break;
	case '--force':
	case '-f':
		$forcerun = true;
		break;
	case '--version':
	case '-V':
	case '-v':
		display_version();
		exit;
	case '--help':
	case '-H':
	case '-h':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

/* Do not process if not enabled */
if (read_config_option('mikrotik_enabled') == '' || db_fetch_cell("SELECT status FROM plugin_config WHERE directory='mikrotik'") != 1) {
	print "WARNING: The MiktroTik Collection is Down!  Exiting\n";
	exit(0);
}

/* see if its time to run */
$last_run  = read_config_option('mikrotik_automation_lastrun');
$frequency = read_config_option('mikrotik_automation_frequency') * 60;
debug("Last Run Was '" . date('Y-m-d H:i:s', $last_run) . "', Frequency is '" . ($frequency/60) . "' Minutes");

if ($frequency == 0) {
	print "NOTE:  Graph Automation is Disabled\n";
} elseif (($frequency > 0 && ($start - $last_run) > $frequency) || ($frequency > 0 && $forcerun)) {
	list($micro,$seconds) = explode(' ', microtime());
	$start = $seconds + $micro;

	print "NOTE:  Starting Automation Process\n";
	db_execute("REPLACE INTO settings (name,value) VALUES ('mikrotik_automation_lastrun', '$start')");

	debug('Removing invalid stations graphs');
	remove_invalid_station_graphs();

	debug('Adding Graphs');
	add_graphs();

	list($micro,$seconds) = explode(' ', microtime());
	$end = $seconds + $micro;

	$cacti_stats = sprintf('Time:%01.4f ', round($end-$start,2));

	/* log to the database */
	db_execute("REPLACE INTO settings (name,value) VALUES ('stats_mikrotik_graphs', '" . $cacti_stats . "')");

	/* log to the logfile */
	cacti_log('MIKROTIK GRAPH STATS: ' . $cacti_stats , true, 'SYSTEM');
} else {
	print "NOTE:  Its Not Time to Run Automation\n";
}

exit(0);

function add_graphs() {
	global $config;

//	/* check for summary changes first */
//	$host_template = read_config_option('mikrotik_summary_host_template');
//	$host_type_dq  = read_config_option('mikrotik_dq_host_type');
//	if (!empty($host_template)) {
//		/* check to see if the template exists */
//		debug('Host Template Set');
//
//		if (db_fetch_cell("SELECT count(*) FROM host_template WHERE id=$host_template")) {
//			debug('Host Template Exists');
//
//			$host_id = db_fetch_cell("SELECT id FROM host WHERE host_template_id=$host_template");
//			if (empty($host_id)) {
//				debug('MikroTik Summary Device Not Found, Adding');
//			} else {
//				debug("Host Exists Hostname is '" . db_fetch_cell("SELECT description FROM host WHERE id=$host_id"). "'");
//			}
//
//
//			add_summary_graphs($host_id, $host_template);
//		} else {
//			cacti_log('WARNING: Unable to find MikroTik Summary Host Template', true, 'MIKROTIK');
//		}
//	} else {
//		cacti_log('NOTE: MikroTik Summary Host Template Not Specified', true, 'MIKROTIK');
//	}

	add_host_based_graphs();
}

function add_host_based_graphs() {
	global $config, $device_hashes, $device_query_hashes, $device_health_hashes;

	debug('Adding Host Based Graphs');

	/* check for host level graphs next data queries */
	$host_cpu_dq   = read_config_option('mikrotik_dq_host_cpu');
	$host_users_dq = mikrotik_data_query_by_hash('ce63249e6cc3d52bc69659a3f32194fe');

	$hosts = db_fetch_assoc("SELECT host_id, host.description, host.hostname
		FROM plugin_mikrotik_system
		INNER JOIN host
		ON host.id=plugin_mikrotik_system.host_id
		WHERE host_status IN(0,3) AND host.disabled=''");

	if (cacti_sizeof($hosts)) {
		foreach($hosts as $h) {
			debug('Processing Host: ' . $h['description'] . ' [' . $h['hostname'] . ']');

			foreach($device_hashes as $hash) {
				$template = mikrotik_template_by_hash($hash);
				if (!empty($template)) {
					debug('Processing ' . db_fetch_cell_prepared('SELECT name FROM graph_templates WHERE hash = ?', array($hash)));
					mikrotik_gt_graph($h['host_id'], $template);
				}
			}

			foreach($device_query_hashes as $hash) {
				$query = mikrotik_data_query_by_hash($hash);
				if (!empty($query)) {
					debug('Processing ' . db_fetch_cell_prepared('SELECT name FROM snmp_query WHERE hash = ?', array($hash)));
					if ($hash == '7dd90372956af1dc8ec7b859a678f227') {
						$exclusion = read_config_option('mikrotik_user_exclusion');
						add_host_dq_graphs($h['host_id'], $query, 'userName', $exclusion, false);
					} else {
						add_host_dq_graphs($h['host_id'], $query);
					}
				}
			}

			$health = db_fetch_row_prepared('SELECT * FROM plugin_mikrotik_system_health WHERE host_id = ?', array($h['host_id']));
			debug('Processing Health');
			if (cacti_sizeof($health)) {
				foreach($device_health_hashes as $column => $hash) {
					if (!empty($health[$column]) && $health[$column] != 'NULL') {
						$template = mikrotik_template_by_hash($hash);
						if (!empty($template)) {
							debug('Processing ' . db_fetch_cell_prepared('SELECT name FROM graph_templates WHERE hash = ?', array($hash)));
							mikrotik_gt_graph($h['host_id'], $template);
						}
					}
				}
			}
		}
	} else {
		debug('No Hosts Found');
	}
}

function add_host_dq_graphs($host_id, $dq, $field = '', $regex = '', $include = true) {
	global $config;

	/* add entry if it does not exist */
	$exists = db_fetch_cell("SELECT count(*) FROM host_snmp_query WHERE host_id=$host_id AND snmp_query_id=$dq");
	if (!$exists) {
		db_execute("REPLACE INTO host_snmp_query (host_id,snmp_query_id,reindex_method) VALUES ($host_id, $dq, 1)");
	}

	/* recache snmp data */
	debug('Reindexing Host');
	run_data_query($host_id, $dq);

	$graph_templates = db_fetch_assoc('SELECT *
		FROM snmp_query_graph
		WHERE snmp_query_id=' . $dq);

	debug('Adding Graphs');
	if (cacti_sizeof($graph_templates)) {
	foreach($graph_templates as $gt) {
		mikrotik_dq_graphs($host_id, $dq, $gt['graph_template_id'], $gt['id'], $field, $regex, $include);
	}
	}
}

function mikrotik_gt_graph($host_id, $graph_template_id) {
	global $config;

	$php_bin = read_config_option('path_php_binary');
	$base    = $config['base_path'];
	$name    = db_fetch_cell("SELECT name FROM graph_templates WHERE id=$graph_template_id");
	$assoc   = db_fetch_cell("SELECT count(*)
		FROM host_graph
		WHERE graph_template_id=$graph_template_id
		AND host_id=$host_id");

	if (!$assoc) {
		db_execute("INSERT INTO host_graph (host_id, graph_template_id) VALUES ($host_id, $graph_template_id)");
	}

	$exists = db_fetch_cell("SELECT count(*)
		FROM graph_local
		WHERE host_id=$host_id
		AND graph_template_id=$graph_template_id");

	if (!$exists) {
		print "NOTE: Adding Graph: '$name' for Host: " . $host_id . "\n";

		$command = "$php_bin -q $base/cli/add_graphs.php" .
			" --graph-template-id=$graph_template_id" .
			" --graph-type=cg" .
			" --host-id=" . $host_id;

		print str_replace("\n", " ", passthru($command)) . "\n";
	}
}

function add_summary_graphs($host_id, $host_template) {
	global $config;

	$php_bin = read_config_option('path_php_binary');
	$base    = $config['base_path'];

	$return_code = 0;
	if (empty($host_id)) {
		/* add the host */
		debug('Adding Host');
		$result = exec("$php_bin -q $base/cli/add_device.php --description='Summary Device' --ip=summary --template=$host_template --version=0 --avail=none", $return_code);
	} else {
		debug('Reindexing Host');
		$result = exec("$php_bin -q $base/cli/poller_reindex_hosts.php -id=$host_id -qid=All", $return_code);
	}

	/* data query graphs first */
	debug('Processing Data Queries');
	$data_queries = db_fetch_assoc("SELECT *
		FROM host_snmp_query
		WHERE host_id=$host_id");

	if (cacti_sizeof($data_queries)) {
	foreach($data_queries as $dq) {
		$graph_templates = db_fetch_assoc("SELECT *
			FROM snmp_query_graph
			WHERE snmp_query_id=" . $dq['snmp_query_id']);

		if (cacti_sizeof($graph_templates)) {
		foreach($graph_templates as $gt) {
			mikrotik_dq_graphs($host_id, $dq['snmp_query_id'], $gt['graph_template_id'], $gt['id']);
		}
		}
	}
	}

	debug('Processing Graph Templates');
	$graph_templates = db_fetch_assoc("SELECT *
		FROM host_graph
		WHERE host_id=$host_id");

	if (cacti_sizeof($graph_templates)) {
	foreach($graph_templates as $gt) {
		/* see if the graph exists already */
		$exists = db_fetch_cell("SELECT count(*)
			FROM graph_local
			WHERE host_id=$host_id
			AND graph_template_id=" . $gt["graph_template_id"]);

		if (!$exists) {
			print "NOTE: Adding item: '$field_value' for Host: " . $host_id;

			$command = "$php_bin -q $base/cli/add_graphs.php" .
				" --graph-template-id=" . $gt["graph_template_id"] .
				" --graph-type=cg" .
				" --host-id=" . $host_id;

			print str_replace("\n", " ", passthru($command)) . "\n";
		}
	}
	}
}

function mikrotik_dq_graphs($host_id, $query_id, $graph_template_id, $query_type_id, $field = '', $regex = '', $include = true) {
	global $config, $php_bin, $path_grid;

	$php_bin = read_config_option('path_php_binary');
	$base    = $config['base_path'];

	if ($field == '') {
		$field = db_fetch_cell("SELECT sort_field
			FROM host_snmp_query
			WHERE host_id=$host_id AND snmp_query_id=" . $query_id);
	}

	$items = db_fetch_assoc("SELECT *
		FROM host_snmp_cache
		WHERE field_name='$field'
		AND host_id=$host_id
		AND snmp_query_id=$query_id");

	if (cacti_sizeof($items)) {
		foreach($items as $item) {
			$field_value = $item['field_value'];
			$index       = $item['snmp_index'];

			if ($regex == '') {
				/* add graph below */
			} else if ($include == false && preg_match("/$regex/", $field_value)) {
				print "NOTE: Bypassing item due to Regex rule: '$regex', Field Value: '" . $field_value . "' for Host: '" . $host_id . "'\n";
				continue;
			} else if ($include == true && preg_match("/$regex/", $field_value)) {
				/* add graph below, we should never be here */
			} else {
				print "NOTE: Not Bypassing item due to Regex rule: '$regex', Field Value: '" . $field_value . "' for Host: '" . $host_id . "'\n";
			}

			/* check to see if the graph exists or not */
			$exists = db_fetch_cell("SELECT id
				FROM graph_local
				WHERE host_id=$host_id
				AND snmp_query_id=$query_id
				AND graph_template_id=$graph_template_id
				AND snmp_index='$index'");

			if (!$exists) {
				$command = "$php_bin -q $base/cli/add_graphs.php" .
					" --graph-template-id=$graph_template_id --graph-type=ds"     .
					" --snmp-query-type-id=$query_type_id --host-id=" . $host_id .
					" --snmp-query-id=$query_id --snmp-field=$field" .
					" --snmp-value=" . cacti_escapeshellarg($field_value);

				print "NOTE: Adding item: '$field_value' " . str_replace("\n", " ", passthru($command)) . "\n";
			}
		}
	}
}

function remove_invalid_station_graphs() {
	$old_wireless_station_hashes = array(
		'0e88ad681dda36417a537c2e06a2add3',
		'8cea2d49a035d5424ff28b9856d78053',
		'0a0e496b94667220dce953cb374cee7c',
		'98ee665dc39e0404a272c87cc4efea2e'
	);

	// Remove incorrect graphs
	foreach($old_wireless_station_hashes as $hash) {
		$graph_template_id = db_fetch_cell_prepared('SELECT id
			FROM graph_templates
			WHERE hash = ?',
			array($hash));

		$snmp_query_ids[] = db_fetch_cell_prepared('SELECT sqg.snmp_query_id
			FROM snmp_query_graph AS sqg
			INNER JOIN graph_templates AS gt
			ON sqg.graph_template_id=gt.id
			WHERE gt.hash = ?', array($hash));

		if ($graph_template_id > 0) {
			mikrotik_delete_graphs_and_data_sources_from_hash($graph_template_id);

			// Remove graph templates
			db_execute_prepared('DELETE FROM graph_templates
				WHERE id = ?',
				array($graph_template_id));

			$graph_template_input = db_fetch_assoc('SELECT id
				FROM graph_template_input
				WHERE graph_template_id = ?',
				array($graph_template_id));

			if (cacti_sizeof($graph_template_input)) {
				foreach ($graph_template_input as $item) {
					db_execute_prepared('DELETE FROM graph_template_input_defs
						WHERE graph_template_input_id = ?', array($item['id']));
				}
			}

			db_execute_prepared('DELETE FROM graph_template_input
				WHERE graph_template_id = ?',
				array($graph_template_id));

			db_execute_prepared('DELETE FROM graph_templates_graph
				WHERE graph_template_id = ?',
				array($graph_template_id));

			db_execute_prepared('DELETE FROM graph_templates_item
				WHERE graph_template_id = ?',
				array($graph_template_id));

			db_execute_prepared('DELETE FROM host_template_graph
				WHERE graph_template_id = ?',
				array($graph_template_id));
		}
	}

	if (!empty($snmp_query_ids)) {
		foreach($snmp_query_ids as $snmp_query_id) {
			db_execute_prepared('DELETE FROM host_template_snmp_query WHERE snmp_query_id = ?', array($snmp_query_id));
			db_execute_prepared('DELETE FROM host_snmp_query WHERE snmp_query_id = ?', array($snmp_query_id));
			db_execute_prepared('DELETE FROM snmp_query_graph WHERE snmp_query_id = ?', array($snmp_query_id));
		}
	}

	$old_data_template_hashes = array(
		'2e88a62f3d3bd3756ab48a9613e86439',
		'852ab786ca385b1bd87d1308d7e3ae75',
		'2828f43f6d8e477ee5616da510ccc314',
		'ca928def30203cc6d7daed75d826f91c'
	);

	foreach($old_data_template_hashes as $hash) {
		$data_template_id = db_fetch_cell_prepared('SELECT id
			FROM data_template
			WHERE hash = ?',
			array($hash));

		if (!empty($data_template_id)) {
			db_execute_prepared('DELETE FROM data_template_data
				WHERE data_template_id = ?', array($data_template_id));

			db_execute_prepared('DELETE FROM data_template_rrd
				WHERE data_template_id = ?', array($data_template_id));

			db_execute_prepared('DELETE FROM snmp_query_graph_rrd
				WHERE data_template_id = ?', array($data_template_id));

			db_execute_prepared('DELETE FROM snmp_query_graph_rrd_sv
				WHERE data_template_id = ?', array($data_template_id));

			db_execute_prepared('DELETE FROM data_template
				WHERE id = ?' , array($data_template_id));

			db_execute_prepared('DELETE FROM data_local
				WHERE data_template_id = ?' , array($data_template_id));
		}
	}
}

function debug($message) {
	global $debug;

	if ($debug) {
		print 'DEBUG: ' . trim($message) . "\n";
	}
}

function display_version() {
	global $config;

	if (!function_exists('plugin_mikrotik_version')) {
		include_once($config['base_path'] . '/plugins/mikrotik/setup.php');
	}

	$info = plugin_mikrotik_version();
	print "MikroTik Graph Automator, Version " . $info['version'] . ", " . COPYRIGHT_YEARS . "\n";
}

function display_help() {
	display_version();

	print "\nThe MikroTik process that creates graphs for Cacti.\n\n";
	print "usage: poller_graphs.php [-f] [-d]\n";
}
