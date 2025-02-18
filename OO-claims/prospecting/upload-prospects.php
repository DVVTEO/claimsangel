<?php
namespace ClaimsManagement;

if ( ! class_exists( __NAMESPACE__ . '\\Prospecting' ) ) {

    class Prospecting {

        /**
         * Constructor.
         */
        public function __construct() {
            // Register our admin menu.
            add_action( 'admin_menu', [ $this, 'register_prospecting_submenu' ] );
            // Handle CSV import form submission.
            add_action( 'admin_post_cm_import_prospects', [ $this, 'handle_csv_import' ] );
            // Handle download of rejected prospects.
            add_action( 'admin_post_download_rejected_prospects', [ $this, 'download_rejected_prospects' ] );
            // Handle individual Sales Manager assignment.
            add_action( 'admin_post_assign_sales_manager', [ $this, 'handle_assign_sales_manager' ] );
            // Handle bulk assignment (Assign All Equally) to Claims Managers.
            add_action( 'admin_post_assign_all_equally', [ $this, 'handle_assign_all_equally' ] );
            // Handle prospect deletion.
            add_action( 'admin_post_delete_prospect', [ $this, 'handle_delete_prospect' ] );
            // Handle individual Claims Manager assignment.
            add_action( 'admin_post_assign_claims_manager', [ $this, 'handle_assign_claims_manager' ] );
            // Handle bulk conversion of assigned prospects.
            add_action( 'admin_post_convert_assigned_prospects', [ $this, 'handle_convert_assigned_prospects' ] );
            // AJAX handler for updating the temporary record.
            add_action( 'wp_ajax_cm_assign_claims_manager_ajax', [ $this, 'ajax_assign_claims_manager' ] );
        }

        /**
         * Register the "Upload Prospects" top‑level admin menu.
         */
        public function register_prospecting_submenu() {
            // Register the top-level menu.
add_menu_page(
    __( 'Prospecting', 'claims-management' ), // Page title (for the header)
    __( 'Prospecting', 'claims-management' ), // Parent menu item label in the sidebar
    'read',
    'upload-prospects',
    [ $this, 'prospecting_page_callback' ],
    'dashicons-phone',
    2
);

// Remove the duplicate submenu item that WordPress auto-creates.
add_action( 'admin_menu', function() {
    remove_submenu_page( 'upload-prospects', 'upload-prospects' );
} );

// Add your custom submenu with a different label.
add_submenu_page(
    'upload-prospects', // Parent slug
    __( 'Upload Prospects', 'claims-management' ), // Page title (header)
    __( 'Upload Prospects', 'claims-management' ), // Submenu label in the sidebar
    'read',
    'upload-prospects',
    'prospecting_page_callback'
);
        }

        /**
         * Render the temporary review page.
         * This page lists prospect records stored in the options table for review.
         */
        public function prospecting_page_callback() {
            // Block access if the current user is not a system admin and not a Claims Admin.
            if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'claims_admin' ) ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page', 'claims-management' ) );
            }
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Add Prospects', 'claims-management' ); ?></h1>
<div class="cm-csv-upload-container" style="padding: 20px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 40px; margin-top: 20px; background-color: #fff;">
    <h2 style="margin:0px;"><?php esc_html_e( 'Upload Prospect CSV', 'claims-management' ); ?></h2>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'cm_import_prospects', 'cm_import_nonce' ); ?>
        <input type="hidden" name="action" value="cm_import_prospects">
        
        <!-- CSV File header -->
        <p>
            <label for="prospects_csv"><?php esc_html_e( 'CSV File', 'claims-management' ); ?></label>
        </p>
        <!-- File upload input below header -->
        <p>
            <input type="file" name="prospects_csv" id="prospects_csv" accept=".csv">
        </p>
        
        <?php submit_button( __( 'Import Prospects', 'claims-management' ) ); ?>
    </form>
