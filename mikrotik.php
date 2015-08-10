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

chdir('../../');
include('./include/auth.php');

define('MAX_DISPLAY_PAGES', 21);

if (!isset($_REQUEST['action'])) {
	$_REQUEST['action'] = 'devices';
}

general_header();

$mikrotik_hrDeviceStatus = array(
	0 => 'Present',
	1 => 'Unknown',
	2 => 'Running',
	3 => 'Warning',
	4 => 'Testing',
	5 => 'Down'
);

$graph_template_hashes = array(
	'47ced1c199d83e8dd79c6ba594c4e3be',
	'8856e3943ecc70e5da835072f584d5a0',
	'f58edbcb3b6e682bc2332942c37b2652',
	'32bd34d525944127063c2d94e2e8f1de',
	'a8553b7cfd6c3298fb8e6e9f9a02d3bf',
	'de393e2fe3c31572c0282607ce785335',
	'2c845abc422a651bb298211f6af3d332',
	'b6089dfa8d9b3638d8ff650e97376c90',
	'ac69d7ecba65c00e19d6db4ebc5132fd',
	'98ee665dc39e0404a272c87cc4efea2e',
	'0e88ad681dda36417a537c2e06a2add3',
	'0a0e496b94667220dce953cb374cee7c',
	'8cea2d49a035d5424ff28b9856d78053',
	'7df474393f58bae8e8d6b85f10efad71',
	'e797d967db24fd86341a8aa8c60fa9e0',
	'4396ae857c4f9bc5ed1f26b5361e42d9',
	'0ece13b90785aa04d1f554a093685948',
	'99e37ff13139f586d257ba9a637d7340',
	'7d8dc3050621a2cb937cac3895bc5d5b',
	// Jimmies templates
	'71865211b9a1ca691027d837109872db',
	'03674b0250f372c49da5b20eedd63da0',
	'5bce162a31293affcaa4ea9b6aa0d745',
	'5deb0d66c81262843dce5f3861be9966',
	'06621cd4a9289417cadcb8f9b5cfba80',
	'10ca5530554da7b73dc69d291bf55d38',
	'df244b337547b434b486662c3c5c7472',
	'1742b2066384637022d178cc5072905a',
	'13b47e10b2d5db45707d61851f69c52b',
	'8ad6790c22b693680e041f21d62537ac',
	'e0d1625a1f4776a5294583659d5cee15'
);

$host_template_hashes = array(
	'2005b5d6b7db6159c510b74cc6207f40',
	'71d908361c9f835cc37f7bd291689629',
	'd364e2b9570f166ab33c8df8bd503887'
);

mikrotik_tabs();

switch($_REQUEST['action']) {
case 'devices':
	mikrotik_devices();
	break;
case 'trees':
	mikrotik_trees();
	break;
case 'queues':
	mikrotik_queues();
	break;
case 'interfaces':
	mikrotik_interfaces();
	break;
case 'users':
	mikrotik_users();
	break;
case 'graphs':
	mikrotik_view_graphs();
	break;
}
bottom_footer();

function mikrotik_check_changed($request, $session) {
	if ((isset($_REQUEST[$request])) && (isset($_SESSION[$session]))) {
		if ($_REQUEST[$request] != $_SESSION[$session]) {
			return true;
		}
	}
}

function mikrotik_get_network($mask) {
	$octets = explode('.', $mask);
	$output = '';
	if (sizeof($octets)) {
		foreach($octets as $octet) {
			$output .= decbin($octet);
		}

		return strlen(trim($output, '0'));
	}

	return '0';
}

function mikrotik_users_exist() {
	return db_fetch_cell("SELECT COUNT(*) FROM plugin_mikrotik_users");
}

function mikrotik_queues_exist() {
	return db_fetch_cell("SELECT COUNT(*) FROM plugin_mikrotik_queues");
}

function mikrotik_queue_trees_exist() {
	return db_fetch_cell("SELECT COUNT(*) FROM plugin_mikrotik_trees");
}

function mikrotik_interfaces_exist() {
	return db_fetch_cell("SELECT COUNT(*) FROM plugin_mikrotik_interfaces");
}

function mikrotik_hotspots_exist() {
	return false;
}

function mikrotik_wroutes_exist() {
	return false;
}

function mikrotik_tabs() {
	global $config;

	/* present a tabbed interface */
	$tabs['devices'] = 'Devices';

	if (mikrotik_users_exist()) {
		$tabs['users'] = 'Users';
	}

	if (mikrotik_queues_exist()) {
		$tabs['queues'] = 'Queues';
	}

	if (mikrotik_queue_trees_exist()) {
		$tabs['trees'] = 'Queue Trees';
	}

	if (mikrotik_interfaces_exist()) {
		$tabs['interfaces'] = 'Interfaces';
	}

	if (mikrotik_hotspots_exist()) {
		$tabs['hotspots'] = 'Hot Spots';
	}

	if (mikrotik_wroutes_exist()) {
		$tabs['wireless'] = 'Wireless Routes';
	}

	$tabs['graphs'] = 'Graphs';

	/* set the default tab */
	$current_tab = $_REQUEST['action'];

	print "<div class='tabs'><nav><ul>\n";

	if (sizeof($tabs)) {
		foreach (array_keys($tabs) as $tab_short_name) {
            print "<li><a class='pic" . (($tab_short_name == $current_tab) ? ' selected' : '') .  "' href='" . $config['url_path'] .
				'plugins/mikrotik/mikrotik.php?' .
				'action=' . $tab_short_name .
				(isset($_REQUEST['host_id']) ? '&host_id=' . $_REQUEST['host_id']:'') .
				"'>$tabs[$tab_short_name]</a></li>\n";
		}
	}
	print "</ul></nav></div>\n";
}

