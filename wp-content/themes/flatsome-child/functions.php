<?php
//classes for menu
function secondary_menu_second_level($classes, $args, $depth)
{
    if ($args->theme_location == 'secondary') {

        $classes[] = 'secondary-menu';
        
        if ($depth == 0) {
            $classes[] = 'first-level';
        }
        if ($depth == 1) {
            $classes[] = 'second-level';
        }
    }
    return $classes;
}

add_filter('nav_menu_submenu_css_class', 'secondary_menu_second_level', 10, 3);

//adding js
function flatsome_child_enqueue_scripts() {
    wp_enqueue_script(
        'custom-menu',
        get_stylesheet_directory_uri() . '/js/custom.js',
        array('jquery'),
        false,
        true
    );
}

add_action('wp_enqueue_scripts', 'flatsome_child_enqueue_scripts');

