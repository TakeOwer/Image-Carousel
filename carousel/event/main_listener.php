<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\exception\runtime_exception;

/**
 * Forum Image Carousel Event listener.
 */
class main_listener implements EventSubscriberInterface
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\auth\auth */
    protected $auth;

    /** @var \phpbb\cache\driver\driver_interface */
    protected $cache;

    /** @var string phpbb_root_path */
    protected $phpbb_root_path;

    /** @var string php_ext */
    protected $php_ext;

    /** @var string */
    protected $table_prefix;

    /**
     * Constructor
     *
     * @param \phpbb\config\config $config
     * @param \phpbb\db\driver\driver_interface $db
     * @param \phpbb\template\template $template
     * @param \phpbb\\user $user
     * @param \phpbb\auth\auth $auth
     * @param \phpbb\cache\driver\driver_interface $cache
     * @param string $phpbb_root_path Root path
     * @param string $php_ext PHP extension
     * @param string $table_prefix Database table prefix
     */
    public function __construct(
        \phpbb\config\config $config,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\auth\auth $auth,
        \phpbb\cache\driver\driver_interface $cache,
        $phpbb_root_path,
        $php_ext,
        $table_prefix
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->template = $template;
        $this->user = $user;
        $this->auth = $auth;
        $this->cache = $cache;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        $this->table_prefix = $table_prefix;
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'core.user_setup' => 'load_language_on_setup',
            'core.page_header' => 'add_page_header_link',
            'core.index_modify_page_title' => 'display_carousel',
        ];
    }

    /**
     * Load the extension language file during user setup
     *
     * @param \phpbb\event\data $event The event object
     */
    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'salvocortesiano/carousel',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    /**
     * Add page header links
     */
    public function add_page_header_link()
    {
        $this->template->assign_vars([
            'S_CAROUSEL_ENABLED' => (bool) $this->config['carousel_enabled'],
        ]);
    }

    /**
     * Display carousel on index page
     */
    public function display_carousel()
    {
        try {
            // Get all enabled carousels
            $sql = 'SELECT * FROM ' . $this->table_prefix . 'carousels WHERE carousel_enabled = 1 ORDER BY carousel_id ASC';
            $result = $this->db->sql_query($sql);
            
            if (!$result) {
                throw new runtime_exception('CAROUSEL_DB_ERROR');
            }
            
            while ($carousel = $this->db->sql_fetchrow($result))
            {
                // Validate carousel data
                if (!isset($carousel['carousel_id']) || !isset($carousel['carousel_title'])) {
                    continue;
                }

                // Check if we have selected forums
                $selected_forums = !empty($carousel['carousel_forums']) ? explode(',', $carousel['carousel_forums']) : [];
                if (empty($selected_forums) || (count($selected_forums) === 1 && $selected_forums[0] == 0))
                {
                    continue;
                }

                // Filter forums based on user permissions
                $forum_read_ary = $this->auth->acl_getf('f_read');
                $allowed_forums = [];
                foreach ($selected_forums as $forum_id)
                {
                    if (isset($forum_read_ary[$forum_id]['f_read']) && $forum_id != 0)
                    {
                        $allowed_forums[] = (int) $forum_id;
                    }
                }

                if (empty($allowed_forums))
                {
                    continue;
                }

                // Get images for this carousel
                $carousel_images = $this->get_carousel_images($allowed_forums, $carousel['carousel_images_per_forum']);
                
                if (!empty($carousel_images))
                {
                    $this->template->assign_block_vars('carousels', [
                        'CAROUSEL_ID' => (int) $carousel['carousel_id'],
                        'CAROUSEL_TITLE' => htmlspecialchars($carousel['carousel_title']),
                        'CAROUSEL_SCROLL_SPEED' => (int) $carousel['carousel_scroll_speed'],
                        'CAROUSEL_SCROLL_DIRECTION' => htmlspecialchars($carousel['carousel_scroll_direction']),
                    ]);

                    foreach ($carousel_images as $index => $image)
                    {
                        $viewtopic_url = append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", "f={$image['forum_id']}&amp;t={$image['topic_id']}");
                        
                        // Format topic time for display
                        $topic_date = $this->user->format_date($image['topic_time']);
                        
                        // User color markup for poster name
                        $username_string = ($image['poster_id'] != ANONYMOUS) ? 
                            '<span style="color: #' . $image['poster_colour'] . ';">' . htmlspecialchars($image['poster_name']) . '</span>' : 
                            htmlspecialchars($image['poster_name']);
                        
                        $this->template->assign_block_vars('carousels.carousel_images', [
                            'URL'            => $viewtopic_url,
                            'TITLE'          => htmlspecialchars($image['topic_title']),
                            'IMAGE'          => htmlspecialchars($image['image_url']),
                            'POSTER_NAME'    => htmlspecialchars($image['poster_name']),
                            'POSTER_COLOUR'  => htmlspecialchars($image['poster_colour']),
                            'USERNAME_FULL'  => $username_string,
                            'TOPIC_DATE'     => $topic_date,
                            'TOPIC_VIEWS'    => (int) ($image['topic_views'] ?? 0),
                            'S_FIRST_ROW'    => ($index === 0),
                            'S_LAST_ROW'     => ($index === count($carousel_images) - 1),
                        ]);
                    }
                }
            }
            $this->db->sql_freeresult($result);
            
            // Set carousel status in template
            $this->template->assign_vars([
                'S_DISPLAY_CAROUSEL' => true,
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Carousel Error: ' . $e->getMessage());
            
            // Set carousel status to false in case of error
            $this->template->assign_vars([
                'S_DISPLAY_CAROUSEL' => false,
            ]);
        }
    }

    /**
     * Get carousel images from forums
     *
     * @param array $forum_ids Array of forum IDs
     * @param int $images_per_forum Number of images to get per forum
     * @return array Array of carousel images
     * @throws runtime_exception
     */
    protected function get_carousel_images($forum_ids, $images_per_forum)
    {
        if (!is_array($forum_ids) || empty($forum_ids)) {
            return [];
        }

        $images_per_forum = (int) $images_per_forum;
        if ($images_per_forum <= 0) {
            $images_per_forum = 1;
        }

        $carousel_images = [];
        
        // Get cached carousel data if available
        $cache_key = '_carousel_images_v2_' . md5(implode(',', $forum_ids) . '_' . $images_per_forum);
        $cached_images = $this->cache->get($cache_key);
        
        if ($cached_images !== false)
        {
            return $cached_images;
        }
        
        // Get images from each forum
        foreach ($forum_ids as $forum_id)
        {
            try {
                // Get latest topics from this forum with additional user information
                $sql = 'SELECT t.topic_id, t.topic_title, t.forum_id, t.topic_time, t.topic_views, t.topic_poster,
                               t.topic_first_poster_name, t.topic_first_poster_colour, p.post_id, p.post_text
                        FROM ' . TOPICS_TABLE . ' t
                        JOIN ' . POSTS_TABLE . ' p ON p.post_id = t.topic_first_post_id
                        WHERE t.forum_id = ' . (int) $forum_id . '
                        AND t.topic_status <> ' . ITEM_MOVED . '
                        ORDER BY t.topic_time DESC';
                $result = $this->db->sql_query($sql);
                
                if (!$result) {
                    throw new runtime_exception('CAROUSEL_DB_ERROR');
                }
                
                $forum_images_count = 0;
                
                while ($row = $this->db->sql_fetchrow($result))
                {
                    if ($forum_images_count >= $images_per_forum) {
                        break;
                    }

                    // Extract all images from the post
                    $image_urls = $this->extract_all_images($row['post_text']);
                    
                    // Take only the first valid image from the post
                    foreach ($image_urls as $image_url)
                    {
                        if ($this->is_valid_image_url($image_url))
                        {
                            $carousel_images[] = [
                                'forum_id'    => (int) $row['forum_id'],
                                'topic_id'    => (int) $row['topic_id'],
                                'post_id'     => (int) $row['post_id'],
                                'topic_title' => $row['topic_title'],
                                'image_url'   => $image_url,
                                'poster_id'   => (int) $row['topic_poster'],
                                'poster_name' => $row['topic_first_poster_name'],
                                'poster_colour' => $row['topic_first_poster_colour'],
                                'topic_time'  => (int) $row['topic_time'],
                                'topic_views' => (int) $row['topic_views'],
                            ];
                            
                            $forum_images_count++;
                            break; // Stop after finding the first valid image for this post
                        }
                    }
                }
                $this->db->sql_freeresult($result);
            } catch (\Exception $e) {
                if (isset($result)) {
                    $this->db->sql_freeresult($result);
                }
                error_log('Carousel Error in forum ' . $forum_id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        // Cache the results
        if (!empty($carousel_images)) {
            $this->cache->put($cache_key, $carousel_images, 3600); // Cache for 1 hour
        }
        
        return $carousel_images;
    }

    /**
     * Extract all images from post text
     *
     * @param string $text Post text
     * @return array Array of image URLs
     */
    protected function extract_all_images($text)
    {
        if (empty($text)) {
            return [];
        }

        $image_urls = [];
        
        // Extract BBCode [img] tags
        preg_match_all('/\[img\](.*?)\[\/img\]/i', $text, $matches);
        if (!empty($matches[1])) {
            $image_urls = array_merge($image_urls, $matches[1]);
        }
        
        // Extract BBCode [img=width,height] tags
        preg_match_all('/\[img=(\d+),(\d+)\](.*?)\[\/img\]/i', $text, $matches);
        if (!empty($matches[3])) {
            $image_urls = array_merge($image_urls, $matches[3]);
        }
        
        // Extract HTML img tags
        preg_match_all('/<img[^>]+src=([\'"])(.*?)\1[^>]*>/i', $text, $matches);
        if (!empty($matches[2])) {
            $image_urls = array_merge($image_urls, $matches[2]);
        }
        
        return array_unique($image_urls);
    }

    /**
     * Validate image URL
     *
     * @param string $url Image URL
     * @return bool True if valid image URL
     */
    protected function is_valid_image_url($url)
    {
        if (empty($url)) {
            return false;
        }

        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Check file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        return in_array($extension, $allowed_extensions);
    }
}
