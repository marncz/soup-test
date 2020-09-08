<?php

global $wp_query;
use Timber\Timber;
use \Timber\User;
use \Timber\PostQuery;

$context = Timber::context();
$context['posts'] = new PostQuery();

if (isset($wp_query->query_vars['author'])) {
    $author = new User($wp_query->query_vars['author']);

    $context['author'] = $author;

    $args = [
        'author' => $author->ID,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'posts_per_page' => 3
    ];

    $context['posts'] = get_posts($args);

    $context['postsUrl'] = get_permalink( get_option( 'page_for_posts' ) );
    $context['authorPostsUrl'] = get_permalink( get_option( 'page_for_posts' ) ) . '?author=' . $author->ID;
}

Timber::render(['author.twig', 'archive.twig'], $context);