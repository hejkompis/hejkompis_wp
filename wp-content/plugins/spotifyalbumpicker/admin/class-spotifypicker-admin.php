<?php
 
class Spotifypicker_Admin {
 
    protected $version;
    private $slug;
 
    public function __construct( $version ) {
        $this->version = $version;
        $this->slug = 'spotifypicker';
    }
 
 	/**
	*
	* Läs in stylesheet för exempelvis metaboxen där man fyller i återförsäljarinfo, om man vill styla något separat.
	*
	*/
    public function enqueue_styles() {
 
        wp_enqueue_style(
            'spotify-admin',
            plugin_dir_url( __FILE__ ) . 'css/admin-style.css',
            array(),
            $this->version,
            FALSE
        );
 
    }

	/** 
	*
	* Funktion för att lägga till options för PCL Testdrive
	*
	*/
	public function add_spotifypicker_options_page() {

		add_submenu_page( 
			'edit.php', 
			'Spotify Picker', 
			'Spotify Picker',
    		'manage_options', 
    		'spotifypicker',
    		array($this, 'spotifypicker_options_metabox')
    	);

	}

	/**
	*
	* Lägg till metabox för settings.
	*
	*/
    public function spotifypicker_options_metabox() {
 
        require_once plugin_dir_path( __FILE__ ) . 'partials/spotifypicker-admin.php';
 
    }
 
}