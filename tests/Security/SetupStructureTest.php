<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('setup.php structure in mikrotik', function () {
	$source = plugin_test_read_source('setup.php');

	$infoFile = parse_ini_file(realpath(__DIR__ . '/../../INFO'), true);
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
	});
});
