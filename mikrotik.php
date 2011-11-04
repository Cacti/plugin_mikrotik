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

chdir("../../");
include("./include/auth.php");

define("MAX_DISPLAY_PAGES", 21);

if (!isset($_REQUEST["action"])) {
	$_REQUEST["action"] = "devices";
}

include_once("./plugins/mikrotik/general_header.php");

$mikrotik_hrDeviceStatus = array(
	0 => "Present",
	1 => "Unknown",
	2 => "Running",
	3 => "Warning",
	4 => "Testing",
	5 => "Down"
);

mikrotik_tabs();

switch($_REQUEST["action"]) {
case "devices":
	mikrotik_devices();
	break;
case "trees":
	mikrotik_trees();
	break;
case "users":
	mikrotik_users();
	break;
case "graphs":
	mikrotik_view_graphs();
	break;
}
include_once("./include/bottom_footer.php");

function mikrotik_check_changed($request, $session) {
	if ((isset($_REQUEST[$request])) && (isset($_SESSION[$session]))) {
		if ($_REQUEST[$request] != $_SESSION[$session]) {
			return true;
		}
	}
}

function mikrotik_trees() {
	global $config, $colors, $item_rows, $mikrotik_trees;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	input_validate_input_number(get_request_var_request("rows"));
	input_validate_input_number(get_request_var_request("device"));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
	}

	/* clean up filter string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	if (isset($_REQUEST["reset"])) {
		kill_session_var("sess_mikrotik_trees_sort_column");
		kill_session_var("sess_mikrotik_trees_sort_direction");
		kill_session_var("sess_mikrotik_trees_filter");
		kill_session_var("sess_mikrotik_trees_rows");
		kill_session_var("sess_mikrotik_trees_device");
		kill_session_var("sess_mikrotik_trees_current_page");
	}elseif (isset($_REQUEST["clear"])) {
		kill_session_var("sess_mikrotik_trees_sort_column");
		kill_session_var("sess_mikrotik_trees_sort_direction");
		kill_session_var("sess_mikrotik_trees_filter");
		kill_session_var("sess_mikrotik_trees_rows");
		kill_session_var("sess_mikrotik_trees_device");
		kill_session_var("sess_mikrotik_trees_current_page");

		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["rows"]);
		unset($_REQUEST["device"]);
		unset($_REQUEST["page"]);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed("fitler",   "sess_mikrotik_trees_filter");
		$changed += mikrotik_check_changed("rows",     "sess_mikrotik_trees_rows");
		$changed += mikrotik_check_changed("device",   "sess_mikrotik_trees_device");
		if ($changed) {
			$_REQUEST["page"] = "1";
		}

	}

	load_current_session_value("page",           "sess_mikrotik_trees_current_page", "1");
	load_current_session_value("rows",           "sess_mikrotik_trees_rows", "-1");
	load_current_session_value("device",         "sess_mikrotik_trees_device", "-1");
	load_current_session_value("sort_column",    "sess_mikrotik_trees_sort_column", "name");
	load_current_session_value("sort_direction", "sess_mikrotik_trees_sort_direction", "ASC");
	load_current_session_value("filter",         "sess_mikrotik_trees_filter", "");

	?>
	<script type="text/javascript">
	<!--
	function applyFilter(objForm) {
		strURL = '?action=trees';
		strURL = strURL + '&filter='   + objForm.filter.value;
		strURL = strURL + '&rows='     + objForm.rows.value;
		strURL = strURL + '&device='   + objForm.device.value;
		document.location = strURL;
	}

	function clearRun() {
		strURL = '?action=trees&clear=';
		document.location = strURL;
	}
	-->
	</script>
	<?php

	html_start_box("<strong>Queue Tree Status</strong>", "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
			<form name="trees" method="get" action="mikrotik.php?action=trees">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Device:&nbsp;
					</td>
					<td width="1">
						<select name="device" onChange="applyFilter(document.trees)">
							<option value="-1"<?php if (get_request_var_request("device") == "-1") {?> selected<?php }?>>All</option>
							<?php
							$hosts = db_fetch_assoc("SELECT DISTINCT host.id, host.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host
								ON hrs.host_id=host.id
								ORDER BY description");

							if (sizeof($hosts)) {
							foreach($hosts AS $h) {
								echo "<option value='" . $h["id"] . "' " . (get_request_var_request("device") == $h["id"] ? "selected":"") . ">" . $h["description"] . "</option>";
							}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilter(document.trees)">
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
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Search:&nbsp;
					</td>
					<td>
						<input type='text' size='40' name='filter' value='<?php print get_request_var_request("filter");?>'>
					</td>
					<td nowrap>
						&nbsp;<input type="button" onClick="applyFilter(document.trees)" value="Go" border="0">
					</td>
					<td nowrap>
						<input type="button" onClick="clearRun()" value="Clear" name="clear" border="0">
					</td>
				</tr>
			</table>
			<input type='hidden' name='action' value='history'>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	if ($_REQUEST["rows"] == "-1") {
		$num_rows = read_config_option("num_rows_device");
	}else{
		$num_rows = get_request_var_request("rows");
	}

	$limit     = " LIMIT " . ($num_rows*(get_request_var_request("page")-1)) . "," . $num_rows;
	$sql_where = "WHERE hrswls.name!='' AND hrswls.name!='System Idle Process'";

	if ($_REQUEST["device"] != "-1") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.id=" . $_REQUEST["device"];
	}

	if ($_REQUEST["filter"] != "") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (host.description LIKE '%" . $_REQUEST["filter"] . "%' OR
			hrswls.name LIKE '%" . $_REQUEST["filter"] . "%' OR
			host.hostname LIKE '%" . $_REQUEST["filter"] . "%')";
	}

	$sql = "SELECT hrswls.*, host.hostname, host.description, host.disabled
		FROM plugin_mikrotik_trees AS hrswls
		INNER JOIN host 
		ON host.id=hrswls.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=host.id
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") . " " . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_trees AS hrswls
		INNER JOIN host
		ON host.id=hrswls.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=host.id
		$sql_where");

	if ($total_rows > 0) {
		/* generate page list */
		$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $num_rows, $total_rows, "mikrotik.php" . "?action=trees");

		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='16'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='left' class='textHeaderDark'>
							<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik.php" . "?action=trees&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
						</td>\n
						<td align='center' class='textHeaderDark'>
							Showing Rows " . (($num_rows*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < $num_rows) || ($total_rows < ($num_rows*get_request_var_request("page")))) ? $total_rows : ($num_rows*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
						</td>\n
						<td align='right' class='textHeaderDark'>
							<strong>"; if ((get_request_var_request("page") * $num_rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik.php" . "?action=trees&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $num_rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
						</td>\n
					</tr>
				</table>
			</td>
		</tr>\n";
	}else{
		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='16'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='center' class='textHeaderDark'>
							No Rows Found
						</td>\n
					</tr>
				</table>
			</td>
		</tr>\n";
	}

	print $nav;

	$display_text = array(
		"nosort"      => array("Actions",   array("", "left")),
		"description" => array("Hostname",   array("ASC",  "left")),
		"name"        => array("Name",       array("DESC", "left")),
		"flow"        => array("Flow",       array("DESC", "left")),
		"parentIndex" => array("Parent",     array("DESC", "left")),
		"bytes"       => array("Bits",       array("DESC", "right")),
		"packets"     => array("Packets",    array("DESC", "right")),
		"last_seen"   => array("Last Scan",  array("ASC",  "right"))
	);

	mikrotik_header_sort($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), "action=trees");

	$i = 0;
	if (sizeof($rows)) {
		foreach ($rows as $row) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i); $i++;
			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a href='" . htmlspecialchars($config["url_path"] . "host.php?action=edit&id=" . $row["host_id"]) . "' title='Edit Hosts'>" . $row["hostname"] . "</a>";
			}else{
				$host_url    = $row["hostname"];
			}
			
			echo "<td style='white-space:nowrap;' align='left' width='60'></td>";
			echo "<td style='white-space:nowrap;' align='left' width='200'><strong>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>",  $row["description"] . "</strong> [" . $host_url . "]"):$row["description"] . "</strong> [" . $host_url . "]") . "</td>";
			echo "<td style='white-space:nowrap;' align='left' width='100'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $row["name"]):$row["name"]) . "</td>";
			echo "<td style='white-space:nowrap;' align='left' width='100'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $row["flow"]):$row["flow"]) . "</td>";
			echo "<td style='white-space:nowrap;' align='left' width='100'>" . $row["parentIndex"] . "</td>";
			echo "<td style='white-space:nowrap;' align='right' width='100'>" . mikrotik_memory($row["bytes"]*8,'b/s') . "</td>";
			echo "<td style='white-space:nowrap;' align='right' width='100'>" . mikrotik_memory($row["packets"]*8,'b/s') . "</td>";
			echo "<td style='white-space:nowrap;' align='right' title='Time when last seen running' width='120'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $row["last_seen"]):$row["last_seen"]) . "</td>";
		}
		echo "</tr>";
		print $nav;
	}else{
		print "<tr><td><em>No Queue Trees Found</em></td></tr>";
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

	return $days . ":" . $hours . ":" . $minutes;
}

