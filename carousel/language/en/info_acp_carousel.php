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
    'ACP_CAROUSEL_TITLE'            => 'Forum Image Carousel',
    'ACP_CAROUSEL_SETTINGS'         => 'Carousel Settings',
    'ACP_CAROUSEL_SETTING_SAVED'    => 'Forum Image Carousel settings have been saved successfully.',
    'ACP_CAROUSEL_DELETED'          => 'Carousel has been deleted successfully.',
    
    'ACP_CAROUSEL_ADD'              => 'Add new carousel',
    'ACP_CAROUSEL_NAME'             => 'Carousel name',
	'ACP_CAROUSEL_ENABLED'          => 'Carousel Enabled?',
    'ACP_CAROUSEL_NAME_EXPLAIN'     => 'Enter a unique name for this carousel.',
    'ACP_CAROUSEL_TITLE_EXPLAIN'    => 'Enter the title that will be displayed above the carousel.',
    'ACP_CAROUSEL_NONE'             => 'No carousels have been created yet.',
    
    'ACP_CAROUSEL_ENABLE'           => 'Enable carousel',
    'ACP_CAROUSEL_ENABLE_EXPLAIN'   => 'If set to yes, this carousel will be displayed on the index page.',
    
    'ACP_CAROUSEL_FORUMS'           => 'Select forums',
    'ACP_CAROUSEL_FORUMS_EXPLAIN'   => 'Select the forums from which images will be extracted for the carousel. Hold CTRL to select multiple forums.',
    
    'ACP_CAROUSEL_IMAGES'           => 'Images per forum',
    'ACP_CAROUSEL_IMAGES_EXPLAIN'   => 'Number of images to extract from each forum (1-10).',
    
    'ACP_CAROUSEL_SPEED'            => 'Carousel scroll speed',
    'ACP_CAROUSEL_SPEED_EXPLAIN'    => 'Time in milliseconds between slides (1000 = 1 second).',
    
    'ACP_CAROUSEL_DIRECTION'        => 'Scroll direction',
    'ACP_CAROUSEL_DIRECTION_EXPLAIN'=> 'Choose the direction images will scroll in the carousel.',
    'ACP_CAROUSEL_DIRECTION_LTR'    => 'Left to Right',
    'ACP_CAROUSEL_DIRECTION_RTL'    => 'Right to Left',
    
    'LOG_CAROUSEL_SETTINGS'         => '<strong>Forum Image Carousel settings updated</strong>',
    'LOG_CAROUSEL_ADDED'            => '<strong>New carousel added</strong><br>» %s',
    'LOG_CAROUSEL_UPDATED'          => '<strong>Carousel updated</strong><br>» %s',
    'LOG_CAROUSEL_DELETED'          => '<strong>Carousel deleted</strong><br>» %s',
]);
