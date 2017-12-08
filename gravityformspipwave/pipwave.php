<?php
/*
Plugin Name: Mari Mari Hom
Plugin URI: http://www.gravityforms.com
Description: A tesing plugin from sun
Version: 1.0
Author: RocketNotGenius
Author URI: http://www.google.com
------------------------------------------------------------------------
* Copyright 2009 -  rocketnotgenius
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
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