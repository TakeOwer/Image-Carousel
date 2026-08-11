<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

// Some characters you may want to copy&paste:
// ' » " " …

$lang = array_merge($lang, [
    'CAROUSEL_TITLE'          => 'FORUM IMAGES CAROUSEL',
    'CAROUSEL_EMPTY'          => 'No images available for the carousel.',
    'CAROUSEL_PREV'           => 'Previous',
    'CAROUSEL_NEXT'           => 'Next',
    'CAROUSEL_POSTED_BY'      => 'Posted by:',
    'CAROUSEL_POSTED_ON'      => 'On date:',
    'CAROUSEL_VISITED_BY'     => 'Visited by:',
    'CAROUSEL_VISITED_USERS'  => 'users',
    'CAROUSEL_VISITED_USER'   => 'user',
]);
