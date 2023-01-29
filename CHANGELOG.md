
--- develop ---

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

* issue: Workaround issues with the SNMP client and voltage,
  power, ampere and temperature reporting

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
