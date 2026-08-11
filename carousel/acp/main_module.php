<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel\acp;

/**
 * Forum Image Carousel ACP module.
 */
class main_module
{
    /** @var string */
    public $page_title;

    /** @var string */
    public $tpl_name;

    /** @var string */
    public $u_action;

    /**
     * Main ACP module
     *
     * @param int $id
     * @param string $mode
     */
    public function main($id, $mode)
    {
        global $phpbb_container;

        // Get an instance of the admin controller
        $admin_controller = $phpbb_container->get('salvocortesiano.carousel.controller');

        // Make the $u_action url available in the admin controller
        $admin_controller->set_page_url($this->u_action);

        // Load a template from adm/style for our ACP page
        $this->tpl_name = 'acp_carousel_body';

        // Set the page title for our ACP page
        $this->page_title = 'ACP_CAROUSEL_TITLE';

        // Load the display options handle in the admin controller
        $admin_controller->handle();
    }
}
