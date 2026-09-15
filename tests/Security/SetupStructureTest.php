<?php

describe('mikrotik setup.php structure', function () {
	$setupPath = realpath(__DIR__ . '/../../setup.php');
	expect($setupPath)->not->toBeFalse('setup.php must exist');
	expect(is_readable($setupPath))->toBeTrue('setup.php must be readable');
	$source = file_get_contents($setupPath);
	expect($source)->not->toBeFalse('setup.php could not be read');

	it('defines required plugin hooks', function () use ($source) {
		expect($source)->toContain('function plugin_mikrotik_install');
		expect($source)->toContain('function plugin_mikrotik_version');
		expect($source)->toContain('function plugin_mikrotik_uninstall');
	});

	it('defines required INFO metadata', function () {
		$infoPath = realpath(__DIR__ . '/../../INFO');
		expect($infoPath)->not->toBeFalse('INFO must exist');
		expect(is_readable($infoPath))->toBeTrue('INFO must be readable');
		$info = parse_ini_file($infoPath, true);
		expect($info)->toBeArray();
		expect($info['info']['name'])->toBe('mikrotik');
		expect($info['info']['version'])->toMatch('/^\d+\.\d+$/');
	});
});
