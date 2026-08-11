<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel\acp;

/**
 * Forum Image Carousel ACP module info.
 */
class main_info
{
    /**
     * Set up the ACP module
     *
     * @return array
     */
    public function module()
    {
        return [
            'filename'  => '\salvocortesiano\carousel\acp\main_module',
            'title'     => 'ACP_CAROUSEL_TITLE',
            'version'   => '1.0.0',
            'modes'     => [
                'settings'  => [
                    'title' => 'ACP_CAROUSEL_SETTINGS',
                    'auth'  => 'ext_salvocortesiano/carousel && acl_a_board',
                    'cat'   => ['ACP_CAROUSEL_TITLE'],
                ],
            ],
        ];
    }
}
