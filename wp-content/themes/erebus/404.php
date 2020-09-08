<?php

use Timber\Timber;
use \Timber\Post;

$postType = get_query_var('post_type');
$postName = get_query_var('name');

// If a product doesn't exist check if it is an obsolete product and redirect
if ('products' == $postType && !empty($postName)) {
    $product = get_page_by_path($postName, OBJECT, 'obsolete-products');
    if (is_object($product)) {
        wp_safe_redirect(get_permalink($product->ID), 301);
    }
}

$context = Timber::get_context();
$errorPage = get_page_by_title('404')->ID;
$post = new Post($errorPage);
$context['post'] = $post;

Timber::render(['views/404.twig'], $context);