function mikrotik_interfaces() {
	global $config, $colors, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('page'));
	input_validate_input_number(get_request_var_request('rows'));
	input_validate_input_number(get_request_var_request('device'));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up filter string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var_request('filter'));
	}

	/* clean up sincereset string */
	if (isset($_REQUEST['sincereset'])) {
		$_REQUEST['sincereset'] = sanitize_search_string(get_request_var_request('sincereset'));
	}

	if (isset($_REQUEST['reset'])) {
		kill_session_var('sess_mikrotik_int_sort_column');
		kill_session_var('sess_mikrotik_int_sort_direction');
		kill_session_var('sess_mikrotik_int_filter');
		kill_session_var('sess_mikrotik_int_sincereset');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_int_device');
		kill_session_var('sess_mikrotik_int_current_page');
	}elseif (isset($_REQUEST['clear'])) {
		kill_session_var('sess_mikrotik_int_sort_column');
		kill_session_var('sess_mikrotik_int_sort_direction');
		kill_session_var('sess_mikrotik_int_filter');
		kill_session_var('sess_mikrotik_int_sincereset');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_int_device');
		kill_session_var('sess_mikrotik_int_current_page');

		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['sincereset']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['device']);
		unset($_REQUEST['page']);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed('filter',     'sess_mikrotik_int_filter');
		$changed += mikrotik_check_changed('sincereset', 'sess_mikrotik_int_sincereset');
		$changed += mikrotik_check_changed('rows',       'sess_default_rows');
		$changed += mikrotik_check_changed('device',     'sess_mikrotik_int_device');
		if ($changed) {
			$_REQUEST['page'] = '1';
		}

	}

	load_current_session_value('page',           'sess_mikrotik_int_current_page', '1');
	load_current_session_value('rows',           'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value('device',         'sess_mikrotik_int_device', '-1');
	load_current_session_value('sort_column',    'sess_mikrotik_int_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_mikrotik_int_sort_direction', 'ASC');
	load_current_session_value('filter',         'sess_mikrotik_int_filter', '');
	load_current_session_value('sincereset',     'sess_mikrotik_int_sincereset', '');

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=interfaces';
		strURL += '&filter='     + $('#filter').val();
		strURL += '&rows='       + $('#rows').val();
		strURL += '&sincereset=' + $('#sincereset').is(':checked');
		strURL += '&device='     + $('#device').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=interfaces&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_interfaces').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box('<strong>Interace Stats</strong>', '100%', $colors['header'], '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_interfaces' action='mikrotik.php?action=interfaces'>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var_request('filter');?>'>
					</td>
					<td>
						Device
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('device') == '-1') {?> selected<?php }?>>All</option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (sizeof($hosts)) {
							foreach($hosts AS $h) {
								echo "<option value='" . $h['id'] . "' " . (get_request_var_request('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						Interfaces
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('rows') == '-1') {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows)) {
							foreach($item_rows AS $key => $name) {
								echo "<option value='" . $key . "' " . (get_request_var_request('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						<label for='sincereset'>Since Reset</label>
					</td>
					<td>
						<input id='sincereset' type='checkbox' <?php print ($_REQUEST['sincereset'] == 'true' ? 'checked':'');?> onClick='applyFilter()'>
					</td>
					<td>
						<input id='refresh' type='button' onClick='applyFilter()' value='Go'>
					</td>
					<td>
						<input id='clear' type='button' onClick='clearFilter()' value='Clear'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' name='page' value='<?php print $_REQUEST['page'];?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box('', '100%', $colors['header'], '3', 'center', '');

	if ($_REQUEST['rows'] == '-1') {
		$num_rows = read_config_option('num_rows_table');
	}else{
		$num_rows = get_request_var_request('rows');
	}

	$limit     = ' LIMIT ' . ($num_rows*(get_request_var_request('page')-1)) . ',' . $num_rows;
	$sql_where = "WHERE mti.name!='' AND mti.name!='System Idle Process'";

	if ($_REQUEST['device'] != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . $_REQUEST['device'];
	}

	if ($_REQUEST['filter'] != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . $_REQUEST['filter'] . "%' OR
			mti.name LIKE '%" . $_REQUEST['filter'] . "%' OR
			h.hostname LIKE '%" . $_REQUEST['filter'] . "%')";
	}

	$sort_column = get_request_var_request('sort_column');
	if ($_REQUEST['sincereset'] == 'true') {
		$pref = '';

		if (strpos($sort_column, 'cur') !== false) {
			$sort_column = str_replace('cur', '', $sort_column);
		}
	}else{
		$pref = 'cur';

		if (strpos($sort_column, 'cur') === false) {
			switch($sort_column) {
			case 'description':
			case 'name':
			case 'last_seen':
			case 'RxErrors':
			case 'RxErrors':
				break;
			default:
				$sort_column = $pref . $sort_column;
			}
		}
	}

	$sql = "SELECT mti.*, h.hostname, h.description, h.disabled, 
		(${pref}RxTooShort+${pref}RxTooLong+${pref}RxFCFSError+${pref}RxAlignError+${pref}RxFragment+${pref}RxOverflow+${pref}RxUnknownOp+${pref}RxLengthError+${pref}RxCodeError+${pref}RxCarrierError+${pref}RxJabber+${pref}RxDrop) AS RxErrors, 
		(${pref}TxTooShort+${pref}TxTooLong+${pref}TxUnderrun+${pref}TxCollision+${pref}TxExCollision+${pref}TxMultCollision+${pref}TxSingCollision+${pref}TxLateCollision+${pref}TxDrop+${pref}TxJabber+${pref}TxFCFSError) AS TxErrors
		FROM plugin_mikrotik_interfaces AS mti
		INNER JOIN host AS h
		ON h.id=mti.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		ORDER BY " . $sort_column . " " . get_request_var_request("sort_direction") . " " . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_interfaces AS mti
		INNER JOIN host AS h
		ON h.id=mti.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$nav = html_nav_bar('mikrotik.php?action=interfaces', MAX_DISPLAY_PAGES, get_request_var_request('page'), $num_rows, $total_rows, 14, 'Queues', 'page', 'main');

	print $nav;

	$display_text = array(
		'nosort'            => array('display' => 'Actions',    'sort' => '',     'align' => 'left'),
		'description'       => array('display' => 'Hostname',   'sort' => 'ASC',  'align' => 'left'),
		'name'              => array('display' => 'Name',       'sort' => 'DESC', 'align' => 'left'),
		$pref . 'RxBytes'   => array('display' => 'Rx Bytes',   'sort' => 'DESC', 'align' => 'right'),
		$pref . 'TxBytes'   => array('display' => 'Tx Bytes',   'sort' => 'DESC', 'align' => 'right'),
		$pref . 'RxPackets' => array('display' => 'Rx Packets', 'sort' => 'DESC', 'align' => 'right'),
		$pref . 'TxPackets' => array('display' => 'Tx Packets', 'sort' => 'DESC', 'align' => 'right'),
		'RxErrors'          => array('display' => 'Rx Errors',  'sort' => 'DESC', 'align' => 'right'),
		'TxErrors'          => array('display' => 'Tx Errors',  'sort' => 'DESC', 'align' => 'right'),
		'last_seen'         => array('display' => 'Last Seen',  'sort' => 'ASC',  'align' => 'right')
	);

    html_header_sort($display_text, $sort_column, get_request_var_request('sort_direction'), false, 'mikrotik.php?action=interfaces');

	if (sizeof($rows)) {
		foreach ($rows as $row) {
			form_alternate_row();

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a class='hyperLink' href='" . htmlspecialchars($config['url_path'] . 'host.php?action=edit&id=' . $row['host_id']) . "' title='Edit Hosts'>" . $row['hostname'] . "</a>\n";
			}else{
				$host_url    = $row['hostname'];
			}
			
			echo "<td style='width:60px;'></td>";
			echo "<td style='text-align:left;white-space:nowrap;'><strong>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>",  $row['description'] . '</strong> [' . $host_url . ']'):$row['description'] . '</strong> [' . $host_url . ']') . '</td>';
			echo "<td style='text-align:left;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", htmlspecialchars($row['name'])):htmlspecialchars($row['name'])) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'RxBytes']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'TxBytes']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'RxPackets']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'TxPackets']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['RxErrors']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['TxErrors']) . '</td>';
			echo "<td style='text-align:right;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['last_seen']):$row['last_seen']) . '</td>';

			form_end_row();
		}

		print $nav;
	}else{
		print '<tr><td colspan="5"><em>No Interfaces Found</em></td></tr>';
	}

	html_end_box();
}

