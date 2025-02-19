<?php
namespace ClaimsManagement;

class Public_Functionality {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'cm_custom_client_login', [ $this, 'custom_client_login_shortcode' ] );
        add_shortcode( 'cm_client_portal', [ $this, 'client_portal_shortcode' ] );
        
        // Hook to override template for the login page.
        add_filter( 'template_include', [ $this, 'maybe_use_custom_login_template' ] );
        
        // Add asset loading hooks
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
    }

    /**
     * Enqueue public-facing assets
     */
    public function enqueue_public_assets() {
        // Get the plugin directory URL
        $plugin_url = plugin_dir_url( dirname( __FILE__ ) );
        
        // Register and enqueue CSS files
        wp_register_style(
            'cm-login-styles',
            $plugin_url . 'assets/css/public/login.css',
            [],
            '1.0.0'
        );

        wp_register_style(
            'cm-portal-styles',
            $plugin_url . 'assets/css/public/portal.css',
            [],
            '1.0.0'
        );

        // Register and enqueue JavaScript files
        wp_register_script(
            'cm-portal-scripts',
            $plugin_url . 'assets/js/public/portal.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Enqueue assets based on current page/shortcode
        if ( is_page() ) {
            $post = get_post();
            if ( has_shortcode( $post->post_content, 'cm_custom_client_login' ) ) {
                wp_enqueue_style( 'cm-login-styles' );
            }
            if ( has_shortcode( $post->post_content, 'cm_client_portal' ) ) {
                wp_enqueue_style( 'cm-portal-styles' );
                wp_enqueue_script( 'cm-portal-scripts' );
                
                // Localize script data
                wp_localize_script( 'cm-portal-scripts', 'cm_portal_data', [
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'logout_url' => wp_logout_url( get_permalink() )
                ]);
            }
        }
    }

    /**
     * Shortcode for the custom client login page.
     *
     * @return string HTML output for the login box (fallback content if template override fails).
     */
    public function custom_client_login_shortcode() {
        // If the user is already logged in, display a simple message.
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You are already logged in.', 'claims-management' ) . '</p>';
        }

        // Fallback content in case the custom template is not applied.
        ob_start();
        ?>
        <div class="container">
            <div class="row justify-content-center login-container">
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow">
                        <div class="card-header text-center bg-primary text-white">
                            <h3 class="mb-0"><?php esc_html_e( 'Client Login', 'claims-management' ); ?></h3>
                        </div>
                        <div class="card-body">
                            <?php
                            echo wp_login_form( [
                                'echo'     => false,
                                'redirect' => home_url( '/client-portal/' ),
                            ] );
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode for the client portal.
     *
     * @return string HTML output for the client portal.
     */
    public function client_portal_shortcode() {
        if ( ! is_user_logged_in() || ! current_user_can( 'cm_client' ) ) {
            return '<div class="cm-client-portal">'
                . '<h1>' . esc_html__( 'Client Portal', 'claims-management' ) . '</h1>'
                . wp_login_form( [ 'echo' => false, 'redirect' => get_permalink() ] )
                . '</div>';
        }

        $current_user  = wp_get_current_user();
        $business_name = get_user_meta( $current_user->ID, 'cm_business_name', true );

        ob_start();
        ?>
        <div class="cm-client-portal">
            <h1><?php printf( esc_html__( 'Client Portal for %s', 'claims-management' ), esc_html( $business_name ) ); ?></h1>
            <button id="cm-logout" class="btn btn-danger float-right"><?php esc_html_e( 'Logout', 'claims-management' ); ?></button>
            <p><?php esc_html_e( 'Welcome to your client portal.', 'claims-management' ); ?></p>
            <!-- Additional client portal content can be added here -->
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Use a custom template for pages containing the [cm_custom_client_login] shortcode.
     *
     * @param string $template The path to the template.
     * @return string Modified template path if conditions are met.
     */
    public function maybe_use_custom_login_template( $template ) {
        if ( is_page() ) {
            $post = get_post();
            if ( has_shortcode( $post->post_content, 'cm_custom_client_login' ) ) {
                $new_template = plugin_dir_path( __FILE__ ) . 'templates/custom-login-template.php';
                if ( file_exists( $new_template ) ) {
                    return $new_template;
                }
            }
        }
        return $template;
    }
}

// Instantiate the public functionality.
new Public_Functionality();