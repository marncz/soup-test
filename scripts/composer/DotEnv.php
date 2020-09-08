<?php

namespace Mimir;

use Composer\Script\Event;

/**
 * Class DotEnv
 * @package Mimir
 */
class DotEnv
{
    /**
     * @param Event $event
     */
    public static function postProjectCreate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir . '/autoload.php';

        $io = $event->getIO();
        $env = new \Symfony\Component\Dotenv\Dotenv();
        $env->load($vendorDir . '/../.env.dist');
        $vars = explode(',', $_ENV['SYMFONY_DOTENV_VARS']);

        $file_contents = '';

        /**
         * Read vars from .env.dist file
         */
        foreach ($vars as $name) {
            $file_contents .= $name . "='" . $io->ask($name . ' (' . $_ENV[$name] . '): ', $_ENV[$name]) . "'" . PHP_EOL;
        }

        /**
         * Get salts from https://api.wordpress.org/secret-key/1.1/salt/
         * I would like to eventually replace this, I don't see why we need to get salts from WP
         */

        $wp_salts = file_get_contents('https://api.wordpress.org/secret-key/1.1/salt/');

        /**
         * Get the actual values we need
         */
        $re = '/define\(\'(.*)?\',.*?\'(.*)?\'.*/m';
        preg_match_all($re, $wp_salts, $matches, PREG_PATTERN_ORDER, 0);

        for ($i = 0; $i < count($matches[1]); $i++) {
            $file_contents .= $matches[1][$i] . "='" . $matches[2][$i] . "'" . PHP_EOL;
        }

        /**
         * Write .env file
         */
        file_put_contents($vendorDir . '/../.env', $file_contents, FILE_APPEND);

    }
}