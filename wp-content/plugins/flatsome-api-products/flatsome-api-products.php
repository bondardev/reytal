<?php
/**
 * Plugin Name: Flatsome Api Products
 * Description: Imports products from API and links them to Flatsome menu items.
 * Version: 1.1
 * Author: Oleksii Bondar
 * Text Domain: flatsome-api-products
 */

// Exit if accessed directly (security check).
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Include required classes from the "includes" folder.
 * These files contain the helper functions for API operations and the admin functionality.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-flatsome-api-helper.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-flatsome-api-admin.php';

/**
 * Class Flatsome_Api_Products_Main
 *
 * The main plugin class that initializes the plugin.
 * It checks for WooCommerce dependency and then sets up the admin functionality.
 */
class Flatsome_Api_Products_Main {

    /**
     * Constructor.
     *
     * Hooks the plugin initialization into the 'plugins_loaded' action with a priority of 20.
     * This ensures that all other plugins have been loaded before this plugin initializes.
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 20 );
    }

    /**
     * Plugin initialization callback.
     *
     * This method is executed on the 'plugins_loaded' hook.
     * It first checks if WooCommerce is active. If WooCommerce is not available,
     * it registers an admin notice to inform the user.
     * Otherwise, it instantiates the admin class (Flatsome_API_Admin) which handles
     * the settings page, saving options, and product import functionality.
     */
    public function init_plugin() {
        // Check if WooCommerce class exists (i.e., if WooCommerce is active).
        if ( ! class_exists( 'WooCommerce' ) ) {
            // If WooCommerce is not active, hook a function to display an admin notice.
            add_action( 'admin_notices', array( $this, 'display_woocommerce_required_notice' ) );
            return;
        }

        // If WooCommerce is active, instantiate the admin class to manage plugin settings and import functionality.
        new Flatsome_API_Admin();
    }

    /**
     * Displays an admin notice when WooCommerce is not active.
     *
     * This message informs the user that the plugin requires WooCommerce to function.
     */
    public function display_woocommerce_required_notice() {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Flatsome Api Products requires WooCommerce to function.', 'flatsome-api-products' ) . '</p></div>';
    }

    /**
     * Activation callback.
     *
     * This static method is called when the plugin is activated.
     * It checks if WooCommerce is active and, if not, deactivates the plugin and displays an error message.
     */
    public static function activate() {
        // Check if WooCommerce is active by verifying the existence of the WooCommerce class.
        if ( ! class_exists( 'WooCommerce' ) ) {
            // Deactivate the plugin if WooCommerce is not active.
            deactivate_plugins( plugin_basename( __FILE__ ) );
            // Display an error message and halt execution.
            wp_die( esc_html__( 'Flatsome Api Products requires WooCommerce to function. The plugin has been deactivated.', 'flatsome-api-products' ) );
        }
    }
}

// Instantiate the main plugin class to initialize the plugin.
$flatsome_api_products_main = new Flatsome_Api_Products_Main();

// Register the plugin activation hook to call the activate() method when the plugin is activated.
register_activation_hook( __FILE__, array( 'Flatsome_Api_Products_Main', 'activate' ) );
