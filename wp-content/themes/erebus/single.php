<?php

use MarcinTest\Kernel;
use MarcinTest\Wordpress\SingleController;
use Timber\Timber;

$action = Kernel::getAction(get_post_type());

try {
    $context = SingleController::$action();
    Timber::render($context['templates'], $context['context']);
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "\n";
}
