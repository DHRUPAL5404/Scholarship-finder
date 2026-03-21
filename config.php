<?php
/**
 * Database Configuration File
 * Stores DB credentials and settings
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'scholarmatch_db');
define('DB_PORT', 3306);

/**
 * Environment Settings
 */
define('ENVIRONMENT', 'development');
define('DEBUG', true);

/**
 * Security Settings
 */
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
