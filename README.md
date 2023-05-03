# MikroTik

This plugin provides support for the MikroTik RouterOS available from
[MikroTik](https://mikrotik.com) and other router and switch hardware suppliers.
It is not a replacement for MikroTik's GUI interface, but provides nice Cacti
Graphs for multiple MikroTik features including, but not limited to:

* Automatic Discovery of Cacti Devices that are MikroTik's

* Enhanced Traffic Graphs

* Graphs of Wireless and PPPoE Users

* Table views of Wireless and PPPoE User utilization

* Graphs of MikroTik environmental data such as temperature and voltage

* Viewing of Devices and summary statistics, Users, Queue Trees, Access Points,
  HotSpots, Interfaces, and Wireless Station information

## Installation

Just like any other Cacti plugin, untar the package to the Cacti plugins
directory, rename the directory to 'mikrotik', and then from Cacti's Plugin
Management interface, Install and Enable the plugin.

With this version of MikroTik, we are also including a Device Template Cacti
Package that includes everything you need to create all the included Cacti
Graphs for this device type.  To import that package, you must use the Cacti
'import_package.php' CLI script.  The MikroTik package is included in the
'templates' sub-directory of the MikroTik plugin.

Once this is done, you have to configure the mikrotik plugin under Cacti's
Console Settings option, and then select the 'Mikrotik' tab.  From there, you
can enable the MikroTik data collection, set the level of parallelization, and
the various collection frequencies for all of the items that the MikroTik will
poll over time.

MikroTik's 'auto-discovery' feature will look for Cacti devices that are
MikroTiks and recognize them as such.  If you are using a different Cacti Device
Template for these Devices, you may want to change them to user the new Cacti
Device Template made exclusively for the MikroTik devices.

## Bugs and Feature Enhancements

Bug and feature enhancements for the MikroTik plugin are handled in GitHub.  If
you find a first search the Cacti forums for a solution before creating an issue
in GitHub.

