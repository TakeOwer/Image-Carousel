<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel;

/**
 * Extension base
 */
class ext extends \phpbb\extension\base
{
    /**
     * Check whether or not the extension can be enabled.
     * The current phpBB version should meet or exceed
     * the minimum version required by this extension.
     *
     * @return bool
     */
    public function is_enableable()
    {
        return phpbb_version_compare(PHPBB_VERSION, '3.2.0', '>=');
    }
}
