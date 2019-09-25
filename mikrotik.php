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

chdir('../../');
include('./include/auth.php');

set_default_action('devices');

if (isset_request_var('action') && get_request_var('action') == 'ajax_hosts') {
	get_allowed_ajax_hosts();
	exit;
}

general_header();

$mikrotik_hrDeviceStatus = array(
	0 => __('Present', 'mikrotik'),
	1 => __('Unknown', 'mikrotik'),
	2 => __('Running', 'mikrotik'),
	3 => __('Warning', 'mikrotik'),
	4 => __('Testing', 'mikrotik'),
	5 => __('Down', 'mikrotik')
);

mikrotik_tabs();

switch(get_request_var('action')) {
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
case 'dhcp':
	mikrotik_dhcp();
	break;
case 'wireless_aps':
	mikrotik_wireless_aps();
	break;
case 'wireless_regs':
	mikrotik_wireless_regs();
	break;
case 'users':
	mikrotik_users();
	break;
case 'graphs':
	mikrotik_view_graphs();
	break;
}
bottom_footer();

function mikrotik_get_network($mask) {
	$octets = explode('.', $mask);
	$output = '';
	if (cacti_sizeof($octets)) {
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

function mikrotik_dhcp_exist() {
	return db_fetch_cell("SELECT COUNT(*) FROM plugin_mikrotik_dhcp");
}

function mikrotik_wireless_aps_exist() {
	return db_fetch_cell("SELECT COUNT(*) FROM plugin_mikrotik_wireless_aps");
}

function mikrotik_wregs_exist() {
	return db_fetch_cell("SELECT COUNT(*) FROM plugin_mikrotik_wireless_registrations");
}

function mikrotik_tabs() {
	global $config;

	/* present a tabbed interface */
	$tabs['devices'] = __('Devices', 'mikrotik');

	if (mikrotik_users_exist()) {
		$tabs['users'] = __('Users', 'mikrotik');
	}

	if (mikrotik_queues_exist()) {
		$tabs['queues'] = __('Queues', 'mikrotik');
	}

	if (mikrotik_queue_trees_exist()) {
		$tabs['trees'] = __('Queue Trees', 'mikrotik');
	}

	if (mikrotik_interfaces_exist()) {
		$tabs['interfaces'] = __('Interfaces', 'mikrotik');
	}

	if (mikrotik_dhcp_exist()) {
		$tabs['dhcp'] = __('DHCP', 'mikrotik');
	}

	if (mikrotik_wireless_aps_exist()) {
		$tabs['wireless_aps'] = __('Wireless Aps', 'mikrotik');
	}

	if (mikrotik_wregs_exist()) {
		$tabs['wireless_regs'] = __('Wireless Registrations', 'mikrotik');
	}

	$tabs['graphs'] = __('Graphs', 'mikrotik');

	/* set the default tab */
	$current_tab = get_request_var('action');

	print "<div class='tabs'><nav><ul>\n";

	if (cacti_sizeof($tabs)) {
		foreach (array_keys($tabs) as $tab_short_name) {
            print "<li><a class='pic" . (($tab_short_name == $current_tab) ? ' selected' : '') .  "' href='" . $config['url_path'] .
				'plugins/mikrotik/mikrotik.php?' .
				'action=' . $tab_short_name .
				(isset_request_var('host_id') ? '&host_id=' . get_filter_request_var('host_id'):'') .
				"'>" . $tabs[$tab_short_name] . "</a></li>\n";
		}
	}
	print "</ul></nav></div>\n";
}

function mikrotik_interfaces() {
	global $config, $item_rows, $interface_hashes;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'device' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'active' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sincereset' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mti');
	/* ================= input validation ================= */

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=interfaces';
		strURL += '&filter='     + $('#filter').val();
		strURL += '&rows='       + $('#rows').val();
		strURL += '&active='     + $('#active').is(':checked');
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

	html_start_box(__('Interface Stats', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_interfaces' action='mikrotik.php?action=interfaces'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Device', 'mikrotik');?>
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (cacti_sizeof($hosts)) {
								foreach($hosts AS $h) {
									print "<option value='" . $h['id'] . "' " . (get_request_var('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Interfaces', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='active' type='checkbox' <?php print (get_request_var('active') == 'true' ? 'checked':'');?> onClick='applyFilter()'>
							<label for='active'><?php print __('Active', 'mikrotik');?></label>
						</span>
					<td>
						<span>
							<input id='sincereset' type='checkbox' <?php print (get_request_var('sincereset') == 'true' ? 'checked':'');?> onClick='applyFilter()'>
							<label for='sincereset'><?php print __('Since Reset', 'mikrotik');?></label>
						</span>
					</td>
					<td>
						<span>
							<input id='refresh' type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>'>
							<input id='clear' type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = "WHERE mti.name!=''";

	if (get_request_var('device') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . get_request_var('device');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . get_request_var('filter') . "%' OR
			mti.name LIKE '%" . get_request_var('filter') . "%' OR
			h.hostname LIKE '%" . get_request_var('filter') . "%')";
	}

	$sort_column = get_request_var('sort_column');
	if (get_request_var('sincereset') == 'true') {
		if (get_request_var('active') == 'true') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (RxBytes>0 or TxBytes>0)';
		}

		$pref = '';

		if (strpos($sort_column, 'cur') !== false) {
			$sort_column = str_replace('cur', '', $sort_column);
		}
	} else {
		if (get_request_var('active') == 'true') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (curRxBytes>0 or curTxBytes>0)';
		}

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

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;

	$sql = "SELECT mti.*, h.hostname, h.description, h.disabled,
		(${pref}RxTooShort+${pref}RxTooLong+${pref}RxFCFSError+${pref}RxAlignError+${pref}RxFragment+${pref}RxOverflow+${pref}RxUnknownOp+${pref}RxLengthError+${pref}RxCodeError+${pref}RxCarrierError+${pref}RxJabber+${pref}RxDrop) AS RxErrors,
		(${pref}TxTooShort+${pref}TxTooLong+${pref}TxUnderrun+${pref}TxCollision+${pref}TxExCollision+${pref}TxMultCollision+${pref}TxSingCollision+${pref}TxLateCollision+${pref}TxDrop+${pref}TxJabber+${pref}TxFCFSError) AS TxErrors
		FROM plugin_mikrotik_interfaces AS mti
		INNER JOIN host AS h
		ON h.id=mti.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		$sql_order
		$sql_limit";

	$data_rows  = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_interfaces AS mti
		INNER JOIN host AS h
		ON h.id=mti.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$display_text = array(
		'nosort'            => array('display' => __('Actions', 'mikrotik'),    'sort' => '',     'align' => 'left'),
		'description'       => array('display' => __('Hostname', 'mikrotik'),   'sort' => 'ASC',  'align' => 'left'),
		'name'              => array('display' => __('Name', 'mikrotik'),       'sort' => 'DESC', 'align' => 'left'),
		$pref . 'RxBytes'   => array('display' => __('Rx Bytes', 'mikrotik'),   'sort' => 'DESC', 'align' => 'right'),
		$pref . 'TxBytes'   => array('display' => __('Tx Bytes', 'mikrotik'),   'sort' => 'DESC', 'align' => 'right'),
		$pref . 'RxPackets' => array('display' => __('Rx Packets', 'mikrotik'), 'sort' => 'DESC', 'align' => 'right'),
		$pref . 'TxPackets' => array('display' => __('Tx Packets', 'mikrotik'), 'sort' => 'DESC', 'align' => 'right'),
		'RxErrors'          => array('display' => __('Rx Errors', 'mikrotik'),  'sort' => 'DESC', 'align' => 'right'),
		'TxErrors'          => array('display' => __('Tx Errors', 'mikrotik'),  'sort' => 'DESC', 'align' => 'right'),
		'last_seen'         => array('display' => __('Last Seen', 'mikrotik'),  'sort' => 'ASC',  'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=interfaces', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($display_text), __('Interfaces', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, $sort_column, get_request_var('sort_direction'), false, 'mikrotik.php?action=interfaces');

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			form_alternate_row();

			$graphs = mikrotik_graphs_url_by_template_hashs($interface_hashes, $row['host_id'], $row['name']);

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url = filter_value($row['description'], get_request_var('filter'), $config['url_path'] . 'host.php?action=edit&id=' . $row['host_id'], __('Edit Device', 'microtik'));
			} else {
				$host_url    = $row['description'];
			}

			print "<td class='nowrap'>$graphs</td>";
			print "<td class='left nowrap'>" . $host_url . '</td>';
			print "<td class='left'>"  . filter_value($row['name'], get_request_var('filter')) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'RxBytes']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'TxBytes']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'RxPackets']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'TxPackets']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['RxErrors']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['TxErrors']) . '</td>';
			print "<td class='right'>" . filter_value($row['last_seen'], get_request_var('filter')) . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Interfaces Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

function mikrotik_queues() {
	global $config, $item_rows, $queue_hashes;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'device' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'active' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sincereset' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mtq');
	/* ================= input validation ================= */

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=queues';
		strURL += '&filter='     + $('#filter').val();
		strURL += '&active='     + $('#active').is(':checked');
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

	html_start_box(__('Queue Status', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_queues' action='mikrotik.php?action=queues'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Device', 'mikrotik');?>
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (cacti_sizeof($hosts)) {
								foreach($hosts AS $h) {
									print "<option value='" . $h['id'] . "' " . (get_request_var('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Queues', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='active' type='checkbox' <?php print (get_request_var('active') == 'true' ? 'checked':'');?> onClick='applyFilter()'>
							<label for='active'><?php print __('Active', 'mikrotik');?></label>
						</span>
					</td>
					<td>
						<span>
							<input id='sincereset' type='checkbox' <?php print (get_request_var('sincereset') == 'true' ? 'checked':'');?> onClick='applyFilter()'>
							<label for='sincereset'><?php print __('Since Reset', 'mikrotik');?></label>
						</span>
					</td>
					<td>
						<span>
							<input id='refresh' type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>'>
							<input id='clear' type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = "WHERE mtq.name!=''";

	$sort_column = get_request_var('sort_column');
	if (get_request_var('sincereset') == 'true') {
		if (get_request_var('active') == 'true') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (BytesIn>0 or BytesOut>0)';
		}

		$pref = '';

		if (strpos($sort_column, 'cur') !== false) {
			$sort_column = str_replace('cur', '', $sort_column);
		}
	} else {
		if (get_request_var('active') == 'true') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (curBytesIn>0 or curBytesOut>0)';
		}

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

	if (get_request_var('device') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . get_request_var('device');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . get_request_var('filter') . "%' OR
			mtq.name LIKE '%" . get_request_var('filter') . "%' OR
			h.hostname LIKE '%" . get_request_var('filter') . "%')";
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;

	$sql = "SELECT mtq.*, h.hostname, h.description, h.disabled
		FROM plugin_mikrotik_queues AS mtq
		INNER JOIN host AS h
		ON h.id=mtq.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		$sql_order
		$sql_limit";

	//print $sql;

	$data_rows  = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_queues AS mtq
		INNER JOIN host AS h
		ON h.id=mtq.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$display_text = array(
		'nosort'             => array('display' => __('Actions', 'mikrotik'),       'sort' => '',     'align' => 'left'),
		'description'        => array('display' => __('Hostname', 'mikrotik'),      'sort' => 'ASC',  'align' => 'left'),
		'name'               => array('display' => __('Name', 'mikrotik'),          'sort' => 'DESC', 'align' => 'left'),
		'srcAddr'            => array('display' => __('Src Addr/Mask', 'mikrotik'), 'sort' => 'DESC', 'align' => 'left'),
		'dstAddr'            => array('display' => __('Dst Addr/Mask', 'mikrotik'), 'sort' => 'DESC', 'align' => 'left'),
		$pref . 'BytesIn'    => array('display' => __('Bytes In', 'mikrotik'),       'sort' => 'DESC', 'align' => 'right'),
		$pref . 'BytesOut'   => array('display' => __('Bytes Out', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		$pref . 'PacketsIn'  => array('display' => __('Pkts In', 'mikrotik'),        'sort' => 'DESC', 'align' => 'right'),
		$pref . 'PacketsOut' => array('display' => __('Pkts Out', 'mikrotik'),       'sort' => 'DESC', 'align' => 'right'),
		$pref . 'QueuesIn'   => array('display' => __('Qs In', 'mikrotik'),          'sort' => 'DESC', 'align' => 'right'),
		$pref . 'QueuesOut'  => array('display' => __('Qs Out', 'mikrotik'),         'sort' => 'DESC', 'align' => 'right'),
		$pref . 'DroppedIn'  => array('display' => __('Drps In', 'mikrotik'),        'sort' => 'DESC', 'align' => 'right'),
		$pref . 'DroppedOut' => array('display' => __('Drps Out', 'mikrotik'),       'sort' => 'DESC', 'align' => 'right'),
		'last_seen'          => array('display' => __('Last Seen', 'mikrotik'),     'sort' => 'ASC',  'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=queues', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($display_text), __('Queues', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, $sort_column, get_request_var('sort_direction'), false, 'mikrotik.php?action=queues');

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			form_alternate_row();

			$graphs = mikrotik_graphs_url_by_template_hashs($queue_hashes, $row['host_id'], str_replace(' ', '%', $row['name']));

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = filter_value($row['description'], get_request_var('filter'), $config['url_path'] . 'host.php?action=edit&id=' . $row['host_id'], __('Edit Device', 'microtik'));
			} else {
				$host_url    = $row['description'];
			}
			$srcNet = mikrotik_get_network($row['srcMask']);
			$dstNet = mikrotik_get_network($row['dstMask']);
			$srcAM  = $row['srcAddr'] . ($srcNet != 32 ? '/' . $srcNet:'');
			$dstAM  = $row['dstAddr'] . ($dstNet != 32 ? '/' . $dstNet:'');

			print "<td class='nowrap'>$graphs</td>";
			print "<td class='left nowrap'>" . $host_url . '</td>';
			print "<td class='left'>"  . filter_value($row['name'], get_request_var('filter')) . '</td>';
			print "<td class='left'>"  . filter_value($srcAM, get_request_var('filter')) . '</td>';
			print "<td class='left'>"  . filter_value($dstAM, get_request_var('filter')) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'BytesIn']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'BytesOut']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'PacketsIn']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'PacketsOut']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'QueuesIn']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'QueuesOut']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'DroppedIn']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'DroppedOut']) . '</td>';
			print "<td class='right'>" . filter_value($row['last_seen'], get_request_var('filter')) . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Simple Queues Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

