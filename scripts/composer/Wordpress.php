<?php

namespace Mimir;

use Composer\Script\Event;

/**
 * Class Wordpress
 * @package Mimir
 */
class Wordpress
{
    /**
     * Sets up the basic WP stuff along with moving the theme and setting up git
     *
     * @param Event $event
     */
    public static function postProjectInstall(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir . '/autoload.php';

        $io = $event->getIO();
        $io->write('Now to setup Wordpress');
        $siteName = $io->ask('Site name: ');
        $siteUrl = $io->ask('Site url: ');
        $adminUser = $io->ask('Admin username: ');
        $adminEmail = $io->ask('Admin email: ');
        $gitRepo = $io->ask('Git repo URL: ');
        $namespace = $io->ask('Choose site namespace (e.g. SiteName): ');

        // Wordpress installation
        system("wp core install --url='$siteUrl' --title='$siteName' --admin_user='$adminUser' --admin_email='$adminEmail'");
        system("wp plugin activate --all --path='$vendorDir/../'");
        system("wp theme activate erebus --path='$vendorDir/../'");

        // Rename the theme to the site name
        system("mv wp-content/themes/erebus/ wp-content/themes/" . strtolower(str_replace(' ', '-', $siteName)));

        // Add the users git repo to the project
        system('git init && git remote add origin ' . $gitRepo);

        // Set up namespaces to make sense within the project scope
        file_put_contents("wp-content/themes/" . strtolower(str_replace(' ', '-', $siteName)) . "/composer.json", str_replace('{{ SITE_NAMESPACE }}', $namespace, file_get_contents("wp-content/themes/" . strtolower(str_replace(' ', '-', $siteName)) . "/composer.json")));
    }
}
