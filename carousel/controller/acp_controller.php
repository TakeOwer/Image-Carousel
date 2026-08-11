<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel\controller;

use phpbb\exception\runtime_exception;

/**
 * ACP Controller
 */
class acp_controller
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\log\log */
    protected $log;

    /** @var string */
    protected $table_prefix;

    /** @var string */
    protected $u_action;

    /** @var \phpbb\auth\auth */
    protected $auth;

    /**
     * Constructor
     *
     * @param \phpbb\config\config $config
     * @param \phpbb\template\template $template
     * @param \phpbb\user $user
     * @param \phpbb\request\request $request
     * @param \phpbb\db\driver\driver_interface $db
     * @param \phpbb\log\log $log
     * @param string $table_prefix
     * @param \phpbb\auth\auth $auth
     */
    public function __construct(
        \phpbb\config\config $config,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\request\request $request,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\log\log $log,
        $table_prefix,
        \phpbb\auth\auth $auth
    ) {
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;
        $this->request = $request;
        $this->db = $db;
        $this->log = $log;
        $this->table_prefix = $table_prefix;
        $this->auth = $auth;
    }

    /**
     * Display the options a user can configure for this extension
     *
     * @return void
     * @throws runtime_exception
     */
    public function handle()
    {
        // Check if user has permission to access ACP
        if (!$this->user->data['is_registered'] || !$this->auth->acl_get('a_')) {
            throw new runtime_exception('NO_AUTH_OPERATION');
        }

        try {
            // Add the ACP lang file
            $this->user->add_lang_ext('salvocortesiano/carousel', 'info_acp_carousel');

            // Create a form key for checking form submissions
            add_form_key('salvocortesiano_carousel_settings');

            // Get all carousels
            $sql = 'SELECT * FROM ' . $this->table_prefix . 'carousels ORDER BY carousel_id ASC';
            $result = $this->db->sql_query($sql);
            
            if (!$result) {
                throw new runtime_exception('CAROUSEL_DB_ERROR');
            }
            
            while ($row = $this->db->sql_fetchrow($result))
            {
                $this->template->assign_block_vars('carousels', [
                    'CAROUSEL_ID' => (int) $row['carousel_id'],
                    'CAROUSEL_NAME' => htmlspecialchars($row['carousel_name']),
                    'CAROUSEL_TITLE' => htmlspecialchars($row['carousel_title']),
                    'CAROUSEL_ENABLED' => (bool) $row['carousel_enabled'],
                    'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id=' . (int) $row['carousel_id'],
                    'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id=' . (int) $row['carousel_id'],
                ]);
            }
            $this->db->sql_freeresult($result);

            // Handle actions
            $action = $this->request->variable('action', '');
            $carousel_id = $this->request->variable('id', 0);

            if ($action === 'add' || $action === 'edit')
            {
                $this->handle_carousel_form($action, $carousel_id);
            }
            else if ($action === 'delete' && $carousel_id)
            {
                $this->handle_carousel_delete($carousel_id);
            }
            else
            {
                // Display the carousel list
                $this->template->assign_vars([
                    'U_ACTION' => $this->u_action,
                    'U_ADD_CAROUSEL' => $this->u_action . '&amp;action=add',
                    'S_EDIT_MODE' => false,
                ]);
            }
        } catch (\Exception $e) {
            // Log the error
            error_log('Carousel ACP Error: ' . $e->getMessage());
            
            // Display error message
            trigger_error($this->user->lang('CAROUSEL_ERROR') . adm_back_link($this->u_action));
        }
    }

    /**
     * Handle carousel form (add/edit)
     *
     * @param string $action The action (add/edit)
     * @param int $carousel_id The carousel ID (for edit)
     * @throws runtime_exception
     */
    protected function handle_carousel_form($action, $carousel_id = 0)
    {
        try {
            $carousel_data = [
                'carousel_name' => '',
                'carousel_title' => '',
                'carousel_forums' => '',
                'carousel_images_per_forum' => 5,
                'carousel_scroll_speed' => 5000,
                'carousel_scroll_direction' => 'rtl',
                'carousel_enabled' => 1,
            ];

            if ($action === 'edit' && $carousel_id)
            {
                $sql = 'SELECT * FROM ' . $this->table_prefix . 'carousels WHERE carousel_id = ' . (int) $carousel_id;
                $result = $this->db->sql_query($sql);
                
                if (!$result) {
                    throw new runtime_exception('CAROUSEL_DB_ERROR');
                }
                
                $carousel_data = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);
                
                if (!$carousel_data) {
                    throw new runtime_exception('CAROUSEL_NOT_FOUND');
                }
            }

            // Get all forum data
            $sql = 'SELECT forum_id, forum_name, forum_type
                    FROM ' . FORUMS_TABLE . '
                    WHERE forum_type = ' . FORUM_POST . '
                    ORDER BY left_id ASC';
            $result = $this->db->sql_query($sql);
            
            if (!$result) {
                throw new runtime_exception('CAROUSEL_DB_ERROR');
            }

            $selected_forums = !empty($carousel_data['carousel_forums']) ? explode(',', $carousel_data['carousel_forums']) : [];
            
            while ($row = $this->db->sql_fetchrow($result))
            {
                $this->template->assign_block_vars('forums', [
                    'FORUM_ID' => (int) $row['forum_id'],
                    'FORUM_NAME' => htmlspecialchars($row['forum_name']),
                    'SELECTED' => in_array($row['forum_id'], $selected_forums),
                ]);
            }
            $this->db->sql_freeresult($result);

            // Check if form has been submitted
            if ($this->request->is_set_post('submit'))
            {
                // Test if the submitted form is valid
                if (!check_form_key('salvocortesiano_carousel_settings'))
                {
                    throw new runtime_exception('FORM_INVALID');
                }

                // Get form data
                $carousel_data = [
                    'carousel_name' => $this->request->variable('carousel_name', ''),
                    'carousel_title' => $this->request->variable('carousel_title', ''),
                    'carousel_forums' => implode(',', $this->request->variable('carousel_forums', [0])),
                    'carousel_images_per_forum' => $this->request->variable('carousel_images_per_forum', 5),
                    'carousel_scroll_speed' => $this->request->variable('carousel_scroll_speed', 5000),
                    'carousel_scroll_direction' => $this->request->variable('carousel_scroll_direction', 'rtl'),
                    'carousel_enabled' => $this->request->variable('carousel_enabled', 1),
                ];

                // Validate input
                if (empty($carousel_data['carousel_name'])) {
                    throw new runtime_exception('CAROUSEL_NAME_REQUIRED');
                }

                if (empty($carousel_data['carousel_title'])) {
                    throw new runtime_exception('CAROUSEL_TITLE_REQUIRED');
                }

                $carousel_data['carousel_images_per_forum'] = max(3, min(50, (int) $carousel_data['carousel_images_per_forum']));
                $carousel_data['carousel_scroll_speed'] = max(1000, min(80000, (int) $carousel_data['carousel_scroll_speed']));
                $carousel_data['carousel_scroll_direction'] = in_array($carousel_data['carousel_scroll_direction'], ['ltr', 'rtl']) ? $carousel_data['carousel_scroll_direction'] : 'rtl';
                $carousel_data['carousel_enabled'] = (bool) $carousel_data['carousel_enabled'];

                if ($action === 'add')
                {
                    $carousel_data['created_at'] = time();
                    $carousel_data['updated_at'] = time();

                    $sql = 'INSERT INTO ' . $this->table_prefix . 'carousels ' . $this->db->sql_build_array('INSERT', $carousel_data);
                    $result = $this->db->sql_query($sql);
                    
                    if (!$result) {
                        throw new runtime_exception('CAROUSEL_DB_ERROR');
                    }

                    // Add action to the admin log
                    $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CAROUSEL_ADDED', time(), [$carousel_data['carousel_name']]);
                }
                else
                {
                    $carousel_data['updated_at'] = time();

                    $sql = 'UPDATE ' . $this->table_prefix . 'carousels SET ' . $this->db->sql_build_array('UPDATE', $carousel_data) . ' WHERE carousel_id = ' . (int) $carousel_id;
                    $result = $this->db->sql_query($sql);
                    
                    if (!$result) {
                        throw new runtime_exception('CAROUSEL_DB_ERROR');
                    }

                    // Add action to the admin log
                    $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CAROUSEL_UPDATED', time(), [$carousel_data['carousel_name']]);
                }

                // Confirm to the user that the settings have been updated
                trigger_error($this->user->lang('ACP_CAROUSEL_SETTING_SAVED') . adm_back_link($this->u_action));
            }

            // Set output variables for display in the template
            $this->template->assign_vars([
                'U_ACTION' => $this->u_action . '&amp;action=' . $action . ($carousel_id ? '&amp;id=' . $carousel_id : ''),
                'CAROUSEL_NAME' => htmlspecialchars($carousel_data['carousel_name']),
                'CAROUSEL_TITLE' => htmlspecialchars($carousel_data['carousel_title']),
                'CAROUSEL_IMAGES_PER_FORUM' => (int) $carousel_data['carousel_images_per_forum'],
                'CAROUSEL_SCROLL_SPEED' => (int) $carousel_data['carousel_scroll_speed'],
                'CAROUSEL_SCROLL_DIRECTION' => htmlspecialchars($carousel_data['carousel_scroll_direction']),
                'CAROUSEL_ENABLED' => (bool) $carousel_data['carousel_enabled'],
                'S_EDIT_MODE' => true,
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Carousel Form Error: ' . $e->getMessage());
            
            // Display error message
            trigger_error($this->user->lang('CAROUSEL_ERROR') . adm_back_link($this->u_action));
        }
    }

    /**
     * Handle carousel deletion
     *
     * @param int $carousel_id The carousel ID to delete
     * @throws runtime_exception
     */
    protected function handle_carousel_delete($carousel_id)
    {
        try {
            // Get carousel name for logging
            $sql = 'SELECT carousel_name FROM ' . $this->table_prefix . 'carousels WHERE carousel_id = ' . (int) $carousel_id;
            $result = $this->db->sql_query($sql);
            
            if (!$result) {
                throw new runtime_exception('CAROUSEL_DB_ERROR');
            }
            
            $carousel_name = $this->db->sql_fetchfield('carousel_name');
            $this->db->sql_freeresult($result);
            
            if (!$carousel_name) {
                throw new runtime_exception('CAROUSEL_NOT_FOUND');
            }

            // Delete the carousel
            $sql = 'DELETE FROM ' . $this->table_prefix . 'carousels WHERE carousel_id = ' . (int) $carousel_id;
            $result = $this->db->sql_query($sql);
            
            if (!$result) {
                throw new runtime_exception('CAROUSEL_DB_ERROR');
            }

            // Add action to the admin log
            $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CAROUSEL_DELETED', time(), [$carousel_name]);

            // Confirm to the user that the carousel has been deleted
            trigger_error($this->user->lang('ACP_CAROUSEL_DELETED') . adm_back_link($this->u_action));
        } catch (\Exception $e) {
            // Log the error
            error_log('Carousel Delete Error: ' . $e->getMessage());
            
            // Display error message
            trigger_error($this->user->lang('CAROUSEL_ERROR') . adm_back_link($this->u_action));
        }
    }

    /**
     * Set action URL
     *
     * @param string $u_action Custom form action
     * @return void
     */
    public function set_page_url($u_action)
    {
        $this->u_action = $u_action;
    }
}
