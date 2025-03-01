<?php
namespace ClaimsAngel\Admin\Prospecting;

class MyProspects {
    /**
     * Singleton instance
     *
     * @var MyProspects|null
     */
    private static $instance = null;
    
    /**
     * Records per page for each table
     */
    private $per_page = 20;

    /**
     * Get the singleton instance
     *
     * @return MyProspects
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - register our admin menu page.
     */
    private function __construct() {
        add_action('admin_menu', [ $this, 'add_admin_menu' ], 11);
    }

    /**
     * Register the My Prospects admin menu page.
     */
    public function add_admin_menu() {
        // Make sure this slug matches exactly with what's in the parent menu
        $parent_slug = 'prospects';
        
        $page_hook = add_submenu_page(
            $parent_slug,                         // Parent slug
            'My Prospects',                       // Page title
            'My Prospects',                       // Menu title
            'manage_options',                     // Capability
            'my-prospects',                       // Menu slug
            [ $this, 'render_page' ]              // Callback function
        );

        // Use the AdminAccessController to restrict access only to administrators.
        \ClaimsAngel\Admin\AdminAccessController::get_instance()->register_admin_page(
            'my-prospects',
            ['administrator'],                    // Allowed roles: administrators only.
            [
                'page_title' => 'My Prospects',
                'menu_title' => 'My Prospects',
                'menu_slug'  => 'my-prospects'
            ],
            false
        );
    }

