# GitHub Copilot Instructions

## Priority Guidelines

When generating code for this repository:

1. **Version Compatibility**: This is a Cacti plugin (`mikrotik`, version 3.1) targeting Cacti 1.2.24+
2. **Context Files**: Prioritize patterns and standards defined in this file (`.github/copilot-instructions.md`)
3. **Codebase Patterns**: When context files don't provide specific guidance, scan the codebase for established patterns
4. **Architectural Consistency**: Maintain plugin-based architecture extending Cacti core
5. **Code Quality**: Prioritize security, maintainability, and compatibility in all generated code

## Technology Stack

### Core Technologies
- **PHP**: Compatible with Cacti 1.2.x supported versions
- **Platform**: Cacti Plugin Architecture (Cacti 1.2.24+)
- **Database**: MySQL/MariaDB with InnoDB engine
- **SNMP**: Cacti's SNMP library for MikroTik RouterOS device polling

### Key Dependencies
- Cacti core framework (`api_plugin_*`, `db_*`, `cacti_snmp_*`)
- `RouterOS/` and `templates/` provide device/graph template XML used during discovery
- Optional: `gettext` for internationalization

## Project Structure

```
mikrotik/                # Repository root (install to plugins/mikrotik/ in Cacti)
├── RouterOS/             # RouterOS-specific data query/template assets
├── templates/            # Graph/device template XML
├── images/                # UI icons
├── locales/               # Internationalization files
├── mikrotik.php            # Main viewer page (tabs, interfaces, queues, trees, wireless)
├── mikrotik_users.php       # Admin page for wireless/PPPoE user views
├── poller_graphs.php         # Graph URL helper output
├── poller_mikrotik.php        # Background poller entry point (CLI)
├── MIKROTIK-MIB.txt            # Vendor MIB reference
├── INFO                        # Plugin metadata (name, version, compat)
├── README.md
└── setup.php                    # Plugin install/uninstall/upgrade hooks
```

## Naming Conventions

### Function Names
- **Plugin lifecycle/hook-registration functions** MUST be prefixed `plugin_mikrotik_`: `plugin_mikrotik_install()`, `plugin_mikrotik_upgrade()`, `plugin_mikrotik_version()`.
- **All other functions** MUST be prefixed `mikrotik_`: `mikrotik_check_upgrade()`, `mikrotik_get_network()`, `mikrotik_users_exist()`.
- Match the existing prefix used by the function you are editing; do not introduce a third naming scheme.

### Database Tables
All plugin tables are prefixed `plugin_mikrotik_` (see `plugin_mikrotik_uninstall()` for the authoritative list):

```
plugin_mikrotik_system, plugin_mikrotik_system_health, plugin_mikrotik_storage,
plugin_mikrotik_users, plugin_mikrotik_trees, plugin_mikrotik_queues,
plugin_mikrotik_interfaces, plugin_mikrotik_wireless_aps,
plugin_mikrotik_wireless_registrations, plugin_mikrotik_processes,
plugin_mikrotik_processor, plugin_mikrotik_credentials, plugin_mikrotik_dhcp,
plugin_mikrotik_dns, plugin_mikrotik_lists
```

### Variables and Constants
- Use snake_case for variables: `$host_id`, `$graph_template`.

## Code Style

### Indentation and Formatting
- **Tabs**: Use tabs (not spaces) for indentation throughout all PHP files.
- **Braces**: Opening brace on the same line for functions and control structures.
- **Spacing**: Space after control structure keywords (`if`, `foreach`, `while`).

### File Headers
ALL PHP files MUST include the standard GPL v2 license header used throughout this repository (see `setup.php` for the exact block), crediting "The Cacti Group".

## Security Standards

### SQL Query Security
Prefer prepared statements for anything involving variable input:

```php
// CORRECT
db_column_exists('plugin_mikrotik_trees', 'prevPackets');
db_execute_prepared('DELETE FROM plugin_mikrotik_users WHERE host_id = ?', array($host_id));

// AVOID - raw concatenation of untrusted input
db_execute("SELECT * FROM plugin_mikrotik_users WHERE host_id = $host_id");
```
Note: several existing `db_execute("ALTER TABLE ...")` upgrade statements use static, hardcoded SQL (no user input) — this is acceptable for DDL, but any query built from request/device data must use the `_prepared` variants.

### Input Validation
Use `get_filter_request_var()` / `get_nfilter_request_var()` for request input; never read `$_GET`/`$_POST` directly.

