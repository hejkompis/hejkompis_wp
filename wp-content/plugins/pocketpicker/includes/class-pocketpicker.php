<?php
 
class Pocketpicker {
 
    protected $loader, $plugin_slug, $version;
 
    public function __construct() {
 
        $this->plugin_slug = 'pocketpicker';
        $this->version = '0.1.0';

        $this->load_dependencies();
        $this->define_admin_hooks();
        //add_action('template_redirect', array($this, 'front_end_hooks'));
        $this->front_end_hooks();
 
    }
 
    /**
    *
    * Ladda in alla klasser som används för att köra action, filters, admin och widget.
    *
    */ 
    private function load_dependencies() {
 
        require_once plugin_dir_path( dirname( __FILE__ ) ) . "admin/class-pocketpicker-admin.php";
        require_once plugin_dir_path( __FILE__ ) . "/class-pocketpicker-functions.php";
        require_once plugin_dir_path( __FILE__ ) . "/class-pocketpicker-loader.php";
        $this->loader = new Pocketpicker_Loader();
 
    }
 
    /**
    *
    * Här läser vi in alla add_action och add_filter.
    * Ett objekt för funktioner i klassen för admin ($admin) och ett för widgeten ($widget).
    * Uppdelat i ('typ av funktion', $admin eller $widget, 'funktionens namn') 
    *
    */
    private function define_admin_hooks() {
 
        // register admin
        $admin = new Pocketpicker_Admin( $this->get_version() );

        $this->loader->add_action( 
            'admin_enqueue_scripts', 
            $admin, 
            'enqueue_styles' 
        );
        $this->loader->add_action( 
            'admin_menu', 
            $admin,
            'add_pocketpicker_options_page'
        );

    }

    public function front_end_hooks() {

        $pocket = new Pocket();        
        
        $this->loader->add_action( 
            'template_redirect', 
            $pocket, 
            'get_all'
        );

    }
 
    /**
    *
    * Kör loadern med alla actions och filters.
    *
    */
    public function run() {
        $this->loader->run();
    }
 
    /**
    *
    * Hämta version av plugin.
    *
    */
    public function get_version() {
        return $this->version;
    }
 
}