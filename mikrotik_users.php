<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2011 The Cacti Group                                 |
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

chdir("../..");
include("./include/auth.php");
include_once("./lib/api_data_source.php");
include_once("./lib/api_graph.php");
include_once("./lib/api_device.php");

define("MAX_DISPLAY_PAGES", 21);

$user_actions = array(
	1 => "Delete",
);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) {
case 'actions':
	form_actions();

	break;
default:
	include_once("./include/top_header.php");
	mikrotik_user();
	include_once("./include/bottom_footer.php");
	break;
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $colors, $user_actions, $fields_user_edit;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if ($_POST["drp_action"] == "1") { /* delete */
			if (!isset($_POST["delete_type"])) { $_POST["delete_type"] = 2; }

			$data_sources_to_act_on = array();
			$graphs_to_act_on       = array();
			$devices_to_act_on      = array();

			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				$selected_items[$i] = sanitize_search_string($selected_items[$i]);
				/* ==================================================== */

				$data_sources = db_fetch_assoc("SELECT
					data_local.id AS local_data_id
					FROM data_local
					WHERE " . array_to_sql_or($selected_items, "data_local.snmp_index") . "
					AND snmp_query_id='" . read_config_option("mikrotik_dq_users") . "'");

				if (sizeof($data_sources) > 0) {
				foreach ($data_sources as $data_source) {
					$data_sources_to_act_on[] = $data_source["local_data_id"];
				}
				}

				$graphs = db_fetch_assoc("SELECT
					graph_local.id AS local_graph_id
					FROM graph_local
					WHERE " . array_to_sql_or($selected_items, "graph_local.snmp_index") . "
					AND snmp_query_id='" . read_config_option("mikrotik_dq_users") . "'");

				if (sizeof($graphs) > 0) {
				foreach ($graphs as $graph) {
					$graphs_to_act_on[] = $graph["local_graph_id"];
				}
				}

				$devices_to_act_on[] = $selected_items[$i];
			}

			api_data_source_remove_multi($data_sources_to_act_on);

			api_graph_remove_multi($graphs_to_act_on);

			db_execute("DELETE FROM plugin_mikrotik_users WHERE name IN ('" . implode("','", $devices_to_act_on) . "')");
		}

		header("Location: mikrotik_users.php");
		exit;
	}

	/* setup some variables */
	$user_list = "";

	/* loop through each of the user templates selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (ereg("^chk_([A-Z0-9]+)$", $var, $matches)) {
			/* ================= input validation ================= */
			$matches[1] = sanitize_search_string($matches[1]);
			/* ==================================================== */

			$user_list .= "<li>" . $matches[1] . "</li>";
			$user_array[] = $matches[1];
		}
	}

	include_once("./include/top_header.php");

	html_start_box("<strong>" . $user_actions{$_POST["drp_action"]} . "</strong>", "60%", $colors["header_panel"], "3", "center", "");

	print "<form action='mikrotik_users.php' autocomplete='off' method='post'>\n";

	if (isset($user_array) && sizeof($user_array)) {
		if ($_POST["drp_action"] == "1") { /* delete */
			print "	<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>When you click \"Continue\" the following Users(s) and their Graph(s) will be deleted.</p>
						<ul>" . $user_list . "</ul>";
						print "</td></tr>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete Device(s)'>";
		}
	}else{
		print "<tr><td bgcolor='#" . $colors["form_alternate1"]. "'><span class='textError'>You must select at least one User.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='window.history.back()'>";
	}

	print "	<tr>
			<td colspan='2' align='right' bgcolor='#eaeaea'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($user_array) ? serialize($user_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . $_POST["drp_action"] . "'>
				$save_html
			</td>
		</tr>
		";

	html_end_box();

	include_once("./include/bottom_footer.php");
}