function mikrotik_queues() {
	global $config, $colors, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('page'));
	input_validate_input_number(get_request_var_request('rows'));
	input_validate_input_number(get_request_var_request('device'));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up filter string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var_request('filter'));
	}

	/* clean up sincereset string */
	if (isset($_REQUEST['sincereset'])) {
		$_REQUEST['sincereset'] = sanitize_search_string(get_request_var_request('sincereset'));
	}

	if (isset($_REQUEST['reset'])) {
		kill_session_var('sess_mikrotik_queues_sort_column');
		kill_session_var('sess_mikrotik_queues_sort_direction');
		kill_session_var('sess_mikrotik_queues_filter');
		kill_session_var('sess_mikrotik_queues_sincereset');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_queues_device');
		kill_session_var('sess_mikrotik_queues_current_page');
	}elseif (isset($_REQUEST['clear'])) {
		kill_session_var('sess_mikrotik_queues_sort_column');
		kill_session_var('sess_mikrotik_queues_sort_direction');
		kill_session_var('sess_mikrotik_queues_filter');
		kill_session_var('sess_mikrotik_queues_sincereset');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_queues_device');
		kill_session_var('sess_mikrotik_queues_current_page');

		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['sincereset']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['device']);
		unset($_REQUEST['page']);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed('filter',     'sess_mikrotik_queues_filter');
		$changed += mikrotik_check_changed('sincereset', 'sess_mikrotik_queues_sincereset');
		$changed += mikrotik_check_changed('rows',       'sess_default_rows');
		$changed += mikrotik_check_changed('device',     'sess_mikrotik_queues_device');
		if ($changed) {
			$_REQUEST['page'] = '1';
		}

	}

	load_current_session_value('page',           'sess_mikrotik_queues_current_page', '1');
	load_current_session_value('rows',           'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value('device',         'sess_mikrotik_queues_device', '-1');
	load_current_session_value('sort_column',    'sess_mikrotik_queues_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_mikrotik_queues_sort_direction', 'ASC');
	load_current_session_value('filter',         'sess_mikrotik_queues_filter', '');
	load_current_session_value('sincereset',     'sess_mikrotik_queues_sincereset', '');

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=queues';
		strURL += '&filter='     + $('#filter').val();
		strURL += '&sincereset=' + $('#sincereset').is(':checked');
		strURL += '&rows='       + $('#rows').val();
		strURL += '&device='     + $('#device').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=queues&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_queues').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box('<strong>Queue Status</strong>', '100%', $colors['header'], '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_queues' action='mikrotik.php?action=queues'>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var_request('filter');?>'>
					</td>
					<td>
						Device
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('device') == '-1') {?> selected<?php }?>>All</option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (sizeof($hosts)) {
							foreach($hosts AS $h) {
								echo "<option value='" . $h['id'] . "' " . (get_request_var_request('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						Queues
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('rows') == '-1') {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows)) {
							foreach($item_rows AS $key => $name) {
								echo "<option value='" . $key . "' " . (get_request_var_request('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						<label for='sincereset'>Since Reset</label>
					</td>
					<td>
						<input id='sincereset' type='checkbox' <?php print ($_REQUEST['sincereset'] == 'true' ? 'checked':'');?> onClick='applyFilter()'>
					</td>
					<td>
						<input id='refresh' type='button' onClick='applyFilter()' value='Go'>
					</td>
					<td>
						<input id='clear' type='button' onClick='clearFilter()' value='Clear'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' name='page' value='<?php print $_REQUEST['page'];?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box('', '100%', $colors['header'], '3', 'center', '');

	if ($_REQUEST['rows'] == '-1') {
		$num_rows = read_config_option('num_rows_table');
	}else{
		$num_rows = get_request_var_request('rows');
	}

	$limit     = ' LIMIT ' . ($num_rows*(get_request_var_request('page')-1)) . ',' . $num_rows;
	$sql_where = "WHERE mtq.name!='' AND mtq.name!='System Idle Process'";

	$sort_column = get_request_var_request('sort_column');
	if ($_REQUEST['sincereset'] == 'true') {
		$pref = '';

		if (strpos($sort_column, 'cur') !== false) {
			$sort_column = str_replace('cur', '', $sort_column);
		}
	}else{
		$pref = 'cur';

		if (strpos($sort_column, 'cur') === false) {
			switch($sort_column) {
			case 'description':
			case 'name':
			case 'last_seen':
			case 'srcAddr':
			case 'dstAddr':
				break;
			default:
				$sort_column = $pref . $sort_column;
			}
		}
	}

	if ($_REQUEST['device'] != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . $_REQUEST['device'];
	}

	if ($_REQUEST['filter'] != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . $_REQUEST['filter'] . "%' OR
			mtq.name LIKE '%" . $_REQUEST['filter'] . "%' OR
			h.hostname LIKE '%" . $_REQUEST['filter'] . "%')";
	}

	$sql = "SELECT mtq.*, h.hostname, h.description, h.disabled
		FROM plugin_mikrotik_queues AS mtq
		INNER JOIN host AS h
		ON h.id=mtq.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		ORDER BY " . $sort_column . " " . get_request_var_request("sort_direction") . " " . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_queues AS mtq
		INNER JOIN host AS h
		ON h.id=mtq.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$nav = html_nav_bar('mikrotik.php?action=queues', MAX_DISPLAY_PAGES, get_request_var_request('page'), $num_rows, $total_rows, 14, 'Queues', 'page', 'main');

	print $nav;

	$display_text = array(
		'nosort'             => array('display' => 'Actions',       'sort' => '',     'align' => 'left'),
		'description'        => array('display' => 'Hostname',      'sort' => 'ASC',  'align' => 'left'),
		'name'               => array('display' => 'Name',          'sort' => 'DESC', 'align' => 'left'),
		'srcAddr'            => array('display' => 'Src Addr/Mask', 'sort' => 'DESC', 'align' => 'left'),
		'dstAddr'            => array('display' => 'Dst Addr/Mask', 'sort' => 'DESC', 'align' => 'left'),
		$pref . 'BytesIn'    => array('display' => 'Bytes In',       'sort' => 'DESC', 'align' => 'right'),
		$pref . 'BytesOut'   => array('display' => 'Bytes Out',      'sort' => 'DESC', 'align' => 'right'),
		$pref . 'PacketsIn'  => array('display' => 'Pkts In',        'sort' => 'DESC', 'align' => 'right'),
		$pref . 'PacketsOut' => array('display' => 'Pkts Out',       'sort' => 'DESC', 'align' => 'right'),
		$pref . 'QueuesIn'   => array('display' => 'Qs In',          'sort' => 'DESC', 'align' => 'right'),
		$pref . 'QueuesOut'  => array('display' => 'Qs Out',         'sort' => 'DESC', 'align' => 'right'),
		$pref . 'DroppedIn'  => array('display' => 'Drps In',        'sort' => 'DESC', 'align' => 'right'),
		$pref . 'DroppedOut' => array('display' => 'Drps Out',       'sort' => 'DESC', 'align' => 'right'),
		'last_seen'          => array('display' => 'Last Seen',     'sort' => 'ASC',  'align' => 'right')
	);

    html_header_sort($display_text, $sort_column, get_request_var_request('sort_direction'), false, 'mikrotik.php?action=queues');

	if (sizeof($rows)) {
		foreach ($rows as $row) {
			form_alternate_row();

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a class='hyperLink' href='" . htmlspecialchars($config['url_path'] . 'host.php?action=edit&id=' . $row['host_id']) . "' title='Edit Hosts'>" . $row['hostname'] . "</a>\n";
			}else{
				$host_url    = $row['hostname'];
			}
			$srcNet = mikrotik_get_network($row['srcMask']);
			$dstNet = mikrotik_get_network($row['dstMask']);
			$srcAM  = $row['srcAddr'] . ($srcNet != 32 ? '/' . $srcNet:'');
			$dstAM  = $row['dstAddr'] . ($dstNet != 32 ? '/' . $dstNet:'');
			
			echo "<td style='width:60px;'></td>";
			echo "<td style='text-align:left;white-space:nowrap;'><strong>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>",  htmlspecialchars($row['description']) . '</strong> [' . $host_url . ']'):htmlspecialchars($row['description']) . '</strong> [' . $host_url . ']') . '</td>';
			echo "<td style='text-align:left;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", htmlspecialchars($row['name'])):htmlspecialchars($row['name'])) . '</td>';
			echo "<td style='text-align:left;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $srcAM):$srcAM) . '</td>';
			echo "<td style='text-align:left;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $dstAM):$dstAM) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'BytesIn']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'BytesOut']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'PacketsIn']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'PacketsOut']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'QueuesIn']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'QueuesOut']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'DroppedIn']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row[$pref . 'DroppedOut']) . '</td>';
			echo "<td style='text-align:right;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['last_seen']):$row['last_seen']) . '</td>';

			form_end_row();
		}

		print $nav;
	}else{
		print '<tr><td colspan="5"><em>No Simple Queues Found</em></td></tr>';
	}

	html_end_box();
}

