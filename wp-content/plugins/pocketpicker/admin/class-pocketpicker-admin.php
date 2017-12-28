<?php
 
class Pocketpicker_Admin {
 
    protected $version;
    private $slug;
 
    public function __construct( $version ) {
        $this->version = $version;
        $this->slug = 'pocketpicker';
    }
 
 	/**
	*
	* Läs in stylesheet för exempelvis metaboxen där man fyller i återförsäljarinfo, om man vill styla något separat.
	*
	*/
    public function enqueue_styles() {
 
        wp_enqueue_style(
            'pocketpicker-admin',
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
	public function add_pocketpicker_options_page() {

		add_submenu_page( 
			'edit.php', 
			'Pocket Picker', 
			'Pocket Picker',
    		'manage_options', 
    		'pocketpicker',
    		array($this, 'pocketpicker_options_metabox')
    	);

	}

	/**
	*
	* Lägg till metabox för settings.
	*
	*/
    public function pocketpicker_options_metabox() {
 
        require_once plugin_dir_path( __FILE__ ) . 'partials/pocketpicker-admin.php';
 
    }
 
}