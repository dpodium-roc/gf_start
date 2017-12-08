<?php
/*
Plugin Name: Mari Mari Hom
Plugin URI: http://www.gravityforms.com
Description: A tesing plugin from sun
Version: 1.0
Author: RocketNotGenius
Author URI: http://www.google.com
------------------------------------------------------------------------
Copyright 
*/

define( 'GF_PIPWAVE_VERSION', 1.0 );

add_action( 'gform_loaded', array( 'GF_Pipwave_Bootstrap', 'load' ),5 );

class GF_Pipwave_Bootstrap {
    public static function load() {
        if ( !method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
            return;
        }
        require_once( 'class-gf-pipwave.php' );
        GFAddOn::register( 'GFPipwave' );
    }
}

function gf_pipwave() {
    return GFPipwave::get_instance();
}