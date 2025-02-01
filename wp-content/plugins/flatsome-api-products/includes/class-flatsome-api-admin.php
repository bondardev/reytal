<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Flatsome_API_Admin
 *
 * Manages the admin area functionality including the settings page, saving settings,
 * processing product import, and displaying notices.
 */
class Flatsome_API_Admin {

    /**
     * Constructor.
     *
     * Registers hooks for the admin menu, settings save, product import, and displaying notices.
     */
    public function __construct() {
        // Add the plugin settings page to the admin menu.
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        // Process settings save requests on admin initialization.
        add_action( 'admin_init', array( $this, 'process_settings_save' ) );
        // Process product import requests on admin initialization.
        add_action( 'admin_init', array( $this, 'process_products_import' ) );
        // Display settings notices (e.g., success or error messages) in the admin area.
        add_action( 'admin_notices', array( $this, 'display_settings_notice' ) );
        // Display product import messages (if any) in the admin area.
        add_action( 'admin_notices', array( $this, 'display_import_notice' ) );
    }

    /**
     * Adds the settings page to the admin menu.
     *
     * This function calls add_menu_page() to create a new top-level menu item in the admin sidebar.
     */
    public function add_admin_menu() {
        add_menu_page(
            esc_html__( 'Flatsome API Products', 'flatsome-api-products' ), // Page title.
            esc_html__( 'Get products', 'flatsome-api-products' ),            // Menu title.
            'manage_options',                                                 // Required capability.
            'flatsome-menu-products',                                           // Menu slug.
            array( $this, 'render_settings_page' ),                           // Callback function to render the page.
            'dashicons-cart',                                                 // Icon for the menu.
            60                                                                // Position in the menu.
        );
    }

    /**
     * Renders the settings page.
     *
     * Determines the plugin root directory (one level up from the "includes" folder)
     * and includes the admin-page.php file. Uses include_once to prevent duplicate output.
     */
    public function render_settings_page() {
        // Get the plugin root directory (since this file is in the "includes" folder)
        $admin_page = plugin_dir_path( dirname( __FILE__ ) ) . 'admin-page.php';
        if ( file_exists( $admin_page ) ) {
            include_once $admin_page;
        } else {
            // Display an error message if the settings page file is not found.
            echo '<div class="error"><p>' . esc_html__( 'Settings page file not found.', 'flatsome-api-products' ) . '</p></div>';
        }
    }

    /**
     * Processes settings save requests.
     *
     * Checks if the request is from an admin user with the proper capability.
     * Verifies the nonce using wp_verify_nonce to avoid duplicate error output.
     * Then, updates the plugin options based on the submitted form data.
     */
    public function process_settings_save() {
        // Ensure the current user is an admin with appropriate permissions.
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if the settings form was submitted.
        if ( isset( $_POST['save_settings'] ) ) {
            // Manually verify the nonce to prevent automatic error output by wp_nonce_ays().
            if ( ! isset( $_POST['flatsome_menu_products_nonce'] ) || 
                 ! wp_verify_nonce( $_POST['flatsome_menu_products_nonce'], 'flatsome_menu_products_save' ) ) {
                // Save an error message in a transient so it can be displayed later.
                set_transient( 'flatsome_settings_notice', esc_html__( 'Nonce verification failed.', 'flatsome-api-products' ), 30 );
                return;
            }
            // Check if the required fields (API URL and SKU list) are not empty.
            if ( ! empty( $_POST['api_url'] ) && ! empty( $_POST['sku_list'] ) ) {
                // Update the plugin options with sanitized data.
                update_option( 'flatsome_api_url', sanitize_text_field( $_POST['api_url'] ) );
                update_option( 'flatsome_sku_list', sanitize_text_field( $_POST['sku_list'] ) );

                // Update API key if provided.
                if ( ! empty( $_POST['api_key'] ) ) {
                    update_option( 'flatsome_api_key', Flatsome_API_Helper::encrypt_api_key( sanitize_text_field( $_POST['api_key'] ) ) );
                }
                // Update API secret if provided.
                if ( ! empty( $_POST['api_secret'] ) ) {
                    update_option( 'flatsome_api_secret', Flatsome_API_Helper::encrypt_api_key( sanitize_text_field( $_POST['api_secret'] ) ) );
                }

                // Check the API connection before saving and set a success or error notice accordingly.
                if ( ! Flatsome_API_Helper::check_api_connection() ) {
                    set_transient( 'flatsome_settings_notice', esc_html__( 'API connection failed. Check your credentials.', 'flatsome-api-products' ), 30 );
                } else {
                    set_transient( 'flatsome_settings_notice', esc_html__( 'Settings saved successfully!', 'flatsome-api-products' ), 30 );
                }
            } else {
                // Set an error notice if required fields are missing.
                set_transient( 'flatsome_settings_notice', esc_html__( 'Please fill in all required fields.', 'flatsome-api-products' ), 30 );
            }
        }
    }

    /**
     * Processes product import requests.
     *
     * Checks if the current user is an admin and has the correct nonce.
     * Calls the import function from Flatsome_API_Helper, sets a success notice,
     * and then redirects the user back to the settings page.
     */
    public function process_products_import() {
        // Ensure the current user is an admin.
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if the import form was submitted.
        if ( isset( $_POST['import_products'] ) ) {
            // Verify the nonce for the import action.
            if ( ! isset( $_POST['flatsome_import_products_nonce'] ) ||
                 ! wp_verify_nonce( $_POST['flatsome_import_products_nonce'], 'flatsome_import_products' ) ) {
                // Set an error notice if nonce verification fails.
                set_transient( 'flatsome_settings_notice', esc_html__( 'Nonce verification failed for product import.', 'flatsome-api-products' ), 30 );
                return;
            }
            // Call the import function from the API helper class.
            Flatsome_API_Helper::import_products();
            // Set a success message for the import.
            set_transient( 'flatsome_settings_notice', esc_html__( 'Products imported successfully!', 'flatsome-api-products' ), 30 );
            // Redirect the user back to the settings page.
            wp_redirect( admin_url( 'admin.php?page=flatsome-menu-products' ) );
            exit;
        }
    }

    /**
     * Displays accumulated settings notices.
     *
     * Checks for a transient message set during settings save or product import,
     * displays it as an admin notice, and then deletes the transient.
     */
    public function display_settings_notice() {
        if ( $notice = get_transient( 'flatsome_settings_notice' ) ) {
            echo '<div class="notice notice-info"><p>' . wp_kses_post( $notice ) . '</p></div>';
            delete_transient( 'flatsome_settings_notice' );
        }
    }

    /**
     * Displays accumulated import messages.
     *
     * Checks for a transient message (accumulated during product import),
     * displays it as an admin notice, and then deletes the transient to prevent repeated output.
     */
    public function display_import_notice() {
        if ( $notice = get_transient( 'flatsome_import_notice' ) ) {
            echo '<div class="notice notice-info"><p>' . wp_kses_post( $notice ) . '</p></div>';
            delete_transient( 'flatsome_import_notice' );
        }
    }
}

// Add an additional hook to display settings notices in case they haven't been output.
add_action( 'admin_notices', function() {
    if ( function_exists( 'get_transient' ) ) {
        if ( $notice = get_transient( 'flatsome_settings_notice' ) ) {
            echo '<div class="notice notice-info"><p>' . wp_kses_post( $notice ) . '</p></div>';
            delete_transient( 'flatsome_settings_notice' );
        }
    }
});

// Instantiate the admin class to initialize the plugin's admin functionality.
new Flatsome_API_Admin();
