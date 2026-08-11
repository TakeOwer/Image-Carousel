<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
    /**
     * Dependencies of this migration
     *
     * @return array
     */
    public static function depends_on()
    {
        return ['\salvocortesiano\carousel\migrations\install_carousel'];
    }

    /**
     * Add the ACP module
     *
     * @return array
     */
    public function update_data()
    {
        return [
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_CAROUSEL_TITLE'
            ]],
            ['module.add', [
                'acp',
                'ACP_CAROUSEL_TITLE',
                [
                    'module_basename'    => '\salvocortesiano\carousel\acp\main_module',
                    'modes'              => ['settings'],
                ],
            ]],
        ];
    }
}
