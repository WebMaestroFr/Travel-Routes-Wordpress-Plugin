<?php

class TravelLocation {
	
	public $term_id, $date;
	
	function __construct( $term_id ) {
		$this->term_id = intval( $term_id );
	}
	
	function __get( $property ) {
		switch ( $property ) {
			case 'term':
				return get_term( $this->term_id, TravelRoutesPlugin::$taxonomy ); // $term OBJECT
				break;
			case 'routes':
				return get_objects_in_term( $this->term_id, TravelRoutesPlugin::$taxonomy ); // $post_ids ARRAY
				break;
			case 'parents':
				return TravelRoutesPlugin::get_term_parents( $this->term->parent, TravelRoutesPlugin::$taxonomy, true, ' &raquo; ' ); // STRING
				break;
			case 'geocode':
				return $this->details['geometry']['location']; // ARRAY( 'lat', 'lng' )
				break;
			case 'points':
				if ( !class_exists( 'TravelMapVector' ) ) return false;
				if ( $this->geocode['lng'] < ( -180 + TravelMapVector::$central_meridian ) ) {
					$this->geocode['lng'] += 360;
				}
				$x = ( $this->geocode['lng'] - TravelMapVector::$central_meridian ) / 360 * TravelMapVector::$circumference;
				$y = ( 180 / M_PI * ( 5 / 4 ) * log( tan( M_PI / 4 + ( 4 / 5 ) * -$this->geocode['lat'] * M_PI / 360 ) ) ) / 360 * TravelMapVector::$circumference;
				$x = number_format( ( $x - TravelMapVector::$bounding_box[0]['x'] ) / ( TravelMapVector::$bounding_box[1]['x'] - TravelMapVector::$bounding_box[0]['x'] ) * TravelMapVector::$map_width, 2 );
				$y = number_format( ( $y - TravelMapVector::$bounding_box[0]['y'] ) / ( TravelMapVector::$bounding_box[1]['y'] - TravelMapVector::$bounding_box[0]['y'] ) * TravelMapVector::$map_height, 2 );
				return array( 'x' => $x, 'y' => $y ); // ARRAY( 'x', 'y' )
				break;
			case 'country':
				$parent = get_term( $this->term_id, TravelRoutesPlugin::$taxonomy );
				while ( $parent->parent != 0 ) {
					$term_id = intval( $parent->parent );
					$parent = get_term( $term_id, TravelRoutesPlugin::$taxonomy);
				}
				return new TravelLocation( $term_id ); // $location OBJECT
				break;
			default:
				// dates, details, code (...)
				return get_metadata('taxonomy', $this->term_id, $property, true);
		}
	}
	
	public static function locate( $lat, $lng ) {
		$locations = get_terms( TravelRoutesPlugin::$taxonomy, array(
			'get' => 'all',
			'fields' => 'ids'
		));
		foreach ($locations as $term_id) {
			$location = new TravelLocation( $term_id );
			if ( $location->parent->term_id !== 0 && number_format( $location->geocode['lat'], 2 ) == number_format( $lat, 2 ) && number_format( $location->geocode['lng'], 2 ) == number_format( $lng, 2 ) ) {
				return $location;
			}
		}
		return false;
	}
}

?>