function mikrotik_trees() {
	global $config, $colors, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('page'));
	input_validate_input_number(get_request_var_request('rows'));
	input_validate_input_number(get_request_var_request('device'));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up filter string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var_request('filter'));
	}

	if (isset($_REQUEST['reset'])) {
		kill_session_var('sess_mikrotik_trees_sort_column');
		kill_session_var('sess_mikrotik_trees_sort_direction');
		kill_session_var('sess_mikrotik_trees_filter');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_trees_device');
		kill_session_var('sess_mikrotik_trees_current_page');
	}elseif (isset($_REQUEST['clear'])) {
		kill_session_var('sess_mikrotik_trees_sort_column');
		kill_session_var('sess_mikrotik_trees_sort_direction');
		kill_session_var('sess_mikrotik_trees_filter');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_trees_device');
		kill_session_var('sess_mikrotik_trees_current_page');

		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['device']);
		unset($_REQUEST['page']);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed('filter',   'sess_mikrotik_trees_filter');
		$changed += mikrotik_check_changed('rows',     'sess_default_rows');
		$changed += mikrotik_check_changed('device',   'sess_mikrotik_trees_device');
		if ($changed) {
			$_REQUEST['page'] = '1';
		}

	}

	load_current_session_value('page',           'sess_mikrotik_trees_current_page', '1');
	load_current_session_value('rows',           'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value('device',         'sess_mikrotik_trees_device', '-1');
	load_current_session_value('sort_column',    'sess_mikrotik_trees_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_mikrotik_trees_sort_direction', 'ASC');
	load_current_session_value('filter',         'sess_mikrotik_trees_filter', '');

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=trees';
		strURL += '&filter='   + $('#filter').val();
		strURL += '&rows='     + $('#rows').val();
		strURL += '&device='   + $('#device').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=trees&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_trees').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box('<strong>Queue Tree Status</strong>', '100%', $colors['header'], '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_trees' action='mikrotik.php?action=trees'>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var_request('filter');?>'>
					</td>
					<td>
						Device
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('device') == '-1') {?> selected<?php }?>>All</option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (sizeof($hosts)) {
							foreach($hosts AS $h) {
								echo "<option value='" . $h['id'] . "' " . (get_request_var_request('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						Trees
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('rows') == '-1') {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows)) {
							foreach($item_rows AS $key => $name) {
								echo "<option value='" . $key . "' " . (get_request_var_request('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						<input id='refresh' type='button' onClick='applyFilter()' value='Go'>
					</td>
					<td>
						<input id='clear' type='button' onClick='clearFilter()' value='Clear'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' name='page' value='<?php print $_REQUEST['page'];?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box('', '100%', $colors['header'], '3', 'center', '');

	if ($_REQUEST['rows'] == '-1') {
		$num_rows = read_config_option('num_rows_table');
	}else{
		$num_rows = get_request_var_request('rows');
	}

	$limit     = ' LIMIT ' . ($num_rows*(get_request_var_request('page')-1)) . ',' . $num_rows;
	$sql_where = "WHERE hrswls.name!='' AND hrswls.name!='System Idle Process'";

	if ($_REQUEST['device'] != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . $_REQUEST['device'];
	}

	if ($_REQUEST['filter'] != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . $_REQUEST['filter'] . "%' OR
			hrswls.name LIKE '%" . $_REQUEST['filter'] . "%' OR
			h.hostname LIKE '%" . $_REQUEST['filter'] . "%')";
	}

	$sql = "SELECT hrswls.*, h.hostname, h.description, h.disabled
		FROM plugin_mikrotik_trees AS hrswls
		INNER JOIN host AS h
		ON h.id=hrswls.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") . " " . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_trees AS hrswls
		INNER JOIN host AS h
		ON h.id=hrswls.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$nav = html_nav_bar('mikrotik.php?action=trees', MAX_DISPLAY_PAGES, get_request_var_request('page'), $num_rows, $total_rows, 9, 'Trees', 'page', 'main');

	print $nav;

	$display_text = array(
		'nosort'      => array('display' => 'Actions',       'sort' => '',     'align' => 'left'),
		'description' => array('display' => 'Hostname',      'sort' => 'ASC',  'align' => 'left'),
		'name'        => array('display' => 'Name',          'sort' => 'DESC', 'align' => 'left'),
		'flow'        => array('display' => 'Flow',          'sort' => 'DESC', 'align' => 'left'),
		'curBytes'    => array('display' => 'Cur Bytes',     'sort' => 'DESC', 'align' => 'right'),
		'curPackets'  => array('display' => 'Cur Packets',   'sort' => 'DESC', 'align' => 'right'),
		'bytes'       => array('display' => 'Total Bytes',   'sort' => 'DESC', 'align' => 'right'),
		'packets'     => array('display' => 'Total Packets', 'sort' => 'DESC', 'align' => 'right'),
		'last_seen'   => array('display' => 'Last Seen',     'sort' => 'ASC',  'align' => 'right')
	);

    html_header_sort($display_text, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), false, 'mikrotik.php?action=trees');

	if (sizeof($rows)) {
		foreach ($rows as $row) {
			form_alternate_row();

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a class='hyperLink' href='" . htmlspecialchars($config['url_path'] . 'host.php?action=edit&id=' . $row['host_id']) . "' title='Edit Hosts'>" . $row['hostname'] . "</a>\n";
			}else{
				$host_url    = $row['hostname'];
			}
			
			echo "<td style='width:60px;'></td>";
			echo "<td style='text-align:left;white-space:nowrap;'><strong>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>",  $row['description'] . '</strong> [' . $host_url . ']'):$row['description'] . '</strong> [' . $host_url . ']') . '</td>';
			echo "<td style='text-align:left;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['name']):$row['name']) . '</td>';
			echo "<td style='text-align:left;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['flow']):$row['flow']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['curBytes']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['curPackets']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['bytes']) . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['packets']) . '</td>';
			echo "<td style='text-align:right;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['last_seen']):$row['last_seen']) . '</td>';

			form_end_row();
		}

		print $nav;
	}else{
		print '<tr><td colspan="5"><em>No Queue Trees Found</em></td></tr>';
	}

	html_end_box();
}

function mikrotik_get_runtime($time) {
	if ($time > 86400) {
		$days  = floor($time/86400);
		$time %= 86400;
	}else{
		$days  = 0;
	}

	if ($time > 3600) {
		$hours = floor($time/3600);
		$time  %= 3600;
	}else{
		$hours = 0;
	}

	$minutes = floor($time/60);

	return $days . ':' . $hours . ':' . $minutes;
}

