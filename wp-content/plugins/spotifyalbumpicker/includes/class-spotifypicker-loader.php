<?php
 
class Spotifypicker_Loader {
 
    protected $actions;
 
    protected $filters;
 
    public function __construct() {
 
        $this->actions = array();
        $this->filters = array();
     
    }
 
    public function add_action( $hook, $component, $callback, $priority = false, $args = false ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $args );
    }
 
    public function add_filter( $hook, $component, $callback, $priority = false, $args = false ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $args );
    }
 
    private function add( $hooks, $hook, $component, $callback, $priority, $args ) {
 
        $hooks[] = array(
            'hook'      => $hook,
            'component' => $component,
            'callback'  => $callback,
            'priority'  => $priority,
            'args'      => $args
        );
 
        return $hooks;
 
    }
 
    public function run() {
 
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $priority, $args );
        }
 
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $priority, $args );
        }
 
    }
 
}