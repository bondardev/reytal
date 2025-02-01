<?php
/**
 * Uninstall Flatsome Menu Products
 *
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit; 
}

delete_option( 'flatsome_api_url' );
delete_option( 'flatsome_api_key' );
delete_option( 'flatsome_api_secret' );
delete_option( 'flatsome_sku_list' );