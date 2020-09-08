<?php

use Timber\Timber;
use MarcinTest\IndexController;

$page = IndexController::indexAction();

Timber::render($page['templates'], $page['context']);