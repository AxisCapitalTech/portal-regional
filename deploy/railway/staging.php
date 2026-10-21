<?php
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos((string)$_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) { $_SERVER['HTTPS'] = 'on'; }
defined('WP_ENVIRONMENT_TYPE') || define('WP_ENVIRONMENT_TYPE', 'staging');
defined('DISALLOW_FILE_EDIT') || define('DISALLOW_FILE_EDIT', true);
defined('DISALLOW_FILE_MODS') || define('DISALLOW_FILE_MODS', true);
defined('DISABLE_WP_CRON') || define('DISABLE_WP_CRON', true);
defined('AUTOMATIC_UPDATER_DISABLED') || define('AUTOMATIC_UPDATER_DISABLED', true);