    /**
     * Get metadata for specific prospect posts in batch
     * 
     * @param array $prospect_ids Post IDs to fetch metadata for
     * @return array Organized array of prospect data
     */
    private function get_prospects_data_batch($prospect_ids) {
        if (empty($prospect_ids)) {
            return [];
        }
        
        global $wpdb;
        
        // Create placeholders for the SQL query
        $placeholders = implode(',', array_fill(0, count($prospect_ids), '%d'));
        
        // Get all metadata for these posts in a single query
        $metadata = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_key, meta_value 
                 FROM {$wpdb->postmeta} 
                 WHERE post_id IN ($placeholders)
                 AND meta_key IN ('business_name', 'date_created', 'call_logs', 'call_back_reminders')",
                ...$prospect_ids
            ),
            ARRAY_A
        );
        
        // Organize metadata by post
        $organized_metadata = [];
        foreach ($metadata as $meta) {
            $post_id = $meta['post_id'];
            $key = $meta['meta_key'];
            $value = $meta['meta_value'];
            
            if (!isset($organized_metadata[$post_id])) {
                $organized_metadata[$post_id] = [
                    'id' => $post_id,
                    'business_name' => '',
                    'date_created' => '',
                    'call_logs' => [],
                    'call_back_reminders' => []
                ];
            }
            
            // Handle serialized data for arrays
            if (in_array($key, ['call_logs', 'call_back_reminders'])) {
                $unserialized_value = maybe_unserialize($value);
                $organized_metadata[$post_id][$key] = is_array($unserialized_value) ? $unserialized_value : [];
            } else {
                $organized_metadata[$post_id][$key] = $value;
            }
        }
        
        // Convert to a simple array format
        $prospects_data = [];
        foreach ($organized_metadata as $post_data) {
            $prospects_data[] = $post_data;
        }
        
        return $prospects_data;
    }
    
    /**
     * Get prospects with reminders due today or earlier
     * 
     * @param int $user_id Current user ID
     * @param string $country_code User's country code
     * @param int $page Current page number
     * @return array Prospects data with pagination info
     */
    private function get_due_reminder_prospects($user_id, $country_code, $page = 1) {
        // Create a cache key
        $cache_key = "due_reminders_{$user_id}_{$country_code}_{$page}_{$this->per_page}";
        $cached_result = wp_cache_get($cache_key);
        
        if (false !== $cached_result) {
            return $cached_result;
        }
        
        // Base query args for all prospects
        $args = [
            'post_type'      => 'business',
            'posts_per_page' => $this->per_page,
            'paged'          => $page,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'business_status',
                    'value'   => 'prospect',
                    'compare' => '='
                ],
                [
                    'key'     => 'prospect_prospector',
                    'value'   => $user_id,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'business_delete',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'business_delete',
                        'value'   => 'yes',
                        'compare' => '!='
                    ]
                ],
                [
                    'key'     => 'country',
                    'value'   => $country_code,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'prospect_closer',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'prospect_closer',
                        'value'   => '',
                        'compare' => '='
                    ]
                ],
                // Add criteria for call reminders
                [
                    'key'     => 'call_back_reminders',
                    'compare' => 'EXISTS',
                ]
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'business_name',
            'order'    => 'ASC'
        ];
        
        $query = new \WP_Query($args);
        $prospect_ids = $query->posts;
        $total = $query->found_posts;
        $max_pages = $query->max_num_pages;
        wp_reset_postdata();
        
        // Get metadata for these prospects
        $prospects_data = $this->get_prospects_data_batch($prospect_ids);
        
        // Filter to only include those with due reminders
        $today_start = strtotime('today midnight');
        $filtered_prospects = [];
        
        foreach ($prospects_data as $prospect) {
            $has_due_reminder = false;
            $earliest_reminder_date = null;
            
            if (!empty($prospect['call_back_reminders']) && is_array($prospect['call_back_reminders'])) {
                foreach ($prospect['call_back_reminders'] as $reminder) {
                    if (isset($reminder['reminder_date'])) {
                        $reminder_timestamp = strtotime($reminder['reminder_date']);
                        
                        if ($reminder_timestamp !== false) {
                            $reminder_day_start = strtotime('midnight', $reminder_timestamp);
                            
                            if ($reminder_day_start <= $today_start) {
                                $has_due_reminder = true;
                                // Track the earliest reminder date
                                if ($earliest_reminder_date === null || $reminder_day_start < $earliest_reminder_date) {
                                    $earliest_reminder_date = $reminder_day_start;
                                }
                            }
                        }
                    }
                }
            }
            
            if ($has_due_reminder) {
                // Add the earliest reminder date to the prospect data for sorting
                $prospect['earliest_reminder'] = $earliest_reminder_date;
                $filtered_prospects[] = $prospect;
            }
        }
        
        // Sort filtered prospects by earliest reminder date (oldest first)
        usort($filtered_prospects, function($a, $b) {
            return $a['earliest_reminder'] - $b['earliest_reminder'];
        });
        
        $result = [
            'prospects' => $filtered_prospects,
            'total' => $total,
            'max_pages' => $max_pages
        ];
        
        // Cache for 5 minutes
        wp_cache_set($cache_key, $result, '', 300);
        
        return $result;
    }
    
    /**
     * Get prospects with no call logs
     * 
     * @param int $user_id Current user ID
     * @param string $country_code User's country code
     * @param array $exclude_ids IDs to exclude (likely due reminder prospects)
     * @return array Prospects data with pagination info
     */
    private function get_no_calls_prospects($user_id, $country_code, $exclude_ids = []) {
        // Create a cache key
        $exclude_key = !empty($exclude_ids) ? md5(json_encode($exclude_ids)) : 'none';
        $cache_key = "no_calls_{$user_id}_{$country_code}_10_{$exclude_key}";
        $cached_result = wp_cache_get($cache_key);
        
        if (false !== $cached_result) {
            return $cached_result;
        }
        
        // Base query args - always gets first 10 results
        $args = [
            'post_type'      => 'business',
            'posts_per_page' => 10, // Always show 10 records
            'paged'          => 1,  // Always first page
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'business_status',
                    'value'   => 'prospect',
                    'compare' => '='
                ],
                [
                    'key'     => 'prospect_prospector',
                    'value'   => $user_id,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'business_delete',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'business_delete',
                        'value'   => 'yes',
                        'compare' => '!='
                    ]
                ],
                [
                    'key'     => 'country',
                    'value'   => $country_code,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'prospect_closer',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'prospect_closer',
                        'value'   => '',
                        'compare' => '='
                    ]
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'call_logs',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'call_logs',
                        'value'   => '',
                        'compare' => '='
                    ],
                    [
                        'key'     => 'call_logs',
                        'value'   => 'a:0:{}',  // Empty serialized array
                        'compare' => '='
                    ]
                ]
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'business_name',
            'order'    => 'ASC'
        ];
        
        // Exclude IDs if provided
        if (!empty($exclude_ids)) {
            $args['post__not_in'] = $exclude_ids;
        }
        
        $query = new \WP_Query($args);
        $prospect_ids = $query->posts;
        $total = $query->found_posts;
        wp_reset_postdata();
        
        // Get metadata for these prospects
        $prospects_data = $this->get_prospects_data_batch($prospect_ids);
        
        // Further filtering to ensure no call logs
        $filtered_prospects = [];
        foreach ($prospects_data as $prospect) {
            $has_calls = !empty($prospect['call_logs']) && is_array($prospect['call_logs']) && count($prospect['call_logs']) > 0;
            
            if (!$has_calls) {
                $filtered_prospects[] = $prospect;
            }
        }
        
        $result = [
            'prospects' => $filtered_prospects,
            'total' => $total,
            'max_pages' => 1 // Always just one page
        ];
        
        // Cache for 5 minutes
        wp_cache_set($cache_key, $result, '', 300);
        
        return $result;
    }
    
    /**
     * Get all other prospects (not due reminders or no calls)
     * 
     * @param int $user_id Current user ID
     * @param string $country_code User's country code
     * @param int $page Current page number
     * @param array $exclude_ids IDs to exclude (from other tables)
     * @return array Prospects data with pagination info
     */
    private function get_other_prospects($user_id, $country_code, $page = 1, $exclude_ids = []) {
        // Create a cache key
        $exclude_key = !empty($exclude_ids) ? md5(json_encode($exclude_ids)) : 'none';
        $cache_key = "other_prospects_{$user_id}_{$country_code}_{$page}_{$this->per_page}_{$exclude_key}";
        $cached_result = wp_cache_get($cache_key);
        
        if (false !== $cached_result) {
            return $cached_result;
        }
        
        // Base query args
        $args = [
            'post_type'      => 'business',
            'posts_per_page' => $this->per_page,
            'paged'          => $page,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'business_status',
                    'value'   => 'prospect',
                    'compare' => '='
                ],
                [
                    'key'     => 'prospect_prospector',
                    'value'   => $user_id,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'business_delete',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'business_delete',
                        'value'   => 'yes',
                        'compare' => '!='
                    ]
                ],
                [
                    'key'     => 'country',
                    'value'   => $country_code,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'prospect_closer',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'prospect_closer',
                        'value'   => '',
                        'compare' => '='
                    ]
                ],
                [
                    'key'     => 'call_logs',
                    'compare' => 'EXISTS',
                ]
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'business_name',
            'order'    => 'ASC'
        ];
        
        // Exclude IDs if provided
        if (!empty($exclude_ids)) {
            $args['post__not_in'] = $exclude_ids;
        }
        
        $query = new \WP_Query($args);
        $prospect_ids = $query->posts;
        $total = $query->found_posts;
        $max_pages = $query->max_num_pages;
        wp_reset_postdata();
        
        // Get metadata for these prospects
        $prospects_data = $this->get_prospects_data_batch($prospect_ids);
        
        // Further filtering to ensure they have call logs but don't have due reminders
        $today_start = strtotime('today midnight');
        $filtered_prospects = [];
        
        foreach ($prospects_data as $prospect) {
            $has_calls = !empty($prospect['call_logs']) && is_array($prospect['call_logs']) && count($prospect['call_logs']) > 0;
            $has_due_reminder = false;
            
            if (!empty($prospect['call_back_reminders']) && is_array($prospect['call_back_reminders'])) {
                foreach ($prospect['call_back_reminders'] as $reminder) {
                    if (isset($reminder['reminder_date'])) {
                        $reminder_timestamp = strtotime($reminder['reminder_date']);
                        
                        if ($reminder_timestamp !== false) {
                            $reminder_day_start = strtotime('midnight', $reminder_timestamp);
                            
                            if ($reminder_day_start <= $today_start) {
                                $has_due_reminder = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            if ($has_calls && !$has_due_reminder) {
                $filtered_prospects[] = $prospect;
            }
        }
        
        $result = [
            'prospects' => $filtered_prospects,
            'total' => $total,
            'max_pages' => $max_pages
        ];
        
        // Cache for 5 minutes
        wp_cache_set($cache_key, $result, '', 300);
        
        return $result;
    }
    
    /**
     * Render pagination controls
     * 
     * @param int $current_page Current page number
     * @param int $max_pages Total number of pages
     * @param string $page_param URL parameter name for this table's pagination
     * @param array $preserve_params Other URL parameters to preserve
     */
    private function render_pagination_controls($current_page, $max_pages, $page_param, $preserve_params = []) {
        if ($max_pages <= 1) {
            return;
        }
        
        $base_url = admin_url('admin.php?page=my-prospects');
        
        echo '<div class="tablenav">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . 
             sprintf(esc_html__('Page %s of %s', 'claims-angel'), $current_page, $max_pages) . 
             '</span>';
        
        // Previous page link
        if ($current_page > 1) {
            $prev_url = add_query_arg(array_merge(
                [$page_param => $current_page - 1],
                $preserve_params
            ), $base_url);
            echo '<a class="prev-page button" href="' . esc_url($prev_url) . '">' . 
                 esc_html__('&laquo; Previous', 'claims-angel') . 
                 '</a>';
        } else {
            echo '<span class="prev-page button disabled">' . 
                 esc_html__('&laquo; Previous', 'claims-angel') . 
                 '</span>';
        }
        
        // Page numbers
        if ($max_pages <= 5) {
            // Show all pages
            for ($i = 1; $i <= $max_pages; $i++) {
                if ($i == $current_page) {
                    echo '<span class="paging-input">' . $i . '</span>';
                } else {
                    $page_url = add_query_arg(array_merge(
                        [$page_param => $i],
                        $preserve_params
                    ), $base_url);
                    echo '<a class="page-numbers" href="' . esc_url($page_url) . '">' . $i . '</a>';
                }
            }
        } else {
            // Show limited page numbers with ellipses
            $start_page = max(1, $current_page - 2);
            $end_page = min($max_pages, $current_page + 2);
            
            // Adjust range if we're at the beginning or end
            if ($start_page == 1) {
                $end_page = min($max_pages, 5);
            } elseif ($end_page == $max_pages) {
                $start_page = max(1, $max_pages - 4);
            }
            
            // First page link if not in range
            if ($start_page > 1) {
                $first_url = add_query_arg(array_merge(
                    [$page_param => 1],
                    $preserve_params
                ), $base_url);
                echo '<a class="page-numbers" href="' . esc_url($first_url) . '">1</a>';
                
                if ($start_page > 2) {
                    echo '<span class="page-numbers dots">…</span>';
                }
            }
            
            // Page numbers
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    echo '<span class="page-numbers current">' . $i . '</span>';
                } else {
                    $page_url = add_query_arg(array_merge(
                        [$page_param => $i],
                        $preserve_params
                    ), $base_url);
                    echo '<a class="page-numbers" href="' . esc_url($page_url) . '">' . $i . '</a>';
                }
            }
            
            // Last page link if not in range
            if ($end_page < $max_pages) {
                if ($end_page < $max_pages - 1) {
                    echo '<span class="page-numbers dots">…</span>';
                }
                
                $last_url = add_query_arg(array_merge(
                    [$page_param => $max_pages],
                    $preserve_params
                ), $base_url);
                echo '<a class="page-numbers" href="' . esc_url($last_url) . '">' . $max_pages . '</a>';
            }
        }
        
        // Next page link
        if ($current_page < $max_pages) {
            $next_url = add_query_arg(array_merge(
                [$page_param => $current_page + 1],
                $preserve_params
            ), $base_url);
            echo '<a class="next-page button" href="' . esc_url($next_url) . '">' . 
                 esc_html__('Next &raquo;', 'claims-angel') . 
                 '</a>';
        } else {
            echo '<span class="next-page button disabled">' . 
                 esc_html__('Next &raquo;', 'claims-angel') . 
                 '</span>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render a prospects table
     * 
     * @param array $prospects Prospects data
     * @param string $title Table title
     * @param string $empty_message Message to show when no prospects
     * @param int $current_page Current page number
     * @param int $max_pages Total number of pages
     * @param string $page_param URL parameter for pagination
     * @param array $preserve_params Other URL parameters to preserve
     */
    private function render_prospects_table($prospects, $title, $empty_message, $current_page, $max_pages, $page_param, $preserve_params = []) {
        echo '<h2>' . esc_html($title) . '</h2>';
        
        if (empty($prospects)) {
            echo '<div class="notice notice-info inline"><p>' . esc_html($empty_message) . '</p></div>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Business Name', 'claims-angel') . '</th>';
        echo '<th>' . esc_html__('Date Created', 'claims-angel') . '</th>';
        echo '<th>' . esc_html__('Call Logs', 'claims-angel') . '</th>';
        echo '<th>' . esc_html__('Call Reminders', 'claims-angel') . '</th>';
        echo '<th>' . esc_html__('Actions', 'claims-angel') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($prospects as $prospect) {
            $date_display = !empty($prospect['date_created']) ? date('d/m/y', $prospect['date_created']) : '';
            $call_logs_count = is_array($prospect['call_logs']) ? count($prospect['call_logs']) : 0;
            $call_reminders_count = is_array($prospect['call_back_reminders']) ? count($prospect['call_back_reminders']) : 0;
            
            echo '<tr>';
            echo '<td>' . esc_html($prospect['business_name']) . '</td>';
            echo '<td>' . esc_html($date_display) . '</td>';
            echo '<td>' . esc_html($call_logs_count) . ' ' . _n('call', 'calls', $call_logs_count, 'claims-angel') . '</td>';
            echo '<td>' . esc_html($call_reminders_count) . ' ' . _n('reminder', 'reminders', $call_reminders_count, 'claims-angel') . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=business-crm&prospect_id=' . $prospect['id'])) . '" class="button">';
            echo esc_html__('View/Edit', 'claims-angel');
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // Render pagination
        $this->render_pagination_controls($current_page, $max_pages, $page_param, $preserve_params);
        
        echo '<div style="margin-bottom: 30px;"></div>';
    }
    
    /**
     * Render the due reminders table
     * 
     * @param int $user_id Current user ID
     * @param string $country_code User country code
     * @param int $page Current page number
     * @param array $preserve_params Other pagination parameters to preserve
     * @return array IDs of prospects in this table
     */
    private function render_reminders_table($user_id, $country_code, $page, $preserve_params) {
    $data = $this->get_due_reminder_prospects($user_id, $country_code, $page);
    $prospects = $data['prospects'];
    
    // Get all IDs for exclusion from other tables
    $all_ids = array_map(function($prospect) {
        return $prospect['id'];
    }, $prospects);
    
    // Build the title without count if total is 0
    $title = $data['total'] > 0 
        ? sprintf(__('Prospects with Call Reminders Due (%d)', 'claims-angel'), $data['total'])
        : __('Prospects with Call Reminders Due', 'claims-angel');
    
    $this->render_prospects_table(
        $prospects,
        $title,
        __('No prospects with call reminders due today or earlier.', 'claims-angel'),
        $page,
        $data['max_pages'],
        'reminder_page',
        $preserve_params
    );
    
    return $all_ids;
}
    
    /**
     * Render the no calls table
     * 
     * @param int $user_id Current user ID
     * @param string $country_code User country code
     * @param array $exclude_ids IDs to exclude
     * @return array IDs of prospects in this table
     */
    private function render_no_calls_table($user_id, $country_code, $exclude_ids) {
    $data = $this->get_no_calls_prospects($user_id, $country_code, $exclude_ids);
    $prospects = $data['prospects'];
    
    // Get all IDs for exclusion from other tables
    $all_ids = array_map(function($prospect) {
        return $prospect['id'];
    }, $prospects);
    
    // If no prospects to display, return empty array without rendering anything
    if (empty($prospects)) {
        return $all_ids;
    }
    
    // Build the title without count if total is 0
    $title = $data['total'] > 0 
        ? sprintf(__('Prospects Not Called (%d)', 'claims-angel'), $data['total'])
        : __('Prospects Not Called', 'claims-angel');
    
    echo '<h2>' . $title . '</h2>';
    echo '<p><em>' . __('Showing the next 10 to call', 'claims-angel') . '</em></p>';
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Business Name', 'claims-angel') . '</th>';
    echo '<th>' . esc_html__('Date Created', 'claims-angel') . '</th>';
    echo '<th>' . esc_html__('Actions', 'claims-angel') . '</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($prospects as $prospect) {
        $date_display = !empty($prospect['date_created']) ? date('d/m/y', $prospect['date_created']) : '';
        
        echo '<tr>';
        echo '<td>' . esc_html($prospect['business_name']) . '</td>';
        echo '<td>' . esc_html($date_display) . '</td>';
        echo '<td>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=business-crm&prospect_id=' . $prospect['id'])) . '" class="button">';
        echo esc_html__('View/Edit', 'claims-angel');
        echo '</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '<div style="margin-bottom: 30px;"></div>';
    
    return $all_ids;
}
    
    /**
     * Render the other prospects table
     * 
     * @param int $user_id Current user ID
     * @param string $country_code User country code
     * @param int $page Current page number
     * @param array $exclude_ids IDs to exclude
     * @param array $preserve_params Other pagination parameters to preserve
     */
    private function render_other_table($user_id, $country_code, $page, $exclude_ids, $preserve_params) {
    $data = $this->get_other_prospects($user_id, $country_code, $page, $exclude_ids);
    $prospects = $data['prospects'];
    
    // Build the title without count if total is 0
    $title = $data['total'] > 0 
        ? sprintf(__('Your Other Prospects (%d)', 'claims-angel'), $data['total'])
        : __('Your Other Prospects', 'claims-angel');
    
    $this->render_prospects_table(
        $prospects,
        $title,
        __('No other prospects found.', 'claims-angel'),
        $page,
        $data['max_pages'],
        'other_page',
        $preserve_params
    );
}

    /**
     * Render the My Prospects page.
     */
    public function render_page() {
        
        // Get the logged in user's ID and country
        $current_user_id = get_current_user_id();
        $user_country    = get_user_meta($current_user_id, 'user_country', true);

        // Use the Countries class to convert the user's country name to its ISO code.
        $countries          = \ClaimsAngel\Data\Countries::get_instance();
        $user_country_code  = $countries->find_country_code($user_country);

        // Optionally handle if no valid country code is found.
        if ( ! $user_country_code ) {
            echo '<div class="notice notice-warning"><p>';
            esc_html_e('Your country is not recognized. Please update your profile.', 'claims-angel');
            echo '</p></div>';
            return;
        }
        
        // Get pagination parameters for each table
        $reminder_page = isset($_GET['reminder_page']) ? max(1, intval($_GET['reminder_page'])) : 1;
        $no_calls_page = isset($_GET['no_calls_page']) ? max(1, intval($_GET['no_calls_page'])) : 1;
        $other_page = isset($_GET['other_page']) ? max(1, intval($_GET['other_page'])) : 1;
        
        // Create page header
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('My Prospects', 'claims-angel') . '</h1>';
        
        // Create an array to track which IDs we've displayed to avoid duplication
        $displayed_ids = [];
        
        // Preserve all pagination parameters when paginating a specific table
        $reminder_preserve = [
            'no_calls_page' => $no_calls_page,
            'other_page' => $other_page
        ];
        
        $no_calls_preserve = [
            'reminder_page' => $reminder_page,
            'other_page' => $other_page
        ];
        
        $other_preserve = [
            'reminder_page' => $reminder_page,
            'no_calls_page' => $no_calls_page
        ];
        
        // Render the due reminders table first
        $reminder_ids = $this->render_reminders_table(
            $current_user_id,
            $user_country_code,
            $reminder_page,
            $reminder_preserve
        );
        $displayed_ids = array_merge($displayed_ids, $reminder_ids);
        
        // Render the prospects not called table, excluding prospects already shown
        $no_calls_ids = $this->render_no_calls_table(
            $current_user_id,
            $user_country_code,
            $displayed_ids
        );
        $displayed_ids = array_merge($displayed_ids, $no_calls_ids);
        
        // Render the other prospects table, excluding prospects already shown
        $this->render_other_table(
            $current_user_id,
            $user_country_code,
            $other_page,
            $displayed_ids,
            $other_preserve
        );
        
    }
}

// Initialize the My Prospects page.
MyProspects::get_instance();