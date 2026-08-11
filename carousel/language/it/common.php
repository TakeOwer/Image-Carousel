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
    'CAROUSEL_TITLE'          => 'Ultime News',
    'CAROUSEL_EMPTY'          => 'Nessuna immagine disponibile per il carosello.',
    'CAROUSEL_PREV'           => 'Precedente',
    'CAROUSEL_NEXT'           => 'Successivo',
    'CAROUSEL_POSTED_BY'      => 'Postato da:',
    'CAROUSEL_POSTED_ON'      => 'In data:',
    'CAROUSEL_VISITED_BY'     => 'Visitato da:',
    'CAROUSEL_VISITED_USERS'  => 'utenti',
    'CAROUSEL_VISITED_USER'   => 'utente',
]);