function mikrotik_users() {
	global $config, $colors, $item_rows, $mikrotik_hrSWTypes, $mikrotik_hrSWRunStatus;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('page'));
	input_validate_input_number(get_request_var_request('rows'));
	input_validate_input_number(get_request_var_request('device'));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up filter string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var_request('filter'));
	}

	/* clean up active string */
	if (isset($_REQUEST['active'])) {
		$_REQUEST['active'] = sanitize_search_string(get_request_var_request('active'));
	}

	if (isset($_REQUEST['reset'])) {
		kill_session_var('sess_mikrotik_users_sort_column');
		kill_session_var('sess_mikrotik_users_sort_direction');
		kill_session_var('sess_mikrotik_users_filter');
		kill_session_var('sess_mikrotik_users_active');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_users_device');
		kill_session_var('sess_mikrotik_users_current_page');
	}elseif (isset($_REQUEST['clear'])) {
		kill_session_var('sess_mikrotik_users_sort_column');
		kill_session_var('sess_mikrotik_users_sort_direction');
		kill_session_var('sess_mikrotik_users_filter');
		kill_session_var('sess_mikrotik_users_active');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_users_device');
		kill_session_var('sess_mikrotik_users_current_page');

		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['active']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['device']);
		unset($_REQUEST['page']);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed('filter',   'sess_mikrotik_users_filter');
		$changed += mikrotik_check_changed('active',   'sess_mikrotik_users_active');
		$changed += mikrotik_check_changed('rows',     'sess_default_rows');
		$changed += mikrotik_check_changed('device',   'sess_mikrotik_users_device');
		if ($changed) {
			$_REQUEST['page'] = '1';
		}

	}

	load_current_session_value('page',           'sess_mikrotik_users_current_page', '1');
	load_current_session_value('rows',           'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value('device',         'sess_mikrotik_users_device', '-1');
	load_current_session_value('sort_column',    'sess_mikrotik_users_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_mikrotik_users_sort_direction', 'ASC');
	load_current_session_value('filter',         'sess_mikrotik_users_filter', '');
	load_current_session_value('active',         'sess_mikrotik_users_active', 'true');

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=users';
		strURL += '&filter='   + $('#filter').val();
		strURL += '&rows='     + $('#rows').val();
		strURL += '&active='   + $('#active').is(':checked');
		strURL += '&device='   + $('#device').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=users&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_users').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box('<strong>User Statistics</strong>', '100%', $colors['header'], '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_users' method='get' action='mikrotik.php?action=users'>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print htmlspecialchars(get_request_var_request('filter'));?>'>
					</td>
					<td>
						Device
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('device') == '-1') {?> selected<?php }?>>All</option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT host.id, host.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host
								ON hrs.host_id=host.id
								ORDER BY description');

							if (sizeof($hosts)) {
							foreach($hosts AS $h) {
								echo "<option value='" . $h['id'] . "' " . (get_request_var_request('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						Devices
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows)) {
							foreach($item_rows AS $key => $name) {
								echo "<option value='" . $key . "' " . (get_request_var_request("rows") == $key ? "selected":"") . ">" . $name . "</option>";
							}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' id='active' onChange='applyFilter()' <?php print (get_request_var_request('active') == 'true' || get_request_var_request('active') == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='active'>Active Users</label>
					</td>
					<td>
						<input type='button' onClick='applyFilter()' value='Go' border='0'>
					</td>
					<td>
						<input type='button' onClick='clearFilter()' value='Clear' name='clear' border='0'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' name='page' value='<?php print $_REQUEST['page'];?>'>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box('', '100%', $colors['header'], '3', 'center', '');

	if ($_REQUEST['rows'] == '-1') {
		$num_rows = read_config_option('num_rows_table');
	}else{
		$num_rows = get_request_var_request('rows');
	}

	$limit     = ' LIMIT ' . ($num_rows*(get_request_var_request('page')-1)) . ',' . $num_rows;
	$sql_where = "WHERE hrswr.name!='' AND hrswr.name!='System Idle Process'";

	if ($_REQUEST['device'] != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . $_REQUEST['device'];
	}

	if ($_REQUEST['active'] == 'true' || $_REQUEST['active'] == 'on') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' present=1';
	}

	if ($_REQUEST['filter'] != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . $_REQUEST['filter'] . "%' OR
			hrswr.name LIKE '%" . $_REQUEST['filter'] . "%' OR
			h.hostname LIKE '%" . $_REQUEST['filter'] . "%')";
	}

	$sql = "SELECT hrswr.*, h.hostname, h.description, h.disabled, 
		bytesIn/connectTime AS avgBytesIn, 
		bytesOut/connectTime AS avgBytesOut
		FROM plugin_mikrotik_users AS hrswr
		INNER JOIN host AS h
		ON h.id=hrswr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		ORDER BY " . get_request_var_request('sort_column') . ' ' . get_request_var_request('sort_direction') . ' ' . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_users AS hrswr
		INNER JOIN host AS h
		ON h.id=hrswr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

    $nav = html_nav_bar('mikrotik.php?action=users', MAX_DISPLAY_PAGES, get_request_var_request('page'), get_request_var_request('rows'), $total_rows, 14, 'Users', 'page', 'main');

    print $nav;

	$display_text = array(
		'nosort'       => array('display' => 'Actions',      'sort' => '',     'align' => 'left'),
		'description'  => array('display' => 'Hostname',     'sort' => 'ASC',  'align' => 'left'),
		'name'         => array('display' => 'User',         'sort' => 'DESC', 'align' => 'left'),
		'domain'       => array('display' => 'Domain',       'sort' => 'ASC',  'align' => 'left'),
		'ip'           => array('display' => 'IP Address',   'sort' => 'ASC',  'align' => 'left'),
		'mac'          => array('display' => 'MAC',          'sort' => 'DESC', 'align' => 'left'),
		'connectTime'  => array('display' => 'Connect Time', 'sort' => 'DESC', 'align' => 'right'),
		'curBytesIn'   => array('display' => 'Cur In',       'sort' => 'DESC', 'align' => 'right'),
		'curBytesOut'  => array('display' => 'Cur Out',      'sort' => 'DESC', 'align' => 'right'),
		'avgBytesIn'   => array('display' => 'Avg In',       'sort' => 'DESC', 'align' => 'right'),
		'avgBytesOut'  => array('display' => 'Avg Out',      'sort' => 'DESC', 'align' => 'right'),
		'bytesIn'      => array('display' => 'Total In',     'sort' => 'DESC', 'align' => 'right'),
		'bytesOut'     => array('display' => 'Total Out',    'sort' => 'DESC', 'align' => 'right'),
		'last_seen'    => array('display' => 'Last Seen',    'sort' => 'ASC',  'align' => 'right')
	);

	html_header_sort($display_text, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), 'false');

	if (sizeof($rows)) {
		foreach ($rows as $row) {
			if ($row['present'] == 1) {
				$days      = intval($row['connectTime'] / (60*60*24));
				$remainder = $row['connectTime'] % (60*60*24);
				$hours     = intval($remainder / (60*60));
				$remainder = $remainder % (60*60);
				$minutes   = intval($remainder / (60));
			}

			form_alternate_row();
			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a class='hyperLink' href='" . htmlspecialchars($config['url_path'] . 'host.php?action=edit&id=' . $row['host_id']) . "' title='Edit Hosts'>" . $row['hostname'] . '</a>';
			}else{
				$host_url    = $row['hostname'];
			}

			$graphdq = mikrotik_data_query_by_hash('ce63249e6cc3d52bc69659a3f32194fe');
			if (!empty($graphdq)) {
				$graphsurl = mikrotik_get_graph_url($graphdq, $row['host_id'], $row['name'], $row['name'], true);
				echo "<td style='text-align:left;width:60px;'>$graphsurl</td>";
			}else{
				echo "<td style='width:60px;'></td>";
			}
			
			echo "<td style='text-align:left;'><strong>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>",  $row['description'] . '</strong> [' . $host_url . ']'):$row['description'] . '</strong> [' . $host_url . ']') . '</td>';
			echo "<td style='text-align:left;'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['name']):$row['name']) . '</td>';
			echo "<td style='text-align:left;' title='" . htmlspecialchars($row['domain']) . "'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['domain']):$row['domain']) . '</td>';
			if ($row['present'] == 1) {
				echo "<td style='text-align:left;' title='" . htmlspecialchars($row['ip']) . "'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['ip']):$row['ip']) . '</td>';
				echo "<td style='text-align:left;' title='" . htmlspecialchars($row['mac']) . "'>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $row['mac']):$row['mac']) . '</td>';
				echo "<td style='text-align:right;'>" . mikrotik_format_uptime($days, $hours, $minutes) . '</td>';
				echo "<td style='text-align:right;'>" . mikrotik_memory($row['curBytesIn']*8, 'b/s') . '</td>';
				echo "<td style='text-align:right;'>" . mikrotik_memory($row['curBytesOut']*8, 'b/s') . '</td>';
				echo "<td style='text-align:right;'>" . mikrotik_memory($row['avgBytesIn']*8, 'b/s') . '</td>';
				echo "<td style='text-align:right;'>" . mikrotik_memory($row['avgBytesOut']*8, 'b/s') . '</td>';
				echo "<td style='text-align:right;'>" . mikrotik_memory($row['bytesIn'], 'B') . '</td>';
				echo "<td style='text-align:right;'>" . mikrotik_memory($row['bytesOut'], 'B') . '</td>';
				echo "<td style='text-align:right;'>" . $row['last_seen'] . '</td>';
			}else{
				echo "<td style='text-align:left;'>N/A</td>";
				echo "<td style='text-align:left;'>N/A</td>";
				echo "<td style='text-align:left;'>N/A</td>";
				echo "<td style='text-align:left;'>N/A</td>";
				echo "<td style='text-align:right;'>N/A</td>";
				echo "<td style='text-align:right;'>N/A</td>";
				echo "<td style='text-align:right;'>N/A</td>";
				echo "<td style='text-align:right;'>N/A</td>";
				echo "<td style='text-align:right;'>N/A</td>";
				echo "<td style='text-align:right;'>" . $row['last_seen'] . '</td>';
			}

			form_end_row();
		}

		print $nav;
	}else{
		print '<tr><td colspan="5"><em>No Users Found</em></td></tr>';
	}

	html_end_box();
}

