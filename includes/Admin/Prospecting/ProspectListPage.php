<?php
namespace ClaimsAngel\Admin\Prospecting;

class ProspectListPage {
    /**
     * Singleton instance
     *
     * @var ProspectListPage|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     *
     * @return ProspectListPage
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
        add_action('admin_menu', [ $this, 'add_admin_menu' ]);
    }

    /**
     * Register the Prospect List admin menu page.
     */
    public function add_admin_menu() {
        $page_hook = add_menu_page(
            'Prospect List',                      // Page title
            'Prospect List',                      // Menu title
            'manage_options',                     // Capability (typically administrators)
            'prospect-list',                      // Menu slug
            [ $this, 'render_page' ],             // Callback function
            'dashicons-list-view',                // Icon
            31                                    // Position
        );

        // Use the AdminAccessController to restrict access only to administrators.
        \ClaimsAngel\Admin\AdminAccessController::get_instance()->register_admin_page(
            'prospect-list',
            ['administrator'],                    // Allowed roles: administrators only.
            [
                'page_title' => 'Prospect List',
                'menu_title' => 'Prospect List',
                'menu_slug'  => 'prospect-list'
            ],
            false
        );
    }

    /**
     * Render the Prospect List page.
     */
public function render_page() {
    // Get the logged in user's country name.
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

    // Query posts of type 'business' where:
    // - business_status equals 'prospect'
    // - prospect_prospector is either not set or is empty
    // - country matches the ISO code of the logged in user's country.
    $args = [
        'post_type'      => 'business',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'business_status',
                'value'   => 'prospect',
                'compare' => '='
            ],
            [
                // Nested query to check if 'prospect_prospector' is not set OR empty.
                'relation' => 'OR',
                [
                    'key'     => 'prospect_prospector',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key'     => 'prospect_prospector',
                    'value'   => '',
                    'compare' => '='
                ]
            ],
            [
                'key'     => 'country',
                'value'   => $user_country_code,
                'compare' => '='
            ]
        ]
    ];
    $query = new \WP_Query($args);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Prospect List', 'claims-angel'); ?></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Business Name', 'claims-angel'); ?></th>
                    <th><?php esc_html_e('Web Address', 'claims-angel'); ?></th>
                    <th><?php esc_html_e('Phone Number', 'claims-angel'); ?></th>
                    <th><?php esc_html_e('LinkedIn Profile', 'claims-angel'); ?></th>
                    <th><?php esc_html_e('Country', 'claims-angel'); ?></th>
                    <th><?php esc_html_e('Date Created', 'claims-angel'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $query->have_posts() ) : ?>
                    <?php while ( $query->have_posts() ) : $query->the_post(); 
                        // Retrieve meta fields
                        $business_name = get_post_meta(get_the_ID(), 'business_name', true);
                        $web_address   = get_post_meta(get_the_ID(), 'web_address', true);
                        $phone_number  = get_post_meta(get_the_ID(), 'phone_number', true);
                        $linkedin      = get_post_meta(get_the_ID(), 'linkedin_profile', true);
                        $country       = get_post_meta(get_the_ID(), 'country', true);
                        $date_created  = get_post_meta(get_the_ID(), 'date_created', true);
                        $date_display  = !empty($date_created) ? date('Y-m-d H:i:s', $date_created) : '';

                        // Convert the ISO country code to the country name.
                        $countries_instance = \ClaimsAngel\Data\Countries::get_instance();
                        $country_name = $countries_instance->get_country_name($country);
                    ?>
                    <tr>
                        <td><?php echo esc_html($business_name); ?></td>
                        <td><?php echo esc_html($web_address); ?></td>
                        <td><?php echo esc_html($phone_number); ?></td>
                        <td><?php echo esc_html($linkedin); ?></td>
                        <td><?php echo esc_html($country_name); ?></td>
                        <td><?php echo esc_html($date_display); ?></td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e('No prospects found.', 'claims-angel'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
}

// Initialize the Prospect List page.
ProspectListPage::get_instance();