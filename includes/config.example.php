<?php
/**
 * Blemhader - Example configuration for deployment
 *
 * Copy this file to config.local.php and set your values, OR set these as
 * environment variables on the server (recommended for production).
 *
 * Required for production:
 *   DB_HOST    - MySQL host (e.g. localhost or 127.0.0.1)
 *   DB_NAME    - Database name
 *   DB_USER    - Database user
 *   DB_PASS    - Database password
 *
 * Optional:
 *   ENV        - Set to "production" to hide DB errors and disable debug (default: development)
 *   DEBUG      - Set to "1" or "true" to show detailed errors when ENV is not production
 *   BASE_URL   - Entry script name (default: index.php)
 *   SITE_NAME_AR / SITE_NAME_FR - Site name per language
 *
 * Example (Apache .htaccess or server env):
 *   SetEnv DB_HOST "127.0.0.1"
 *   SetEnv DB_NAME "blemhader_db"
 *   SetEnv DB_USER "blemhader_user"
 *   SetEnv DB_PASS "your_secure_password"
 *   SetEnv ENV "production"
 *
 * Example (PHP-FPM / nginx pool env):
 *   env[DB_HOST] = 127.0.0.1
 *   env[DB_NAME] = blemhader_db
 *   env[DB_USER] = blemhader_user
 *   env[DB_PASS] = your_secure_password
 *   env[ENV] = production
 */

// This file is for documentation only. Do not require it.
// config.php reads from getenv() and uses the fallbacks shown in config.php.
