<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

namespace salvocortesiano\carousel\migrations;

class install_carousel extends \phpbb\db\migration\migration
{
    /**
     * If this migration depends on other migrations.
     *
     * @return array List of migrations
     */
    public static function depends_on()
    {
        return ['\phpbb\db\migration\data\v32x\v321'];
    }
    
    /**
     * Update the database schema.
     * 
     * @return array Array of schema changes
     */
    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'carousel_config' => [
                    'COLUMNS' => [
                        'config_id' => ['UINT', null, 'auto_increment'],
                        'config_name' => ['VCHAR:255', ''],
                        'config_value' => ['TEXT', ''],
                        'is_dynamic' => ['BOOL', 0],
                        'created_at' => ['TIMESTAMP', 0],
                        'updated_at' => ['TIMESTAMP', 0],
                    ],
                    'PRIMARY_KEY' => 'config_id',
                    'KEYS' => [
                        'config_name' => ['UNIQUE', 'config_name'],
                    ],
                ],
                $this->table_prefix . 'carousel_log' => [
                    'COLUMNS' => [
                        'log_id' => ['UINT', null, 'auto_increment'],
                        'forum_id' => ['UINT', 0],
                        'topic_id' => ['UINT', 0],
                        'post_id' => ['UINT', 0],
                        'image_url' => ['TEXT', ''],
                        'topic_title' => ['VCHAR:255', ''],
                        'poster_name' => ['VCHAR:255', ''],
                        'extracted_at' => ['TIMESTAMP', 0],
                    ],
                    'PRIMARY_KEY' => 'log_id',
                ],
                $this->table_prefix . 'carousels' => [
                    'COLUMNS' => [
                        'carousel_id' => ['UINT', null, 'auto_increment'],
                        'carousel_name' => ['VCHAR:255', ''],
                        'carousel_title' => ['VCHAR:255', ''],
                        'carousel_forums' => ['TEXT', ''],
                        'carousel_images_per_forum' => ['UINT', 5],
                        'carousel_scroll_speed' => ['UINT', 5000],
                        'carousel_scroll_direction' => ['VCHAR:10', 'rtl'],
                        'carousel_enabled' => ['BOOL', 1],
                        'created_at' => ['TIMESTAMP', 0],
                        'updated_at' => ['TIMESTAMP', 0],
                    ],
                    'PRIMARY_KEY' => 'carousel_id',
                ],
            ],
        ];
    }

    /**
     * Remove the schema when uninstalling.
     * 
     * @return array Array of schema changes
     */
    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'carousel_config',
                $this->table_prefix . 'carousel_log',
                $this->table_prefix . 'carousels',
            ],
        ];
    }

    /**
     * Add the carousel configuration options
     *
     * @return array Array of data update instructions
     */
    public function update_data()
    {
        return [
            // Add config entries
            ['config.add', ['carousel_version', '1.0.0']],
            ['config.add', ['carousel_enabled', 1]],
            ['config.add', ['carousel_forums', '']],
            ['config.add', ['carousel_images_per_forum', 5]],
            ['config.add', ['carousel_scroll_speed', 5000]],
            ['config.add', ['carousel_scroll_direction', 'rtl']],
            
            // Add custom database records for multilanguage titles
            ['custom', [[$this, 'insert_config_records']]],
        ];
    }
    
    /**
     * Insert additional config records
     */
    public function insert_config_records()
    {
        // Inserisci configurazioni aggiuntive nel database
        $carousel_configs = [
            ['carousel_title_en', 'Forum Image Carousel', 0],
            ['carousel_title_it', 'Carosello Immagini Forum', 0],
        ];
        
        // Formatta la data corrente per il database
        $current_timestamp = time();
        
        // Inserisci i record
        foreach ($carousel_configs as $config)
        {
            $sql = 'INSERT INTO ' . $this->table_prefix . 'carousel_config ' .
                '(config_name, config_value, is_dynamic, created_at, updated_at) ' .
                'VALUES (\'' . $config[0] . '\', \'' . $config[1] . '\', ' . $config[2] . ', ' . $current_timestamp . ', ' . $current_timestamp . ')';
            
            $this->db->sql_query($sql);
        }
    }
}
