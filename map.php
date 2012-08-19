<?php

class TravelMap extends WP_Widget {

	public function __construct() {
		parent::__construct( 'travel_map', 'Travel Map', array( 'description' => __( 'Show the routes and locations of your travels on a map.' ) ) );
		add_action('admin_print_scripts-widgets.php', array( __CLASS__, 'widget_scripts' ) );
	}
	
	public function widget_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'farbtastic' );
		wp_enqueue_style( 'farbtastic' );
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
		$markers = array();
		$singles = array();
		foreach ( $routes as $route ) {
			$length = count( $route->locations );
			$points = array();
			$countries = array();
			foreach ( $route->locations as $i => $location ) {
				if ( !$markers[$location->term_id] ) {
					$markers[$location->term_id]['points'] = $location->points;
					$markers[$location->term_id]['country'] = $location->country->code;
					$markers[$location->term_id]['first'] = ( $i == 0 );
					$markers[$location->term_id]['last'] = ( $i == $length - 1 );
					$links['locations'][$location->term_id] = str_replace( get_bloginfo( 'url' ), '', get_term_link( $location->term_id, TravelRoutesPlugin::$taxonomy ) );
					if ( !$links['countries'][$location->country->code] ) {
						$links['countries'][$location->country->code] = str_replace( get_bloginfo( 'url' ), '', get_term_link( $location->country->term_id, TravelRoutesPlugin::$taxonomy ) );
					}
				} else {
					if ( $i == 0 ) $markers[$location->term_id]['first'] = true;
					if ( $i == $length - 1 ) $markers[$location->term_id]['last'] = true;
				}
				$markers[$location->term_id]['routes'][] = $route->post_id;
				if ( $length > 1) {
					$points[] = implode( ',', $location->points );
					if ( !in_array( $location->country->code, $countries ) ) {
						$countries[] = $location->country->code;
					}
				}
			}
			if ( $length == 1) {
				$location = $route->locations[0];
				$singles[] = '<circle id="travel-route-'.$route->post_id.'" data-post="'.$route->post_id.'" data-country="'.$location->country->code.'" class="travel-route" cx="'.$location->points['x'].'" cy="'.$location->points['y'].'" r="6" style="fill:'.$route->color.'" />';
			} else {
				echo '<polyline id="travel-route-'.$route->post_id.'" data-post="'.$route->post_id.'" data-country="'.implode( ' ', $countries ).'" class="travel-route'.( $route->dashed ? ' dashed' : '' ).'" points="'.implode( ' ', $points ).'" style="stroke:'.$route->color.'" />';
			}
			$links['routes'][$route->post_id] = str_replace( get_bloginfo( 'url' ), '', get_permalink( $route->post_id ) );
		}
		foreach ( $markers as $term_id => $marker ) {
			echo '<circle id="travel-location-'.$term_id.'" data-term="'.$term_id.'" data-post="'.implode( ' ', array_unique( $marker['routes'] ) ).'" data-country="'.$marker['country'].'" cx="'.$marker['points']['x'].'" cy="'.$marker['points']['y'].'" r="4" class="travel-location'.( $marker['first'] ? ' first' : '' ).( $marker['last'] ? ' last' : '' ).' route-'.implode( ' route-', array_unique( $marker['routes'] ) ).'" />';
		}
		echo implode( $singles );
	?></g>
</svg>
		<?php
		echo $after_widget;
		wp_enqueue_script( 'travel-routes-display', plugins_url( 'js/display.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_style( 'travel-routes-style', plugins_url( 'css/display.css', __FILE__ ) );
		$links['site'] = get_bloginfo( 'url' );
		wp_localize_script( 'travel-routes-display', 'permalinks', $links );
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
		$title = isset( $instance[ 'title' ] ) ? '' : $instance[ 'title' ];
		$version = empty( $instance[ 'version' ] ) ? 'light' : $instance[ 'version' ];
		$height = empty( $instance[ 'height' ] ) ? 288 : $instance[ 'height' ];
		$background = empty( $instance[ 'background' ] ) ? '#a5bfdd' : $instance[ 'background' ];
		$stroke = empty( $instance[ 'stroke' ] ) ? '#818181' : $instance[ 'stroke' ];
		$fill = empty( $instance[ 'fill' ] ) ? '#f4f3f0' : $instance[ 'fill' ];
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'height' ); ?>">Height</label> 
			<input id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" size="3" value="<?php echo $height; ?>" />px
		</p>
		
		<div class="travelmap-color">
			<label for="<?php echo $this->get_field_id( 'background' ); ?>">Background</label>
			<div class="travelmap-colorpicker"></div>
			<input class="widefat" id="<?php echo $this->get_field_id( 'background' ); ?>" name="<?php echo $this->get_field_name( 'background' ); ?>" type="text" value="<?php echo $background; ?>" />
		</div>
		<div class="travelmap-color">
			<label for="<?php echo $this->get_field_id( 'stroke' ); ?>">Stroke</label>
			<div class="travelmap-colorpicker"></div>
			<input class="widefat" id="<?php echo $this->get_field_id( 'stroke' ); ?>" name="<?php echo $this->get_field_name( 'stroke' ); ?>" type="text" value="<?php echo $stroke; ?>" />
		</div>
		<div class="travelmap-color">
			<label for="<?php echo $this->get_field_id( 'fill' ); ?>">Fill</label>
			<div class="travelmap-colorpicker"></div>
			<input class="widefat" id="<?php echo $this->get_field_id( 'fill' ); ?>" name="<?php echo $this->get_field_name( 'fill' ); ?>" type="text" value="<?php echo $fill; ?>" />
		</div>
		
		<?php /* <label for="<?php echo $this->get_field_id( 'fill' ); ?>">Version</label> */ ?>
		<input id="<?php echo $this->get_field_id( 'version' ); ?>" name="<?php echo $this->get_field_name( 'version' ); ?>" type="hidden" value="<?php echo $version; ?>" />
		
		<script type="text/javascript">
			(function($) {
				$('.widget-content .travelmap-color').each(function(i, field){
					field.picker = $(field).find('.travelmap-colorpicker');
					field.input = $(field).find('input');
					$(field.picker).hide().farbtastic('#' + $(field.input).attr('id'));
					$(field.input).focus(function() {
						$(field.picker).show('fast');
					});
					$(field.input).focusout(function() {
						$(field.picker).hide('fast');
					});
				});
			})(jQuery);
		</script>
		<?php
	}

}

?>