`get_filter_request_var()` (and its `gfrv()` shorthand, where available) called with only the
`$name` argument (no regex/filter as the 2nd/3rd argument) already validates the value as numeric
and returns it as a **string** -- it does not return an int, and it halts execution if the request
value is not numeric. Because of this, do NOT cast its output to `(int)` when the result is only
used for string output (e.g. `print`/`echo`, string concatenation, embedding in HTML/JS); the cast
is redundant. Only cast when the value is genuinely used in an integer/numeric context (e.g.
arithmetic, strict `===` comparisons).

## Database Operations

### Upgrade Handling
Version-gate schema changes in `mikrotik_check_upgrade()` (`setup.php`), comparing `plugin_mikrotik_version()['version']` against the stored `plugin_config` row, and re-enable hooks via `api_plugin_enable_hooks('mikrotik')` when upgrading an enabled plugin:

```php
function mikrotik_check_upgrade() {
	global $config, $database_default;

	$info    = plugin_mikrotik_version();
	$current = $info['version'];
	$old     = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='mikrotik'");

	if ($current != $old) {
		if (api_plugin_is_enabled('mikrotik')) {
			api_plugin_enable_hooks('mikrotik');
		}
		// db_column_exists()/db_table_exists() guarded ALTER/CREATE statements here
	}
}
```

## Internationalization

ALL user-facing strings MUST use `__()` with the `'mikrotik'` text domain:

```php
api_plugin_register_realm('mikrotik', 'mikrotik.php', __('MikroTik Viewer', 'mikrotik'), 1);
```

## Plugin Architecture

### Plugin Hooks
Register all plugin hooks in `plugin_mikrotik_install()` (`setup.php`):

```php
api_plugin_register_hook('mikrotik', 'config_arrays',         'mikrotik_config_arrays',         'setup.php');
api_plugin_register_hook('mikrotik', 'config_settings',       'mikrotik_config_settings',       'setup.php');
api_plugin_register_hook('mikrotik', 'draw_navigation_text',  'mikrotik_draw_navigation_text',  'setup.php');
api_plugin_register_hook('mikrotik', 'poller_bottom',         'mikrotik_poller_bottom',         'setup.php');
api_plugin_register_hook('mikrotik', 'top_header_tabs',       'mikrotik_show_tab',              'setup.php');
api_plugin_register_hook('mikrotik', 'top_graph_header_tabs', 'mikrotik_show_tab',              'setup.php');
api_plugin_register_hook('mikrotik', 'host_edit_top',         'mikrotik_host_top',              'setup.php');
api_plugin_register_hook('mikrotik', 'host_save',             'mikrotik_host_save',             'setup.php');
api_plugin_register_hook('mikrotik', 'host_delete',           'mikrotik_host_delete',            'setup.php');

api_plugin_register_realm('mikrotik', 'mikrotik.php', __('MikroTik Viewer', 'mikrotik'), 1);
api_plugin_register_realm('mikrotik', 'mikrotik_users.php', __('MikroTik Admin', 'mikrotik'), 1);
```

## Best Practices

1. Match existing filter/table/graph-URL helper patterns (`mikrotik_get_graph_url()`, `mikrotik_get_graph_template_url()`) rather than introducing new ones.
2. Always guard schema changes with `db_column_exists()`/`db_table_exists()` before altering.
3. Use Cacti's SNMP wrapper functions defensively; RouterOS devices vary in supported OIDs across firmware versions.
4. Wrap all user-facing strings with `__('text', 'mikrotik')`.

## Common Pitfalls to Avoid

```php
// WRONG - unfiltered request var
$host_id = $_GET['host_id'];

// CORRECT
$host_id = get_filter_request_var('host_id');

// WRONG - unconditional ALTER TABLE on every load
db_execute("ALTER TABLE plugin_mikrotik_trees ADD COLUMN prevPackets ...");

// CORRECT - guarded
if (!db_column_exists('plugin_mikrotik_trees', 'prevPackets')) {
	db_execute("ALTER TABLE plugin_mikrotik_trees ADD COLUMN prevPackets BIGINT UNSIGNED default NULL AFTER prevBytes");
}
```

## Version Control

Document all changes in `CHANGELOG.md`; use descriptive commit messages referencing issue/PR numbers when applicable.

## References

- [Cacti main repo](https://github.com/Cacti/cacti/tree/1.2.x)
- [Cacti Documentation](https://www.github.com/Cacti/documentation)
- `README.md` for feature descriptions
- `CHANGELOG.md` for version history