function mikrotik_devices() {
	global $config, $colors, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('status'));
	input_validate_input_number(get_request_var_request('page'));
	input_validate_input_number(get_request_var_request('rows'));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up filter string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var_request('filter'));
	}

	if (isset($_REQUEST['reset'])) {
		kill_session_var('sess_mikrotik_device_sort_column');
		kill_session_var('sess_mikrotik_device_sort_direction');
		kill_session_var('sess_mikrotik_device_status');
		kill_session_var('sess_mikrotik_device_filter');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_device_current_page');
	}elseif (isset($_REQUEST['clear'])) {
		kill_session_var('sess_mikrotik_device_sort_column');
		kill_session_var('sess_mikrotik_device_sort_direction');
		kill_session_var('sess_mikrotik_device_status');
		kill_session_var('sess_mikrotik_device_filter');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mikrotik_device_current_page');

		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['status']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['page']);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed('status',   'sess_mikrotik_device_status');
		$changed += mikrotik_check_changed('filter',   'sess_mikrotik_device_filter');
		$changed += mikrotik_check_changed('rows',     'sess_default_rows');

		if ($changed) {
			$_REQUEST['page'] = '1';
		}
	}

	load_current_session_value('page',           'sess_mikrotik_device_current_page', '1');
	load_current_session_value('rows',           'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value('sort_column',    'sess_mikrotik_device_sort_column', 'description');
	load_current_session_value('sort_direction', 'sess_mikrotik_device_sort_direction', 'ASC');
	load_current_session_value('status',         'sess_mikrotik_device_status', '-1');
	load_current_session_value('filter',         'sess_mikrotik_device_filter', '');

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=devices';
		strURL += '&status='   + $('#status').val();
		strURL += '&filter='   + $('#filter').val();
		strURL += '&rows='     + $('#rows').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=devices&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_devices').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box('<strong>Device Filter</strong>', '100%', $colors['header'], '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_devices' action='mikrotik.php?action=devices'>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var_request('filter');?>'>
					</td>
					<td>
						Status
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('type') == '-1') {?> selected<?php }?>>All</option>
							<?php
							$statuses = db_fetch_assoc('SELECT DISTINCT status
								FROM host
								INNER JOIN plugin_mikrotik_system
								ON host.id=plugin_mikrotik_system.host_id');
							$statuses = array_merge($statuses, array('-2' => array('status' => '-2')));

							if (sizeof($statuses)) {
							foreach($statuses AS $s) {
								switch($s['status']) {
									case '0':
										$status = 'Unknown';
										break;
									case '1':
										$status = 'Down';
										break;
									case '2':
										$status = 'Recovering';
										break;
									case '3':
										$status = 'Up';
										break;
									case '-2':
										$status = 'Disabled';
										break;
								}
								echo "<option value='" . $s['status'] . "' " . (get_request_var_request('status') == $s['status'] ? 'selected':'') . '>' . $status . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						Devices
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('rows') == '-1') {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows)) {
							foreach($item_rows AS $key => $name) {
								echo "<option value='" . $key . "' " . (get_request_var_request('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						<input id='refresh' type='button' onClick='applyFilter()' value='Go'>
					</td>
					<td>
						<input id='clear' type='button' onClick='clearFilter()' value='Clear'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' name='page' value='<?php print $_REQUEST['page'];?>'>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box('', '100%', $colors['header'], '3', 'center', '');

	if ($_REQUEST['rows'] == '-1') {
		$num_rows = read_config_option('num_rows_table');
	}else{
		$num_rows = get_request_var_request('rows');
	}

	$limit     = ' LIMIT ' . ($num_rows*(get_request_var_request('page')-1)) . ',' . $num_rows;
	$sql_where = '';

	if ($_REQUEST['status'] != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' hrs.host_status=' . $_REQUEST['status'];
	}

	$sql_join = '';

	if ($_REQUEST['filter'] != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " h.description LIKE '%" . $_REQUEST['filter'] . "%' OR
			h.hostname LIKE '%" . $_REQUEST['filter'] . "%'";
	}

	$sql = "SELECT hrs.*, h.hostname, h.description, h.disabled, trees.trees, queues.queues
		FROM plugin_mikrotik_system AS hrs
		INNER JOIN host AS h 
		ON h.id=hrs.host_id
		LEFT JOIN (SELECT host_id AS hid, count(*) AS trees FROM plugin_mikrotik_trees GROUP BY host_id) AS trees
		ON trees.hid=hrs.host_id
		LEFT JOIN (SELECT host_id AS hid, count(*) AS queues FROM plugin_mikrotik_queues GROUP BY host_id) AS queues
		ON queues.hid=hrs.host_id
		$sql_join
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") . " " . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_system AS hrs
		INNER JOIN host AS h
		ON h.id=hrs.host_id
		$sql_join
		$sql_where");

	$nav = html_nav_bar('mikrotik.php?action=devices', MAX_DISPLAY_PAGES, get_request_var_request('page'), $num_rows, $total_rows, 15, 'Devices', 'page', 'main');

	print $nav;

	$display_text = array(
		'nosort'          => array('display' => 'Actions',       'sort' => 'ASC',  'align' => 'left'),
		'description'     => array('display' => 'Name',          'sort' => 'ASC',  'align' => 'left'),
		'host_status'     => array('display' => 'Status',        'sort' => 'DESC', 'align' => 'center'),
		'firmwareVersion' => array('display' => 'FW Ver',        'sort' => 'DESC', 'align' => 'right'),
		'licVersion'      => array('display' => 'Lic Ver',       'sort' => 'DESC', 'align' => 'right'),
		'uptime'          => array('display' => 'Uptime(d:h:m)', 'sort' => 'DESC', 'align' => 'right'),
		'trees'           => array('display' => 'Trees',         'sort' => 'DESC', 'align' => 'right'),
		'users'           => array('display' => 'Users',         'sort' => 'DESC', 'align' => 'right'),
		'cpuPercent'      => array('display' => 'CPU %',         'sort' => 'DESC', 'align' => 'right'),
		'numCpus'         => array('display' => 'CPUs',          'sort' => 'DESC', 'align' => 'right'),
		'processes'       => array('display' => 'Processes',     'sort' => 'DESC', 'align' => 'right'),
		'memSize'         => array('display' => 'Total Mem',     'sort' => 'DESC', 'align' => 'right'),
		'memUsed'         => array('display' => 'Used Mem',      'sort' => 'DESC', 'align' => 'right'),
		'diskSize'        => array('display' => 'Total Disk',    'sort' => 'DESC', 'align' => 'right'),
		'diskUsed'        => array('display' => 'Used Disk',     'sort' => 'DESC', 'align' => 'right')
	);

	html_header_sort($display_text, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), 'false');

	/* set some defaults */
	$url        = $config['url_path'] . 'plugins/mikrotik/mikrotik.php';
	$users      = $config['url_path'] . 'plugins/mikrotik/images/view_users.gif';
	$usersn     = $config['url_path'] . 'plugins/mikrotik/images/view_users_none.gif';
	$host       = $config['url_path'] . 'plugins/mikrotik/images/view_hosts.gif';
	$trees      = $config['url_path'] . 'plugins/mikrotik/images/view_trees.gif';
	$treesn     = $config['url_path'] . 'plugins/mikrotik/images/view_trees_none.gif';
	$queues     = $config['url_path'] . 'plugins/mikrotik/images/view_queues.gif';
	$queuesn    = $config['url_path'] . 'plugins/mikrotik/images/view_queues_none.gif';
	$interfaces = $config['url_path'] . 'plugins/mikrotik/images/view_interfaces.gif';
	$dashboard  = $config['url_path'] . 'plugins/mikrotik/images/view_dashboard.gif';
	$graphs     = $config['url_path'] . 'plugins/mikrotik/images/view_graphs.gif';
	$nographs   = $config['url_path'] . 'plugins/mikrotik/images/view_graphs_disabled.gif';

	$hcpudq  = read_config_option('mikrotik_dq_host_cpu');

	if (sizeof($rows)) {
		foreach ($rows as $row) {
			$days      = intval($row['uptime'] / (60*60*24*100));
			$remainder = $row['uptime'] % (60*60*24*100);
			$hours     = intval($remainder / (60*60*100));
			$remainder = $remainder % (60*60*100);
			$minutes   = intval($remainder / (60*100));

			$found = db_fetch_cell('SELECT COUNT(*) FROM graph_local WHERE host_id=' . $row['host_id']);

			form_alternate_row();

			echo "<td style='white-space:nowrap;min-width:115px;text-align:left;'>";
			//echo "<a style='padding:1px;' href='" . htmlspecialchars("$url?action=dashboard&reset=1&device=" . $row["host_id"]) . "'><img src='$dashboard' title='View Dashboard' align='absmiddle' border='0'></a>";
			if ($row['users'] > 0) {
				echo "<a class='hyperLink' href='" . htmlspecialchars("$url?action=users&reset=1&device=" . $row['host_id']) . "'><img src='$users' title='View Users' align='absmiddle' border='0' alt=''></a>";
			}else{
				echo "<img style='border:0px;padding:3px;' src='$usersn' title='No Users Found' align='absmiddle' alt=''>";
			}

			if ($row['queues'] > 0) {
				echo "<a class='hyperLink' href='" . htmlspecialchars("$url?action=queues&reset=1&device=" . $row['host_id']) . "'><img src='$queues' title='View Simple Queue' align='absmiddle' border='0' alt=''></a>";
			}else{
				echo "<img style='border:0px;padding:3px;' src='$queuesn' title='No Simple Queues Found' align='absmiddle' alt=''>";
			}


			if ($row['trees'] > 0) {
				echo "<a class='hyperLink' href='" . htmlspecialchars("$url?action=trees&reset=1&device=" . $row['host_id']) . "'><img src='$trees' title='View Queue Trees' align='absmiddle' border='0' alt=''></a>";
			}else{
				echo "<img style='border:0px;padding:3px;' src='$treesn' title='No Queue Trees Found' align='absmiddle' alt=''>";
			}

			echo "<a class='hyperLink' href='" . htmlspecialchars("$url?action=interfaces&reset=1&device=" . $row['host_id']) . "'><img src='$interfaces' title='View Interfaces' align='absmiddle' border='0' alt=''></a>";

			if ($found) {
				echo "<a class='hyperLink' href='" . htmlspecialchars("$url?action=graphs&reset=1&host_id=" . $row['host_id'] . "&style=selective&graph_add=&graph_list=&graph_template_id=0&filter=") . "'><img  src='$graphs' title='View Graphs' align='absmiddle' border='0' alt=''></a>";
			}else{
				echo "<img src='$nographs' title='No Graphs Defined' align='absmiddle' border='0'>";
			}

			$graph_cpu   = mikrotik_get_graph_url($hcpudq, $row['host_id'], '', $row['numCpus'], false);
			$graph_cpup  = mikrotik_get_graph_template_url(mikrotik_template_by_hash('7df474393f58bae8e8d6b85f10efad71'), $row['host_id'], round($row['cpuPercent'],2), false);
			$graph_users = mikrotik_get_graph_template_url(mikrotik_template_by_hash('99e37ff13139f586d257ba9a637d7340'), $row['host_id'], (empty($rows['users']) ? '-':$row['users']), false);
			$graph_aproc = mikrotik_get_graph_template_url(mikrotik_template_by_hash('e797d967db24fd86341a8aa8c60fa9e0'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':$row['processes']), false);
			$graph_disk  = mikrotik_get_graph_template_url(mikrotik_template_by_hash('0ece13b90785aa04d1f554a093685948'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':round($row['diskUsed'],2)), false);
			$graph_mem   = mikrotik_get_graph_template_url(mikrotik_template_by_hash('4396ae857c4f9bc5ed1f26b5361e42d9'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':round($row['memUsed'],2)), false);
			$graph_upt   = mikrotik_get_graph_template_url(mikrotik_template_by_hash('7d8dc3050621a2cb937cac3895bc5d5b'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':mikrotik_format_uptime($days, $hours, $minutes)), false);

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a class='hyperLink' href='" . htmlspecialchars($config['url_path'] . 'host.php?action=edit&id=' . $row['host_id']) . "' title='Edit Hosts'>" . $row['hostname'] . '</a>';
			}else{
				$host_url    = $row['hostname'];
			}

			echo '</td>';
			echo "<td style='text-align:left;white-space:nowrap;'><strong>" . (strlen($_REQUEST['filter']) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>",  $row['description'] . '</strong> [' . $host_url . ']'):$row['description'] . '</strong> [' . $host_url . ']') . '</td>';
			echo "<td style='text-align:center;'>" . get_colored_device_status(($row['disabled'] == 'on' ? true : false), $row['host_status']) . '</td>';
			echo "<td style='text-align:right;'>" . $row['firmwareVersion'] . '</td>';
			echo "<td style='text-align:right;'>" . $row['licVersion'] . '</td>';
			echo "<td style='text-align:right;'>" . $graph_upt . '</td>';
			echo "<td style='text-align:right;'>" . (!empty($row['trees']) ? $row['trees']:'-') . '</td>';
			echo "<td style='text-align:right;'>" . $graph_users . '</td>';
			echo "<td style='text-align:right;'>" . ($row['host_status'] < 2 ? 'N/A':$graph_cpup) . '</td>';
			echo "<td style='text-align:right;'>" . ($row['host_status'] < 2 ? 'N/A':$graph_cpu) . '</td>';
			echo "<td style='text-align:right;'>" . $graph_aproc . '</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['memSize']) . '</td>';
			echo "<td style='text-align:right;'>" . $graph_mem . ' %</td>';
			echo "<td style='text-align:right;'>" . mikrotik_memory($row['diskSize']) . '</td>';
			echo "<td style='text-align:right;'>" . $graph_disk . ' %</td>';

			form_end_row();
		}

		print $nav;
	}else{
		print '<tr><td colspan="5"><em>No Devices Found</em></td></tr>';
	}

	html_end_box();
}

function mikrotik_format_uptime($d, $h, $m) {
	return ($d > 0 ? mikrotik_right('000' . $d, 3, true) . 'd ':'') . mikrotik_right('000' . $h, 2) . 'h ' . mikrotik_right('000' . $m, 2) . 'm';
}

function mikrotik_right($string, $chars, $strip = false) {
	if ($strip) {
		return ltrim(strrev(substr(strrev($string), 0, $chars)),'0');
	}else{
		return strrev(substr(strrev($string), 0, $chars));
	}
}

function mikrotik_memory($mem, $suffix = '') {
	if ($mem < 1024) {
		return round($mem,0) . "  $suffix";
	}
	$mem /= 1024;

	if ($mem < 1024) {
		return round($mem,2) . " K$suffix";
	}
	$mem /= 1024;

	if ($mem < 1024) {
		return round($mem,2) . " M$suffix";
	}
	$mem /= 1024;

	if ($mem < 1024) {
		return round($mem,2) . " G$suffix";
	}
	$mem /= 1024;

	if ($mem < 1024) {
		return round($mem,2) . " T$suffix";
	}
	$mem /= 1024;

	return round($mem,2) . "P";
}

function mikrotik_get_device_status_url($count, $status) {
	global $config;

	if ($count > 0) {
		return "<a href='" . htmlspecialchars($config['url_path'] . "plugins/mikrotik/mikrotik.php?action=devices&reset=1&status=$status") . "' title='View Hosts'>$count</a>";
	}else{
		return $count;
	}
}

function mikrotik_get_graph_template_url($graph_template, $host_id = 0, $title = '', $image = true) {
	global $config;

	$url     = $config['url_path'] . 'plugins/mikrotik/mikrotik.php';
	$nograph = $config['url_path'] . 'plugins/mikrotik/images/view_graphs_disabled.gif';
	$graph   = $config['url_path'] . 'plugins/mikrotik/images/view_graphs.gif';

	if (!empty($graph_template)) {
		if ($host_id > 0) {
			$sql_join  = '';
			$sql_where = "AND gl.host_id=$host_id";
		} else {
			$sql_join  = '';
			$sql_where = '';
		}

		$graphs = db_fetch_assoc("SELECT gl.* FROM graph_local AS gl
			$sql_join
			WHERE gl.graph_template_id=$graph_template
			$sql_where");

		$graph_add = '';
		if (sizeof($graphs)) {
		foreach($graphs as $graph) {
			$graph_add .= (strlen($graph_add) ? ',':'') . $graph['id'];
		}
		}

		if (sizeof($graphs)) {
			if ($image) {
				return "<a class='hyperLink' href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'><img border='0' src='" . $graph . "'></a>";
			}else{
				return "<a href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'>$title</a>";
			}
		}else{
			return "<img src='$nograph' title='No Graphs Found' align='absmiddle' border='0'>";
		}
	}elseif ($image) {
		return "<img src='$nograph' title='Please Select Data Query First from Console -> Settings -> Host Mib First' align='absmiddle' border='0'>";
	}else{
		return $title;
	}
}

function mikrotik_get_graph_url($data_query, $host_id, $index, $title = '', $image = true) {
	global $config;

	$url     = $config['url_path'] . 'plugins/mikrotik/mikrotik.php';
	$nograph = $config['url_path'] . 'plugins/mikrotik/images/view_graphs_disabled.gif';
	$graph   = $config['url_path'] . 'plugins/mikrotik/images/view_graphs.gif';

	$hsql = '';
	$hstr = '';

	if (!empty($data_query)) {
		$sql    = "SELECT DISTINCT gl.id
			FROM graph_local AS gl
			WHERE gl.snmp_query_id=$data_query " .
			($index!='' ? " AND gl.snmp_index IN ('$index')":"") .
			($host_id!="" ? " AND gl.host_id=$host_id":"") .
			($hstr!="" ? " AND gl.host_id IN $hstr":"");

		$graphs = db_fetch_assoc($sql);

		$graph_add = "";
		if (sizeof($graphs)) {
		foreach($graphs as $g) {
			$graph_add .= (strlen($graph_add) ? ",":"") . $g["id"];
		}
		}

		if (sizeof($graphs)) {
			if ($image) {
				return "<a class='hyperLink' href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'><img border='0' align='absmiddle' src='" . $graph . "'></a>";
			}else{
				return "<a class='hyperLink' href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'>$title</a>";
			}
		}else{
			return "<img src='$nograph' title='No Graphs Found' align='absmiddle' border='0'>";
		}
	}elseif ($image) {
		return "<img src='$nograph' title='Please Select Data Query First from Console->Settings->Host Mib First' align='absmiddle' border='0'>";
	}else{
		return $title;
	}
}

function mikrotik_graph_templates_from_hashes($hashes) {
	return array_rekey(db_fetch_assoc('SELECT id 
		FROM graph_templates 
		WHERE hash IN ("' . implode('","', $hashes) . '")'), 'id', 'id');
}

function mikrotik_host_ids_from_hashes($hashes) {
	return array_rekey(db_fetch_assoc('SELECT h.id 
		FROM host AS h 
		INNER JOIN host_template AS ht 
		ON h.host_template_id=ht.id 
		WHERE hash IN ("' . implode('","', $hashes) . '")'), 'id', 'id');
}

function mikrotik_view_graphs() {
	global $current_user, $colors, $config, $host_template_hashes, $graph_template_hashes;

	include('./lib/timespan_settings.php');
	include('./lib/html_graph.php');

	html_graph_validate_preview_request_vars();

	/* include graph view filter selector */
	html_start_box('<strong>Graph Preview Filters</strong>' . (isset($_REQUEST['style']) && strlen($_REQUEST['style']) ? ' [ Custom Graph List Applied - Filtering from List ]':''), '100%', '', '3', 'center', '');

	html_graph_preview_filter('mikrotik.php', 'graphs', 'ht.hash IN ("' . implode('","', $host_template_hashes) . '")', 'gt.hash IN ("' . implode('","', $graph_template_hashes) . '")');

	html_end_box();

	/* the user select a bunch of graphs of the 'list' view and wants them displayed here */
	$sql_or = '';
	if (isset($_REQUEST['style'])) {
		if (get_request_var_request('style') == 'selective') {

			/* process selected graphs */
			if (!empty($_REQUEST['graph_list'])) {
				foreach (explode(',',$_REQUEST['graph_list']) as $item) {
					$graph_list[$item] = 1;
				}
			}else{
				$graph_list = array();
			}
			if (!empty($_REQUEST['graph_add'])) {
				foreach (explode(',',$_REQUEST['graph_add']) as $item) {
					$graph_list[$item] = 1;
				}
			}
			/* remove items */
			if (!empty($_REQUEST['graph_remove'])) {
				foreach (explode(',',$_REQUEST['graph_remove']) as $item) {
					unset($graph_list[$item]);
				}
			}

			$i = 0;
			foreach ($graph_list as $item => $value) {
				$graph_array[$i] = $item;
				$i++;
			}

			if ((isset($graph_array)) && (sizeof($graph_array) > 0)) {
				/* build sql string including each graph the user checked */
				$sql_or = array_to_sql_or($graph_array, 'gtg.local_graph_id');

				$set_rra_id = empty($rra_id) ? read_graph_config_option('default_rra_id') : get_request_var_request('rra_id');
			}
		}
	}

	$total_graphs = 0;

	// Filter sql_where
	$sql_where  = (strlen($_REQUEST['filter']) ? "gtg.title_cache LIKE '%" . get_request_var_request('filter') . "%'":'');
	$sql_where .= (strlen($sql_or) && strlen($sql_where) ? ' AND ':'') . $sql_or;

	// Host Id sql_where
	if ($_REQUEST['host_id'] > 0) {
		$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.host_id=' . $_REQUEST['host_id'];
	}else{
		$host_ids = mikrotik_host_ids_from_hashes($host_template_hashes);
		if (sizeof($host_ids)) {
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.host_id IN (' . implode(',', $host_ids) . ')';
		}else{
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' 1=0';
		}
	}

	// Graph Template Id sql_where
	if ($_REQUEST['graph_template_id'] > 0) {
		$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.graph_template_id=' . $_REQUEST['graph_template_id'];
	}else{
		$graph_template_ids = mikrotik_graph_templates_from_hashes($graph_template_hashes);
		if (sizeof($graph_template_ids)) {
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.graph_template_id IN (' . implode(',', $graph_template_ids) . ')';
		}else{
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' 1=0';
		}
	}

	$limit      = ($_REQUEST['graphs']*($_REQUEST['page']-1)) . ',' . $_REQUEST['graphs'];
	$order      = 'gtg.title_cache';

	$graphs     = get_allowed_graphs($sql_where, $order, $limit, $total_graphs);	

	/* do some fancy navigation url construction so we don't have to try and rebuild the url string */
	if (preg_match('/page=[0-9]+/',basename($_SERVER['QUERY_STRING']))) {
		$nav_url = str_replace('&page=' . get_request_var_request('page'), '', get_browser_query_string());
	}else{
		$nav_url = get_browser_query_string() . '&host_id=' . get_request_var_request('host_id');
	}

	$nav_url = preg_replace('/((\?|&)host_id=[0-9]+|(\?|&)filter=[a-zA-Z0-9]*)/', '', $nav_url);

	html_start_box('', '100%', '', '3', 'center', '');

	$nav = html_nav_bar($nav_url, MAX_DISPLAY_PAGES, get_request_var_request('page'), get_request_var_request('graphs'), $total_graphs, get_request_var_request('columns'), 'Graphs', 'page', 'main');

	print $nav;

	if (get_request_var_request('thumbnails') == 'true') {
		html_graph_thumbnail_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var_request('columns'));
	}else{
		html_graph_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var_request('columns'));
	}

	if ($total_graphs > 0) {
		print $nav;
	}

	html_end_box();

	bottom_footer();
}
