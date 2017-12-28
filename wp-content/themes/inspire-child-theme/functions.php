<?php

// !!! INFO !!!
//
// get_stylesheet_directory() <- använd för att hänvisa till child-theme-mapp
//

function theme_enqueue_styles() {

    $parent_style = 'parent-style';

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style )
    );

 //    wp_register_script('custom_js', get_stylesheet_directory_uri(__FILE__) . '/js/custom.jquery.js', array('jquery'));
	// wp_enqueue_script('custom_js');

}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );