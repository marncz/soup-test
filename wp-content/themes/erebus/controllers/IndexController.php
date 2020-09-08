<?php

use Timber\Timber;

/**
 * Class IndexController
 *
 * Handles requests that go through the index.php file
 */
class IndexController
{

    /**
     * Index action
     *
     * Used as the "posts" page action in index.php
     *
     * @return array
     */
    public static function indexAction()
    {
        $context = Timber::get_context();

        $context['page'] = \Timber\Helper::transient('page', function () {
            $posts = Timber::get_posts();

            $main_stories['posts'] = $posts;

            return $main_stories;
        }, 600);

        return [
            'templates' => ['views/index/index.twig'],
            'context' => $context
        ];
    }

}
