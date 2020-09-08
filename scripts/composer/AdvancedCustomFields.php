<?php

namespace Mimir;

use Composer\Script\Event;

class AdvancedCustomFields
{
    public static function preInstallCmd(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir . '/autoload.php';

        $io = $event->getIO();
        $key = $io->ask('ACF Key: ');

        $io->write(system("export ACF_PRO_KEY='$key'"));
        $file_contents = 'ACF_PRO_KEY="' . $key . '"';
        file_put_contents($vendorDir . '/../.env', $file_contents);
    }
}