function mikrotik_user() {
	global $colors, $user_actions, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	input_validate_input_number(get_request_var_request("rows"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
	}

	/* clean up search string */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		kill_session_var("sess_mtu_current_page");
		kill_session_var("sess_mtu_filter");
		kill_session_var("sess_mtu_rows");
		kill_session_var("sess_mtu_sort_column");
		kill_session_var("sess_mtu_sort_direction");

		unset($_REQUEST["page"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["rows"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_mtu_current_page", "1");
	load_current_session_value("filter", "sess_mtu_filter", "");
	load_current_session_value("rows", "sess_mtu_rows", read_config_option("num_rows_device"));
	load_current_session_value("sort_column", "sess_mtu_sort_column", "name");
	load_current_session_value("sort_direction", "sess_mtu_sort_direction", "ASC");

	/* if the number of rows is -1, set it to the default */
	if ($_REQUEST["rows"] == -1) {
		$_REQUEST["rows"] = read_config_option("num_rows_device");
	}

	?>
	<script type="text/javascript">
	<!--

	function filterChange(objForm) {
		strURL = '?filter=' + objForm.filter.value;
		strURL = strURL + '&rows=' + objForm.rows.value;
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box("<strong>MikroTik Users</strong>", "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="users" action="mikrotik_users.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="20">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="filterChange(document.users)">
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td nowrap>
						&nbsp;<input type="submit" value="Go" title="Set/Refresh Filters">
					</td>
					<td nowrap>
						<input type="submit" name="clear_x" value="Clear" title="Clear Filters">
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
	if (strlen(get_request_var_request("filter"))) {
		$sql_where = "WHERE (name LIKE '%%" . get_request_var_request("filter") . "%%') AND name!=''";
	}else{
		$sql_where = "WHERE name!=''";
	}

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='mikrotik_users.php'>\n";

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	$total_rows = db_fetch_cell("SELECT 
		COUNT(DISTINCT name)
		FROM plugin_mikrotik_users
		$sql_where");

	$sortby = get_request_var_request("sort_column");

	$sql_query = "SELECT name, domain, MAX(last_seen) AS last_seen, MAX(present) AS present
		FROM plugin_mikrotik_users
		$sql_where
		GROUP BY name, domain
		ORDER BY " . $sortby . " " . get_request_var_request("sort_direction") . "
		LIMIT " . (get_request_var_request("rows")*(get_request_var_request("page")-1)) . "," . get_request_var_request("rows");

	$users = db_fetch_assoc($sql_query);

	/* generate page list */
	$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, get_request_var_request("rows"), $total_rows, "mikrotik_users.php?filter=" . get_request_var_request("filter"));

	if ($total_rows > 0) {
		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
				<td colspan='5'>
					<table width='100%' cellspacing='0' cellpadding='0' border='0'>
						<tr>
							<td align='left' class='textHeaderDark'>
								<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik_users.php?page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
							</td>
							<td align='center' class='textHeaderDark'>
								Showing Rows " . ((get_request_var_request("rows")*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < read_config_option("num_rows_device")) || ($total_rows < (get_request_var_request("rows")*get_request_var_request("page")))) ? $total_rows : (get_request_var_request("rows")*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
							</td>
							<td align='right' class='textHeaderDark'>
								<strong>"; if ((get_request_var_request("page") * get_request_var_request("rows")) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik_users.php?page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * get_request_var_request("rows")) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
							</td>
						</tr>
					</table>
				</td>
			</tr>\n";
	}else{
		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
				<td colspan='11'>
					<table width='100%' cellspacing='0' cellpadding='0' border='0'>
						<tr>
							<td align='center' class='textHeaderDark'>
								No Rows Found
							</td>
						</tr>
					</table>
				</td>
			</tr>\n";
	}

	print $nav;

	$display_text = array(
		"name" => array("User Name", "ASC"),
		"domain" => array("Domain", "ASC"),
		"last_seen" => array("Last Seen", "ASC"),
		"present" => array("Active", "ASC"));

	html_header_sort_checkbox($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);

	$i = 0;
	if (sizeof($users) > 0) {
		foreach ($users as $user) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $user["name"]); $i++;
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars("user.php?action=edit&id=" . $user["id"]) . "'>" . (strlen(get_request_var_request("filter")) ? eregi_replace("(" . preg_quote(get_request_var_request("filter")) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($user["name"])) : htmlspecialchars($user["name"])) . "</a>", $user["name"], 250);
			form_selectable_cell(($user["domain"] != '' ? $user["domain"]:"Not Set"), $user["name"]);
			form_selectable_cell($user["last_seen"], $user["name"]);
			form_selectable_cell(($user["present"] == 0 ? "<b><i>Inactive</i></b>":"<b><i>Active</i></b>"), $user["name"]);
			form_checkbox_cell($user["name"], $user["name"]);
			form_end_row();
		}

		/* put the nav bar on the bottom as well */
		print $nav;
	}else{
		print "<tr><td><em>No Users Found</em></td></tr>";
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($user_actions);

	print "</form>\n";
}

?>

