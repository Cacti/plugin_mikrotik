<?php

$no_http_headers = true;

/* display no errors */
error_reporting(0);

include_once(dirname(__FILE__) . '/../../../../lib/snmp.php');

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../../../include/global.php');
	include_once(dirname(__FILE__) . '/../../../../lib/snmp.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_count_oids', $_SERVER['argv']);
}

function ss_count_oids($host_id = '', $oid = '') {
	global $config;

	if ($host_id > 0) {
		$host = db_fetch_row_prepared('SELECT hostname, snmp_community, snmp_version, snmp_username, snmp_password,
			snmp_auth_protocol, snmp_priv_passphrase, snmp_priv_protocol, snmp_context,
			snmp_port, snmp_timeout, max_oids, snmp_engine_id
			FROM host
			WHERE id = ?',
			array($host_id));

		if (sizeof($host)) {
			$walk = cacti_snmp_walk($host['hostname'], $host['snmp_community'], $oid, $host['snmp_version'],
				$host['snmp_username'], $host['snmp_password'],
				$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
				$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout'],
				read_config_option('snmp_retries'), $host['max_oids'], SNMP_WEBUI, $host['snmp_engine_id']);

			if (sizeof($walk)) {
				return sizeof($walk);
			}
		}
	}

	return '0';
}

