<?php

use Timber\Timber;
use \Timber\Post;

while (have_posts()) : the_post();
    $context = Timber::get_context();
    $post = new Post();
    $context['post'] = $post;

    Timber::render(['views/page.twig'], $context);
endwhile; // End of the loop.