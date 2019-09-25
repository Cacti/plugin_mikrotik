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

chdir('../..');
include('./include/auth.php');
include_once('./lib/utility.php');
include_once('./lib/api_data_source.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_device.php');

$user_actions = array(
	1 => __('Delete', 'mikrotik'),
);

set_default_action('');

switch (get_request_var('action')) {
case 'actions':
	form_actions();

	break;
default:
	top_header();
	mikrotik_user();
	bottom_footer();
	break;
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $colors, $user_actions, $fields_user_edit;

	/* ================= input validation ================= */
	get_filter_request_var('drp_action');
	/* ==================================================== */

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));

		if (get_request_var('drp_action') == '1') { /* delete */
			if (!isset_request_var('delete_type')) { set_request_var('delete_type', 2); }

			$data_sources_to_act_on = array();
			$graphs_to_act_on       = array();
			$devices_to_act_on      = array();

			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				$selected_items[$i] = sanitize_search_string($selected_items[$i]);
				/* ==================================================== */

				$data_sources = db_fetch_assoc('SELECT
					data_local.id AS local_data_id
					FROM data_local
					WHERE ' . array_to_sql_or($selected_items, 'data_local.snmp_index') . "
					AND snmp_query_id='" . mikrotik_data_query_by_hash('ce63249e6cc3d52bc69659a3f32194fe') . "'");

				if (cacti_sizeof($data_sources)) {
					foreach ($data_sources as $data_source) {
						$data_sources_to_act_on[] = $data_source['local_data_id'];
					}
				}

				$graphs = db_fetch_assoc('SELECT
					graph_local.id AS local_graph_id
					FROM graph_local
					WHERE ' . array_to_sql_or($selected_items, 'graph_local.snmp_index') . "
					AND snmp_query_id='" . mikrotik_data_query_by_hash('ce63249e6cc3d52bc69659a3f32194fe') . "'");

				if (cacti_sizeof($graphs)) {
					foreach ($graphs as $graph) {
						$graphs_to_act_on[] = $graph['local_graph_id'];
					}
				}

				$devices_to_act_on[] = $selected_items[$i];
			}

			api_data_source_remove_multi($data_sources_to_act_on);

			api_graph_remove_multi($graphs_to_act_on);

			db_execute("DELETE FROM plugin_mikrotik_users WHERE name IN ('" . implode("','", $devices_to_act_on) . "')");
		}

		header('Location: mikrotik_users.php?header=false');

		exit;
	}

	/* setup some variables */
	$user_list = '';

	/* loop through each of the user templates selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match('/^chk_([A-Z0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			$matches[1] = sanitize_search_string($matches[1]);
			/* ==================================================== */

			$user_list .= '<li>' . $matches[1] . '</li>';
			$user_array[] = $matches[1];
		}
	}

	top_header();

	html_start_box('<strong>' . $user_actions{get_request_var('drp_action')} . '</strong>', '60%', '', '3', 'center', '');

	print "<form action='mikrotik_users.php' autocomplete='off' method='post'>\n";

	if (isset($user_array) && sizeof($user_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "	<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Delete the following Users(s) and their Graph(s).', 'mikrotik') . "</p>
						<ul>" . $user_list . "</ul>";
						print "</td></tr>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='" . __esc('Cancel', 'mikrotik') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'mikrotik') . "' title='" . __esc('Delete Device(s)', 'mikrotik') . "'>";
		}
	} else {
		print "<tr><td><span class='textError'>" . __('You must select at least one User.', 'mikrotik') . "</span></td></tr>\n";
		$save_html = "<input type='button' value='" . __esc('Return', 'mikrotik') . "' onClick='cactiReturnTo()'>";
	}

	print "<tr class='saveRow'>
		<td colspan='2' align='right' bgcolor='#eaeaea'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($user_array) ? serialize($user_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var("drp_action") . "'>
			$save_html
		</td>
	</tr>\n";

	html_end_box();

	bottom_footer();
}

