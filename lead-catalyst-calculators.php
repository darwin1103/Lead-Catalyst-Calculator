<?php
/**
 * Plugin Name: Lead Catalyst Estimators & ROI Calculators
 * Description: Registers a custom Elementor Widget for ROI and Missed Opportunity Calculators, saves submissions, and manages leads.
 * Version: 1.0.2
 * Author: NP Connect
 * Author URI: https://nickpackard.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// 1. Database Table Creation on Activation
function lc_create_leads_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lc_calculator_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        company varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(50) NOT NULL,
        calc_type varchar(50) NOT NULL,
        industry varchar(50) DEFAULT '' NOT NULL,
        inputs text NOT NULL,
        outputs text NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'lc_create_leads_table' );

// Register frontend assets
add_action( 'wp_enqueue_scripts', function() {
    wp_register_script(
        'lead-catalyst-calculator-script',
        plugins_url( 'assets/calculator.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
    // Localize the REST API URL and a nonce so the frontend JS can securely call the backend
    wp_localize_script( 'lead-catalyst-calculator-script', 'lcVars', array(
        'restUrl' => esc_url_raw( rest_url( 'lead-catalyst/v1/submit-lead' ) ),
        'nonce'   => wp_create_nonce( 'wp_rest' )
    ) );

    wp_register_style(
        'lead-catalyst-calculator-style',
        plugins_url( 'assets/calculator.css', __FILE__ ),
        array(),
        '1.0.0'
    );
} );

// Register the Elementor Widget
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    $widget_file = __DIR__ . '/widgets/calculator-widget.php';
    if ( file_exists( $widget_file ) ) {
        require_once $widget_file;
        $widgets_manager->register( new \Lead_Catalyst_Calculator_Widget() );
    }
} );

// 2. Register REST API Route
add_action( 'rest_api_init', function () {
    register_rest_route( 'lead-catalyst/v1', '/submit-lead', array(
        'methods'             => 'POST',
        'callback'            => 'lc_handle_submit_lead',
        'permission_callback' => '__return_true', // Public lead collection
    ) );
} );

// REST Callback
function lc_handle_submit_lead( WP_REST_Request $request ) {
    $name      = sanitize_text_field( $request->get_param( 'name' ) );
    $company   = sanitize_text_field( $request->get_param( 'company' ) );
    $email     = sanitize_email( $request->get_param( 'email' ) );
    $phone     = sanitize_text_field( $request->get_param( 'phone' ) );
    $calc_type = sanitize_text_field( $request->get_param( 'calc_type' ) );
    $industry  = sanitize_text_field( $request->get_param( 'industry' ) );
    $inputs    = $request->get_param( 'inputs' );
    $outputs   = $request->get_param( 'outputs' );

    // Basic Validation
    if ( empty( $name ) || empty( $email ) || empty( $company ) || empty( $phone ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => esc_html__( 'Please fill out all fields.', 'lead-catalyst' ) ), 400 );
    }
    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => esc_html__( 'Please enter a valid email address.', 'lead-catalyst' ) ), 400 );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'lc_calculator_leads';

    // Auto-create table if it doesn't exist yet
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
        lc_create_leads_table();
    }

    // Debounce: prevent duplicate submissions from the same email within a 15-second window
    $last_lead = $wpdb->get_row( $wpdb->prepare(
        "SELECT submitted_at FROM $table_name WHERE email = %s ORDER BY id DESC LIMIT 1",
        $email
    ), ARRAY_A );

    if ( $last_lead ) {
        $last_submit_time = strtotime( $last_lead['submitted_at'] );
        $time_diff        = current_time( 'timestamp' ) - $last_submit_time;
        if ( $time_diff < 15 ) {
            // Return success immediately without inserting a duplicate or sending duplicate email
            return new WP_REST_Response( array( 'success' => true, 'message' => esc_html__( 'Your results have been sent successfully!', 'lead-catalyst' ) ), 200 );
        }
    }

    $inserted = $wpdb->insert(
        $table_name,
        array(
            'name'         => $name,
            'company'      => $company,
            'email'        => $email,
            'phone'        => $phone,
            'calc_type'    => $calc_type,
            'industry'     => $industry,
            'inputs'       => wp_json_encode( $inputs ),
            'outputs'      => wp_json_encode( $outputs ),
            'submitted_at' => current_time( 'mysql' ),
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    if ( $inserted ) {
        lc_send_admin_email( $name, $company, $email, $phone, $calc_type, $industry, $inputs, $outputs );
        return new WP_REST_Response( array( 'success' => true, 'message' => esc_html__( 'Your results have been sent successfully!', 'lead-catalyst' ) ), 200 );
    }

    return new WP_REST_Response( array( 'success' => false, 'message' => esc_html__( 'An error occurred while saving. Please try again.', 'lead-catalyst' ) ), 500 );
}

// 3. Email Alert Dispatcher
function lc_send_admin_email( $name, $company, $email, $phone, $calc_type, $industry, $inputs, $outputs ) {
    $admin_email = get_option( 'admin_email' );
    $subject     = sprintf( '[Lead Catalyst] New Lead Captured: %s (%s)', $name, $company );
    $calc_name   = ( $calc_type === 'roi' ) ? 'ROI Calculator' : 'Missed Opportunity Calculator';

    $inputs_rows = '';
    if ( is_array( $inputs ) ) {
        foreach ( $inputs as $key => $val ) {
            $label = ucwords( str_replace( array( '-', '_' ), ' ', $key ) );
            $inputs_rows .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', esc_html( $label ), esc_html( $val ) );
        }
    }

    $outputs_rows = '';
    if ( is_array( $outputs ) ) {
        foreach ( $outputs as $key => $val ) {
            $label = ucwords( str_replace( array( '-', '_' ), ' ', $key ) );
            $outputs_rows .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', esc_html( $label ), esc_html( $val ) );
        }
    }

    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
            h2 { color: #0c2340; border-bottom: 2px solid #1d70b8; padding-bottom: 12px; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #edf2f7; font-size: 14px; }
            th { font-weight: 700; background: #f7fafc; color: #4a5568; }
            td:first-child { width: 45%; font-weight: 600; color: #4a5568; }
            .section-title { font-size: 16px; font-weight: 700; color: #1d70b8; margin-top: 25px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
            .footer { margin-top: 30px; font-size: 11px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 15px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>New Prospect Submission</h2>
            <table>
                <tr><td>Name</td><td>" . esc_html( $name ) . "</td></tr>
                <tr><td>Company</td><td>" . esc_html( $company ) . "</td></tr>
                <tr><td>Email</td><td>" . esc_html( $email ) . "</td></tr>
                <tr><td>Phone</td><td>" . esc_html( $phone ) . "</td></tr>
                <tr><td>Calculator Type</td><td>" . esc_html( $calc_name ) . "</td></tr>
                " . ( ! empty( $industry ) ? "<tr><td>Selected Industry</td><td>" . esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $industry ) ) ) . "</td></tr>" : "" ) . "
            </table>

            <div class='section-title'>User Inputs</div>
            <table>
                $inputs_rows
            </table>

            <div class='section-title'>Calculated Results</div>
            <table>
                $outputs_rows
            </table>

            <div class='footer'>
                This notification email was automatically generated by the Lead Catalyst Calculator plugin.
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    wp_mail( $admin_email, $subject, $message, $headers );
}

// 4. Admin Menu Registration
add_action( 'admin_menu', function () {
    add_menu_page(
        esc_html__( 'Lead Catalyst Leads', 'lead-catalyst' ),
        esc_html__( 'LC Leads', 'lead-catalyst' ),
        'manage_options',
        'lead-catalyst-leads',
        'lc_render_leads_page',
        'dashicons-chart-area',
        25
    );
} );

// CSV Export Handler - Intercept on admin_init before output starts
add_action( 'admin_init', function () {
    if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'lead-catalyst-leads' && isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied.', 'lead-catalyst' ) );
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=lead-catalyst-leads-' . date( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'ID', 'Name', 'Company', 'Email', 'Phone', 'Calculator Type', 'Industry', 'Inputs JSON', 'Outputs JSON', 'Date' ) );

        global $wpdb;
        $table_name = $wpdb->prefix . 'lc_calculator_leads';
        $leads = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A );

        foreach ( $leads as $lead ) {
            fputcsv( $output, array(
                $lead['id'],
                $lead['name'],
                $lead['company'],
                $lead['email'],
                $lead['phone'],
                $lead['calc_type'],
                $lead['industry'],
                $lead['inputs'],
                $lead['outputs'],
                $lead['submitted_at'],
            ) );
        }
        fclose( $output );
        exit;
    }
} );

// Render Admin Page
function lc_render_leads_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'lc_calculator_leads';

    // Auto-create table if it somehow got deleted or didn't install on activation
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
        lc_create_leads_table();
    }

    // Handle Entry Deletion
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['lead_id'] ) ) {
        check_admin_referer( 'lc_delete_lead_' . $_GET['lead_id'] );
        $lead_id = intval( $_GET['lead_id'] );
        $wpdb->delete( $table_name, array( 'id' => $lead_id ), array( '%d' ) );
        echo '<div class="updated"><p>' . esc_html__( 'Lead deleted successfully.', 'lead-catalyst' ) . '</p></div>';
    }

    // Capture filters
    $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

    // Pagination variables
    $per_page = 15;
    $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset = ( $current_page - 1 ) * $per_page;

    // Build Query
    $where_sql = '';
    $query_params = array();

    if ( ! empty( $search ) ) {
        $where_sql = " WHERE name LIKE %s OR company LIKE %s OR email LIKE %s OR phone LIKE %s ";
        $like_search = '%' . $wpdb->esc_like( $search ) . '%';
        $query_params = array( $like_search, $like_search, $like_search, $like_search );
    }

    // Count Total
    $total_sql = "SELECT COUNT(*) FROM $table_name" . $where_sql;
    if ( ! empty( $query_params ) ) {
        $total_items = $wpdb->get_var( $wpdb->prepare( $total_sql, $query_params ) );
    } else {
        $total_items = $wpdb->get_var( $total_sql );
    }

    // Fetch Page Items
    $data_sql = "SELECT * FROM $table_name" . $where_sql . " ORDER BY id DESC LIMIT %d OFFSET %d";
    $query_params[] = $per_page;
    $query_params[] = $offset;
    $leads = $wpdb->get_results( $wpdb->prepare( $data_sql, $query_params ), ARRAY_A );

    $total_pages = ceil( $total_items / $per_page );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Lead Catalyst Calculator Submissions', 'lead-catalyst' ); ?></h1>
        <a href="<?php echo esc_url( add_query_arg( 'action', 'export_csv' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export to CSV', 'lead-catalyst' ); ?></a>
        
        <hr class="wp-header-end">

        <form method="get" style="margin-bottom: 20px; display: flex; justify-content: flex-end;">
            <input type="hidden" name="page" value="lead-catalyst-leads">
            <p class="search-box">
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_html_e( 'Search leads...', 'lead-catalyst' ); ?>">
                <input type="submit" class="button" value="<?php esc_html_e( 'Search', 'lead-catalyst' ); ?>">
            </p>
        </form>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th style="width: 15%;"><?php esc_html_e( 'Name', 'lead-catalyst' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Company', 'lead-catalyst' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Email', 'lead-catalyst' ); ?></th>
                    <th style="width: 12%;"><?php esc_html_e( 'Phone', 'lead-catalyst' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Calculator', 'lead-catalyst' ); ?></th>
                    <th style="width: 23%;"><?php esc_html_e( 'Submission Details', 'lead-catalyst' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Date', 'lead-catalyst' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $leads ) ) : ?>
                    <?php foreach ( $leads as $lead ) : ?>
                        <?php
                        $inputs_arr  = json_decode( $lead['inputs'], true );
                        $outputs_arr = json_decode( $lead['outputs'], true );
                        $delete_url  = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'lead_id' => $lead['id'] ) ), 'lc_delete_lead_' . $lead['id'] );

                        // Construct a small detailed tooltip or block
                        $details = '';
                        if ( $lead['calc_type'] === 'roi' ) {
                            $industry_name = ucwords( str_replace( array( '-', '_' ), ' ', $lead['industry'] ) );
                            $details .= '<strong>' . esc_html( $industry_name ) . '</strong><br>';
                        }
                        if ( is_array( $inputs_arr ) && is_array( $outputs_arr ) ) {
                            if ( $lead['calc_type'] === 'roi' ) {
                                $details .= sprintf( 
                                    'Conv: %s | Avg Sale: %s<br><strong style="color: #10b981;">ROI: %s</strong> (Net: %s)',
                                    esc_html( $inputs_arr['conversion-rate'] ?? $inputs_arr['Conversion-rate'] ?? '' ),
                                    esc_html( $inputs_arr['average-sale'] ?? $inputs_arr['Average-sale'] ?? '' ),
                                    esc_html( $outputs_arr['estimated-roi'] ?? '' ),
                                    esc_html( $outputs_arr['net-annual-return'] ?? '' )
                                );
                            } else {
                                $details .= sprintf( 
                                    'Conv: %s | Avg Sale: %s<br><strong style="color: #d97706;">Missed Rev: %s</strong>',
                                    esc_html( $inputs_arr['conversion-rate'] ?? $inputs_arr['Conversion-rate'] ?? '' ),
                                    esc_html( $inputs_arr['average-sale'] ?? $inputs_arr['Average-sale'] ?? '' ),
                                    esc_html( $outputs_arr['missed-revenue-opportunity'] ?? '' )
                                );
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $lead['name'] ); ?></strong>
                                <div class="row-actions">
                                    <span class="delete">
                                        <a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete" onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete this lead?', 'lead-catalyst' ); ?>')"><?php esc_html_e( 'Delete', 'lead-catalyst' ); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html( $lead['company'] ); ?></td>
                            <td><a href="mailto:<?php echo esc_attr( $lead['email'] ); ?>"><?php echo esc_html( $lead['email'] ); ?></a></td>
                            <td><?php echo esc_html( $lead['phone'] ); ?></td>
                            <td>
                                <span class="badge" style="background: <?php echo ( $lead['calc_type'] === 'roi' ) ? '#e2f3fe' : '#fffbeb'; ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: <?php echo ( $lead['calc_type'] === 'roi' ) ? '#1d70b8' : '#d97706'; ?>;">
                                    <?php echo ( $lead['calc_type'] === 'roi' ) ? 'ROI' : 'Missed Opp'; ?>
                                </span>
                            </td>
                            <td><?php echo $details; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lead['submitted_at'] ) ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e( 'No leads found.', 'lead-catalyst' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf( _n( '%s lead', '%s leads', $total_items, 'lead-catalyst' ), number_format_i18n( $total_items ) ); ?></span>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $current_page,
                        ) );
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
