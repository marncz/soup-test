<?php

use MarcinTest\Kernel;
use MarcinTest\Wordpress\ArchiveController;
use Timber\Timber;

$postType = get_post_type();

// For empty taxonomies we need to fetch the post type from the tax object
if (empty($postType) && is_tax()) {
    $taxonomy = get_taxonomy(get_queried_object()->taxonomy);
    $postType = array_shift($taxonomy->object_type);
}

$action = Kernel::getAction($postType);

try {
    $context = ArchiveController::$action();
    Timber::render($context['templates'], $context['context']);
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "\n";
}