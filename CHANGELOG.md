
--- develop ---

* security: Move every page's inline event handlers to CSP-safe jQuery bindings so the pages no longer trip Cacti's Content-Security-Policy script-src-attr directive: the per-view filter selects, checkboxes, Go/Clear/Export buttons are bound in each view's ready block, and the confirmation Cancel/Return buttons use the `cactiReturnTo` class. `mikrotik.php`/`mikrotik_users.php` move from the phpunit measured `<source>` set into the patch-coverage allowlist (matching the rest of the plugin fleet) because they are web UI entry points that cannot be loaded into the isolated unit process; their pure helpers remain covered via `mikrotik_test_load_function()`
* dev: Enforce patch coverage of changed lines in CI and remove the inert COMPOSER_ROOT_VERSION env from the Pest step
* dev: Bring all plugin code to PHPStan level 8 with accurate native and PHPDoc types
* issue: Normalize nullable/config-string inputs to runCollector() and mikrotik_memory() so unset last-run settings and NULL wireless AP rate columns no longer raise a TypeError under PHP 8.2
* issue: Fix stale/invalid html_start_box() and html_header_sort() arguments
* issue: Fix uptime/TTL arithmetic on SNMP string values and a double strlen() typo
* issue: Fix db_fetch_assoc() used with prepared parameters in poller_graphs.php
* security: Add a version-safe CSP nonce (`plugin_mikrotik_csp_nonce()`) to every inline `<script>` tag so pages stay compatible with Cacti's Content-Security-Policy nonce enforcement, while falling back cleanly on older Cacti releases that lack the `CactiSecureHeaders` class
* chore: Harmonize CI workflow, issue/PR templates, and PHP-compatibility test structure with the shared Cacti plugin baseline
* issue#78: SQL Syntax Error in poller_mikrotik.php
* issue: Data Query Graphs with spaces in their indexes not working.  This is Cacti issue fixed in 1.2.280
* issue: Don't captitalize everything including Queues, etc.
* issue: Fix some PHP8 errors in Graph Automation
* issue: Update Data Queries to allow Spaces and Underscores
* issue: Fix issues with Mikrotik Interfaces polling and re-indexing
* issue: Fix issues with Mikrotik Graph Automation throwing warnings

--- 3.1 ---

* issue: Devices packages pointing to incorrect locations

--- 3.0 ---

* issue: When combined with Cacti's new test data source, errors in scripts are thrown
* issue: Move the package location for scripts and resource files to the correct location
* issue#61: PLUGIN WARNING: Function does not exist config_form with function mikrotik_config_form
* issue#62: PHP DEPRECATED warnings in mikrotik plugin
* issue#63: ERROR PHP DEPRECATED in Plugin 'mikrotik': str_replace() in poller_graphs.php on line: 386
* feature: Add Device ID's to MikroTik API stats to track password failures
* feature: Add DNS Cache to MikroTik Plugin
* feature: Add Address Lists to MikroTik Plugin
* feature: Add DHCP Leases to Device Template
* feature: Add the Mikrotik Switch OS Device Package
* feature: Minimum Cacti version 1.2.24

--- 2.5 ---

* issue#36: Mikrotik Plugin -- simple queue issue
* feature: Allow disablement of API data collection
* feature: Moving from images to glyphs
* feature: Minimum version Cacti 1.2.11
* issue: Internationalization issues on console

--- 2.4 ---

* issue: Properly display uptime for Wireless Registrations
* issue: Do not log when a device does not have DHCP enabled
* issue: Workaround issues with the SNMP client and voltage, power, ampere and temperature reporting
* feature: Specify a retention time for DHCP Registrations

--- 2.3 ---

* issue#31: Handle case where 'dhcp' package is not installed
* issue#32: No Uptime and a non mikrotik device detected
* issue#35: Login Failure for RouterOS Login method post-v6.43
* feature: PHP 7.2 compatibility

--- 2.2 ---

* feature: Add DHCP table to view DHCP registrations
* issue#23: The health values are showed without dot
* issue: Undefined offset when attempting to connect to Mikrotik
* issue: MikroTik Uptime not reporting correctly

--- 2.1 ---

* issue: Resolve issues when you attempt to sort on reserved word
* issue: Properly remove aged Wireless AP interfaces
* issue: Remove dependency on custom snmp.php module
* feature: Add wireless registrations table view to show all registrations
* feature: Roll out Cacti sort API to support multiple column sort

--- 2.0 ---

* issue#10: SQL Error when sorting from the Wireless Aps page
* issue#12: All pages lack navigation
* feature: Support for Cacti 1.0
* feature: Lot's of new features
* feature: Update text domain for i18n

--- 1.01 ---

* bug#0002318: Invalid round robin archive

--- 1.0 ---

* Initial release

-----------------------------------------------
Copyright (c) 2004-2026 - The Cacti Group, Inc.