function mikrotik_user() {
	global $user_actions, $item_rows;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
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
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mtue');
    /* ================= input validation and session storage ================= */

	/* if the number of rows is -1, set it to the default */
	if (get_request_var('rows') != '-1') {
		$rows = get_request_var('rows');
	} else {
		$rows = read_config_option('num_rows_table');
	}

	?>
	<script type='text/javascript'>
	function applyFilter(objForm) {
		strURL  = 'mikrotik_users.php?filter=' + $('#filter').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'mikrotik_users.php?clear=1';
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#users').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('MikroTik Users', 'mikrotik'), '100%', '', '3', 'center', '');

	?>
	<tr class='even'>
		<td>
		<form id='users' action='mikrotik_users.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'mikrotik');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print htmlspecialchars(get_request_var('filter'));?>'>
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
						<?php print __('Users', 'mikrotik');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'mikrotik');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Status', 'mikrotik');?>
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('All', 'mikrotik');?></option>
							<option value='1'<?php if (get_request_var('status') == '1') {?> selected<?php }?>><?php print __('Active', 'mikrotik');?></option>
							<option value='2'<?php if (get_request_var('status') == '2') {?> selected<?php }?>><?php print __('Inactive', 'mikrotik');?></option>
						</select>
					<td>
						<span>
							<input id='refresh' type='button' value='<?php print __esc('Go', 'mikrotik');?>' title='<?php print __esc('Set/Refresh Filters', 'mikrotik');?>' onClick='applyFilter()'>
							<input id='clear' type='button' value='<?php print __esc('Clear', 'mikrotik');?>' title='<?php print __esc('Clear Filters', 'mikrotik');?>' onClick='clearFilter()'>
						<span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var('filter'))) {
		$sql_where = "WHERE (name LIKE '%%" . get_request_var('filter') . "%%') AND name!=''";
	} else {
		$sql_where = "WHERE name!=''";
	}

	if (get_request_var('status') == 1) {
		$sql_where .= ' AND present=1';
	} elseif (get_request_var('status') == 2) {
		$sql_where .= ' AND present=0';
	}

	if (get_request_var('type') == '0') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' userType=0';
	} elseif (get_request_var('type') == '1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' userType=1';
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(DISTINCT name)
		FROM plugin_mikrotik_users
		$sql_where");

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;

	$sql_query = "SELECT name, domain, userType, MAX(last_seen) AS last_seen, MAX(present) AS present
		FROM plugin_mikrotik_users
		$sql_where
		GROUP BY name, domain
		$sql_order
		$sql_limit";

	$users = db_fetch_assoc($sql_query);

	$nav = html_nav_bar('mikrotik_users.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 6, __('Users', 'mikrotik'), 'page', 'main');

	form_start('mikrotik_users.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	$display_text = array(
		'name'      => array(__('User Name', 'mikrotik'), 'ASC'),
		'domain'    => array(__('Domain', 'mikrotik'), 'ASC'),
		'type'      => array(__('Type', 'mikrotik'), 'ASC'),
		'last_seen' => array(__('Last Seen', 'mikrotik'), 'DESC'),
		'present'   => array(__('Active', 'mikrotik'), 'ASC'));

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($users)) {
		foreach ($users as $user) {
			form_alternate_row('line' . $user['name'], true);
			form_selectable_cell("<span class='noLinkEditMain'>" . filter_value($user['name'], get_request_var('filter')) . '</span>', $user['name'], 250);
			form_selectable_cell(($user['domain'] != '' ? $user['domain']:'Not Set'), $user['name']);
			form_selectable_cell(($user['userType'] == '0' ? 'Hotspot':'PPPoe'), $user['name']);
			form_selectable_cell($user['last_seen'], $user['name']);
			form_selectable_cell(($user['present'] == 0 ? '<b><i>' . __('Inactive', 'mikrotik') . '</i></b>':'<b><i>' . __('Active', 'mikrotik') . '</i></b>'), $user['name']);
			form_checkbox_cell($user['name'], $user['name']);
			form_end_row();
		}
	} else {
		print '<tr><td><em>' . __('No Users Found', 'mikrotik') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($users)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($user_actions);

	form_end();
}

