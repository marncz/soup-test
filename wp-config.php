<?php

use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$env = new Dotenv();
$env->load(__DIR__ . '/.env');

/**
 * If we are in DEBUG mode, enable symfony debug component
 */
if (filter_var(getenv('WP_DEBUG'), FILTER_VALIDATE_BOOLEAN) === true) {
    Debug::enable();
}

foreach (explode(',', $_ENV['SYMFONY_DOTENV_VARS']) as $env_var) {
    define($env_var, filter_var($_ENV[$env_var], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $_ENV[$env_var]);
}

define('AS3CF_SETTINGS', serialize(array(
    'provider' => 'aws',
    'access-key-id' => getenv('S3_AWS_ID'),
    'secret-access-key' => getenv('S3_AWS_SECRET'),
)));

$table_prefix = 'wp_';

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
