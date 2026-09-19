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
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

describe('setup.php structure in mikrotik', function () {
	$source = plugin_test_read_source('setup.php');

	$infoPath = realpath(__DIR__ . '/../../INFO');
	if ($infoPath === false) {
		throw new RuntimeException('Unable to resolve required file: INFO');
	}

	$infoFile = parse_ini_file($infoPath, true);
	if (!is_array($infoFile) || !isset($infoFile['info']) || !is_array($infoFile['info'])) {
		throw new RuntimeException('Unable to parse the INFO section');
	}
	$info = $infoFile['info'];

	it('defines plugin_mikrotik_install function', function () use ($source) {
		expect($source)->toContain('function plugin_mikrotik_install');
	});

	it('defines plugin_mikrotik_uninstall function', function () use ($source) {
		expect($source)->toContain('function plugin_mikrotik_uninstall');
	});

	it('defines plugin_mikrotik_version function', function () use ($source) {
		expect($source)->toContain('function plugin_mikrotik_version');
	});

	it('defines plugin_mikrotik_check_config function', function () use ($source) {
		expect($source)->toContain('function plugin_mikrotik_check_config');
	});

	it('registers hooks via api_plugin_register_hook', function () use ($source) {
		expect($source)->toContain("api_plugin_register_hook('mikrotik'");
	});

	it('declares a plugin name in INFO', function () use ($info) {
		expect($info)->toHaveKey('name');
		expect($info['name'])->toBe('mikrotik');
	});

	it('declares a plugin version in INFO', function () use ($info) {
		expect($info)->toHaveKey('version');
		expect($info['version'])->not->toBe('');
		expect($info['version'])->toMatch('/^\d+\.\d+$/');
	});
});
