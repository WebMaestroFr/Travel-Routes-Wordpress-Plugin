<?php

class TravelRoute {

	public $post_id;

	function __construct( $post_ID ) {
		$this->post_id = intval( $post_ID );
	}
	
	function __get( $property ) {
		switch ( $property ) {
			case 'post':
				return get_post( $this->post_id ); // $post OBJECT
				break;
			case 'locations':
				$terms = wp_get_post_terms( $this->post_id, TravelRoutesPlugin::$taxonomy, array('fields' => 'ids') );
				$locations = array();
				foreach ( $terms as $term_id ) {
					$location = new TravelLocation( $term_id );
					foreach ( $location->dates[$this->post_id] as $index=>$date ) {
						$location->date = $date;
						$locations[$index] = $location;
					}
				}
				ksort($locations);
				return $locations; // ARRAY of $location OBJECTS
				break;
			default:
				// show, color, dashed
				return get_post_meta( $this->post_id, 'route_'.$property, true);
		}
	}
}

?>