function mikrotik_users() {
	global $config, $colors, $item_rows, $mikrotik_hrSWTypes, $mikrotik_hrSWRunStatus;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	input_validate_input_number(get_request_var_request("rows"));
	input_validate_input_number(get_request_var_request("device"));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
	}

	/* clean up filter string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	/* clean up active string */
	if (isset($_REQUEST["active"])) {
		$_REQUEST["active"] = sanitize_search_string(get_request_var("active"));
	}

	if (isset($_REQUEST["reset"])) {
		kill_session_var("sess_mikrotik_users_sort_column");
		kill_session_var("sess_mikrotik_users_sort_direction");
		kill_session_var("sess_mikrotik_users_filter");
		kill_session_var("sess_mikrotik_users_active");
		kill_session_var("sess_mikrotik_users_rows");
		kill_session_var("sess_mikrotik_users_device");
		kill_session_var("sess_mikrotik_users_current_page");
	}elseif (isset($_REQUEST["clear"])) {
		kill_session_var("sess_mikrotik_users_sort_column");
		kill_session_var("sess_mikrotik_users_sort_direction");
		kill_session_var("sess_mikrotik_users_filter");
		kill_session_var("sess_mikrotik_users_active");
		kill_session_var("sess_mikrotik_users_rows");
		kill_session_var("sess_mikrotik_users_device");
		kill_session_var("sess_mikrotik_users_current_page");

		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["active"]);
		unset($_REQUEST["rows"]);
		unset($_REQUEST["device"]);
		unset($_REQUEST["page"]);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed("fitler",   "sess_mikrotik_users_filter");
		$changed += mikrotik_check_changed("active",   "sess_mikrotik_users_active");
		$changed += mikrotik_check_changed("rows",     "sess_mikrotik_users_rows");
		$changed += mikrotik_check_changed("device",   "sess_mikrotik_users_device");
		if ($changed) {
			$_REQUEST["page"] = "1";
		}

	}

	load_current_session_value("page",           "sess_mikrotik_users_current_page", "1");
	load_current_session_value("rows",           "sess_mikrotik_users_rows", "-1");
	load_current_session_value("device",         "sess_mikrotik_users_device", "-1");
	load_current_session_value("sort_column",    "sess_mikrotik_users_sort_column", "name");
	load_current_session_value("sort_direction", "sess_mikrotik_users_sort_direction", "ASC");
	load_current_session_value("filter",         "sess_mikrotik_users_filter", "");
	load_current_session_value("active",         "sess_mikrotik_users_active", "true");

	?>
	<script type="text/javascript">
	<!--
	function applyFilter(objForm) {
		strURL = '?action=users';
		strURL = strURL + '&filter='   + objForm.filter.value;
		strURL = strURL + '&rows='     + objForm.rows.value;
		strURL = strURL + '&active='   + objForm.active.checked;
		strURL = strURL + '&device='   + objForm.device.value;
		document.location = strURL;
	}

	function clearRun() {
		strURL = '?action=users&clear=';
		document.location = strURL;
	}
	-->
	</script>
	<?php

	html_start_box("<strong>User Statistics</strong>", "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
			<form name="users" method="get" action="mikrotik.php?action=users">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Device:&nbsp;
					</td>
					<td width="1">
						<select name="device" onChange="applyFilter(document.users)">
							<option value="-1"<?php if (get_request_var_request("device") == "-1") {?> selected<?php }?>>All</option>
							<?php
							$hosts = db_fetch_assoc("SELECT DISTINCT host.id, host.description
								FROM plugin_mikrotik_system AS hrs
								INNER JOIN host
								ON hrs.host_id=host.id
								ORDER BY description");

							if (sizeof($hosts)) {
							foreach($hosts AS $h) {
								echo "<option value='" . $h["id"] . "' " . (get_request_var_request("device") == $h["id"] ? "selected":"") . ">" . $h["description"] . "</option>";
							}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilter(document.users)">
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
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Search:&nbsp;
					</td>
					<td>
						<input type='text' size='40' name='filter' value='<?php print get_request_var_request("filter");?>'>
					</td>
					<td nowrap>
						<input type='checkbox' id='active' name='active'  onChange='applyFilter(document.users)' <?php print (get_request_var_request("active") == 'true' ? "checked":"");?>>
					</td>
					<td nowrap>
						<label for='active'>Active Users</label>
					</td>
					<td nowrap>
						&nbsp;<input type="button" onClick="applyFilter(document.users)" value="Go" border="0">
					</td>
					<td nowrap>
						<input type="button" onClick="clearRun()" value="Clear" name="clear" border="0">
					</td>
				</tr>
			</table>
			<input type='hidden' name='action' value='running'>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	if ($_REQUEST["rows"] == "-1") {
		$num_rows = read_config_option("num_rows_device");
	}else{
		$num_rows = get_request_var_request("rows");
	}

	$limit     = " LIMIT " . ($num_rows*(get_request_var_request("page")-1)) . "," . $num_rows;
	$sql_where = "WHERE hrswr.name!='' AND hrswr.name!='System Idle Process'";

	if ($_REQUEST["device"] != "-1") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.id=" . $_REQUEST["device"];
	}

	if ($_REQUEST["active"] == "true") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " present=1";
	}

	if ($_REQUEST["filter"] != "") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (host.description LIKE '%" . $_REQUEST["filter"] . "%' OR
			hrswr.name LIKE '%" . $_REQUEST["filter"] . "%' OR
			host.hostname LIKE '%" . $_REQUEST["filter"] . "%')";
	}

	$sql = "SELECT hrswr.*, host.hostname, host.description, host.disabled, bytesIn/connectTime AS avgBytesIn, bytesOut/connectTime AS avgBytesOut
		FROM plugin_mikrotik_users AS hrswr
		INNER JOIN host 
		ON host.id=hrswr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=host.id
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") . " " . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_users AS hrswr
		INNER JOIN host
		ON host.id=hrswr.host_id
		INNER JOIN plugin_mikrotik_system AS hrs
		ON hrs.host_id=host.id
		$sql_where");

	if ($total_rows > 0) {
		/* generate page list */
		$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $num_rows, $total_rows, "mikrotik.php" . "?action=users");

		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='16'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='left' class='textHeaderDark'>
							<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik.php" . "?action=users&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
						</td>\n
						<td align='center' class='textHeaderDark'>
							Showing Rows " . (($num_rows*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < $num_rows) || ($total_rows < ($num_rows*get_request_var_request("page")))) ? $total_rows : ($num_rows*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
						</td>\n
						<td align='right' class='textHeaderDark'>
							<strong>"; if ((get_request_var_request("page") * $num_rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik.php" . "?action=users&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $num_rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
						</td>\n
					</tr>
				</table>
			</td>
		</tr>\n";
	}else{
		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='16'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='center' class='textHeaderDark'>
							No Rows Found
						</td>\n
					</tr>
				</table>
			</td>
		</tr>\n";
	}

	print $nav;

	$display_text = array(
		"nosort"       => array("Actions",       array("", "left")),
		"description"  => array("Hostname",      array("ASC",  "left")),
		"name"         => array("User",          array("DESC", "left")),
		"domain"       => array("Domain",        array("ASC",  "left")),
		"ip"           => array("IP Address",    array("ASC",  "left")),
		"mac"          => array("MAC",           array("DESC", "left")),
		"connectTime"  => array("Connect Time",  array("DESC", "right")),
		"curBytesIn"   => array("Cur In",        array("DESC", "right")),
		"curBytesOut"  => array("Cur Out",       array("DESC", "right")),
		"avgBytesIn"   => array("Avg In",        array("DESC", "right")),
		"avgBytesOut"  => array("Avg Out",       array("DESC", "right")),
		"bytesIn"      => array("Total In",      array("DESC", "right")),
		"bytesOut"     => array("Total Out",     array("DESC", "right")),
		"last_seen"    => array("Last Scan",     array("ASC",  "right"))
	);

	mikrotik_header_sort($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), "action=users");

	$i = 0;
	if (sizeof($rows)) {
		foreach ($rows as $row) {
			if ($row["present"] == 1) {
				$days      = intval($row["connectTime"] / (60*60*24));
				$remainder = $row["connectTime"] % (60*60*24);
				$hours     = intval($remainder / (60*60));
				$remainder = $remainder % (60*60);
				$minutes   = intval($remainder / (60));
			}

			form_alternate_row_color($colors["alternate"], $colors["light"], $i); $i++;
			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a href='" . htmlspecialchars($config["url_path"] . "host.php?action=edit&id=" . $row["host_id"]) . "' title='Edit Hosts'>" . $row["hostname"] . "</a>";
			}else{
				$host_url    = $row["hostname"];
			}

			$graphdq = read_config_option("mikrotik_dq_users");
			if (!empty($graphdq)) {
				$graphsurl = mikrotik_get_graph_url($graphdq, $row["host_id"], $row["name"], $row["name"], true);
				echo "<td style='white-space:nowrap;' align='left' width='60'>$graphsurl</td>";
			}else{
				echo "<td style='white-space:nowrap;' align='left' width='60'></td>";
			}
			
			echo "<td style='white-space:nowrap;' align='left' width='200'><strong>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>",  $row["description"] . "</strong> [" . $host_url . "]"):$row["description"] . "</strong> [" . $host_url . "]") . "</td>";
			echo "<td style='white-space:nowrap;' align='left' width='100'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $row["name"]):$row["name"]) . "</td>";
			echo "<td style='white-space:nowrap;' align='left' title='" . $row["domain"] . "' width='100'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", title_trim($row["domain"],40)):title_trim($row["domain"],40)) . "</td>";
			if ($row["present"] == 1) {
				echo "<td style='white-space:nowrap;' align='left' title='" . $row["ip"] . "' width='100'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", title_trim($row["ip"], 40)):title_trim($row["ip"],40)) . "</td>";
				echo "<td style='white-space:nowrap;' align='left' title='" . $row["mac"] . "' width='100'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", title_trim($row["mac"], 40)):title_trim($row["mac"],40)) . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_format_uptime($days, $hours, $minutes) . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["curBytesIn"]*8, "b/s") . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["curBytesOut"]*8, "b/s") . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["avgBytesIn"]*8, "b/s") . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["avgBytesOut"]*8, "b/s") . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["bytesIn"]*8, "b") . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["bytesOut"]*8, "b") . "</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . $row["last_seen"] . "</td>";
			}else{
				echo "<td style='white-space:nowrap;' align='left'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='left'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='left'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='left'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='right'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='right'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='right'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='right'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='right'>N/A</td>";
				echo "<td style='white-space:nowrap;' align='right'>" . $row["last_seen"] . "</td>";
			}
		}
		echo "</tr>";
		print $nav;
	}else{
		print "<tr><td><em>No Users Found</em></td></tr>";
	}

	html_end_box();
}

