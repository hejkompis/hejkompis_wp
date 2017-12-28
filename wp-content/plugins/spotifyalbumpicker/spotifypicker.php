<?php
/*
 * Plugin Name:       Spotify Picker
 * Plugin URI:        http://grafikprofil.se/spotifypicker
 * Description:       A plugin that picks from Spotify and saves as Wordpress posts
 * Version:           0.1.0
 * Author:            Per Olsson
 * Author URI:        http://grafikprofil.se
 * Text Domain:       spotifypicker-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// include required files
require_once(ABSPATH . 'wp-config.php'); 
require_once(ABSPATH . 'wp-includes/wp-db.php'); 
require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

// If this file is called directly, then about execution.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Läs in WP_List_Table om den inte finns.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Include the core class responsible for loading all necessary components of the plugin.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-spotifypicker.php';

/**
 * Instantiates the Single Post Meta Manager class and then
 * calls its run method officially starting up the plugin.
 */ 
function run_spotifypicker() {
 
    $pp = new SpotifyPicker();
    $pp->run();
 
}
 
// Call the above function to begin execution of the plugin.
run_spotifypicker();