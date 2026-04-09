# Cacti mikrotik Plugin AI Instructions

## Project Overview
This is a Cacti plugin. It integrates with the Cacti monitoring platform via the plugin hook architecture.

## Technology Stack
- PHP 7.4+ (targeting Cacti 1.2.x compatibility)
- MySQL/MariaDB via Cacti's DB abstraction layer
- PSR-12 coding standards

## Key Rules
- Use prepared statements (db_execute_prepared, db_fetch_row_prepared, etc.) for ALL queries with variables
- Use get_request_var() / get_filter_request_var() for ALL user input, never raw $_REQUEST/$_GET/$_POST
- Use html_escape() / htmlspecialchars() for ALL output of DB/user values in HTML context
- Use cacti_escapeshellarg() for ALL shell command arguments
- No PHP 8.0+ features (str_contains, match, union types, named args) - target PHP 7.4
- Use ?? and ??= operators (PHP 7.4) instead of isset() ternary patterns
- All unserialize() calls must use allowed_classes => false

## Testing
- Tests in tests/ directory
- Use Pest PHP or PHPUnit
- php -l lint check required before commit