function mikrotik_devices() {
	global $config, $colors, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("status"));
	input_validate_input_number(get_request_var_request("page"));
	input_validate_input_number(get_request_var_request("rows"));
	/* ==================================================== */

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
	}

	/* clean up sort_direction */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
	}

	/* clean up filter string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	if (isset($_REQUEST["reset"])) {
		kill_session_var("sess_mikrotik_device_sort_column");
		kill_session_var("sess_mikrotik_device_sort_direction");
		kill_session_var("sess_mikrotik_device_status");
		kill_session_var("sess_mikrotik_device_filter");
		kill_session_var("sess_mikrotik_device_rows");
		kill_session_var("sess_mikrotik_device_current_page");
	}elseif (isset($_REQUEST["clear"])) {
		kill_session_var("sess_mikrotik_device_sort_column");
		kill_session_var("sess_mikrotik_device_sort_direction");
		kill_session_var("sess_mikrotik_device_status");
		kill_session_var("sess_mikrotik_device_filter");
		kill_session_var("sess_mikrotik_device_rows");
		kill_session_var("sess_mikrotik_device_current_page");

		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
		unset($_REQUEST["status"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["rows"]);
		unset($_REQUEST["page"]);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed("status",   "sess_mikrotik_device_status");
		$changed += mikrotik_check_changed("fitler",   "sess_mikrotik_device_filter");
		$changed += mikrotik_check_changed("rows",     "sess_mikrotik_device_rows");

		if ($changed) {
			$_REQUEST["page"] = "1";
		}
	}

	load_current_session_value("page",           "sess_mikrotik_device_current_page", "1");
	load_current_session_value("rows",           "sess_mikrotik_device_rows", "-1");
	load_current_session_value("sort_column",    "sess_mikrotik_device_sort_column", "description");
	load_current_session_value("sort_direction", "sess_mikrotik_device_sort_direction", "ASC");
	load_current_session_value("status",         "sess_mikrotik_device_status", "-1");
	load_current_session_value("filter",         "sess_mikrotik_device_filter", "");

	?>
	<script type="text/javascript">
	<!--
	function applyHostFilter(objForm) {
		strURL = '?action=devices';
		strURL = strURL + '&status='   + objForm.status.value;
		strURL = strURL + '&filter='   + objForm.filter.value;
		strURL = strURL + '&rows='     + objForm.rows.value;
		document.location = strURL;
	}

	function clearHosts() {
		strURL = '?action=devices&clear=';
		document.location = strURL;
	}
	-->
	</script>
	<?php

	html_start_box("<strong>Device Filter</strong>", "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
			<form name="devices" action="mikrotik.php?action=devices">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Status:&nbsp;
					</td>
					<td width="1">
						<select name="status" onChange="applyHostFilter(document.devices)">
							<option value="-1"<?php if (get_request_var_request("type") == "-1") {?> selected<?php }?>>All</option>
							<?php
							$statuses = db_fetch_assoc("SELECT DISTINCT status
								FROM host
								INNER JOIN plugin_mikrotik_system
								ON host.id=plugin_mikrotik_system.host_id");
							$statuses = array_merge($statuses, array("-2" => array("status" => "-2")));

							if (sizeof($statuses)) {
							foreach($statuses AS $s) {
								switch($s["status"]) {
									case "0":
										$status = "Unknown";
										break;
									case "1":
										$status = "Down";
										break;
									case "2":
										$status = "Recovering";
										break;
									case "3":
										$status = "Up";
										break;
									case "-2":
										$status = "Disabled";
										break;
								}
								echo "<option value='" . $s["status"] . "' " . (get_request_var_request("status") == $s["status"] ? "selected":"") . ">" . $status . "</option>";
							}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyHostFilter(document.devices)">
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
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Search:&nbsp;
					</td>
					<td>
						<input type='text' size='40' name='filter' value='<?php print get_request_var_request("filter");?>'>
					</td>
					<td nowrap>
						<input type="button" onClick="applyHostFilter(document.devices)" value="Go" border="0">
					</td>
					<td nowrap>
						<input type="button" onClick="clearHosts()" value="Clear" name="clear" border="0">
					</td>
				</tr>
			</table>
			<input type='hidden' name='action' value='devices'>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	if ($_REQUEST["rows"] == "-1") {
		$num_rows = read_config_option("num_rows_device");
	}else{
		$num_rows = get_request_var_request("rows");
	}

	$limit     = " LIMIT " . ($num_rows*(get_request_var_request("page")-1)) . "," . $num_rows;
	$sql_where = "";

	if ($_REQUEST["status"] != "-1") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " hrs.host_status=" . $_REQUEST["status"];
	}

	$sql_join = "";

	if ($_REQUEST["filter"] != "") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.description LIKE '%" . $_REQUEST["filter"] . "%' OR
			host.hostname LIKE '%" . $_REQUEST["filter"] . "%'";
	}

	$sql = "SELECT hrs.*, host.hostname, host.description, host.disabled
		FROM plugin_mikrotik_system AS hrs
		INNER JOIN host ON host.id=hrs.host_id
		$sql_join
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") . " " . $limit;

	//echo $sql;

	$rows       = db_fetch_assoc($sql);
	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM plugin_mikrotik_system AS hrs
		INNER JOIN host ON host.id=hrs.host_id
		$sql_join
		$sql_where");

	if ($total_rows > 0) {
		/* generate page list */
		$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $num_rows, $total_rows, "mikrotik.php" . "?action=devices");

		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='16'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='left' class='textHeaderDark'>
							<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik.php" . "?action=devices&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
						</td>\n
						<td align='center' class='textHeaderDark'>
							Showing Rows " . (($num_rows*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < $num_rows) || ($total_rows < ($num_rows*get_request_var_request("page")))) ? $total_rows : ($num_rows*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
						</td>\n
						<td align='right' class='textHeaderDark'>
							<strong>"; if ((get_request_var_request("page") * $num_rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("mikrotik.php" . "?action=devices&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $num_rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
						</td>\n
					</tr>
				</table>
			</td>
		</tr>\n";
	}else{
		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='16'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='center' class='textHeaderDark'>
							No Rows Found
						</td>\n
					</tr>
				</table>
			</td>
		</tr>\n";
	}

	print $nav;

	$display_text = array(
		"nosort"      => array("Actions",    array("ASC",  "left")),
		"description" => array("Name",       array("ASC",  "left")),
		"host_status" => array("Status",     array("DESC", "right")),
		"uptime"      => array("Uptime(d:h:m)",     array("DESC", "right")),
		"users"       => array("Users",      array("DESC", "right")),
		"cpuPercent"  => array("CPU %",      array("DESC", "right")),
		"numCpus"     => array("CPUs",       array("DESC", "right")),
		"processes"   => array("Processes",  array("DESC", "right")),
		"memSize"     => array("Total Mem",  array("DESC", "right")),
		"memUsed"     => array("Used Mem",   array("DESC", "right")),
		"diskSize"    => array("Total Disk", array("DESC", "right")),
		"diskUsed"    => array("Used Disk",  array("DESC", "right")),

	);

	mikrotik_header_sort($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), "action=devices");

	/* set some defaults */
	$url       = $config["url_path"] . "plugins/mikrotik/mikrotik.php";
	$users     = $config["url_path"] . "plugins/mikrotik/images/view_users.gif";
	$host      = $config["url_path"] . "plugins/mikrotik/images/view_hosts.gif";
	$trees     = $config["url_path"] . "plugins/mikrotik/images/view_trees.gif";
	$dashboard = $config["url_path"] . "plugins/mikrotik/images/view_dashboard.gif";
	$graphs    = $config["url_path"] . "plugins/mikrotik/images/view_graphs.gif";
	$nographs  = $config["url_path"] . "plugins/mikrotik/images/view_graphs_disabled.gif";

	$hcpudq  = read_config_option("mikrotik_dq_host_cpu");

	$i = 0;
	if (sizeof($rows)) {
		foreach ($rows as $row) {
			$days      = intval($row["uptime"] / (60*60*24*100));
			$remainder = $row["uptime"] % (60*60*24*100);
			$hours     = intval($remainder / (60*60*100));
			$remainder = $remainder % (60*60*100);
			$minutes   = intval($remainder / (60*100));

			$found = db_fetch_cell("SELECT COUNT(*) FROM graph_local WHERE host_id=" . $row["host_id"]);

			form_alternate_row_color($colors["alternate"], $colors["light"], $i); $i++;
			echo "<td width='100' align='left'>";
			//echo "<a style='padding:1px;' href='" . htmlspecialchars("$url?action=dashboard&reset=1&device=" . $row["host_id"]) . "'><img src='$dashboard' title='View Dashboard' align='absmiddle' border='0'></a>";
			echo "<a style='padding:1px;' href='" . htmlspecialchars("$url?action=users&reset=1&device=" . $row["host_id"]) . "'><img src='$users' title='View Users' align='absmiddle' border='0'></a>";
			echo "<a style='padding:1px;' href='" . htmlspecialchars("$url?action=trees&reset=1&device=" . $row["host_id"]) . "'><img src='$trees' title='View Queue Trees' align='absmiddle' border='0'></a>";
			if ($found) {
				echo "<a style='padding:1px;' href='" . htmlspecialchars("$url?action=graphs&reset=1&host=" . $row["host_id"] . "&style=selective&graph_add=&graph_list=&graph_template_id=0&filter=") . "'><img  src='$graphs' title='View Graphs' align='absmiddle' border='0'></a>";
			}else{
				echo "<img src='$nographs' title='No Graphs Defined' align='absmiddle' border='0'>";
			}

			$graph_cpu   = mikrotik_get_graph_url($hcpudq, $row["host_id"], "", $row["numCpus"], false);
			$graph_cpup  = mikrotik_get_graph_template_url(read_config_option("mikrotik_gt_cpu"), $row["host_id"], round($row["cpuPercent"],2), false);
			$graph_users = mikrotik_get_graph_template_url(read_config_option("mikrotik_gt_users"), $row["host_id"], $row["users"], false);
			$graph_aproc = mikrotik_get_graph_template_url(read_config_option("mikrotik_gt_processes"), $row["host_id"], ($row["host_status"] < 2 ? "N/A":$row["processes"]), false);
			$graph_disk  = mikrotik_get_graph_template_url(read_config_option("mikrotik_gt_disk"), $row["host_id"], ($row["host_status"] < 2 ? "N/A":round($row["diskUsed"],2)), false);
			$graph_mem   = mikrotik_get_graph_template_url(read_config_option("mikrotik_gt_memory"), $row["host_id"], ($row["host_status"] < 2 ? "N/A":round($row["memUsed"],2)), false);
			$graph_upt   = mikrotik_get_graph_template_url(read_config_option("mikrotik_gt_uptime"), $row["host_id"], ($row["host_status"] < 2 ? "N/A":mikrotik_format_uptime($days, $hours, $minutes)), false);


			if (api_plugin_user_realm_auth('host.php')) {
				$host_url    = "<a href='" . htmlspecialchars($config["url_path"] . "host.php?action=edit&id=" . $row["host_id"]) . "' title='Edit Hosts'>" . $row["hostname"] . "</a>";
			}else{
				$host_url    = $row["hostname"];
			}

			echo "</td>";
			echo "<td style='white-space:nowrap;' align='left' width='200'><strong>" . $row["description"] . "</strong> [" . $host_url . "]" . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . get_colored_device_status(($row["disabled"] == "on" ? true : false), $row["host_status"]) . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . $graph_upt . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . $graph_users . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . ($row["host_status"] < 2 ? "N/A":$graph_cpup) . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . ($row["host_status"] < 2 ? "N/A":$graph_cpu) . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . $graph_aproc . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["memSize"]) . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . $graph_mem . " %</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . mikrotik_memory($row["diskSize"]) . "</td>";
			echo "<td style='white-space:nowrap;' align='right'>" . $graph_disk . " %</td>";
		}
		echo "</tr>";
		print $nav;
	}else{
		print "<tr><td><em>No Devices Found</em></td></tr>";
	}

	html_end_box();
}

