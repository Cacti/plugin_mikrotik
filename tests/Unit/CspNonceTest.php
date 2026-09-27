<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for plugin_mikrotik_csp_nonce() in setup.php.
 *
 * This helper is the security boundary for every inline <script> tag the
 * plugin emits, so both its Cacti-version branches are covered: the
 * empty-string fallback on releases without CactiSecureHeaders (verified at
 * runtime, since the unit bootstrap does not define that class), and the
 * delegation to CactiSecureHeaders::getNonceAttribute() on newer releases
 * (verified by source assertion, which the stubbed bootstrap cannot isolate
 * at runtime because a declared class cannot later be undeclared).
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

test('csp nonce falls back to an empty string on Cacti without CactiSecureHeaders', function () {
	expect(class_exists('CactiSecureHeaders'))->toBeFalse();
	expect(plugin_mikrotik_csp_nonce())->toBe('');
});

test('csp nonce helper is declared to return a string', function () {
	$ref = new ReflectionFunction('plugin_mikrotik_csp_nonce');

	expect((string) $ref->getReturnType())->toBe('string');
});

test('csp nonce delegates to CactiSecureHeaders when the class is available', function () {
	$source = file_get_contents(__DIR__ . '/../../setup.php');

	expect($source)->toContain("class_exists('CactiSecureHeaders')");
	expect($source)->toContain('CactiSecureHeaders::getNonceAttribute()');
});
