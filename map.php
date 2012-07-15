<?php

class TravelMap extends WP_Widget {

	public function __construct() {
		parent::__construct( 'travel_map', 'Travel Map', array( 'description' => __( 'Show the routes and locations of your travels on a map.' ) ) );
	}
	
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		require_once( dirname(__FILE__).'/vector/'.$instance['version'].'.php' );
		echo $before_widget;
		if ( ! empty( $title ) ) echo $before_title . $title . $after_title;
		?>
<svg class="travel-routes-map" width="100%" height="<?php echo $instance['height']; ?>" version="1.1"
	xmlns="http://www.w3.org/2000/svg" style="background: <?php echo $instance['background']; ?>;">
	<defs>
		<style type="text/css"><![CDATA[
			path {
				stroke: <?php echo $instance['stroke']; ?>;
				fill: <?php echo $instance['fill']; ?>;
			}
			<?php echo file_get_contents( dirname(__FILE__).'/css/map.css' ); ?>
		]]></style>
	</defs>
	<?php echo new TravelMapVector; ?>
	<g class="travel-routes"><?php
		$routes = TravelRoutesPlugin::getRoutes();
		foreach ( $routes as $post ) {
			$route = new TravelRoute( $post->ID );
			if ( $length = count( $route->locations ) ) {
				$points = array();
				foreach ( $route->locations as $i => $location ) {
					$points[] = implode( ',', $location->points );
					if ( !$markers[$location->term_id] ) {
						$markers[$location->term_id] = '<circle  id="travel-location-'.$location->term_id.'" cx="'.$location->points['x'].'" cy="'.$location->points['y'].'" r="4" class="travel-location country-'.$location->country;
						$links['locations'][$location->term_id] = str_replace( get_bloginfo( 'url' ), '', get_term_link( $location->term_id, TravelRoutesPlugin::$taxonomy ) );
					}
					$markers[$location->term_id] .= ' route-'.$route->post_id;
					$markers[$location->term_id] .= ( $i == 0 ) ? ' first' : '';
					$markers[$location->term_id] .= ( $i == $length - 1 ) ? ' last' : '';
				}
				if ( $length == 1) {
					$singles[] = '<circle id="travel-route-'.$route->post_id.'" class="travel-route country-'.$route->locations[0]->country.'" cx="'.$route->locations[0]->points['x'].'" cy="'.$route->locations[0]->points['y'].'" r="6" style="fill:'.$route->color.'" />';
				} else {
					echo '<polyline id="travel-route-'.$route->post_id.'" class="travel-route'.( $route->dashed ? ' dashed' : '' ).'" points="'.implode( ' ', $points ).'" style="stroke:'.$route->color.( $route->dashed ? ';stroke-dasharray:2,4' : '' ).'" />';
				}
				$links['routes'][$post->ID] = str_replace( get_bloginfo( 'url' ), '', get_permalink( $post->ID ) );
			}
		}
		echo implode( '" />', $markers ).'" />'.implode( $singles );
	?></g>
</svg>
		<?php
		echo $after_widget;
		wp_enqueue_script( 'travel-routes', plugins_url( 'js/display.js', __FILE__ ), array( 'jquery' ) );
		$links['site'] = get_bloginfo( 'url' );
		wp_localize_script( 'travel-routes', 'permalinks', $links );
	}

	public function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['version'] = $new_instance['version'];
		$instance['height'] = $new_instance['height'];
		$instance['background'] = $new_instance['background'];
		$instance['stroke'] = $new_instance['stroke'];
		$instance['fill'] = $new_instance['fill'];
		return $instance;
	}

	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		$version = isset( $instance[ 'version' ] ) ? $instance[ 'version' ] : 'light';
		$height = isset( $instance[ 'height' ] ) ? $instance[ 'height' ] : 288;
		$background = isset( $instance[ 'background' ] ) ? $instance[ 'background' ] : '#a5bfdd';
		$stroke = isset( $instance[ 'stroke' ] ) ? $instance[ 'stroke' ] : '#818181';
		$fill = isset( $instance[ 'fill' ] ) ? $instance[ 'fill' ] : '#f4f3f0';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'height' ); ?>">Height</label> 
			<input id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" size="3" value="<?php echo $height; ?>" />px
		</p>
		<details>
			<summary>More Options [beta]</summary>
			<label for="<?php echo $this->get_field_id( 'background' ); ?>">Background</label> 
			<input id="<?php echo $this->get_field_id( 'background' ); ?>" name="<?php echo $this->get_field_name( 'background' ); ?>" type="text" value="<?php echo $background; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'stroke' ); ?>">Stroke</label> 
			<input id="<?php echo $this->get_field_id( 'stroke' ); ?>" name="<?php echo $this->get_field_name( 'stroke' ); ?>" type="text" value="<?php echo $stroke; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'fill' ); ?>">Fill</label> 
			<input id="<?php echo $this->get_field_id( 'fill' ); ?>" name="<?php echo $this->get_field_name( 'fill' ); ?>" type="text" value="<?php echo $fill; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'fill' ); ?>">Version</label>
			<input id="<?php echo $this->get_field_id( 'version' ); ?>" name="<?php echo $this->get_field_name( 'version' ); ?>" type="text" value="<?php echo $version; ?>" />
		</details>
		<?php 
	}

}

?>