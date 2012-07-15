<?php

class TravelRoutesAdmin {
	 
	function __construct()
	{
		register_activation_hook( dirname(__FILE__).'/travel-routes.php', array( __CLASS__, 'activate' ) );
		// register_deactivation_hook( dirname(__FILE__).'/travel-routes.php', array( __CLASS__, 'deactivate' ) );
		// load_plugin_textdomain( 'travel-routes', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		add_action( 'admin_print_styles', array( __CLASS__, 'admin_print_styles' ) );
		add_action( 'admin_head-post.php', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'admin_head-post-new.php', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_route' ) );
		add_action( 'delete_term', array( __CLASS__, 'delete_location' ) );
		add_action( 'admin_notices', array( __CLASS__, 'error_notice' ), 0 );
	}
	
	public static function add_meta_boxes()
	{
		add_meta_box('post-travel-route', 'Travel Route', array( __CLASS__, 'post_route_meta_box' ), 'post', 'normal', 'high' );
	}
	
	public static function admin_print_styles()
	{
		wp_enqueue_style( 'jquery-ui', plugins_url( 'css/jquery-ui-fresh.css', __FILE__ ) );
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_style( 'travel-routes-admin', plugins_url( 'css/admin.css', __FILE__ ) );
	}

	public static function admin_enqueue_scripts()
	{
		wp_enqueue_script( 'google-places', 'http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'farbtastic' );
		wp_enqueue_script( 'travel-routes-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ) );
	}
	
	public static function post_route_meta_box( $post )
	{
		$route = new TravelRoute( $post->ID ); ?>
		<input type="hidden" name="post_route_meta_box_nonce" value="<?php echo wp_create_nonce(basename(__FILE__)); ?>" />
		<table id="route-options">
			<tr>
				<td><label><input type="checkbox" name="route_show" <?php echo ($route->show == 'no') ? '' : 'checked="checked"'; ?> /> Show this route on the map</label></td>
				<td class="colorpicker">
					<div id="route-colorpicker"></div>
					<label>Color <input type="text" name="route_color" id="route-color" value="<?php echo ( $color = $route->color) ? $color : '#8c2c0f'; ?>" size="7" /></label>
				</td>
				<td><label><input type="checkbox" name="route_dashed" <?php echo ($route->dashed == 'yes') ? 'checked="checked"' : ''; ?> /> Dashed</label></td>
			</tr>
		</table>
		<div id="route-map"></div>
		<table id="route-locations">
			<tfoot>
				<tr>
					<th><label>Place</label></th>
					<th><a href="javascript:void(0)" class="sort-dates">Date</a></label></th>
					<th>
						<input type="button" class="button add-location" value="Add a location" />
					</th>
				</tr>
			</tfoot>
			<tbody>
			<?php foreach ($route->locations as $location) { ?>
				<tr class="location">
					<td>
						<input type="hidden" name="route_location_latitude[]" value="<?php echo $location->geocode['lat']; ?>" class="latitude" />
						<input type="hidden" name="route_location_longitude[]" value="<?php echo $location->geocode['lng']; ?>" class="longitude" />
						<small class="parents"><?php echo $location->parents; ?></small>
						<input name="route_location_place[]" type="text" value="<?php echo $location->term->name; ?>" class="autocomplete">
					</td>
					<td>
						<input name="route_location_date[]" type="text" value="<?php echo $location->date; ?>" class="datepicker">
					</td>
					<td>
						<input type="button" class="button delete-location" value="Delete" />
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php  unset($route);
	}
	
	public static function save_route( $post_id )
	{
		if ( !wp_verify_nonce( $_POST['post_route_meta_box_nonce'], basename( __FILE__) ) ) return $post_id;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || defined( 'DOING_AJAX' ) && DOING_AJAX || ! current_user_can( 'edit_post', $post_id ) || false !== wp_is_post_revision( $post_id ) ) return;
		$places = $_POST['route_location_place'];
		$dates = $_POST['route_location_date'];
		$latitudes = $_POST['route_location_latitude'];
		$longitudes = $_POST['route_location_longitude'];
		$show = $_POST['route_show'] ? 'yes' : 'no';
		$color = $_POST['route_color'];
		$dashed = $_POST['route_dashed'] ? 'yes' : 'no';
		if ( $places && $dates && $latitudes && $longitudes ) {
			$visited = array();
			$route = new TravelRoute( $post_id );
			$i = 0;
			foreach ($places as $index=>$place) {
				if ( !empty( $place ) ) {
					$location = $route->locations[$i];
					if ( !$location || !( $location->geocode['lat'] == $latitudes[$index] && $location->geocode['lng'] == $longitudes[$index] ) ) {
						$term_id = self::insert_terms( $place );
						$location = new TravelLocation( $term_id );
					}
					if ($location->term_id) {
						if ( in_array( $location->term_id, $visited ) ) {
							$new_dates = $location->dates[$route->post_id];
						} else {
							$new_dates = array();
							$visited[] = $location->term_id;
						}
						$new_dates[$i] = $dates[$index];
						$location_dates = $location->dates;
						$location_dates[$route->post_id] = $new_dates;
						update_metadata('taxonomy', $location->term_id, 'dates', $location_dates);
						$i++;
					}
				}
			}
			update_post_meta( $route->post_id, 'route_show', $show );
			update_post_meta( $route->post_id, 'route_color', $color );
			update_post_meta( $route->post_id, 'route_dashed', $dashed );
			wp_set_post_terms( $route->post_id, $visited, TravelRoutesPlugin::$taxonomy, false );
		}
	}
	
	private static function insert_terms( $place ) {
		$datas = file_get_contents( 'http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode( $place ).'&sensor=false' );
		sleep( .72 );
		$datas = json_decode( $datas, true );
		// var_dump($datas);
		if ( $datas['status'] == 'OK' ) {
			$details = $datas['results'][0];
			$components = array_reverse( $details['address_components'] );
			$hierarchy = array();
			foreach ( $components as $component ) {
				if ( is_array( $type = $component['types'] ) ) $type = $type[0];
				if ( !in_array( $type, array( 'postal_code', 'post_box', 'street_number', 'floor', 'room' ) ) && $long_name !== $component['long_name'] ) {
					if ( !$term = term_exists( $component['long_name'], TravelRoutesPlugin::$taxonomy, end( $hierarchy ) ) ) {
						$term = wp_insert_term( $component['long_name'], TravelRoutesPlugin::$taxonomy, array( 'parent' => end( $hierarchy ) ) );
					}
					$hierarchy[] = intval( $term['term_id'] );
					if ( $type == 'country') update_metadata('taxonomy', end( $hierarchy ), 'code', $component['short_name'], true );
				}
				$long_name = $component['long_name'];
			}
			$term_id = end( $hierarchy );
			update_metadata('taxonomy', $term_id, 'details', $details, true );
			update_metadata('taxonomy', $term_id, 'dates', '', true );
			return $term_id;
		} else {
			global $post;
			$notice = get_option( 'travel_notice' );
			$notice[$post->ID] = 'Something is wrong with the <a href="https://developers.google.com/maps/documentation/geocoding/">Google Geocoding API</a>. The response status is : <code>'.$datas['status'].'</code>. The locations haven\'t been attached to the route.';
			update_option( 'travel_notice', $notice );
		}
	}
	
	public static function delete_location( $term ) {
		if ( $term['taxonomy'] == TravelRoutesPlugin::$taxonomy ) {
			$term_id = intval( $term['term_id'] );
			delete_metadata('taxonomy', $term_id, 'dates' );
			delete_metadata('taxonomy', $term_id, 'details' );
		}
	}
	
	public static function error_notice() {
    	global $post;
    	$notice = get_option( 'travel_notice' );
    	if ( empty( $notice ) ) return '';
    	foreach( $notice as $pid => $m ){
    	    if ( $post->ID == $pid ){
    	        echo '<div id="message" class="error"><p>'.$m.'</p></div>';
    	        unset( $notice[$pid] );
    	        update_option( 'travel_notice', $notice );
    	        break;
    	    }
    	}
	}
  
}

?>