function mikrotik_format_uptime($d, $h, $m) {
	return ($d > 0 ? mikrotik_right("000" . $d, 3, true) . "d ":"") . mikrotik_right("000" . $h, 2) . "h " . mikrotik_right("000" . $m, 2) . "m";
}

function mikrotik_right($string, $chars, $strip = false) {
	if ($strip) {
		return ltrim(strrev(substr(strrev($string), 0, $chars)),'0');
	}else{
		return strrev(substr(strrev($string), 0, $chars));
	}
}

function mikrotik_memory($mem, $suffix = "") {
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

function mikrotik_tabs() {
	global $config;

	/* present a tabbed interface */
	$tabs = array(
		"devices"  => "Devices",
		"users"    => "Users",
		"trees"    => "Queue Trees",
		"graphs"   => "Graphs");

	/* set the default tab */
	$current_tab = $_REQUEST["action"];

	/* draw the tabs */
	print "<table class='tabs' width='100%' cellspacing='0' cellpadding='3' align='center'><tr>\n";

	if (sizeof($tabs)) {
		foreach (array_keys($tabs) as $tab_short_name) {
			print "<td style='padding:3px 10px 2px 5px;background-color:" . (($tab_short_name == $current_tab) ? "silver;" : "#DFDFDF;") .
				"white-space:nowrap;'" .
				" nowrap width='1%'" .
				" align='center' class='tab'>
				<span class='textHeader'><a href='" . htmlspecialchars($config['url_path'] .
				"plugins/mikrotik/mikrotik.php?" .
				"action=" . $tab_short_name .
				(isset($_REQUEST["host_id"]) ? "&host_id=" . $_REQUEST["host_id"]:"")) .
				"'>$tabs[$tab_short_name]</a></span>
			</td>\n
			<td width='1'></td>\n";
		}
	}
	print "<td></td><td></td>\n</tr></table>\n";
}