function mikrotik_trees() {
	global $config, $item_rows, $tree_hashes;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'device' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mtt');
	/* ================= input validation ================= */

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

	html_start_box(__('Queue Tree Status', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_trees' action='mikrotik.php?action=trees'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Device', 'mikrotik');?>
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (cacti_sizeof($hosts)) {
								foreach($hosts AS $h) {
									print "<option value='" . $h['id'] . "' " . (get_request_var('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Trees', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='refresh' type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>'>
							<input id='clear' type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = "WHERE hrswls.name!=''";
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	if (get_request_var('device') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . get_request_var('device');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . get_request_var('filter') . "%' OR
			hrswls.name LIKE '%" . get_request_var('filter') . "%' OR
			h.hostname LIKE '%" . get_request_var('filter') . "%')";
	}

	$sql = "SELECT hrswls.*, h.hostname, h.description, h.disabled
		FROM plugin_mikrotik_trees AS hrswls
		INNER JOIN host AS h
		ON h.id=hrswls.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		$sql_order
		$sql_limit";

	//print $sql;

	$data_rows  = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_trees AS hrswls
		INNER JOIN host AS h
		ON h.id=hrswls.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$display_text = array(
		'nosort'      => array('display' => __('Actions', 'mikrotik'),       'sort' => '',     'align' => 'left'),
		'description' => array('display' => __('Hostname', 'mikrotik'),      'sort' => 'ASC',  'align' => 'left'),
		'name'        => array('display' => __('Name', 'mikrotik'),          'sort' => 'DESC', 'align' => 'left'),
		'flow'        => array('display' => __('Flow', 'mikrotik'),          'sort' => 'DESC', 'align' => 'left'),
		'curBytes'    => array('display' => __('Cur Bytes', 'mikrotik'),     'sort' => 'DESC', 'align' => 'right'),
		'curPackets'  => array('display' => __('Cur Packets', 'mikrotik'),   'sort' => 'DESC', 'align' => 'right'),
		'bytes'       => array('display' => __('Total Bytes', 'mikrotik'),   'sort' => 'DESC', 'align' => 'right'),
		'packets'     => array('display' => __('Total Packets', 'mikrotik'), 'sort' => 'DESC', 'align' => 'right'),
		'last_seen'   => array('display' => __('Last Seen', 'mikrotik'),     'sort' => 'ASC',  'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=trees', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($display_text), __('Trees', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'mikrotik.php?action=trees');

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			form_alternate_row();

			$graphs = mikrotik_graphs_url_by_template_hashs($tree_hashes, $row['host_id'], str_replace(' ', '%', $row['name']));

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url = filter_value($row['description'], get_request_var('filter'), $config['url_path'] . 'host.php?action=edit&id=' . $row['host_id'], __('Edit Device', 'microtik'));
			} else {
				$host_url = $row['description'];
			}

			print "<td class='nowrap'>$graphs</td>";
			print "<td class='left nowrap'>" . $host_url . '</td>';
			print "<td class='left'>"  . filter_value($row['name'], get_request_var('filter')) . '</td>';
			print "<td class='left'>"  . filter_value($row['flow'], get_request_var('filter')) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['curBytes']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['curPackets']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['bytes']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['packets']) . '</td>';
			print "<td class='right'>" . filter_value($row['last_seen'], get_request_var('filter')) . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Queue Trees Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

function mikrotik_wireless_aps() {
	global $config, $item_rows, $wireless_station_hashes;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'device' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'apSSID',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_waps');
	/* ================= input validation ================= */

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=wireless_aps';
		strURL += '&filter='   + $('#filter').val();
		strURL += '&rows='     + $('#rows').val();
		strURL += '&device='   + $('#device').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=wireless_aps&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_wireless_aps').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('Wireless Aps Status', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_wireless_aps' action='mikrotik.php?action=wireless_aps'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Device', 'mikrotik');?>
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (cacti_sizeof($hosts)) {
								foreach($hosts AS $h) {
									print "<option value='" . $h['id'] . "' " . (get_request_var('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Aps', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='refresh' type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>'>
							<input id='clear' type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	if (get_request_var('device') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . get_request_var('device');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . get_request_var('filter') . "%' OR
			hraps.apSSID LIKE '%" . get_request_var('filter') . "%' OR
			hraps.apBSSID LIKE '%" . get_request_var('filter') . "%' OR
			hraps.apBand LIKE '%" . get_request_var('filter') . "%' OR
			h.hostname LIKE '%" . get_request_var('filter') . "%')";
	}

	$sql = "SELECT hraps.*, h.hostname, h.description, h.disabled
		FROM plugin_mikrotik_wireless_aps AS hraps
		INNER JOIN host AS h
		ON h.id=hraps.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		$sql_order
		$sql_limit";

	//print $sql;

	$data_rows  = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_wireless_aps AS hraps
		INNER JOIN host AS h
		ON h.id=hraps.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$display_text = array(
		'nosort'            => array('display' => __('Actions', 'mikrotik'),      'sort' => '',     'align' => 'left'),
		'description'       => array('display' => __('Hostname', 'mikrotik'),     'sort' => 'ASC',  'align' => 'left'),
		'apSSID'            => array('display' => __('SSID', 'mikrotik'),         'sort' => 'ASC',  'align' => 'left'),
		'apBSSID'           => array('display' => __('BSSID', 'mikrotik'),        'sort' => 'ASC',  'align' => 'left'),
		'apTxRate'          => array('display' => __('Tx Rate', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'apRxRate'          => array('display' => __('Rx Rate', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'apClientCount'     => array('display' => __('Clients', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'apAuthClientCount' => array('display' => __('Auth Clients', 'mikrotik'), 'sort' => 'DESC', 'align' => 'right'),
		'apFreq'            => array('display' => __('Frequency', 'mikrotik'),    'sort' => 'DESC', 'align' => 'right'),
		'apBand'            => array('display' => __('Band', 'mikrotik'),         'sort' => 'ASC',  'align' => 'right'),
		'apNoiseFloor'      => array('display' => __('Noise Floor', 'mikrotik'),  'sort' => 'ASC',  'align' => 'right'),
		'apOverallTxCCQ'    => array('display' => __('Tx CQQ', 'mikrotik'),       'sort' => 'ASC',  'align' => 'right'),
		'last_seen'         => array('display' => __('Last Seen', 'mikrotik'),    'sort' => 'ASC',  'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=wireless_aps', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($display_text), __('Wireless Aps', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'mikrotik.php?action=wireless_aps');

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			form_alternate_row();

			$graphs = mikrotik_graphs_url_by_template_hashs($wireless_station_hashes, $row['host_id'], str_replace(' ', '%', $row['apSSID']));

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url = filter_value($row['description'], get_request_var('filter'), $config['url_path'] . 'host.php?action=edit&id=' . $row['host_id'], __('Edit Device', 'microtik'));
			} else {
				$host_url = $row['description'];
			}

			print "<td class='nowrap'></td>";
			print "<td class='left nowrap'>" . $host_url . '</td>';
			print "<td class='left'>"  . filter_value($row['apSSID'], get_request_var('filter')) . '</td>';
			print "<td class='left'>"  . filter_value($row['apBSSID'], get_request_var('filter')) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['apTxRate'], 'b/s') . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['apRxRate'], 'b/s') . '</td>';
			print "<td class='right'>" . $row['apClientCount'] . '</td>';
			print "<td class='right'>" . $row['apAuthClientCount'] . '</td>';
			print "<td class='right'>" . round($row['apFreq']/1000,3) . ' GHz</td>';
			print "<td class='right'>" . filter_value($row['apBand'], get_request_var('filter')) . '</td>';
			print "<td class='right'>" . $row['apNoiseFloor'] . '</td>';
			print "<td class='right'>" . $row['apOverallTxCCQ'] . '</td>';
			print "<td class='right'>" . filter_value($row['last_seen'], get_request_var('filter')) . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>No Access Points Found</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

function mikrotik_get_runtime($time) {
	if ($time > 86400) {
		$days  = floor($time/86400);
		$time %= 86400;
	} else {
		$days  = 0;
	}

	if ($time > 3600) {
		$hours = floor($time/3600);
		$time  %= 3600;
	} else {
		$hours = 0;
	}

	$minutes = floor($time/60);

	return $days . ':' . $hours . ':' . $minutes;
}

function mikrotik_users() {
	global $config, $item_rows, $mikrotik_hrSWTypes, $mikrotik_hrSWRunStatus, $user_hashes;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'device' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'active' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_users');
	/* ================= input validation ================= */

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=users';
		strURL += '&filter='   + $('#filter').val();
		strURL += '&type='     + $('#type').val();
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

	html_start_box(__('User Statistics', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_users' method='get' action='mikrotik.php?action=users'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print htmlspecialchars(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Device', 'mikrotik');?>
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT host.id, host.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host
								ON hrs.host_id=host.id
								ORDER BY description');

							if (cacti_sizeof($hosts)) {
								foreach($hosts AS $h) {
									print "<option value='" . $h['id'] . "' " . (get_request_var('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Type', 'mikrotik');?>
					</td>
					<td>
						<select id='type' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('Hotspot', 'mikrotik');?></option>
							<option value='1'<?php if (get_request_var('type') == '1') {?> selected<?php }?>><?php print __('PPPoe', 'mikrotik');?></option>
						</select>
					</td>
					<td>
						<?php print __('Devices', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var("rows") == $key ? "selected":"") . ">" . $name . "</option>";
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='checkbox' id='active' onChange='applyFilter()' <?php print (get_request_var('active') == 'true' || get_request_var('active') == 'on' ? 'checked':'');?>>
							<label for='active'><?php print __('Active Users', 'mikrotik');?></label>
						</span>
					</td>
					<td>
						<span>
							<input type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>' id='refresh'>
							<input type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>' id='clear'>
						</span>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = "WHERE hrswr.name!=''";
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	if (get_request_var('device') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . get_request_var('device');
	}

	if (get_request_var('active') == 'true' || get_request_var('active') == 'on') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' present=1';
	}

	if (get_request_var('type') == '0') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' userType=0';
	} elseif (get_request_var('type') == '1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' userType=1';
	}

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . get_request_var('filter') . "%' OR
			hrswr.name LIKE '%" . get_request_var('filter') . "%' OR
			h.hostname LIKE '%" . get_request_var('filter') . "%')";
	}

	$sql = "SELECT hrswr.*, h.hostname, h.description, h.disabled,
		bytesIn, bytesOut, curBytesIn, curBytesOut,
		bytesIn/connectTime AS avgBytesIn,
		bytesOut/connectTime AS avgBytesOut
		FROM plugin_mikrotik_users AS hrswr
		INNER JOIN host AS h
		ON h.id=hrswr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		$sql_order
		$sql_limit";

	//print $sql;

	$data_rows  = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_users AS hrswr
		INNER JOIN host AS h
		ON h.id=hrswr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$display_text = array(
		'nosort'       => array('display' => __('Actions', 'mikrotik'),      'sort' => '',     'align' => 'left'),
		'description'  => array('display' => __('Hostname', 'mikrotik'),     'sort' => 'ASC',  'align' => 'left'),
		'name'         => array('display' => __('User', 'mikrotik'),         'sort' => 'DESC', 'align' => 'left'),
		'userType'     => array('display' => __('Type', 'mikrotik'),         'sort' => 'ASC',  'align' => 'left'),
		'ip'           => array('display' => __('IP Address', 'mikrotik'),   'sort' => 'ASC',  'align' => 'left'),
		'mac'          => array('display' => __('MAC', 'mikrotik'),          'sort' => 'DESC', 'align' => 'left'),
		'connectTime'  => array('display' => __('Connect Time', 'mikrotik'), 'sort' => 'DESC', 'align' => 'right'),
		'curBytesIn'   => array('display' => __('Cur In', 'mikrotik'),       'sort' => 'DESC', 'align' => 'right'),
		'curBytesOut'  => array('display' => __('Cur Out', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'avgBytesIn'   => array('display' => __('Avg In', 'mikrotik'),       'sort' => 'DESC', 'align' => 'right'),
		'avgBytesOut'  => array('display' => __('Avg Out', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'bytesIn'      => array('display' => __('Total In', 'mikrotik'),     'sort' => 'DESC', 'align' => 'right'),
		'bytesOut'     => array('display' => __('Total Out', 'mikrotik'),    'sort' => 'DESC', 'align' => 'right'),
		'last_seen'    => array('display' => __('Last Seen', 'mikrotik'),    'sort' => 'ASC',  'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=users', MAX_DISPLAY_PAGES, get_request_var('page'), get_request_var('rows'), $total_rows, sizeof($display_text), __('Users', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 'false', 'mikrotik.php?action=users');

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			if ($row['present'] == 1) {
				$days      = intval($row['connectTime'] / (60*60*24));
				$remainder = $row['connectTime'] % (60*60*24);
				$hours     = intval($remainder / (60*60));
				$remainder = $remainder % (60*60);
				$minutes   = intval($remainder / (60));
			}

			form_alternate_row();

			$graphs = mikrotik_graphs_url_by_template_hashs($user_hashes, $row['host_id'], str_replace(' ', '%', $row['name']));

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url = filter_value($row['description'], get_request_var('filter'), $config['url_path'] . 'host.php?action=edit&id=' . $row['host_id'], __('Edit Device', 'microtik'));
			} else {
				$host_url = $row['description'];
			}

			print "<td class='nowrap'>$graphs</td>";
			print "<td class='left nowrap'>" . $host_url . '</td>';
			print "<td class='left'>" . filter_value($row['name'], get_request_var('filter')) . '</td>';
			print "<td class='left'>" . ($row['userType'] == 0 ? 'Hotspot':'PPPoe') . '</td>';

			if ($row['present'] == 1) {
				print "<td class='left'>"  . filter_value($row['ip'], get_request_var('filter')) . '</td>';
				print "<td class='left'>"  . filter_value($row['mac'], get_request_var('filter')) . '</td>';
				print "<td class='right'>" . mikrotik_format_uptime($days, $hours, $minutes) . '</td>';
				print "<td class='right'>" . mikrotik_memory($row['curBytesIn']*8, 'b/s') . '</td>';
				print "<td class='right'>" . mikrotik_memory($row['curBytesOut']*8, 'b/s') . '</td>';
				print "<td class='right'>" . mikrotik_memory($row['avgBytesIn']*8, 'b/s') . '</td>';
				print "<td class='right'>" . mikrotik_memory($row['avgBytesOut']*8, 'b/s') . '</td>';
				print "<td class='right'>" . mikrotik_memory($row['bytesIn'], 'B') . '</td>';
				print "<td class='right'>" . mikrotik_memory($row['bytesOut'], 'B') . '</td>';
				print "<td class='right'>" . $row['last_seen'] . '</td>';
			} else {
				print "<td class='left'>"  . __('N/A', 'mikrotik') . '</td>';
				print "<td class='left'>"  . $row['mac']           . '</td>';
				print "<td class='right'>" . __('N/A', 'mikrotik') . '</td>';
				print "<td class='right'>" . __('N/A', 'mikrotik') . '</td>';
				print "<td class='right'>" . __('N/A', 'mikrotik') . '</td>';
				print "<td class='right'>" . __('N/A', 'mikrotik') . '</td>';
				print "<td class='right'>" . __('N/A', 'mikrotik') . '</td>';
				print "<td class='right'>" . __('N/A', 'mikrotik') . '</td>';
				print "<td class='right'>" . __('N/A', 'mikrotik') . '</td>';
				print "<td class='right'>" . $row['last_seen']     . '</td>';
			}

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Users Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

function mikrotik_devices() {
	global $config, $item_rows;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'description',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mtd');
	/* ================= input validation ================= */

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

	$mikrotik_version_date = read_config_option('mikrotik_latestversion_date');
	if (empty($mikrotik_version_date) || intval($mikrotik_version_date) == 0) {
		$header = __('Device Filter (Latest MikroTik Version is: %s)', read_config_option('mikrotik_latestversion'), 'mikrotik');
	} else {
		$header = __('Device Filter (Latest MikroTik Version is: %s, Released: %s)', read_config_option('mikrotik_latestversion'), date('Y-m-d H:i:s', intval($mikrotik_version_date)), 'mikrotik');
	}

	$header .= "&nbsp;<a class='hyperLink' href='https://mikrotik.com/download/changelogs' target='_blank'>" . __('ChangeLog', 'mikrotik', 'mikrotik') . '</a>';

	html_start_box($header, '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_devices' action='mikrotik.php?action=devices'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Status', 'mikrotik');?>
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$statuses = db_fetch_assoc('SELECT DISTINCT status
								FROM host
								INNER JOIN plugin_mikrotik_system
								ON host.id=plugin_mikrotik_system.host_id');

							$statuses = array_merge($statuses, array('-2' => array('status' => '-2')));

							if (cacti_sizeof($statuses)) {
								foreach($statuses AS $s) {
									switch($s['status']) {
									case '0':
										$status = __('Unknown', 'mikrotik');
										break;
									case '1':
										$status = __('Down', 'mikrotik');
										break;
									case '2':
										$status = __('Recovering', 'mikrotik');
										break;
									case '3':
										$status = __('Up', 'mikrotik');
										break;
									case '-2':
										$status = __('Disabled', 'mikrotik');
										break;
									}

									print "<option value='" . $s['status'] . "' " . (get_request_var('status') == $s['status'] ? 'selected':'') . '>' . $status . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Devices', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='refresh' type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>'>
							<input id='clear' type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>'>
						</span>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	if (get_request_var('status') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' hrs.host_status=' . get_request_var('status');
	}

	$sql_join = '';

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " h.description LIKE '%" . get_request_var('filter') . "%' OR
			h.hostname LIKE '%" . get_request_var('filter') . "%'";
	}

	$sql = "SELECT hrs.*, h.hostname, h.description, h.disabled, trees.trees, queues.queues, aps.aps
		FROM plugin_mikrotik_system AS hrs
		INNER JOIN host AS h
		ON h.id=hrs.host_id
		LEFT JOIN (SELECT host_id AS hid, count(*) AS trees FROM plugin_mikrotik_trees GROUP BY host_id) AS trees
		ON trees.hid=hrs.host_id
		LEFT JOIN (SELECT host_id AS hid, count(*) AS queues FROM plugin_mikrotik_queues GROUP BY host_id) AS queues
		ON queues.hid=hrs.host_id
		LEFT JOIN (SELECT host_id AS hid, count(*) AS aps FROM plugin_mikrotik_wireless_aps GROUP BY host_id) AS aps
		ON aps.hid=hrs.host_id
		$sql_join
		$sql_where
		$sql_order
		$sql_limit";

	//print $sql;

	$data_rows  = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_system AS hrs
		INNER JOIN host AS h
		ON h.id=hrs.host_id
		$sql_join
		$sql_where");

	$display_text = array(
		'nosort'          => array('display' => __('Actions', 'mikrotik'),       'sort' => 'ASC',  'align' => 'left'),
		'description'     => array('display' => __('Name', 'mikrotik'),          'sort' => 'ASC',  'align' => 'left'),
		'sysDescr'        => array('display' => __('Description', 'mikrotik'),   'sort' => 'ASC',  'align' => 'left'),
		'host_status'     => array('display' => __('Status', 'mikrotik'),        'sort' => 'DESC', 'align' => 'center'),
		'firmwareVersion' => array('display' => __('FW Ver', 'mikrotik'),        'sort' => 'DESC', 'align' => 'right'),
		'licVersion'      => array('display' => __('Lic Ver', 'mikrotik'),       'sort' => 'DESC', 'align' => 'right'),
		'uptime'          => array('display' => __('Uptime(d:h:m)', 'mikrotik'), 'sort' => 'DESC', 'align' => 'right'),
		'trees'           => array('display' => __('Trees', 'mikrotik'),         'sort' => 'DESC', 'align' => 'right'),
		'users'           => array('display' => __('Users', 'mikrotik'),         'sort' => 'DESC', 'align' => 'right'),
		'cpuPercent'      => array('display' => __('CPU %', 'mikrotik'),         'sort' => 'DESC', 'align' => 'right'),
		'numCpus'         => array('display' => __('CPUs', 'mikrotik'),          'sort' => 'DESC', 'align' => 'right'),
		'processes'       => array('display' => __('Processes', 'mikrotik'),     'sort' => 'DESC', 'align' => 'right'),
		'memSize'         => array('display' => __('Total Mem', 'mikrotik'),     'sort' => 'DESC', 'align' => 'right'),
		'memUsed'         => array('display' => __('Used Mem', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'diskSize'        => array('display' => __('Total Disk', 'mikrotik'),    'sort' => 'DESC', 'align' => 'right'),
		'diskUsed'        => array('display' => __('Used Disk', 'mikrotik'),     'sort' => 'DESC', 'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=devices', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($display_text), __('Devices', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 'false', 'mikrotik.php?action=devices');

	/* set some defaults */
	$url        = $config['url_path'] . 'plugins/mikrotik/mikrotik.php';
	$users      = $config['url_path'] . 'plugins/mikrotik/images/view_users.gif';
	$usersn     = $config['url_path'] . 'plugins/mikrotik/images/view_users_none.gif';
	$host       = $config['url_path'] . 'plugins/mikrotik/images/view_hosts.gif';
	$trees      = $config['url_path'] . 'plugins/mikrotik/images/view_trees.gif';
	$treesn     = $config['url_path'] . 'plugins/mikrotik/images/view_trees_none.gif';
	$queues     = $config['url_path'] . 'plugins/mikrotik/images/view_queues.gif';
	$queuesn    = $config['url_path'] . 'plugins/mikrotik/images/view_queues_none.gif';
	$aps        = $config['url_path'] . 'plugins/mikrotik/images/view_aps.gif';
	$apsn       = $config['url_path'] . 'plugins/mikrotik/images/view_aps_none.gif';
	$interfaces = $config['url_path'] . 'plugins/mikrotik/images/view_interfaces.gif';
	$dashboard  = $config['url_path'] . 'plugins/mikrotik/images/view_dashboard.gif';
	$graphs     = $config['url_path'] . 'plugins/mikrotik/images/view_graphs.gif';
	$nographs   = $config['url_path'] . 'plugins/mikrotik/images/view_graphs_disabled.gif';

	$hcpudq  = read_config_option('mikrotik_dq_host_cpu');
	$licVersionLatest  = read_config_option('mikrotik_latestversion', true);

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			$days      = intval($row['uptime'] / (60*60*24*100));
			$remainder = $row['uptime'] % (60*60*24*100);
			$hours     = intval($remainder / (60*60*100));
			$remainder = $remainder % (60*60*100);
			$minutes   = intval($remainder / (60*100));

			$found = db_fetch_cell('SELECT COUNT(*) FROM graph_local WHERE host_id=' . $row['host_id']);

			form_alternate_row();

			print "<td class='nowrap left'>";
			//print "<a class='padding:1px;' href='" . htmlspecialchars("$url?action=dashboard&reset=1&device=" . $row["host_id"]) . "'><img src='$dashboard' title='View Dashboard'></a>";
			if ($row['users'] > 0) {
				print "<a class='hyperLink' href='" . htmlspecialchars("$url?action=users&reset=1&device=" . $row['host_id']) . "'><img src='$users' title='" . __esc('View Users', 'mikrotik') . "' alt=''></a>";
			} elseif (read_config_option('mikrotik_users_freq') != '-1') {
				print "<img style='border:0px;padding:3px;' src='$usersn' title='" . __esc('No Users Found', 'mikrotik') . "' align='absmiddle' alt=''>";
			}

			if ($row['queues'] > 0) {
				print "<a class='hyperLink' href='" . htmlspecialchars("$url?action=queues&reset=1&device=" . $row['host_id']) . "'><img src='$queues' title='" . __esc('View Simple Queue', 'mikrotik') . "' alt=''></a>";
			} elseif (read_config_option('mikrotik_queues_freq') != '-1') {
				print "<img style='border:0px;padding:3px;' src='$queuesn' title='" . __esc('No Simple Queues Found', 'mikrotik') . "' align='absmiddle' alt=''>";
			}

			if ($row['trees'] > 0) {
				print "<a class='hyperLink' href='" . htmlspecialchars("$url?action=trees&reset=1&device=" . $row['host_id']) . "'><img src='$trees' title='" . __esc('View Queue Trees', 'mikrotik') . "' alt=''></a>";
			} elseif (read_config_option('mikrotik_trees_freq') != '-1') {
				print "<img style='border:0px;padding:3px;' src='$treesn' title='" . __esc('No Queue Trees Found', 'mikrotik') . "' align='absmiddle' alt=''>";
			}

			if ($row['aps'] > 0) {
				print "<a class='hyperLink' href='" . htmlspecialchars("$url?action=wireless_aps&reset=1&device=" . $row['host_id']) . "'><img src='$aps' title='" . __esc('View Wireless Aps', 'mikrotik') . "' alt=''></a>";
			} elseif (read_config_option('mikrotik_wireless_aps_freq') != '-1') {
				print "<img style='border:0px;padding:3px;' src='$apsn' title='" . __esc('No Wireless Aps Found', 'mikrotik') . "' align='absmiddle' alt=''>";
			}

			print "<a class='hyperLink' href='" . htmlspecialchars("$url?action=interfaces&reset=1&device=" . $row['host_id']) . "'><img src='$interfaces' title='" . __esc('View Interfaces', 'mikrotik') . "' alt=''></a>";

			if ($found) {
				print "<a class='hyperLink' href='" . htmlspecialchars("$url?action=graphs&reset=1&host_id=" . $row['host_id'] . "&style=selective&graph_add=&graph_list=&graph_template_id=0&filter=") . "'><img  src='$graphs' title='" . __esc('View Graphs', 'mikrotik') . "' alt=''></a>";
			} else {
				print "<img src='$nographs' title='" . __esc('No Graphs Defined', 'mikrotik') . "'>";
			}

			$graph_cpu   = mikrotik_get_graph_url($hcpudq, $row['host_id'], '', $row['numCpus'], false);
			$graph_cpup  = mikrotik_get_graph_template_url(mikrotik_template_by_hash('7df474393f58bae8e8d6b85f10efad71'), $row['host_id'], round($row['cpuPercent'],2), false);
			$graph_users = mikrotik_get_graph_template_url(mikrotik_template_by_hash('99e37ff13139f586d257ba9a637d7340'), $row['host_id'], (empty($row['users']) ? '-':$row['users']), false);
			$graph_aproc = mikrotik_get_graph_template_url(mikrotik_template_by_hash('e797d967db24fd86341a8aa8c60fa9e0'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':$row['processes']), false);
			$graph_disk  = mikrotik_get_graph_template_url(mikrotik_template_by_hash('0ece13b90785aa04d1f554a093685948'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':round($row['diskUsed'],2)), false);
			$graph_mem   = mikrotik_get_graph_template_url(mikrotik_template_by_hash('4396ae857c4f9bc5ed1f26b5361e42d9'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':round($row['memUsed'],2)), false);
			$graph_upt   = mikrotik_get_graph_template_url(mikrotik_template_by_hash('7d8dc3050621a2cb937cac3895bc5d5b'), $row['host_id'], ($row['host_status'] < 2 ? 'N/A':mikrotik_format_uptime($days, $hours, $minutes)), false);

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a class='hyperLink' href='" . htmlspecialchars($config['url_path'] . 'host.php?action=edit&id=' . $row['host_id']) . "' title='Edit Device'>" . (strlen(get_request_var('filter')) ? preg_replace('/(' . preg_quote(get_request_var('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>",  $row['description']):$row['description']) . '</a>';
			} else {
				$host_url    = $row['description'];
			}

			print '</td>';
			print "<td class='left nowrap'>" . $host_url . '</td>';
			print "<td class='left'>"   . $row['sysDescr'] . '</td>';
			print "<td class='center'>" . get_colored_device_status(($row['disabled'] == 'on' ? true : false), $row['host_status']) . '</td>';
			print "<td class='right'>"  . ($row['firmwareVersionLatest'] != $row['firmwareVersion'] && $row['firmwareVersionLatest'] != '' ? '* ' : '') . $row['firmwareVersion'] . '</td>';
			print "<td class='right'>"  . ($licVersionLatest > $row['licVersion'] && $licVersionLatest != '' ? '* ' : '') . $row['licVersion'] . '</td>';
			print "<td class='right'>"  . $graph_upt . '</td>';
			print "<td class='right'>"  . (!empty($row['trees']) ? $row['trees']:'-') . '</td>';
			print "<td class='right'>"  . $graph_users . '</td>';
			print "<td class='right'>"  . ($row['host_status'] < 2 ? 'N/A':$graph_cpup) . '</td>';
			print "<td class='right'>"  . ($row['host_status'] < 2 ? 'N/A':$graph_cpu) . '</td>';
			print "<td class='right'>"  . $graph_aproc . '</td>';
			print "<td class='right'>"  . mikrotik_memory($row['memSize']) . '</td>';
			print "<td class='right'>"  . ($graph_mem == '-' ? '-':$graph_mem . ' %') . '</td>';
			print "<td class='right'>"  . mikrotik_memory($row['diskSize']) . '</td>';
			print "<td class='right'>"  . ($graph_disk == '-' ? '-':$graph_disk . ' %') . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Devices Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

function mikrotik_format_uptime($d, $h, $m) {
	return ($d > 0 ? mikrotik_right('000' . $d, 3, true) . 'd ':'') . mikrotik_right('000' . $h, 2) . 'h ' . mikrotik_right('000' . $m, 2) . 'm';
}

function mikrotik_right($string, $chars, $strip = false) {
	if ($strip) {
		return ltrim(strrev(substr(strrev($string), 0, $chars)),'0');
	} else {
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
	} else {
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
		if (cacti_sizeof($graphs)) {
			foreach($graphs as $graph) {
				$graph_add .= (strlen($graph_add) ? ',':'') . $graph['id'];
			}
		}

		if (cacti_sizeof($graphs)) {
			if ($image) {
				return "<a class='hyperLink' href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='" . __esc('View Graphs', 'mikrotik') . "'><img src='" . $graph . "'></a>";
			} else {
				return "<a class='hyperLink' href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='" . __esc('View Graphs', 'mikrotik') . "'>$title</a>";
			}
		} else {
			return "-";
		}
	} elseif ($image) {
		return "<img src='$nograph' title='" . __esc('Please Select Data Query First from Console -> Settings -> Host MIB First', 'mikrotik') . "'>";
	} else {
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
		if (cacti_sizeof($graphs)) {
			foreach($graphs as $g) {
				$graph_add .= (strlen($graph_add) ? ",":"") . $g["id"];
			}
		}

		if (cacti_sizeof($graphs)) {
			if ($image) {
				return "<a class='hyperLink' href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='" . __esc('View Graphs', 'mikrotik') . "'><img src='" . $graph . "'></a>";
			} else {
				return "<a class='hyperLink' href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='" . __esc('View Graphs', 'mikrotik') . "'>$title</a>";
			}
		} else {
			return "<img src='$nograph' title='" . __esc('Graphs skipped or not created yet', 'mikrotik') . "'>";
		}
	} elseif ($image) {
		return "<img src='$nograph' title='" . __esc('Please select Data Query first from Console->Settings->Host MIB First', 'mikrotik') . "'>";
	} else {
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
	global $current_user, $config, $host_template_hashes, $graph_template_hashes;

	include('./lib/timespan_settings.php');
	include('./lib/html_graph.php');

	html_graph_validate_preview_request_vars();

	/* include graph view filter selector */
	html_start_box(__('Graph Preview Filters', 'mikrotik') . (isset_request_var('style') && strlen(get_request_var('style')) ? ' [ ' . __('Custom Graph List Applied - Filtering from List', 'mikrotik') . ' ]':''), '100%', '', '3', 'center', '');

	html_graph_preview_filter('mikrotik.php', 'graphs', 'ht.hash IN ("' . implode('","', $host_template_hashes) . '")', 'gt.hash IN ("' . implode('","', $graph_template_hashes) . '")');

	html_end_box();

	/* the user select a bunch of graphs of the 'list' view and wants them displayed here */
	$sql_or = '';
	if (isset_request_var('style')) {
		if (get_request_var('style') == 'selective') {

			/* process selected graphs */
			if (!isempty_request_var('graph_list')) {
				foreach (explode(',',get_request_var('graph_list')) as $item) {
					$graph_list[$item] = 1;
				}
			} else {
				$graph_list = array();
			}
			if (!isempty_request_var('graph_add')) {
				foreach (explode(',',get_request_var('graph_add')) as $item) {
					$graph_list[$item] = 1;
				}
			}
			/* remove items */
			if (!isempty_request_var('graph_remove')) {
				foreach (explode(',',get_request_var('graph_remove')) as $item) {
					unset($graph_list[$item]);
				}
			}

			$i = 0;
			foreach ($graph_list as $item => $value) {
				$graph_array[$i] = $item;
				$i++;
			}

			if ((isset($graph_array)) && (cacti_sizeof($graph_array) > 0)) {
				/* build sql string including each graph the user checked */
				$sql_or = array_to_sql_or($graph_array, 'gtg.local_graph_id');

				$set_rra_id = empty($rra_id) ? read_user_setting('default_rra_id') : get_request_var('rra_id');
			}
		}
	}

	$total_graphs = 0;

	// Filter sql_where
	$sql_where  = (strlen(get_request_var('filter')) ? "gtg.title_cache LIKE '%" . get_request_var('filter') . "%'":'');
	$sql_where .= (strlen($sql_or) && strlen($sql_where) ? ' AND ':'') . $sql_or;

	// Host Id sql_where
	if (get_request_var('host_id') > 0) {
		$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.host_id=' . get_request_var('host_id');
	} else {
		$host_ids = mikrotik_host_ids_from_hashes($host_template_hashes);
		if (cacti_sizeof($host_ids)) {
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.host_id IN (' . implode(',', $host_ids) . ')';
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' 1=0';
		}
	}

	// Graph Template Id sql_where
	if (get_request_var('graph_template_id') > 0) {
		$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.graph_template_id IN(' . get_request_var('graph_template_id') . ')';
	} else {
		$graph_template_ids = mikrotik_graph_templates_from_hashes($graph_template_hashes);
		if (cacti_sizeof($graph_template_ids)) {
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' gl.graph_template_id IN (' . implode(',', $graph_template_ids) . ')';
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND':'') . ' 1=0';
		}
	}

	$limit  = (get_request_var('graphs')*(get_request_var('page')-1)) . ',' . get_request_var('graphs');
	$order  = 'gtg.title_cache';

	$graphs = get_allowed_graphs($sql_where, $order, $limit, $total_graphs);

	/* do some fancy navigation url construction so we don't have to try and rebuild the url string */
	if (preg_match('/page=[0-9]+/',basename($_SERVER['QUERY_STRING']))) {
		$nav_url = str_replace('&page=' . get_request_var('page'), '', get_browser_query_string());
	} else {
		$nav_url = get_browser_query_string() . '&host_id=' . get_request_var('host_id');
	}

	$nav_url = preg_replace('/((\?|&)host_id=[0-9]+|(\?|&)filter=[a-zA-Z0-9]*)/', '', $nav_url);

	$nav = html_nav_bar($nav_url, MAX_DISPLAY_PAGES, get_request_var('page'), get_request_var('graphs'), $total_graphs, get_request_var('columns'), __('Graphs', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	if (get_request_var('thumbnails') == 'true') {
		html_graph_thumbnail_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var('columns'));
	} else {
		html_graph_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var('columns'));
	}

	html_end_box();

	if ($total_graphs > 0) {
		print $nav;
	}

	bottom_footer();
}

function mikrotik_wireless_regs() {
	global $config, $item_rows, $wreg_hashes;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'device' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'active' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sincereset' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'index',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mtwr');
	/* ================= input validation ================= */

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=wireless_regs';
		strURL += '&filter='     + $('#filter').val();
		strURL += '&rows='       + $('#rows').val();
		strURL += '&active='     + $('#active').is(':checked');
		strURL += '&sincereset=' + $('#sincereset').is(':checked');
		strURL += '&device='     + $('#device').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=wireless_regs&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_wregs').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('Wireless Registration Stats', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_wregs' action='mikrotik.php?action=wireless_regs'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Device', 'mikrotik');?>
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (cacti_sizeof($hosts)) {
								foreach($hosts AS $h) {
									print "<option value='" . $h['id'] . "' " . (get_request_var('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Registrations', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='active' type='checkbox' <?php print (get_request_var('active') == 'true' ? 'checked':'');?> onClick='applyFilter()'>
							<label for='active'><?php print __('Active', 'mikrotik');?></label>
						</span>
					<td>
						<span>
							<input id='sincereset' type='checkbox' <?php print (get_request_var('sincereset') == 'true' ? 'checked':'');?> onClick='applyFilter()'>
							<label for='sincereset'><?php print __('Since Reset', 'mikrotik');?></label>
						</span>
					</td>
					<td>
						<span>
							<input id='refresh' type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>'>
							<input id='clear' type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = "WHERE mtwr.index!=''";

	if (get_request_var('device') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' h.id=' . get_request_var('device');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (h.description LIKE '%" . get_request_var('filter') . "%' OR
			mtwr.index LIKE '%" . get_request_var('filter') . "%' OR
			h.hostname LIKE '%" . get_request_var('filter') . "%')";
	}

	$sort_column = get_request_var('sort_column');
	if (get_request_var('sincereset') == 'true') {
		if (get_request_var('active') == 'true') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (RxBytes>0 or TxBytes>0)';
		}

		$pref = '';

		if (strpos($sort_column, 'cur') !== false) {
			$sort_column = str_replace('cur', '', $sort_column);
		}
	} else {
		if (get_request_var('active') == 'true') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (curRxBytes>0 or curTxBytes>0)';
		}

		$pref = 'cur';

		if (strpos($sort_column, 'cur') === false) {
			switch($sort_column) {
			case 'description':
			case 'index':
			case 'last_seen':
			case 'Uptime':
			case 'TxRate':
			case 'RxRate':
			case 'SignalToNoise':
			case 'Strength':
				break;
			default:
				$sort_column = $pref . $sort_column;
			}
		}
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;

	$sql = "SELECT mtwr.*, h.hostname, dhcp.hostname AS client_name, h.description, h.disabled
		FROM plugin_mikrotik_wireless_registrations AS mtwr
		INNER JOIN host AS h
		ON h.id=mtwr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		LEFT JOIN plugin_mikrotik_mac2hostname AS dhcp
		ON dhcp.mac_address=mtwr.index
		$sql_where
		$sql_order
		$sql_limit";

	$data_rows  = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_wireless_registrations AS mtwr
		INNER JOIN host AS h
		ON h.id=mtwr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		LEFT JOIN plugin_mikrotik_mac2hostname AS dhcp
		ON dhcp.mac_address=mtwr.index
		$sql_where");

	$display_text = array(
		'nosort'            => array('display' => __('Actions', 'mikrotik'),         'sort' => '',     'align' => 'left'),
		'description'       => array('display' => __('Device', 'mikrotik'),          'sort' => 'ASC',  'align' => 'left'),
		'client_name'       => array('display' => __('Client Name', 'mikrotik'),     'sort' => 'ASC',  'align' => 'left'),
		'index'             => array('display' => __('MAC Address', 'mikrotik'),     'sort' => 'ASC',  'align' => 'left'),
		$pref . 'RxBytes'   => array('display' => __('Rx Bytes', 'mikrotik'),        'sort' => 'DESC', 'align' => 'right'),
		$pref . 'TxBytes'   => array('display' => __('Tx Bytes', 'mikrotik'),        'sort' => 'DESC', 'align' => 'right'),
		$pref . 'RxPackets' => array('display' => __('Rx Packets', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		$pref . 'TxPackets' => array('display' => __('Tx Packets', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'RxRate'            => array('display' => __('Rx Rate', 'mikrotik'),         'sort' => 'DESC', 'align' => 'right'),
		'TxRate'            => array('display' => __('Tx Rate', 'mikrotik'),         'sort' => 'DESC', 'align' => 'right'),
		'Uptime'            => array('display' => __('Uptime(d:h:m)', 'mikrotik'),   'sort' => 'DESC', 'align' => 'right'),
		'SignalToNoise'     => array('display' => __('Signal to Noise', 'mikrotik'), 'sort' => 'DESC', 'align' => 'right'),
		'last_seen'         => array('display' => __('Last Seen', 'mikrotik'),       'sort' => 'ASC',  'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=wireless_regs', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($display_text), __('Registrations', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, $sort_column, get_request_var('sort_direction'), false, 'mikrotik.php?action=wireless_regs');

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			if (strpos($row['Uptime'], ':') !== false) {
				list($days, $hours, $minutes, $seconds) = explode(':', $row['Uptime']);
			} else {
				$days      = intval($row['Uptime'] / (60*60*24*100));
				$remainder = $row['Uptime'] % (60*60*24*100);
				$hours     = intval($remainder / (60*60*100));
				$remainder = $remainder % (60*60*100);
				$minutes   = intval($remainder / (60*100));
			}

			form_alternate_row();

			$graphs = mikrotik_graphs_url_by_template_hashs($wreg_hashes, $row['host_id'], $row['index']);

			if (api_plugin_user_realm_auth('host.php')) {
				$host_url = filter_value($row['description'], get_request_var('filter'), $config['url_path'] . 'host.php?action=edit&id=' . $row['host_id'], __('Edit Device', 'microtik'));
			} else {
				$host_url = $row['description'];
			}

			print "<td class='nowrap'>$graphs</td>";
			print "<td class='left nowrap'>" . $host_url . '</td>';
			print "<td class='left nowrap'>" . filter_value($row['client_name'], get_request_var('filter')) . '</td>';
			print "<td class='left nowrap'>" . filter_value($row['index'], get_request_var('filter')) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'RxBytes']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'TxBytes']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'RxPackets']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row[$pref . 'TxPackets']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['RxRate']) . '</td>';
			print "<td class='right'>" . mikrotik_memory($row['TxRate']) . '</td>';
			print "<td class='right'>" . mikrotik_format_uptime($days, $hours, $minutes) . '</td>';
			print "<td class='right'>" . $row['SignalToNoise'] . '</td>';
			print "<td class='right'>" . filter_value($row['last_seen'], get_request_var('filter')) . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Wireless Registrations Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

function mikrotik_dhcp() {
	global $config, $item_rows, $tree_hashes;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('num_rows_table')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'device' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'dhcp.hostname',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mtdh');
	/* ================= input validation ================= */

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = '?action=dhcp';
		strURL += '&filter='   + $('#filter').val();
		strURL += '&rows='     + $('#rows').val();
		strURL += '&device='   + $('#device').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = '?action=dhcp&clear=&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_dhcp').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('DHCP Registrations', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
		<form id='form_dhcp' action='mikrotik.php?action=dhcp'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Device', 'mikrotik');?>
					</td>
					<td>
						<select id='device' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<?php
							$hosts = db_fetch_assoc('SELECT DISTINCT h.id, h.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host AS h
								ON hrs.host_id=h.id
								ORDER BY description');

							if (cacti_sizeof($hosts)) {
								foreach($hosts AS $h) {
									print "<option value='" . $h['id'] . "' " . (get_request_var('device') == $h['id'] ? 'selected':'') . '>' . $h['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Entries', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach($item_rows AS $key => $name) {
									print "<option value='" . $key . "' " . (get_request_var('rows') == $key ? 'selected':'') . '>' . $name . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='refresh' type='button' onClick='applyFilter()' value='<?php print __esc('Go', 'mikrotik');?>'>
							<input id='clear' type='button' onClick='clearFilter()' value='<?php print __esc('Clear', 'mikrotik');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	if (get_request_var('device') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' h.id=' . get_request_var('device');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . " (h.description LIKE '%" . get_request_var('filter') . "%' OR
			dhcp.hostname LIKE '%" . get_request_var('filter') . "%')";
	}

	$sql = "SELECT dhcp.*, h.description
		FROM plugin_mikrotik_dhcp AS dhcp
		INNER JOIN host AS h
		ON h.id=dhcp.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where
		$sql_order
		$sql_limit";

	//print $sql;

	$data_rows  = db_fetch_assoc($sql);

	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_dhcp AS dhcp
		INNER JOIN host AS h
		ON h.id=dhcp.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=h.id
		$sql_where");

	$display_text = array(
		'description'   => array('display' => __('Hostname', 'mikrotik'),      'sort' => 'ASC',  'align' => 'left'),
		'dhcp.hostname' => array('display' => __('Client Name', 'mikrotik'),   'sort' => 'ASC',  'align' => 'left'),
		'address'       => array('display' => __('IP Address', 'mikrotik'),    'sort' => 'ASC',  'align' => 'left'),
		'status'        => array('display' => __('Status', 'mikrotik'),        'sort' => 'ASC',  'align' => 'left'),
		'mac_address'   => array('display' => __('MAC Address', 'mikrotik'),   'sort' => 'ASC',  'align' => 'right'),
		'expires_after' => array('display' => __('Expires in', 'mikrotik'),    'sort' => 'DESC', 'align' => 'right'),
		'last_seen'     => array('display' => __('Last Seen', 'mikrotik'),     'sort' => 'DESC', 'align' => 'right'),
		'dynamic'       => array('display' => __('Type', 'mikrotik'),          'sort' => 'DESC', 'align' => 'right'),
		'blocked'       => array('display' => __('Blocked', 'mikrotik'),       'sort' => 'DESC', 'align' => 'right'),
		'disabled'      => array('display' => __('Disabled', 'mikrotik'),      'sort' => 'DESC', 'align' => 'right'),
		'last_updated'  => array('display' => __('Last Updated', 'mikrotik'),  'sort' => 'ASC',  'align' => 'right')
	);

	$nav = html_nav_bar('mikrotik.php?action=dhcp', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, sizeof($display_text), __('Entries', 'mikrotik'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'mikrotik.php?action=dhcp');

	if (cacti_sizeof($data_rows)) {
		foreach ($data_rows as $row) {
			form_alternate_row();

			print "<td class='left nowrap'>" . $row['description'] . '</td>';
			print "<td class='left'>"  . ($row['hostname'] != '' ? filter_value($row['hostname'], get_request_var('filter')):__('Unknown', 'mikrotik')) . '</td>';
			print "<td class='left'>"  . filter_value($row['address'], get_request_var('filter')) . '</td>';
			print "<td class='left'>"  . ($row['status'] ? $row['status']:__('N/A', 'mikrotik')) .  '</td>';
			print "<td class='right'>"  . filter_value($row['mac_address'], get_request_var('filter')) . '</td>';

			print "<td class='right'>" . ($row['expires_after'] ? __('%s Seconds', $row['expires_after']):__('N/A', 'mikrotik'))  . '</td>';
			print "<td class='right'>" . ($row['last_seen'] ? __('%s Seconds', $row['last_seen']):__('N/A', 'mikrotik'))  . '</td>';

			print "<td class='right'>" . ($row['dynamic'] ? __('Dynamic', 'mikrotik'):__('Static', 'mikrotik')) . '</td>';
			print "<td class='right'>" . ($row['blocked'] ? 'true':'false') . '</td>';
			print "<td class='right'>" . ($row['disabled'] ? 'true':'false') . '</td>';
			print "<td class='right'>" . $row['last_updated'] . '</td>';

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . sizeof($display_text) . '"><em>' . __('No DHCP Entries Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($data_rows)) {
		print $nav;
	}

	print '<script type="text/javascript">$(function() { $("a.hyperLink, img").tooltip(); });</script>';
}

