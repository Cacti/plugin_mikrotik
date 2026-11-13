
--- develop ---

* issue: Preserve the configured RouterOS API transport after connection failures; changing SSL remains an explicit device setting, and diagnostics distinguish transport/TLS failures from login failures
* security: Verify API TLS certificates and hostnames by default, with an explicit compatibility setting for unverified certificates

* issue: Harden the RouterOS 7 health gauge collection: store a missing/non-numeric `mtxrGaugeValue` as NULL (unknown) rather than a misleading 0, NULL every overlay-managed legacy health column not seen in a poll so a removed sensor stops flatlining on its last value, skip the collection (so the legacy-scalar fallback runs) when `plugin_mikrotik_health_sensors` does not exist yet on a fresh upgrade, and HTML-escape the device description in the Health view
* issue: Collect WifiWave2 wireless data from either the modern `/interface/wifi` menu (RouterOS 7.13+) or the older `/interface/wifiwave2` menu used by the wifiwave2 package on RouterOS 7.1-7.12, falling back to SNMP only when neither answers
* issue: Align `ss_mikrotik_wireless_reg.php`'s `get` handler with the `mikrotik_wireless_reg.xml` data query contract so the wireless registration graphs can gather data once the templates are repackaged (the final step): it now maps each data-query field (`TxBytes`, `RxBytes`, `TxPackets`, `RxPackets`, `Strength`, `SignalToNoise`, `TxRate`, `RxRate`, `Uptime`, `TxStrengthCh*`/`RxStrengthCh*`, `TxStrength`, and `macAddress`→`index`) to the real `plugin_mikrotik_wireless_registrations` column, replacing the previous `ap*`-prefixed whitelist whose columns do not exist (and handling the historical `RxStrengthChl` column-name typo)
* issue: Bring the MikroTik script-server data query definitions (`resource/script_server/mikrotik_*.xml`) into the plugin as a version-controlled `resource/` directory, alongside the `scripts/` backends they drive, so the data-query-to-script-to-schema contract can be audited in one place. (The `mikrotik_wireless_reg.xml` query_name fields — `TxBytes`, `Strength`, `SignalToNoise`, … — match the real `plugin_mikrotik_wireless_registrations` columns, confirming the `ss_mikrotik_wireless_reg.php` script's `ap*` whitelist is the drifted side.)
* issue: Add a Health tab that lists the RouterOS 7 health sensors (mtxrGaugeTable) collected into `plugin_mikrotik_health_sensors` per device, and bring the `ss_mikrotik_*.php` script-server data backends into the plugin under a version-controlled `scripts/` directory. Fix `ss_mikrotik_health.php` to read the real schema — mapping each graph metric to its gauge sensor name (preferred) and legacy `plugin_mikrotik_system_health` column (fallback) — so the health graph templates can gather data on RouterOS 7 (and continue to work on pre-7.x) once the templates are repackaged with the corrected scripts (the final step)
* issue: Restore System Health data on RouterOS 7+ devices: v7 reports sensors through the dynamic `mtxrGaugeTable` (mtxrHealth.100, keyed by sensor name) rather than the fixed `mtxrHealth.1..18` scalars the poller read, which are mostly empty on v7. The poller now collects the gauge table on 7.x+ (falling back to the fixed scalars when it is unavailable, and keeping the fixed scalars for pre-7.x), storing every discovered sensor (name, scaled value by unit, and unit) in a new `plugin_mikrotik_health_sensors` table so multi-sensor hardware (multiple temperatures/fans/PSUs) is represented, and overlaying the well-known single-instance sensors onto the legacy `plugin_mikrotik_system_health` columns so the existing per-metric health graphs keep working (schema version bump to 3.4)
* issue: Restore the Wireless Aps and Wireless Registrations views for RouterOS 7+ devices: the legacy wireless SNMP tables (mtxrWlApTable/mtxrWlRtabTable) are no longer populated by the v7 WifiWave2 (`/interface/wifi`) driver, so the poller now checks each device's RouterOS version and collects wireless AP and client registration data through the RouterOS API (`/interface/wifi/print` and `/interface/wifi/registration-table/print`) on 7.x, falling back to SNMP collection when the API is unavailable; pre-7.x devices continue to use SNMP
* issue#134: Fix a 404 (`The requested URL was not found on this server`) when using the Device/Template filters on the MikroTik Graphs page by rendering the preview filter with a plugin-local wrapper that uses a plain Device dropdown and an absolute page URL, so it works on both Cacti 1.2.x and the develop branch regardless of the sub-directory the plugin is served from
* issue: When the poller finds a device's stored SSL transport unusable because the api-ssl TLS handshake never completes, and the plaintext api port (8728) then accepts the same credentials, switch that device back to non-SSL in the database and log a single message instead of flooding the Cacti log with repeated handshake errors on every poll; confirming the plaintext port first prevents a transient SSL blip on a TLS-only device from being downgraded to cleartext
* issue: Detect SSL availability with a real TLS handshake instead of a plain TCP connect, so a device whose api-ssl port is open but whose TLS handshake fails is correctly treated as SSL-unavailable; in that case the SSL dropdown cannot enable SSL but preserves a saved `on` preference (shown as "Yes (currently unreachable)") so saving unrelated host settings never silently downgrades a TLS-only device to cleartext, and the low-level `stream_socket_client()`/`fsockopen()` connection warnings in the RouterOS client are suppressed so a failed SSL handshake no longer floods the Cacti log from the device edit page or the poller
* issue: Probe the MikroTik api-ssl port (8729) when editing a device and, when it is not reachable, disable the SSL dropdown (shown as "Not Available") while preserving the saved SSL preference, and skip the SSL connection test rather than falling back to plaintext, so the Cacti log is no longer flooded with `stream_socket_client()` TLS handshake warnings and a TLS-only password is never sent over port 8728
* issue: Fix the MikroTik device credential save failing with a SQL syntax error (`ssl`, `user` and `password` are reserved words) so the SSL selection and credentials persist again; bump the schema version to 3.3 so installations that recorded the broken 3.2 migration retry and add the missing `ssl` column
* issue: Obfuscate the MikroTik device password field: render it as a single masked input that is no longer pre-populated with the stored value, keep the stored password when the field is submitted blank, and update it only when a new value is entered
* dev: Run the CI poller with `runuser -u www-data -g runner` so RRD files it creates get the runner group that owns the tree, clearing the "collaborative owner/group/mode arrangement could not be fully applied" warnings that failed the Cacti log check
* issue: Preserve the previous indexed SNMP snapshot when a required table walk fails
* issue: Add an SSL option (Yes/No, default No) to the MikroTik host edit form, directly above the Read Only User, and connect to the RouterOS API over api-ssl (port 8729) when enabled, accepting the device's self-signed api-ssl certificate by default; adds an `ssl` column to plugin_mikrotik_credentials (schema version bump to 3.2)
* issue: Query MikroTik's current RouterOS 7 stable update feed (NEWESTa7.stable) over HTTPS so the "Latest MikroTik Version" header and per-device firmware comparison report the real latest release; the legacy LATEST.6/LATEST.7 files are frozen (stuck at 7.12.1) and no longer reflect releases
* issue: Load the RouterOS 7 MIB (mikrotik-7.mib) by default in the poller, as RouterOS 7 is now the current stable series
* issue#84: Update the bundled MikroTik MIBs to the latest by major RouterOS version, replacing MIKROTIK-MIB.txt with mikrotik-6.mib and mikrotik-7.mib (tombstoned in manifest.json and loaded by the poller)
* issue: Fix an uncaught TypeError that disabled the plugin when mikrotik_graphs_url_by_template_hashs() received a null hash list (unpopulated wireless AP station hashes), and wire the Wireless Registrations view to the correctly-named $wireless_reg_hashes global so its graph links resolve
* security: Move every page's inline event handlers to CSP-safe jQuery bindings so the pages no longer trip Cacti's Content-Security-Policy script-src-attr directive: the per-view filter selects, checkboxes, Go/Clear/Export buttons are bound in each view's ready block, and the confirmation Cancel/Return buttons use the `cactiReturnTo` class. `mikrotik.php`/`mikrotik_users.php` move from the phpunit measured `<source>` set into the patch-coverage allowlist (matching the rest of the plugin fleet) because they are web UI entry points that cannot be loaded into the isolated unit process; their pure helpers remain covered via `mikrotik_test_load_function()`
* dev: Enforce patch coverage of changed lines in CI and remove the inert COMPOSER_ROOT_VERSION env from the Pest step
* dev: Bring all plugin code to PHPStan level 8 with accurate native and PHPDoc types
* dev: Pin the CI database image and shared test dependencies, disable persisted checkout credentials, and limit workflow permissions and runtime
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
