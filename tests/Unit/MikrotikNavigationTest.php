<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for mikrotik_draw_navigation_text() in setup.php.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

it('adds the mikrotik breadcrumb entries without disturbing existing ones', function () {
	$nav = mikrotik_draw_navigation_text(array('other.php:' => array('title' => 'Other')));

	expect($nav)->toHaveKey('other.php:');
	expect($nav)->toHaveKey('mikrotik.php:');
	expect($nav)->toHaveKey('mikrotik.php:devices');
	expect($nav)->toHaveKey('mikrotik_users.php:');
	expect($nav['mikrotik_users.php:edit']['mapping'])->toBe('index.php:,mikrotik_users.php:');
});
