<?php
/**
 * Plugin Name: Flatsome Secondary Menu Products
 * Description: Adds product image, title, SKU, regular price, and sale price (on a new line) to Flatsome secondary menu items if the item links to a WooCommerce product.
 * Version: 1.2
 * Author: Oleksii Bondar
 * Text Domain: flatsome-secondary-menu-products
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Flatsome_Secondary_Menu_Products' ) ) {

    class Flatsome_Secondary_Menu_Products {

        /**
         * Constructor.
         *
         * Hooks into plugins_loaded to initialize the plugin.
         */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
        }

        /**
         * Plugin initialization.
         *
         * Checks if WooCommerce is active. If not, displays an admin notice.
         * Otherwise, adds a filter to modify the secondary menu items.
         */
        public function init_plugin() {
            if ( ! class_exists( 'WooCommerce' ) ) {
                add_action( 'admin_notices', array( $this, 'display_woocommerce_required_notice' ) );
                return;
            }
            add_filter( 'walker_nav_menu_start_el', array( $this, 'filter_menu_item' ), 10, 4 );
        }

        /**
         * Displays an admin notice if WooCommerce is not active.
         */
        public function display_woocommerce_required_notice() {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Flatsome Secondary Menu Products', 'flatsome-secondary-menu-products' ) . '</strong> ' . esc_html__( 'requires WooCommerce to work.', 'flatsome-secondary-menu-products' ) . '</p></div>';
        }

        /**
         * Generates an HTML block with product information.
         *
         * @param WC_Product $product WooCommerce product object.
         * @return string HTML markup with product details.
         */
        public function get_product_info( $product ) {
            $sku           = $product->get_sku();
            $regular_price = $product->get_regular_price();
            $sale_price    = $product->get_sale_price();
            $image_id      = $product->get_image_id();
            $image_url     = wp_get_attachment_image_url( $image_id, array( 150, 150 ) );
            $product_title = $product->get_name();

            $output = '';

            // 1. Product image.
            if ( $image_url ) {
                $output .= sprintf(
                    '<img src="%s" alt="%s" class="fsmenu-product-image">',
                    esc_url( $image_url ),
                    esc_attr( $product_title )
                );
            }

            // 2. Product title (after the image).
            $output .= sprintf(
                '<span class="fsmenu-product-title">%s</span>',
                esc_html( $product_title )
            );

            // 3. Product SKU (if available).
            if ( ! empty( $sku ) ) {
                $output .= sprintf(
                    '<span class="fsmenu-product-sku">%s</span>',
                    esc_html( $sku )
                );
            }

            // 4. Regular price (if exists).
            if ( $regular_price !== '' ) {
                $output .= sprintf(
                    '<span class="fsmenu-product-price">%s %s</span>',
                    esc_html__( 'Hind:', 'flatsome-secondary-menu-products' ),
                    esc_html( strip_tags( wc_price( $regular_price ) ) )
                );
            }

            // 5. Sale price (on a new line if available and different from the regular price).
            if ( $sale_price && $sale_price !== $regular_price ) {
                $output .= sprintf(
                    '<span class="fsmenu-product-sale-price">%s %s</span>',
                    esc_html__( 'Soodushind:', 'flatsome-secondary-menu-products' ),
                    esc_html( strip_tags( wc_price( $sale_price ) ) )
                );
            }

            return $output;
        }

        /**
         * Filters the nav menu item output to insert product information.
         *
         * @param string   $item_output The original HTML output for the menu item.
         * @param WP_Post  $item        The menu item object.
         * @param int      $depth       The menu item depth.
         * @param stdClass $args        An object of wp_nav_menu() arguments.
         * @return string Modified menu item HTML output.
         */
        public function filter_menu_item( $item_output, $item, $depth, $args ) {
            // Check if this is the "secondary" menu.
            if ( isset( $args->theme_location ) && $args->theme_location === 'secondary' ) {
                // Check if the menu item links to a WooCommerce product.
                if ( isset( $item->object ) && $item->object === 'product' ) {
                    $product_id = (int) $item->object_id;
                    $product    = wc_get_product( $product_id );
                    if ( $product && $product instanceof WC_Product ) {
                        $product_info = $this->get_product_info( $product );
                        // Replace the content inside the <a> tag with the product info block.
                        $item_output = preg_replace( '/(<a[^>]*>)(.*?)(<\/a>)/i', '$1' . $product_info . '$3', $item_output );
                    }
                }
            }
            return $item_output;
        }
    }

    // Instantiate the plugin class.
    new Flatsome_Secondary_Menu_Products();
}
