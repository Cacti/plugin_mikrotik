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

chdir('../..');
include('./include/auth.php');
include_once('./lib/api_data_source.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_device.php');

define('MAX_DISPLAY_PAGES', 21);

$user_actions = array(
	1 => 'Delete',
);

/* set default action */
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = ''; }

switch ($_REQUEST['action']) {
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
	input_validate_input_number(get_request_var_post('drp_action'));
	/* ==================================================== */

	/* if we are to save this form, instead of display it */
	if (isset($_POST['selected_items'])) {
		$selected_items = unserialize(stripslashes($_POST['selected_items']));

		if ($_POST['drp_action'] == '1') { /* delete */
			if (!isset($_POST['delete_type'])) { $_POST['delete_type'] = 2; }

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

				if (sizeof($data_sources) > 0) {
				foreach ($data_sources as $data_source) {
					$data_sources_to_act_on[] = $data_source['local_data_id'];
				}
				}

				$graphs = db_fetch_assoc('SELECT
					graph_local.id AS local_graph_id
					FROM graph_local
					WHERE ' . array_to_sql_or($selected_items, 'graph_local.snmp_index') . "
					AND snmp_query_id='" . mikrotik_data_query_by_hash('ce63249e6cc3d52bc69659a3f32194fe') . "'");

				if (sizeof($graphs) > 0) {
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

	html_start_box('<strong>' . $user_actions{$_POST['drp_action']} . '</strong>', '60%', '', '3', 'center', '');

	print "<form action='mikrotik_users.php' autocomplete='off' method='post'>\n";

	if (isset($user_array) && sizeof($user_array)) {
		if ($_POST['drp_action'] == '1') { /* delete */
			print "	<tr>
					<td class='textArea'>
						<p>Click 'Continue' to Delete the following Users(s) and their Graph(s).</p>
						<ul>" . $user_list . "</ul>";
						print "</td></tr>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='Continue' title='Delete Device(s)'>";
		}
	}else{
		print "<tr><td><span class='textError'>You must select at least one User.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='cactiReturnTo()'>";
	}

	print "<tr class='saveRow'>
		<td colspan='2' align='right' bgcolor='#eaeaea'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($user_array) ? serialize($user_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . $_POST["drp_action"] . "'>
			$save_html
		</td>
	</tr>\n";

	html_end_box();

	bottom_footer();
}

function mikrotik_user() {
	global $user_actions, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('page'));
	input_validate_input_number(get_request_var_request('rows'));
	input_validate_input_number(get_request_var_request('status'));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var('filter'));
	}

	/* clean up sort_column */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var('sort_column'));
	}

	/* clean up search string */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var('sort_direction'));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST['clear'])) {
		kill_session_var('sess_mtu_current_page');
		kill_session_var('sess_mtu_filter');
		kill_session_var('sess_mtu_status');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_mtu_sort_column');
		kill_session_var('sess_mtu_sort_direction');

		unset($_REQUEST['page']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['status']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
	}else{
		$changed = 0;
		$changed += check_changed('filter', 'sess_mtu_filter');
		$changed += check_changed('status', 'sess_mtu_status');
		$changed += check_changed('rows', 'sess_default_rows');
		if ($changed) {
			$_REQUEST['page'] = 1;
		}
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('page', 'sess_mtu_current_page', '1');
	load_current_session_value('filter', 'sess_mtu_filter', '');
	load_current_session_value('status', 'sess_mtu_status', '-1');
	load_current_session_value('rows', 'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value('sort_column', 'sess_mtu_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_mtu_sort_direction', 'ASC');

	/* if the number of rows is -1, set it to the default */
	if ($_REQUEST['rows'] == -1) {
		$_REQUEST['rows'] = read_config_option('num_rows_table');
	}

	?>
	<script type='text/javascript'>
	function applyFilter(objForm) {
		strURL  = 'mikrotik_users.php?filter=' + $('#filter').val();
		strURL += '&status=' + $('#status').val();
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

	html_start_box('<strong>MikroTik Users</strong>', '100%', '', '3', 'center', '');

	?>
	<tr class='even'>
		<td>
		<form id='users' action='mikrotik_users.php'>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print htmlspecialchars(get_request_var_request('filter'));?>'>
					</td>
					<td>
						Users
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('rows') == '-1') {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request('rows') == $key) { print ' selected'; } print '>' . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						Status
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('status') == '-1') {?> selected<?php }?>>All</option>
							<option value='1'<?php if (get_request_var_request('status') == '1') {?> selected<?php }?>>Active</option>
							<option value='2'<?php if (get_request_var_request('status') == '2') {?> selected<?php }?>>Inactive</option>
						</select>
					<td>
						<input type='button' value='Go' title='Set/Refresh Filters' onClick='applyFilter()'>
					</td>
					<td>
						<input type='button' name='clear_x' value='Clear' title='Clear Filters' onClick='clearFilter()'>
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var_request('filter'))) {
		$sql_where = "WHERE (name LIKE '%%" . get_request_var_request('filter') . "%%') AND name!=''";
	}else{
		$sql_where = "WHERE name!=''";
	}

	if (get_request_var_request('status') == 1) {
		$sql_where .= ' AND present=1';
	}elseif (get_request_var_request('status') == 2) {
		$sql_where .= ' AND present=0';
	}

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='mikrotik_users.php'>\n";

	html_start_box('', '100%', '', '3', 'center', '');

	$total_rows = db_fetch_cell("SELECT 
		COUNT(DISTINCT name)
		FROM plugin_mikrotik_users
		$sql_where");

	$sortby = get_request_var_request('sort_column');

	$sql_query = "SELECT name, domain, MAX(last_seen) AS last_seen, MAX(present) AS present
		FROM plugin_mikrotik_users
		$sql_where
		GROUP BY name, domain
		ORDER BY " . $sortby . ' ' . get_request_var_request('sort_direction') . '
		LIMIT ' . (get_request_var_request('rows')*(get_request_var_request('page')-1)) . ',' . get_request_var_request('rows');

	$users = db_fetch_assoc($sql_query);

	$nav = html_nav_bar('mikrotik_users.php?filter=' . get_request_var_request('filter'), MAX_DISPLAY_PAGES, get_request_var_request('page'), get_request_var_request('rows'), $total_rows, 5, 'Users', 'page', 'main');

	print $nav;

	$display_text = array(
		'name' => array('User Name', 'ASC'),
		'domain' => array('Domain', 'ASC'),
		'last_seen' => array('Last Seen', 'ASC'),
		'present' => array('Active', 'ASC'));

	html_header_sort_checkbox($display_text, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), false);

	$i = 0;
	if (sizeof($users) > 0) {
		foreach ($users as $user) {
			form_alternate_row_color($colors['alternate'], $colors['light'], $i, 'line' . $user['name']); $i++;
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars('user.php?action=edit&id=' . $user['id']) . "'>" . (strlen(get_request_var_request('filter')) ? eregi_replace('(' . preg_quote(get_request_var_request('filter')) . ')', "<span class='filteredValue'>\\1</span>", htmlspecialchars($user['name'])) : htmlspecialchars($user['name'])) . '</a>', $user['name'], 250);
			form_selectable_cell(($user['domain'] != '' ? $user['domain']:'Not Set'), $user['name']);
			form_selectable_cell($user['last_seen'], $user['name']);
			form_selectable_cell(($user['present'] == 0 ? '<b><i>Inactive</i></b>':'<b><i>Active</i></b>'), $user['name']);
			form_checkbox_cell($user['name'], $user['name']);
			form_end_row();
		}

		print $nav;
	}else{
		print '<tr><td><em>No Users Found</em></td></tr>';
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($user_actions);

	print "</form>\n";
}

?>

