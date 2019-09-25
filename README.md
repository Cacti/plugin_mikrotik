# mikrotik

This plugin provides support for the MikroTik RouterOS available from
[MikroTik](https://microtik.com) and other router and switch harware suppliers.
It is not a replacement for MikroTiks GUI interface, but provides nice Cacti
Graphs for multiple MikroTik features including, but not limited to:

* Automatic Discovery of Cacti Devices that are MikroTik's

* Enhanced Traffic Graphs

* Graphs of Wireless and PPPoE Users

* Table views of Wireless and PPPoE User utilization

* Graphs of MikroTik enviornmentals such as temperature and voltage

* Viewing of Devices and summary statistics, Users, Queue Trees, Access Points,
  HotSpots, Interfaces, and Wireless Station information

## Installation

Just like any other Cacti plugin, untar the package to the Cacti plugins
directory, rename the directory to 'mikrotik', and then from Cacti's Plugin
Management interface, Install and Enable the plugin.

With this version of mikrotik, we are also including a Device Template Cacti
Package that includes everything you need to create all the included Cacti
Graphs for this device type.  To import that package, you must use the Cacti
'import_package.php' CLI script.  The MikroTik package is included in the
'templates' sub-directory of the MikroTik plugin.

Once this is done, you have to configure the mikrotik plugin under Cacti's
Console Settings option, and then select the 'Mikrotik' tab.  From there, you
can enable the MikroTik data collection, set the level of parallelization, and
the various collection frequencies for all of the items that the mikrotik will
poll over time.

MikroTik's 'auto-discovery' feature will look for Cacti devices that are
MikroTiks and recognize them as such.  If you are using a different Cacti Device
Template for these Devices, you may want to change them to user the new Cacti
Device Template made exclusively for the MikroTik devices.

## Bugs and Feature Enhancements

Bug and feature enhancements for the mikrotik plugin are handled in GitHub.  If
you find a first search the Cacti forums for a solution before creating an issue
in GitHub.

## ChangeLog

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
