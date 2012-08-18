<?php

/*
Plugin Name: Travel Routes
Plugin URI: http://webmaestro.fr/travel-routes-wordpress-plugin/
Description: Display your posts locations on customizable maps.
Version: 1.0
Author: WebMaestro.Fr
Author URI: http://webmaestro.fr
License:

  Copyright 2012 (etienne@webmaestro.fr)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

require_once( dirname(__FILE__).'/route.php' );
require_once( dirname(__FILE__).'/location.php' );
require_once( dirname(__FILE__).'/map.php' );
if ( is_admin() ) require_once( dirname(__FILE__).'/admin.php' );

class TravelRoutesPlugin {

	public static $taxonomy = 'route_location';
	 
	function __construct()
	{
		if ( is_admin() ) {
			new TravelRoutesAdmin;
		}
	    add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
	}
	
	public static function init()
	{
		global $wpdb;
		$wpdb->taxonomymeta = $wpdb->prefix.'taxonomymeta';
		$args_taxonomy = array(
			'label' => __( 'Locations' ),
			'hierarchical' => true,
			'public' => true
		);
		register_taxonomy( self::$taxonomy, 'post', $args_taxonomy );
	}
	
	// public static function enqueue_scripts() {}
	
	public static function widgets_init() {
		register_widget( 'TravelMap' );
	}
	
	
	public static function getRoutes() {
		$posts = get_posts( array(
			'numberposts'	=> -1,
			'orderby'		=> 'post_date',
			'order'			=> 'ASC'
		) );
		$routes = array();
		foreach ( $posts as $post ) {
			$route = new TravelRoute( $post->ID );
			if ( $route->show && count( $route->locations ) ) $routes[] = $route;
		}
		return $routes;
	}
	
	// GET_TERM_PARENTS
	static function get_term_parents( $id, $taxonomy, $link = false, $separator = '/', $nicename = false, $visited = array() )
	{
		$chain = '';
		$parent = &get_term( $id, $taxonomy );
		if ( is_wp_error( $parent ) )
			return $parent;
		if ( $nicename )
			$name = $parent->slug;
		else
			$name = $parent->name;
		if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
			$visited[] = $parent->parent;
			$chain .= self::get_term_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
		}
		if ( $link )
			$chain .= '<a href="' . esc_url( get_term_link( intval( $parent->term_id ), $taxonomy ) ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
		else
			$chain .= $name.$separator;
		return $chain;
	}
	// GET_TERM_PARENTS
  
}

new TravelRoutesPlugin;

?>