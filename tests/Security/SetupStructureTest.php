<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 */

$source = plugin_test_read_source('setup.php');
$infoFile = parse_ini_file(__DIR__ . '/../../INFO', true);
if (!is_array($infoFile) || !isset($infoFile['info']) || !is_array($infoFile['info'])) {
	throw new RuntimeException('Unable to parse the INFO section');
}
$info = $infoFile['info'];

it('defines plugin_mikrotik_install function', function () use ($source) {
	expect($source)->toContain('function plugin_mikrotik_install');
});

it('defines plugin_mikrotik_version function', function () use ($source) {
	expect($source)->toContain('function plugin_mikrotik_version');
});

it('defines plugin_mikrotik_uninstall function', function () use ($source) {
	expect($source)->toContain('function plugin_mikrotik_uninstall');
});

it('declares a plugin name in INFO', function () use ($info) {
	expect($info)->toHaveKey('name');
});

it('declares a plugin version in INFO', function () use ($info) {
	expect($info)->toHaveKey('version');
});
