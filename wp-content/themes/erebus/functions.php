<?php

use MarcinTest\Kernel;

$kernel = new Kernel();

// namespaces hack
require(ABSPATH . 'vendor/nanosoup/nemesis/src/ACF/Blocks/Block.php');
require(ABSPATH . 'vendor/nanosoup/nemesis/src/ACF/Blocks/BlockInterface.php');

$block = new \MarcinTest\ACF\Blocks\Content\Block5050();
$block->registerBlock();
$block->registerFieldGroup();

$block = new \MarcinTest\ACF\Blocks\Content\HeroSocial();
$block->registerBlock();
$block->registerFieldGroup();