</div>

                <?php
                // If results have been passed in the URL, decode and display them.
                if ( isset( $_GET['import_result'] ) ) {
                    $result = json_decode( base64_decode( sanitize_text_field( wp_unslash( $_GET['import_result'] ) ) ), true );
                    if ( $result && is_array( $result ) ) {
                        ?>
                        <div class="notice notice-success is-dismissible">
                            <p><?php printf( esc_html__( 'Successfully imported %d records. Found %d rejected records.', 'claims-management' ), intval( $result['imported'] ), intval( $result['rejected_count'] ) ); ?></p>
                        </div>
                        <?php if ( ! empty( $result['rejected'] ) ) { ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h2 style="margin: 0;"><?php esc_html_e( 'Rejected Records', 'claims-management' ); ?></h2>
                                <a class="button" href="<?php echo esc_url( admin_url( 'admin-post.php?action=download_rejected_prospects' ) ); ?>">
                                    <?php esc_html_e( 'Download Rejected Records', 'claims-management' ); ?>
                                </a>
                            </div>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Business Name', 'claims-management' ); ?></th>
                                        <th><?php esc_html_e( 'Web Address', 'claims-management' ); ?></th>
                                        <th><?php esc_html_e( 'Phone Number', 'claims-management' ); ?></th>
                                        <th><?php esc_html_e( 'Country', 'claims-management' ); ?></th>
                                        <th><?php esc_html_e( 'Rejection Reason', 'claims-management' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $result['rejected'] as $rej ) { ?>
                                        <tr>
                                            <td><?php echo esc_html( $rej['Business Name'] ); ?></td>
                                            <td><a href="<?php echo esc_url( $rej['Web Address'] ); ?>" target="_blank"><?php echo esc_html( $rej['Web Address'] ); ?></a></td>
                                            <td><?php echo esc_html( $rej['Phone Number'] ); ?></td>
                                            <td>
                                                <?php 
                                                $rej_iso = ( strlen( trim( $rej['Country'] ) ) === 2 ) ? strtolower( trim( $rej['Country'] ) ) : cm_map_country_to_iso( $rej['Country'] );
                                                echo $rej_iso ? cm_get_flag_img( $rej_iso, $rej['Country'] ) : '';
                                                echo esc_html( $rej['Country'] ); 
                                                ?>
                                            </td>
                                            <td><?php echo esc_html( $rej['Rejection Reason'] ); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php }
                    }
                }
                ?>
                <h2><?php esc_html_e( 'Accepted Prospects', 'claims-management' ); ?></h2>
                <?php
                // Display prospects from temporary storage (options table).
                $prospects = get_option( 'cm_prospects', [] );
                if ( ! empty( $prospects ) ) {
                    $grouped = [];
                    foreach ( $prospects as $index => $prospect ) {
                        $country = $prospect['Country'];
                        if ( ! isset( $grouped[ $country ] ) ) {
                            $grouped[ $country ] = [];
                        }
                        $grouped[ $country ][] = [
                            'index'    => $index,
                            'prospect' => $prospect,
                        ];
                    }
                    foreach ( $grouped as $country => $records ) {
                        if ( empty( $records ) ) {
                            continue;
                        }
                        $iso_code = ( strlen( trim( $country ) ) === 2 ) ? strtolower( trim( $country ) ) : cm_map_country_to_iso( $country );
                        $flag_html = $iso_code ? cm_get_flag_img( $iso_code, $country ) : '';
                        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
                            echo '<h3 style="margin: 0;">' . $flag_html . ' ' . esc_html( $country ) . '</h3>';
                            // Button for bulk assignment.
                            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php?action=assign_all_equally' ) ) . '" style="margin: 0;">';
                                wp_nonce_field( 'cm_assign_all_equally', 'cm_assign_all_nonce' );
                                echo '<input type="hidden" name="country" value="' . esc_attr( $country ) . '">';
                                echo '<input type="submit" class="button" value="' . esc_attr__( 'Assign All Equally', 'claims-management' ) . '">';
                            echo '</form>';
                        echo '</div>';
                        echo '<table class="wp-list-table widefat fixed striped prospects-table" style="margin-bottom: 30px;">';
                        echo '<thead><tr>';
                        echo '<th>' . esc_html__( 'Business Name', 'claims-management' ) . '</th>';
                        echo '<th>' . esc_html__( 'Web Address', 'claims-management' ) . '</th>';
                        echo '<th>' . esc_html__( 'Phone Number', 'claims-management' ) . '</th>';
                        echo '<th>' . esc_html__( 'Claims Manager', 'claims-management' ) . '</th>';
                        echo '<th style="text-align: right;">' . esc_html__( 'Delete', 'claims-management' ) . '</th>';
                        echo '</tr></thead><tbody>';
                        foreach ( $records as $record ) {
                            $prospect       = $record['prospect'];
                            $prospect_index = $record['index'];
                            echo '<tr>';
                            echo '<td>' . esc_html( $prospect['Business Name'] ) . '</td>';
                            echo '<td><a href="' . esc_url( $prospect['Web Address'] ) . '" target="_blank">' . esc_html( $prospect['Web Address'] ) . '</a></td>';
                            echo '<td>' . esc_html( $prospect['Phone Number'] ) . '</td>';
                            echo '<td>';
                                // Form to update the temporary record with an assigned Claims Manager.
                                echo '<form class="ajax-assign-claims-manager" method="post" style="display: inline-block; margin: 0;">';
                                    wp_nonce_field( 'cm_assign_claims_manager', 'cm_assign_claims_manager_nonce' );
                                    echo '<input type="hidden" name="prospect_index" value="' . esc_attr( $prospect_index ) . '">';
                                    echo '<select name="claims_manager">';
                                        echo '<option value="">' . esc_html__( 'Select Claims Manager', 'claims-management' ) . '</option>';
                                        $claims_managers = get_users( [
                                            'role'       => 'claims_manager',
                                            'meta_key'   => 'cm_user_country',
                                            'meta_value' => $country,
                                        ] );
                                        if ( ! empty( $claims_managers ) ) {
                                            foreach ( $claims_managers as $manager ) {
                                                $selected = ( isset( $prospect['claims_manager'] ) && $prospect['claims_manager'] == $manager->ID ) ? ' selected' : '';
                                                echo '<option value="' . esc_attr( $manager->ID ) . '"' . $selected . '>' . esc_html( $manager->display_name ) . '</option>';
                                            }
                                        }
                                    echo '</select> ';
                                    echo '<input type="submit" class="button" value="' . esc_attr__( 'Save', 'claims-management' ) . '">';
                                echo '</form>';
                            echo '</td>';
                            echo '<td style="text-align: right;">';
                                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php?action=delete_prospect' ) ) . '" onsubmit="return confirm(\'Are you sure you want to delete this prospect?\');">';
                                    wp_nonce_field( 'cm_delete_prospect', 'cm_delete_nonce' );
                                    echo '<input type="hidden" name="prospect_index" value="' . esc_attr( $prospect_index ) . '">';
                                    echo '<input type="submit" class="button" value="' . esc_html__( 'Delete', 'claims-management' ) . '">';
                                echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    }
                } else {
                    echo '<p>' . esc_html__( 'No prospects found.', 'claims-management' ) . '</p>';
                }
                ?>
                <!-- Button: Convert All Assigned Prospects -->
                <div style="margin-top: 20px;">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'cm_convert_assigned', 'cm_convert_assigned_nonce' ); ?>
                        <input type="hidden" name="action" value="convert_assigned_prospects">
                        <input type="submit" class="button" value="<?php esc_attr_e( 'Convert Assigned Prospects', 'claims-management' ); ?>">
                    </form>
                </div>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('.prospects-table').each(function() {
                    var $table = $(this);
                    var $rows = $table.find('tbody tr');
                    var rowsPerPage = 20;
                    var totalRows = $rows.length;
                    if(totalRows > rowsPerPage) {
                        var totalPages = Math.ceil(totalRows / rowsPerPage);
                        $rows.hide();
                        $rows.slice(0, rowsPerPage).show();
                        var $pagination = $('<div class="prospects-pagination" style="text-align: center; margin-top: 10px;"></div>');
                        for(var i = 1; i <= totalPages; i++) {
                            var $link = $('<a href="#" class="prospects-page-link" style="margin: 0 5px;">' + i + '</a>');
                            if(i === 1) {
                                $link.css('font-weight', 'bold');
                            }
                            $link.data('page', i);
                            $pagination.append($link);
                        }
                        $table.after($pagination);
                        $pagination.on('click', '.prospects-page-link', function(e) {
                            e.preventDefault();
                            var page = $(this).data('page');
                            $pagination.find('.prospects-page-link').css('font-weight', 'normal');
                            $(this).css('font-weight', 'bold');
                            var start = (page - 1) * rowsPerPage;
                            var end = start + rowsPerPage;
                            $rows.hide().slice(start, end).show();
                        });
                    }
                });
                
                // AJAX submission for updating the temporary record.
                $(document).on('submit', '.ajax-assign-claims-manager', function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    var data = $form.serialize() + '&action=cm_assign_claims_manager_ajax';
                    $.post(ajaxurl, data, function(response) {
                        if(response.success) {
                            var $row = $form.closest('tr');
                            $row.addClass('highlight');
                            setTimeout(function() {
                                $row.removeClass('highlight');
                            }, 3000);
                        } else {
                            alert(response.data.message || 'An error occurred.');
                        }
                    });
                });
            });
            </script>
            <style>
            .highlight {
                background-color: #d4edda !important;
                transition: background-color 2s ease;
            }
            </style>
            <?php
        }

        /**
         * Handle the CSV import process.
         * New prospects are stored in the options table as temporary records.
         */
        public function handle_csv_import() {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'claims_admin' ) ) {
        wp_die( esc_html__( 'Insufficient permissions', 'claims-management' ) );
    }
    if ( ! isset( $_POST['cm_import_nonce'] ) || ! wp_verify_nonce( $_POST['cm_import_nonce'], 'cm_import_prospects' ) ) {
        wp_die( esc_html__( 'Nonce verification failed', 'claims-management' ) );
    }
    if ( ! isset( $_FILES['prospects_csv'] ) || $_FILES['prospects_csv']['error'] !== UPLOAD_ERR_OK ) {
        wp_die( esc_html__( 'Error uploading file', 'claims-management' ) );
    }
    $file = $_FILES['prospects_csv']['tmp_name'];

    $imported = 0;
    $rejected = [];
    if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
        $headers = fgetcsv( $handle );
        $expected_headers = [ 'Business Name', 'Web Address', 'Phone Number', 'Country' ];
        $headers = array_map( 'trim', $headers );
        if ( array_diff( $expected_headers, $headers ) ) {
            fclose( $handle );
            wp_die( esc_html__( 'Invalid CSV headers. Please ensure the CSV has the following headers: Business Name, Web Address, Phone Number, Country', 'claims-management' ) );
        }
        $header_map = array_flip( $headers );
        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            $prospect = [];
            foreach ( $expected_headers as $header ) {
                $index = isset( $header_map[ $header ] ) ? $header_map[ $header ] : false;
                if ( $index === false ) {
                    continue;
                }
                $prospect[ $header ] = isset( $data[ $index ] ) ? sanitize_text_field( $data[ $index ] ) : '';
            }
            // Skip empty rows.
            if ( empty( $prospect['Business Name'] ) && empty( $prospect['Web Address'] ) && empty( $prospect['Phone Number'] ) && empty( $prospect['Country'] ) ) {
                continue;
            }
            if ( empty( trim( $prospect['Business Name'] ) ) ) {
                $prospect['Rejection Reason'] = "Missing Business Name";
                $rejected[] = $prospect;
                continue;
            }
            if ( empty( trim( $prospect['Web Address'] ) ) ) {
                $prospect['Rejection Reason'] = "Missing Web Address";
                $rejected[] = $prospect;
                continue;
            }
            // Validate and clean country.
            $fixed_country = $this->fix_country( $prospect['Country'] );
            if ( empty( $fixed_country ) ) {
                $prospect['Rejection Reason'] = "Not a valid country";
                $rejected[] = $prospect;
                continue;
            }
            $prospect['Country'] = $fixed_country;
            if ( ! empty( $prospect['Web Address'] ) ) {
                $prospect['Web Address'] = $this->clean_root_domain_url( $prospect['Web Address'] );
            }
            // --- New Phone Cleaning Section ---
            // Remove all non-digit characters from the phone number.
            $phone = preg_replace( '/\D+/', '', $prospect['Phone Number'] );
            // Retrieve the dialing code for the country.
            $dialing_code = cm_get_country_dialing_code( $prospect['Country'] );
            // Clean the dialing code (remove any non-digits).
            $dialing_code_clean = preg_replace( '/\D+/', '', $dialing_code );
            // If the phone number does not already start with the dialing code, prepend it.
            if ( strpos( $phone, $dialing_code_clean ) !== 0 ) {
                $phone = $dialing_code_clean . $phone;
            }
            // Store the final phone number with a plus sign.
            $prospect['Phone Number'] = '+' . $phone;
            // --- End Phone Cleaning Section ---

            $dup_reason = $this->get_duplicate_reason( $prospect );
            if ( ! empty( $dup_reason ) ) {
                $prospect['Rejection Reason'] = $dup_reason;
                $rejected[] = $prospect;
                continue;
            }
            $existing = get_option( 'cm_prospects', [] );
            $existing[] = $prospect;
            update_option( 'cm_prospects', $existing );
            $imported++;
        }
        fclose( $handle );
    } else {
        wp_die( esc_html__( 'Error opening the uploaded file', 'claims-management' ) );
    }
    if ( ! empty( $rejected ) ) {
        set_transient( 'cm_rejected_' . get_current_user_id(), $rejected, 10 * MINUTE_IN_SECONDS );
    }
    $result = [
        'imported'       => $imported,
        'rejected_count' => count( $rejected ),
        'rejected'       => $rejected,
    ];
    $redirect_url = add_query_arg( 'cm_settings_updated', '1', admin_url( 'admin.php?page=upload-prospects' ) );
    $redirect_url = add_query_arg( 'import_result', base64_encode( json_encode( $result ) ), $redirect_url );
    wp_redirect( $redirect_url );
    exit;
}

        /**
         * Normalize a string by trimming, removing special characters (except spaces), and converting to lowercase.
         *
         * @param string $string The input string.
         * @return string The normalized string.
         */
        private function normalize_string( $string ) {
            $string = trim( $string );
            $string = preg_replace( '/[^a-zA-Z\s]/', '', $string );
            $string = strtolower( $string );
            return $string;
        }

        /**
         * Fix the country value by matching it against the valid countries from plugin settings.
         *
         * @param string $input_country The country value from the CSV.
         * @return string The corrected country name from the valid list, or an empty string if no match is found.
         */
        private function fix_country( $input_country ) {
            $input_norm = $this->normalize_string( $input_country );
            if ( empty( $input_norm ) ) {
                return '';
            }
            $valid = get_option( 'cm_countries', get_default_countries() );
            foreach ( $valid as $country ) {
                if ( $input_norm === $this->normalize_string( $country ) ) {
                    return $country;
                }
            }
            return '';
        }

        /**
         * Clean a URL to include only the scheme, host, and port (if any).
         *
         * @param string $url The original URL.
         * @return string The cleaned URL.
         */
        private function clean_root_domain_url( $url ) {
            $url = trim( $url );
            if ( ! preg_match( '/^https?:\/\//i', $url ) ) {
                $url = 'http://' . $url;
            }
            $parts = parse_url( $url );
            if ( ! $parts || ! isset( $parts['host'] ) ) {
                return $url;
            }
            $clean_url = $parts['scheme'] . '://' . $parts['host'];
            if ( isset( $parts['port'] ) ) {
                $clean_url .= ':' . $parts['port'];
            }
            return $clean_url;
        }

        /**
         * Get the duplicate reason for a prospect if it matches an existing record.
         *
         * Checks against:
         *  - Temporary prospects (stored in the options table)
         *  - Client users (role "cm_client")
         *  - Permanent prospect custom posts (post type "cm_prospect")
         *
         * @param array $prospect Associative array with keys: Business Name, Web Address, Phone Number, Country.
         * @return string The reason for rejection if duplicate, or empty if not duplicate.
         */
        private function get_duplicate_reason( $prospect ) {
            $prospect_business = strtolower( trim( $prospect['Business Name'] ) );
            $prospect_web      = strtolower( trim( $prospect['Web Address'] ) );
            $prospect_phone    = strtolower( trim( $prospect['Phone Number'] ) );

            // Check in temporary prospects.
            $existing_prospects = get_option( 'cm_prospects', [] );
            foreach ( $existing_prospects as $existing ) {
                $existing_business = strtolower( trim( $existing['Business Name'] ) );
                $existing_web      = strtolower( trim( $existing['Web Address'] ) );
                $existing_phone    = strtolower( trim( $existing['Phone Number'] ) );
                if ( ! empty( $prospect_business ) && ! empty( $existing_business ) && $prospect_business === $existing_business ) {
                    return "Matched against existing prospect";
                }
                if ( ! empty( $prospect_web ) && ! empty( $existing_web ) && $prospect_web === $existing_web ) {
                    return "Matched against existing prospect";
                }
                if ( ! empty( $prospect_phone ) && ! empty( $existing_phone ) && $prospect_phone === $existing_phone ) {
                    return "Matched against existing prospect";
                }
            }

            // Check against client users.
            $meta_queries = [];
            if ( ! empty( $prospect_business ) ) {
                $meta_queries[] = [
                    'key'     => 'cm_business_name',
                    'value'   => $prospect_business,
                    'compare' => '=',
                ];
            }
            if ( ! empty( $prospect_phone ) ) {
                $meta_queries[] = [
                    'key'     => 'cm_phone',
                    'value'   => $prospect_phone,
                    'compare' => '=',
                ];
            }
            if ( ! empty( $prospect_web ) ) {
                $meta_queries[] = [
                    'key'     => 'cm_web_address',
                    'value'   => $prospect_web,
                    'compare' => '=',
                ];
            }
            if ( ! empty( $meta_queries ) ) {
                $args = [
                    'role__in'   => [ 'cm_client' ],
                    'meta_query' => array_merge( [ 'relation' => 'OR' ], $meta_queries ),
                ];
                $users = get_users( $args );
                if ( ! empty( $users ) ) {
                    return "Matched against existing client";
                }
            }
            
            // Check against permanent prospects (custom post type "cm_prospect").
            $args = [
                'post_type'   => 'cm_prospect',
                'post_status' => 'publish',
                'meta_query'  => [
                    'relation' => 'OR',
                    [
                        'key'     => 'cm_business_name',
                        'value'   => $prospect_business,
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'cm_web_address',
                        'value'   => $prospect_web,
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'cm_phone',
                        'value'   => $prospect_phone,
                        'compare' => '=',
                    ],
                ],
            ];
            $query = new \WP_Query( $args );
            if ( $query->have_posts() ) {
                return "Matched against existing prospect (permanent)";
            }

            return "";
        }

        /**
         * Check if a prospect is a duplicate.
         *
         * Returns true if get_duplicate_reason returns a non-empty value.
         *
         * @param array $prospect Associative array with keys: Business Name, Web Address, Phone Number, Country.
         * @return bool True if duplicate, false otherwise.
         */
        private function is_duplicate_prospective( $prospect ) {
            return !empty( $this->get_duplicate_reason( $prospect ) );
        }

        // For backward compatibility.
        private function is_duplicate_prospect( $prospect ) {
            return $this->is_duplicate_prospective( $prospect );
        }

        /**
         * Handle single prospect assignment to a Sales Manager.
         */
        public function handle_assign_sales_manager() {
            if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'claims_admin' ) ) {
                wp_die( esc_html__( 'Insufficient permissions', 'claims-management' ) );
            }
            if ( ! isset( $_POST['cm_assign_nonce'] ) || ! wp_verify_nonce( $_POST['cm_assign_nonce'], 'cm_assign_sales_manager' ) ) {
                wp_die( esc_html__( 'Nonce verification failed', 'claims-management' ) );
            }
            $prospect_index = isset( $_POST['prospect_index'] ) ? intval( $_POST['prospect_index'] ) : -1;
            $sales_manager  = isset( $_POST['sales_manager'] ) ? trim( $_POST['sales_manager'] ) : '';
            if ( $prospect_index < 0 ) {
                wp_die( esc_html__( 'Invalid data provided', 'claims-management' ) );
            }
            $prospects = get_option( 'cm_prospects', [] );
            if ( ! isset( $prospects[ $prospect_index ] ) ) {
                wp_die( esc_html__( 'Prospect not found', 'claims-management' ) );
            }
            $prospects[ $prospect_index ]['sales_manager'] = $sales_manager;
            update_option( 'cm_prospects', $prospects );
            wp_redirect( admin_url( 'admin.php?page=upload-prospects' ) );
            exit;
        }

        /**
         * Handle bulk assignment (Assign All Equally) for a given country.
         */
        public function handle_assign_all_equally() {
            if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'claims_admin' ) ) {
                wp_die( esc_html__( 'Insufficient permissions', 'claims-management' ) );
            }
            if ( ! isset( $_POST['cm_assign_all_nonce'] ) || ! wp_verify_nonce( $_POST['cm_assign_all_nonce'], 'cm_assign_all_equally' ) ) {
                wp_die( esc_html__( 'Nonce verification failed', 'claims-management' ) );
            }
            $country = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
            if ( empty( $country ) ) {
                wp_die( esc_html__( 'No country provided', 'claims-management' ) );
            }
            $prospects = get_option( 'cm_prospects', [] );
            $indices = [];
            foreach ( $prospects as $index => $prospect ) {
                if ( isset( $prospect['Country'] ) && $prospect['Country'] === $country && ( !isset( $prospect['claims_manager'] ) || empty( $prospect['claims_manager'] ) ) ) {
                    $indices[] = $index;
                }
            }
            if ( empty( $indices ) ) {
                wp_redirect( admin_url( 'admin.php?page=upload-prospects' ) );
                exit;
            }
            $claims_managers = get_users( [
                'role'       => 'claims_manager',
                'meta_key'   => 'cm_user_country',
                'meta_value' => $country,
            ] );
            if ( empty( $claims_managers ) ) {
                wp_die( esc_html__( 'No Claims Managers found for this country', 'claims-management' ) );
            }
            $manager_ids = wp_list_pluck( $claims_managers, 'ID' );
            $count_managers = count( $manager_ids );
            shuffle( $indices );
            $total = count( $indices );
            $base = floor( $total / $count_managers );
            $remainder = $total % $count_managers;
            $assignment = [];
            $i = 0;
            foreach ( $manager_ids as $manager_id ) {
                $assign_count = $base;
                if ( $remainder > 0 ) {
                    $assign_count++;
                    $remainder--;
                }
                for ( $j = 0; $j < $assign_count; $j++ ) {
                    if ( isset( $indices[$i] ) ) {
                        $assignment[] = [ 'index' => $indices[$i], 'manager_id' => $manager_id ];
                        $i++;
                    }
                }
            }
            foreach ( $assignment as $assign ) {
                $idx = $assign['index'];
                $prospects[ $idx ]['claims_manager'] = $assign['manager_id'];
            }
            update_option( 'cm_prospects', $prospects );
            wp_redirect( admin_url( 'admin.php?page=upload-prospects' ) );
            exit;
        }

        /**
         * Handle prospect deletion.
         */
        public function handle_delete_prospect() {
            if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'claims_admin' ) ) {
                wp_die( esc_html__( 'Insufficient permissions', 'claims-management' ) );
            }
            if ( ! isset( $_POST['cm_delete_nonce'] ) || ! wp_verify_nonce( $_POST['cm_delete_nonce'], 'cm_delete_prospect' ) ) {
                wp_die( esc_html__( 'Nonce verification failed', 'claims-management' ) );
            }
            $prospect_index = isset( $_POST['prospect_index'] ) ? intval( $_POST['prospect_index'] ) : -1;
            if ( $prospect_index < 0 ) {
                wp_die( esc_html__( 'Invalid prospect index', 'claims-management' ) );
            }
            $prospects = get_option( 'cm_prospects', [] );
            if ( ! isset( $prospects[ $prospect_index ] ) ) {
                wp_die( esc_html__( 'Prospect not found', 'claims-management' ) );
            }
            unset( $prospects[ $prospect_index ] );
            $prospects = array_values( $prospects );
            update_option( 'cm_prospects', $prospects );
            wp_redirect( admin_url( 'admin.php?page=upload-prospects' ) );
            exit;
        }

        /**
         * Handle individual Claims Manager assignment.
         * This version updates the temporary record with the assigned Claims Manager.
         */
        public function handle_assign_claims_manager() {
            if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'claims_admin' ) ) {
                wp_die( esc_html__( 'Insufficient permissions', 'claims-management' ) );
            }
            if ( ! isset( $_POST['cm_assign_claims_manager_nonce'] ) || ! wp_verify_nonce( $_POST['cm_assign_claims_manager_nonce'], 'cm_assign_claims_manager' ) ) {
                wp_die( esc_html__( 'Nonce verification failed', 'claims-management' ) );
            }
            $prospect_index = isset( $_POST['prospect_index'] ) ? intval( $_POST['prospect_index'] ) : -1;
            $claims_manager = isset( $_POST['claims_manager'] ) ? trim( $_POST['claims_manager'] ) : '';
            if ( $prospect_index < 0 ) {
                wp_die( esc_html__( 'Invalid prospect index', 'claims-management' ) );
            }
            $prospects = get_option( 'cm_prospects', [] );
            if ( ! isset( $prospects[ $prospect_index ] ) ) {
                wp_die( esc_html__( 'Prospect not found', 'claims-management' ) );
            }
            $prospects[ $prospect_index ]['claims_manager'] = $claims_manager;
            update_option( 'cm_prospects', $prospects );
            wp_redirect( admin_url( 'admin.php?page=upload-prospects' ) );
            exit;
        }

        /**
         * Handle bulk conversion of assigned prospects.
         * Converts all temporary prospect records with an assigned Claims Manager
         * into permanent custom posts (post type "cm_prospect") and removes them from temporary storage.
         */
        public function handle_convert_assigned_prospects() {
            if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'claims_admin' ) ) {
                wp_die( esc_html__( 'Insufficient permissions', 'claims-management' ) );
            }
            if ( ! isset( $_POST['cm_convert_assigned_nonce'] ) || ! wp_verify_nonce( $_POST['cm_convert_assigned_nonce'], 'cm_convert_assigned' ) ) {
                wp_die( esc_html__( 'Nonce verification failed', 'claims-management' ) );
            }
            $prospects = get_option( 'cm_prospects', [] );
            $converted = 0;
            if ( ! empty( $prospects ) ) {
                foreach ( $prospects as $key => $prospect ) {
                    if ( ! empty( $prospect['claims_manager'] ) ) {
                        $post_id = wp_insert_post( [
                            'post_type'   => 'cm_prospect',
                            'post_title'  => $prospect['Business Name'],
                            'post_status' => 'publish',
                            'post_author' => get_current_user_id(),
                        ] );
                        if ( $post_id && ! is_wp_error( $post_id ) ) {
                            update_post_meta( $post_id, 'cm_business_name', $prospect['Business Name'] );
                            update_post_meta( $post_id, 'cm_web_address', esc_url_raw( $prospect['Web Address'] ) );
                            update_post_meta( $post_id, 'cm_phone', $prospect['Phone Number'] );
                            update_post_meta( $post_id, 'cm_country', $prospect['Country'] );
                            update_post_meta( $post_id, 'claims_manager', $prospect['claims_manager'] );
                            if ( ! empty( $prospect['sales_manager'] ) ) {
                                update_post_meta( $post_id, 'sales_manager', $prospect['sales_manager'] );
                            }
                            unset( $prospects[ $key ] );
                            $converted++;
                        }
                    }
                }
                $prospects = array_values( $prospects );
                update_option( 'cm_prospects', $prospects );
            }
            wp_redirect( admin_url( 'admin.php?page=upload-prospects' ) );
            exit;
        }

        /**
         * AJAX handler for updating the temporary record.
         * This version updates the temporary record with the assigned Claims Manager.
         */
        public function ajax_assign_claims_manager() {
            if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'claims_admin' ) ) {
                wp_send_json_error( [ 'message' => esc_html__( 'Insufficient permissions', 'claims-management' ) ] );
            }
            if ( ! isset( $_POST['cm_assign_claims_manager_nonce'] ) || ! wp_verify_nonce( $_POST['cm_assign_claims_manager_nonce'], 'cm_assign_claims_manager' ) ) {
                wp_send_json_error( [ 'message' => esc_html__( 'Nonce verification failed', 'claims-management' ) ] );
            }
            if ( isset( $_POST['prospect_index'] ) ) {
                $prospect_index = intval( $_POST['prospect_index'] );
                $claims_manager = isset( $_POST['claims_manager'] ) ? trim( $_POST['claims_manager'] ) : '';
                $prospects = get_option( 'cm_prospects', [] );
                if ( ! isset( $prospects[ $prospect_index ] ) ) {
                    wp_send_json_error( [ 'message' => esc_html__( 'Prospect not found', 'claims-management' ) ] );
                }
                $prospects[ $prospect_index ]['claims_manager'] = $claims_manager;
                update_option( 'cm_prospects', $prospects );
                wp_send_json_success( [ 'message' => esc_html__( 'Prospect updated', 'claims-management' ) ] );
            } else {
                wp_send_json_error( [ 'message' => esc_html__( 'Prospect index not provided', 'claims-management' ) ] );
            }
        }
    } // end class Prospecting

} // end if class not exists

new Prospecting();