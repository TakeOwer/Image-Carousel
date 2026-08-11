<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel\event;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Forum Image Extractor Listener
 */
class extract_images implements EventSubscriberInterface
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\user */
    protected $user;

    /** @var string */
    protected $table_prefix;
    
    /**
     * Constructor
     *
     * @param \phpbb\config\config $config
     * @param \phpbb\db\driver\driver_interface $db
     * @param \phpbb\user $user
     * @param string $table_prefix
     */
    public function __construct(
        config $config,
        driver_interface $db,
        user $user,
        $table_prefix
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->user = $user;
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
            'core.submit_post_end' => 'extract_post_images',
            'core.delete_posts_after' => 'remove_post_images',
        ];
    }

    /**
     * Extract images from a post and store them in the database
     *
     * @param \phpbb\event\data $event
     */
    public function extract_post_images($event)
    {
        // Only process if carousel is enabled
        if (!$this->config['carousel_enabled']) {
            return;
        }
        
        // Check if the post's forum is in the carousel forums list
        $forum_id = $event['data']['forum_id'];
        $carousel_forums = explode(',', $this->config['carousel_forums']);
        
        if (!in_array($forum_id, $carousel_forums)) {
            return;
        }
        
        // Extract post data
        $post_id = $event['data']['post_id'];
        $topic_id = $event['data']['topic_id'];
        $poster_name = $event['data']['poster_id'] ? $this->user->data['username'] : $event['data']['post_username'];
        $post_text = $event['data']['message'];
        $topic_title = $event['data']['topic_title'] ?? '';
        
        // Parse the post to extract images
        $this->extract_and_store_images($forum_id, $topic_id, $post_id, $post_text, $topic_title, $poster_name);
    }
    
    /**
     * Remove images from the database when a post is deleted
     *
     * @param \phpbb\event\data $event
     */
    public function remove_post_images($event)
    {
        // Only process if carousel is enabled
        if (!$this->config['carousel_enabled']) {
            return;
        }
        
        $post_ids = $event['post_ids'];
        
        if (!empty($post_ids)) {
            // Delete all images associated with these posts
            $sql = 'DELETE FROM ' . $this->table_prefix . 'carousel_log 
                    WHERE ' . $this->db->sql_in_set('post_id', $post_ids);
            $this->db->sql_query($sql);
        }
    }
    
    /**
     * Extract images from post text and store them in the database
     *
     * @param int $forum_id
     * @param int $topic_id
     * @param int $post_id
     * @param string $post_text
     * @param string $topic_title
     * @param string $poster_name
     */
    protected function extract_and_store_images($forum_id, $topic_id, $post_id, $post_text, $topic_title, $poster_name)
    {
        // Regular expression to extract image URLs from post text
        // This pattern looks for both BBCode [img] tags and HTML <img> tags
        $patterns = [
            // BBCode [img] tags
            '/\[img\](.*?)\[\/img\]/si',
            // HTML <img> tags with src attribute
            '/<img[^>]*?src=[\'"](.*?)[\'"][^>]*?>/si'
        ];
        
        $image_urls = [];
        
        // Extract image URLs using each pattern
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $post_text, $matches)) {
                foreach ($matches[1] as $img_url) {
                    // Clean the URL (remove any unwanted parameters or attributes)
                    $clean_url = trim($img_url);
                    
                    // Store unique URLs only
                    if (!in_array($clean_url, $image_urls) && !empty($clean_url)) {
                        $image_urls[] = $clean_url;
                    }
                }
            }
        }
        
        // If we found any images, store them in the database
        if (!empty($image_urls)) {
            $current_timestamp = time();
            
            foreach ($image_urls as $image_url) {
                // Prepare the data for insertion
                $sql_data = [
                    'forum_id'       => (int) $forum_id,
                    'topic_id'       => (int) $topic_id,
                    'post_id'        => (int) $post_id,
                    'image_url'      => $image_url,
                    'topic_title'    => $topic_title,
                    'poster_name'    => $poster_name,
                    'extracted_at'   => $current_timestamp,
                ];
                
                // Insert the image record
                $sql = 'INSERT INTO ' . $this->table_prefix . 'carousel_log ' .
                       '(' . implode(', ', array_keys($sql_data)) . ')' .
                       ' VALUES (' . implode(', ', array_map(function($value) {
                           return is_int($value) ? $value : '\'' . $this->db->sql_escape($value) . '\'';
                       }, $sql_data)) . ')';
                
                $this->db->sql_query($sql);
            }
        }
    }
}