function mikrotik_get_device_status_url($count, $status) {
	global $config;

	if ($count > 0) {
		return "<a href='" . htmlspecialchars($config["url_path"] . "plugins/mikrotik/mikrotik.php?action=devices&reset=1&status=$status") . "' title='View Hosts'>$count</a>";
	}else{
		return $count;
	}
}

function mikrotik_get_graph_template_url($graph_template, $host_id = 0, $title = "", $image = true) {
	global $config;

	$url     = $config["url_path"] . "plugins/mikrotik/mikrotik.php";
	$nograph = $config["url_path"] . "plugins/mikrotik/images/view_graphs_disabled.gif";
	$graph   = $config["url_path"] . "plugins/mikrotik/images/view_graphs.gif";

	if (!empty($graph_template)) {
		if ($host_id > 0) {
			$sql_join  = "";
			$sql_where = "AND gl.host_id=$host_id";
		} else {
			$sql_join  = "";
			$sql_where = "";
		}

		$graphs = db_fetch_assoc("SELECT gl.* FROM graph_local AS gl
			$sql_join
			WHERE gl.graph_template_id=$graph_template
			$sql_where");

		$graph_add = "";
		if (sizeof($graphs)) {
		foreach($graphs as $graph) {
			$graph_add .= (strlen($graph_add) ? ",":"") . $graph["id"];
		}
		}

		if (sizeof($graphs)) {
			if ($image) {
				return "<a href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'><img border='0' src='" . $graph . "'></a>";
			}else{
				return "<a href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'>$title</a>";
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

function mikrotik_get_graph_url($data_query, $host_id, $index, $title = "", $image = true) {
	global $config;

	$url     = $config["url_path"] . "plugins/mikrotik/mikrotik.php";
	$nograph = $config["url_path"] . "plugins/mikrotik/images/view_graphs_disabled.gif";
	$graph   = $config["url_path"] . "plugins/mikrotik/images/view_graphs.gif";

	$hsql = "";
	$hstr = "";

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
				return "<a href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'><img border='0' align='absmiddle' src='" . $graph . "'></a>";
			}else{
				return "<a href='" . htmlspecialchars($url . "?action=graphs&reset=1&style=selective&graph_add=$graph_add&graph_list=&graph_template_id=0&filter=") . "' title='View Graphs'>$title</a>";
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

/* mikrotik_header_sort - draws a header row suitable for display inside of a box element.  When
     a user selects a column header, the collback function "filename" will be called to handle
     the sort the column and display the altered results.
   @arg $header_items - an array containing a list of column items to display.  The
        format is similar to the html_header, with the exception that it has three
        dimensions associated with each element (db_column => display_text, default_sort_order)
   @arg $sort_column - the value of current sort column.
   @arg $sort_direction - the value the current sort direction.  The actual sort direction
        will be opposite this direction if the user selects the same named column.
   @arg $jsprefix - a prefix to properly apply the sort direction to the right page */
function mikrotik_header_sort($header_items, $sort_column, $sort_direction, $jsprefix, $last_item_colspan = 1) {
	global $colors;

	static $count = 0;

	/* reverse the sort direction */
	if ($sort_direction == "ASC") {
		$new_sort_direction = "DESC";
	}else{
		$new_sort_direction = "ASC";
	}

	?>
	<tr style='display:none;'><td>
	<script type="text/javascript">
	<!--
	function sortMe<?php print "_$count";?>(sort_column, sort_direction) {
		strURL = '?<?php print (strlen($jsprefix) ? $jsprefix:"");?>';
		strURL = strURL + '&sort_direction='+sort_direction;
		strURL = strURL + '&sort_column='+sort_column;
		document.location = strURL;
	}
	-->
	</script>
	</td></tr>
	<?php

	print "<tr bgcolor='#" . $colors["header_panel"] . "'>\n";

	$i = 1;
	foreach ($header_items as $db_column => $display_array) {
		/* by default, you will always sort ascending, with the exception of an already sorted column */
		if ($sort_column == $db_column) {
			$direction = $new_sort_direction;
			if (is_array($display_array[1])) {
				$align        = " align='" . $display_array[1][1] . "'";
				$talign       =  $display_array[1][1];
				$display_text = "**" . $display_array[0];
			}else{
				$align        = " align='left'";
				$talign    = "left";
				$display_text = $display_array[0] . "**";
			}
		}else{
			$display_text = $display_array[0];
			if (is_array($display_array[1])) {
				$align     = "align='" . $display_array[1][1] . "'";
				$talign    =  $display_array[1][1];
				$direction = $display_array[1][0];
			}else{
				$align     = " align='left'";
				$talign    = "left";
				$direction = $display_array[1];
			}
		}

		if (($db_column == "") || (substr_count($db_column, "nosort"))) {
			print "<th class='tableSubHeaderColumn' style='vertical-align:bottom;' $align " . ((($i) == count($header_items)) ? "colspan='$last_item_colspan'>" : ">");
			print "<span style='cursor:pointer;display:block;text-align:$talign;' class='textSubHeaderDark'>" . $display_text . "</span>";
			print "</th>\n";
		}else{
			print "<th class='tableSubHeaderColumn' style='vertical-align:bottom;' $align " . ((($i) == count($header_items)) ? "colspan='$last_item_colspan'>" : ">");
			print "<span style='cursor:pointer;display:block;text-align:$talign;' class='textSubHeaderDark' onClick='sortMe_" . $count . "(\"" . $db_column . "\", \"" . $direction . "\")'>" . $display_text . "</span>";
			print "</th>\n";
		}

		$i++;
	}

	$count++;

	print "</tr>\n";
}

function mikrotik_view_graphs() {
	global $current_user, $colors, $config;

	if (file_exists("./lib/timespan_settings.php")) {
		include("./lib/timespan_settings.php");
	}else{
		include("./include/html/inc_timespan_settings.php");
	}

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("rra_id"));
	input_validate_input_number(get_request_var("host"));
	input_validate_input_number(get_request_var("cols"));
	input_validate_input_regex(get_request_var_request('graph_list'), "^([\,0-9]+)$");
	input_validate_input_regex(get_request_var_request('graph_add'), "^([\,0-9]+)$");
	input_validate_input_regex(get_request_var_request('graph_remove'), "^([\,0-9]+)$");
	/* ==================================================== */

	define("ROWS_PER_PAGE", read_graph_config_option("preview_graphs_per_page"));

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("graph_template_id"));
	input_validate_input_number(get_request_var_request("page"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
	}

	/* clean up styl string */
	if (isset($_REQUEST["style"])) {
		$_REQUEST["style"] = sanitize_search_string(get_request_var_request("style"));
	}

	/* clean up styl string */
	if (isset($_REQUEST["thumb"])) {
		$_REQUEST["thumb"] = sanitize_search_string(get_request_var_request("thumb"));
	}

	$sql_or = ""; $sql_where = ""; $sql_join = "";

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["reset"])) {
		kill_session_var("sess_mikrotik_graph_current_page");
		kill_session_var("sess_mikrotik_graph_filter");
		kill_session_var("sess_mikrotik_graph_host");
		kill_session_var("sess_mikrotik_graph_cols");
		kill_session_var("sess_mikrotik_graph_thumb");
		kill_session_var("sess_mikrotik_graph_add");
		kill_session_var("sess_mikrotik_graph_style");
		kill_session_var("sess_mikrotik_graph_graph_template");
	}elseif (isset($_REQUEST["clear"])) {
		kill_session_var("sess_mikrotik_graph_current_page");
		kill_session_var("sess_mikrotik_graph_filter");
		kill_session_var("sess_mikrotik_graph_host");
		kill_session_var("sess_mikrotik_graph_cols");
		kill_session_var("sess_mikrotik_graph_thumb");
		kill_session_var("sess_mikrotik_graph_add");
		kill_session_var("sess_mikrotik_graph_style");
		kill_session_var("sess_mikrotik_graph_graph_template");

		unset($_REQUEST["page"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["host"]);
		unset($_REQUEST["cols"]);
		unset($_REQUEST["thumb"]);
		unset($_REQUEST["graph_template_id"]);
		unset($_REQUEST["graph_list"]);
		unset($_REQUEST["graph_add"]);
		unset($_REQUEST["style"]);
		unset($_REQUEST["graph_remove"]);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += mikrotik_check_changed("fitler",            "sess_mikrotik_graph_filter");
		$changed += mikrotik_check_changed("host",              "sess_mikrotik_graph_host");
		$changed += mikrotik_check_changed("style",             "sess_mikrotik_graph_style");
		$changed += mikrotik_check_changed("graph_add",         "sess_mikrotik_graph_add");
		$changed += mikrotik_check_changed("graph_template_id", "sess_mikrotik_graph_graph_template");

		if ($changed) {
			$_REQUEST["page"]      = "1";
			$_REQUEST["style"]     = "";
			$_REQUEST["graph_add"] = "";
		}

	}

	load_current_session_value("graph_template_id", "sess_mikrotik_graph_graph_template", "0");
	load_current_session_value("host",              "sess_mikrotik_graph_host", "0");
	load_current_session_value("cols",              "sess_mikrotik_graph_cols", "2");
	load_current_session_value("thumb",             "sess_mikrotik_graph_thumb", "true");
	load_current_session_value("graph_add",         "sess_mikrotik_graph_add", "");
	load_current_session_value("style",             "sess_mikrotik_graph_style", "");
	load_current_session_value("filter",            "sess_mikrotik_graph_filter", "");
	load_current_session_value("page",              "sess_mikrotik_graph_current_page", "1");

	if ($_REQUEST["graph_add"] != "") {
		$_REQUEST["style"] = "selective";
	}

	/* graph permissions */
	if (read_config_option("auth_method") != 0) {
		$sql_where = "WHERE " . get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_hosts"], $current_user["policy_graph_templates"]);

		$sql_join = "LEFT JOIN host ON (host.id=graph_local.host_id)
			LEFT JOIN graph_templates
			ON (graph_templates.id=graph_local.graph_template_id)
			LEFT JOIN user_auth_perms
			ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id
			AND user_auth_perms.type=1
			AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ")
			OR (host.id=user_auth_perms.item_id AND user_auth_perms.type=3 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ")
			OR (graph_templates.id=user_auth_perms.item_id AND user_auth_perms.type=4 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))";
	}else{
		$sql_where = "";
		$sql_join = "";
	}

	/* the user select a bunch of graphs of the 'list' view and wants them dsplayed here */
	if (isset($_REQUEST["style"])) {
		if ($_REQUEST["style"] == "selective") {

			/* process selected graphs */
			if (!empty($_REQUEST["graph_list"])) {
				foreach (explode(",",$_REQUEST["graph_list"]) as $item) {
					$graph_list[$item] = 1;
				}
			}else{
				$graph_list = array();
			}
			if (!empty($_REQUEST["graph_add"])) {
				foreach (explode(",",$_REQUEST["graph_add"]) as $item) {
					$graph_list[$item] = 1;
				}
			}
			/* remove items */
			if (!empty($_REQUEST["graph_remove"])) {
				foreach (explode(",",$_REQUEST["graph_remove"]) as $item) {
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
				$sql_or = "AND " . array_to_sql_or($graph_array, "graph_templates_graph.local_graph_id");

				/* clear the filter vars so they don't affect our results */
				$_REQUEST["filter"]  = "";

				$set_rra_id = empty($rra_id) ? read_graph_config_option("default_rra_id") : $_REQUEST["rra_id"];
			}
		}
	}

	$sql_base = "FROM (graph_templates_graph,graph_local)
		$sql_join
		$sql_where
		" . (empty($sql_where) ? "WHERE" : "AND") . "   graph_templates_graph.local_graph_id > 0
		AND graph_templates_graph.local_graph_id=graph_local.id
		" . (strlen($_REQUEST["filter"]) ? "AND graph_templates_graph.title_cache like '%%" . $_REQUEST["filter"] . "%%'":"") . "
		" . (empty($_REQUEST["graph_template_id"]) ? "" : " and graph_local.graph_template_id=" . $_REQUEST["graph_template_id"]) . "
		" . (empty($_REQUEST["host"]) ? "" : " and graph_local.host_id=" . $_REQUEST["host"]) . "
		$sql_or";

	$total_rows = count(db_fetch_assoc("SELECT
		graph_templates_graph.local_graph_id
		$sql_base"));

	/* reset the page if you have changed some settings */
	if (ROWS_PER_PAGE * ($_REQUEST["page"]-1) >= $total_rows) {
		$_REQUEST["page"] = "1";
	}

	$graphs = db_fetch_assoc("SELECT
		graph_templates_graph.local_graph_id,
		graph_templates_graph.title_cache
		$sql_base
		GROUP BY graph_templates_graph.local_graph_id
		ORDER BY graph_templates_graph.title_cache
		LIMIT " . (ROWS_PER_PAGE*($_REQUEST["page"]-1)) . "," . ROWS_PER_PAGE);

	?>
	<script type="text/javascript">
	<!--
	function applyGraphWReset(objForm) {
		strURL = '?action=graphs&reset=1&graph_template_id=' + objForm.graph_template_id.value;
		strURL = strURL + '&host=' + objForm.host.value;
		strURL = strURL + '&cols=' + objForm.cols.value;
		strURL = strURL + '&thumb=' + objForm.thumb.checked;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}
	function applyGraphWOReset(objForm) {
		strURL = '?action=graphs&graph_template_id=' + objForm.graph_template_id.value;
		strURL = strURL + '&host=' + objForm.host.value;
		strURL = strURL + '&cols=' + objForm.cols.value;
		strURL = strURL + '&thumb=' + objForm.thumb.checked;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}
	-->
	</script>
	<?php

	html_start_box("<strong>Host MIB Graphs" . ($_REQUEST["style"] == "selective" ? " (Custom Selective Filter)":"") . "</strong>", "100%", $colors["header"], "1", "center", "");
	mikrotik_graph_view_filter();

	/* include time span selector */
	if (read_graph_config_option("timespan_sel") == "on") {
		mikrotik_timespan_selector();
	}
	html_end_box();

	/* do some fancy navigation url construction so we don't have to try and rebuild the url string */
	if (ereg("page=[0-9]+",basename($_SERVER["QUERY_STRING"]))) {
		$nav_url = str_replace("page=" . $_REQUEST["page"], "page=<PAGE>", basename($_SERVER["PHP_SELF"]) . "?" . $_SERVER["QUERY_STRING"]);
	}else{
		$nav_url = basename($_SERVER["PHP_SELF"]) . "?" . $_SERVER["QUERY_STRING"] . "&page=<PAGE>";
	}

	$nav_url = ereg_replace("((\?|&)filter=[a-zA-Z0-9]*)", "", $nav_url);

	html_start_box("", "100%", $colors["header"], "3", "center", "");
	mikrotik_nav_bar($_REQUEST["page"], ROWS_PER_PAGE, $total_rows, $nav_url);
	mikrotik_graph_area($graphs, "", "graph_start=" . get_current_graph_start() . "&graph_end=" . get_current_graph_end(), "", $_REQUEST["cols"], $_REQUEST["thumb"]);

	if ($total_rows) {
		mikrotik_nav_bar($_REQUEST["page"], ROWS_PER_PAGE, $total_rows, $nav_url);
	}
	html_end_box();
}

function mikrotik_graph_start_box() {
	print "<table width='100%' cellpadding='3' cellspacing='0' border='0' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center'>\n";
}

function mikrotik_graph_end_box() {
	print "</table>";
}

function mikrotik_nav_bar($current_page, $rows_per_page, $total_rows, $nav_url) {
	global $config, $colors;

	if ($total_rows) {
		?>
		<tr bgcolor='#<?php print $colors["header"];?>' class='noprint'>
			<td colspan='<?php print read_graph_config_option("num_columns");?>'>
				<table width='100%' cellspacing='0' cellpadding='1' border='0'>
					<tr>
						<td align='left' class='textHeaderDark'>
							<strong>&lt;&lt; <?php if ($current_page > 1) { print "<a class='linkOverDark' href='" . htmlspecialchars(str_replace("<PAGE>", ($current_page-1), $nav_url)) . "'>"; } print "Previous"; if ($current_page > 1) { print "</a>"; } ?></strong>
						</td>
						<td align='center' class='textHeaderDark'>
							Showing Graphs <?php print (($rows_per_page*($current_page-1))+1);?> to <?php print ((($total_rows < $rows_per_page) || ($total_rows < ($rows_per_page*$current_page))) ? $total_rows : ($rows_per_page*$current_page));?> of <?php print $total_rows;?>
						</td>
						<td align='right' class='textHeaderDark'>
							<strong><?php if (($current_page * $rows_per_page) < $total_rows) { print "<a class='linkOverDark' href='" . htmlspecialchars(str_replace("<PAGE>", ($current_page+1), $nav_url)) . "'>"; } print "Next"; if (($current_page * $rows_per_page) < $total_rows) { print "</a>"; } ?> &gt;&gt;</strong>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}else{
		?>
		<tr bgcolor='#<?php print $colors["header"];?>' class='noprint'>
			<td colspan='<?php print read_graph_config_option("num_columns");?>'>
				<table width='100%' cellspacing='0' cellpadding='1' border='0'>
					<tr>
						<td align='center' class='textHeaderDark'>
							No Graphs Found
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}

function mikrotik_graph_view_filter() {
	global $config, $colors;

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>" class="noprint">
		<td class="noprint">
			<form name="form_graph_view" method="post" action="mikrotik.php?action=graphs">
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr class="noprint">
					<td nowrap style='white-space: nowrap;' width="55">
						&nbsp;Device:&nbsp;
					</td>
					<td width="1">
						<select name="host" onChange="applyGraphWReset(document.form_graph_view)">
							<?php if ($_REQUEST["style"] == "selective") {?>
							<option value="0"<?php if ($_REQUEST["host"] == "0") {?> selected<?php }?>>Custom</option>
							<?php }else{?>
							<option value="0"<?php if ($_REQUEST["host"] == "0") {?> selected<?php }?>>Any</option>
							<?php }?>

							<?php
							$hosts = db_fetch_assoc("SELECT host_id, host.description
								FROM host
								INNER JOIN plugin_mikrotik_system AS hrs
								ON host.id=hrs.host_id
								ORDER BY description");

							if (sizeof($hosts)) {
							foreach ($hosts as $host) {
								print "<option value='" . $host["host_id"] . "'"; if ($_REQUEST["host"] == $host["host_id"]) { print " selected"; } print ">" . $host["description"] . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="70">
						&nbsp;Template:&nbsp;
					</td>
					<td width="1">
						<select name="graph_template_id" onChange="applyGraphWReset(document.form_graph_view)">
							<?php if ($_REQUEST["style"] == "selective") {?>
							<option value="0"<?php if ($_REQUEST["graph_template_id"] == "0") {?> selected<?php }?>>Custom</option>
							<?php }else{?>
							<option value="0"<?php if ($_REQUEST["graph_template_id"] == "0") {?> selected<?php }?>>Any</option>
							<?php }?>

							<?php
							if (read_config_option("auth_method") != 0) {
								$graph_templates = db_fetch_assoc("SELECT DISTINCT graph_templates.*
									FROM (graph_templates_graph,graph_local)
									INNER JOIN plugin_mikrotik_system AS hrs ON graph_local.host_id=hrs.host_id
									LEFT JOIN host ON (host.id=graph_local.host_id)
									LEFT JOIN graph_templates 
									ON (graph_templates.id=graph_local.graph_template_id)
									LEFT JOIN user_auth_perms 
									ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id 
									AND user_auth_perms.type=1 
									AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") 
									OR (host.id=user_auth_perms.item_id 
									AND user_auth_perms.type=3 
									AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") 
									OR (graph_templates.id=user_auth_perms.item_id 
									AND user_auth_perms.type=4 
									AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))
									WHERE graph_templates_graph.local_graph_id=graph_local.id
									" . (empty($sql_where) ? "" : "and $sql_where") . "
									ORDER BY name");
							}else{
								$graph_templates = db_fetch_assoc("SELECT DISTINCT graph_templates.*
									FROM (graph_templates,graph_local)
									INNER JOIN plugin_mikrotik_system AS hrs ON graph_local.host_id=hrs.host_id
									WHERE graph_templates_graph.local_graph_id=graph_local.id
									ORDER BY name");
							}

							if (sizeof($graph_templates) > 0) {
							foreach ($graph_templates as $template) {
								print "<option value='" . $template["id"] . "'"; if ($_REQUEST["graph_template_id"] == $template["id"]) { print " selected"; } print ">" . $template["name"] . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="55">
						&nbsp;Columns:&nbsp;
					</td>
					<td width="1">
						<select name="cols" onChange="applyGraphWOReset(document.form_graph_view)">
							<?php
							print "<option value='1'"; if ($_REQUEST["cols"] == 1) { print " selected"; } print ">1</option>\n";
							print "<option value='2'"; if ($_REQUEST["cols"] == 2) { print " selected"; } print ">2</option>\n";
							print "<option value='3'"; if ($_REQUEST["cols"] == 3) { print " selected"; } print ">3</option>\n";
							print "<option value='4'"; if ($_REQUEST["cols"] == 4) { print " selected"; } print ">4</option>\n";
							print "<option value='5'"; if ($_REQUEST["cols"] == 5) { print " selected"; } print ">5</option>\n";
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="55">
						&nbsp;<label for='thumb'>Thumbnails:</label>&nbsp;
					</td>
					<td width='1'>
						<input name='thumb' id='thumb' type='checkbox' onChange="applyGraphWOReset(document.form_graph_view)" <?php print ($_REQUEST["thumb"] == "on" || $_REQUEST["thumb"] == "true" ? " checked":""); ?>>
					</td>
					<td nowrap style='white-space: nowrap;' width="60">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print $_REQUEST["filter"];?>">
					</td>
					<td>
						&nbsp;<input type="submit" name="go" value="Go">
						<input type="submit" name="clear" value="Clear">
					</td>
				</tr>
			</table>
			<input type='hidden' name='action' value='graphs'>
			</form>
		</td>
	</tr>
	<?php
}

function mikrotik_timespan_selector() {
	global $config, $colors, $graph_timespans, $graph_timeshifts;

	?>
	<script type='text/javascript'>
	<!--
	calendar=null;
	function showCalendar(id) {
		var el = document.getElementById(id);
		if (calendar != null) {
			calendar.hide();
		} else {
			var cal = new Calendar(true, null, selected, closeHandler);
			cal.weekNumbers = false;
			cal.showsTime = true;
			cal.time24 = true;
			cal.showsOtherMonths = false;
			calendar = cal;
			cal.setRange(1900, 2070);
			cal.create();
		}

		calendar.setDateFormat('%Y-%m-%d %H:%M');
		calendar.parseDate(el.value);
		calendar.sel = el;
		calendar.showAtElement(el, "Br");

		return false;
	}

	function selected(cal, date) {
		cal.sel.value = date;
	}

	function closeHandler(cal) {
		cal.hide();
		calendar = null;
	}
	-->
	</script>
	<script type="text/javascript">
	<!--
	function applyTimespanFilterChange(objForm) {
		strURL = '?action=graphs&predefined_timespan=' + objForm.predefined_timespan.value;
		strURL = strURL + '&predefined_timeshift=' + objForm.predefined_timeshift.value;
		document.location = strURL;
	}
	-->
	</script>
	<tr bgcolor="#<?php print $colors["panel"];?>" class="noprint">
		<td class="noprint">
			<form name="form_timespan_selector" method="post" action="mikrotik.php?action=graphs">
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width='55'>
						&nbsp;Presets:&nbsp;
					</td>
					<td nowrap style='white-space: nowrap;' width='130'>
						<select name='predefined_timespan' onChange="applyTimespanFilterChange(document.form_timespan_selector)">
							<?php
							if ($_SESSION["custom"]) {
								$graph_timespans[GT_CUSTOM] = "Custom";
								$start_val = 0;
								$end_val = sizeof($graph_timespans);
							} else {
								if (isset($graph_timespans[GT_CUSTOM])) {
									asort($graph_timespans);
									array_shift($graph_timespans);
								}
								$start_val = 1;
								$end_val = sizeof($graph_timespans)+1;
							}

							if (sizeof($graph_timespans) > 0) {
								for ($value=$start_val; $value < $end_val; $value++) {
									print "<option value='$value'"; if ($_SESSION["sess_current_timespan"] == $value) { print " selected"; } print ">" . title_trim($graph_timespans[$value], 40) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width='30'>
						&nbsp;From:&nbsp;
					</td>
					<td width='155' nowrap style='white-space: nowrap;'>
						<input type='text' name='date1' id='date1' title='Graph Begin Timestamp' size='14' value='<?php print (isset($_SESSION["sess_current_date1"]) ? $_SESSION["sess_current_date1"] : "");?>'>
						&nbsp;<input style='padding-bottom: 4px;' type='image' src='<?php print $config["url_path"];?>images/calendar.gif' alt='Start date selector' title='Start date selector' border='0' align='absmiddle' onclick="return showCalendar('date1');">&nbsp;
					</td>
					<td nowrap style='white-space: nowrap;' width='20'>
						&nbsp;To:&nbsp;
					</td>
					<td width='155' nowrap style='white-space: nowrap;'>
						<input type='text' name='date2' id='date2' title='Graph End Timestamp' size='14' value='<?php print (isset($_SESSION["sess_current_date2"]) ? $_SESSION["sess_current_date2"] : "");?>'>
						&nbsp;<input style='padding-bottom: 4px;' type='image' src='<?php print $config["url_path"];?>images/calendar.gif' alt='End date selector' title='End date selector' border='0' align='absmiddle' onclick="return showCalendar('date2');">
					</td>
					<td width='130' nowrap style='white-space: nowrap;'>
						&nbsp;&nbsp;<input style='padding-bottom: 4px;' type='image' name='move_left' src='<?php print $config['url_path'];?>images/move_left.gif' alt='Left' border='0' align='absmiddle' title='Shift Left'>
						<select name='predefined_timeshift' title='Define Shifting Interval' onChange="applyTimespanFilterChange(document.form_timespan_selector)">
							<?php
							$start_val = 1;
							$end_val = sizeof($graph_timeshifts)+1;
							if (sizeof($graph_timeshifts) > 0) {
								for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
									print "<option value='$shift_value'"; if ($_SESSION["sess_current_timeshift"] == $shift_value) { print " selected"; } print ">" . title_trim($graph_timeshifts[$shift_value], 40) . "</option>\n";
								}
							}
							?>
						</select>
						<input style='padding-bottom: 4px;' type='image' name='move_right' src='<?php print $config['url_path'];?>images/move_right.gif' alt='Right' border='0' align='absmiddle' title='Shift Right'>
					</td>
					<td nowrap style='white-space: nowrap;'>
						&nbsp;&nbsp;<input type='submit' name='refresh' value='Refresh'>
						<input type='submit' name='clear' value='Clear'>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
}

/* mikrotik_graph_area - draws an area the contains graphs
   @arg $graph_array - the array to contains graph information. for each graph in the
     array, the following two keys must exist
     $arr[0]["local_graph_id"] // graph id
     $arr[0]["title_cache"] // graph title
   @arg $no_graphs_message - display this message if no graphs are found in $graph_array
   @arg $extra_url_args - extra arguments to append to the url
   @arg $header - html to use as a header
   @arg $columns - number of columns per row
   @arg $thumbnails - thumbnail graphs */
function mikrotik_graph_area(&$graph_array, $no_graphs_message = "", $extra_url_args = "", $header = "", $columns = 2, $thumbnails = "true") {
	global $config;

	if ($thumbnails == "true" || $thumbnails == "on") {
		$th_option = "&graph_nolegend=true&graph_height=" . read_graph_config_option("default_height") . "&graph_width=" . read_graph_config_option("default_width");
	}else{
		$th_option = "";
	}

	$i = 0; $k = 0;
	if (sizeof($graph_array) > 0) {
		if ($header != "") {
			print $header;
		}

		print "<tr>";

		foreach ($graph_array as $graph) {
			?>
			<td align='center' width='<?php print (98 / $columns);?>%'>
				<table width='1' cellpadding='0'>
					<tr>
						<td>
							<a href='<?php print htmlspecialchars($config['url_path'] . "graph.php?action=view&rra_id=all&local_graph_id=" . $graph["local_graph_id"]);?>'><img class='graphimage' id='graph_<?php print $graph["local_graph_id"] ?>' src='<?php print $config['url_path']; ?>graph_image.php?local_graph_id=<?php print $graph["local_graph_id"] . "&rra_id=0" . $th_option . (($extra_url_args == "") ? "" : "&$extra_url_args");?>' border='0' alt='<?php print $graph["title_cache"];?>'></a>
						</td>
						<td valign='top' style='padding: 3px;'>
							<a href='<?php print htmlspecialchars($config['url_path'] . "graph.php?action=zoom&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='<?php print $config['url_path']; ?>images/graph_zoom.gif' border='0' alt='Zoom Graph' title='Zoom Graph' style='padding: 3px;'></a><br>
							<a href='<?php print htmlspecialchars($config['url_path'] . "graph_xport.php?local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='<?php print $config['url_path']; ?>images/graph_query.png' border='0' alt='CSV Export' title='CSV Export' style='padding: 3px;'></a><br>
							<a href='#page_top'><img src='<?php print $config['url_path']; ?>images/graph_page_top.gif' border='0' alt='Page Top' title='Page Top' style='padding: 3px;'></a><br>
							<?php api_plugin_hook('graph_buttons', array('hook' => 'thumbnails', 'local_graph_id' => $graph["local_graph_id"], 'rra' =>  0, 'view_type' => '')); ?>
						</td>
					</tr>
				</table>
			</td>
			<?php

			$i++;
			$k++;

			if (($i == $columns) && ($k < count($graph_array))) {
				$i = 0;
				print "</tr><tr>";
			}
		}

		print "</tr>";
	}else{
		if ($no_graphs_message != "") {
			print "<td><em>$no_graphs_message</em></td>";
		}
	}
}
?>
