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
				$x = ( $x - TravelMapVector::$bounding_box[0]['x'] ) / ( TravelMapVector::$bounding_box[1]['x'] - TravelMapVector::$bounding_box[0]['x'] ) * TravelMapVector::$map_width;
				$y = ( $y - TravelMapVector::$bounding_box[0]['y'] ) / ( TravelMapVector::$bounding_box[1]['y'] - TravelMapVector::$bounding_box[0]['y'] ) * TravelMapVector::$map_height;
				return array( 'x' => $x, 'y' => $y ); // ARRAY( 'x', 'y' )
				break;
			case 'country':
				foreach ( $this->details['address_components'] as $component ) {
					if ( $component['types'][0] == 'country') return $component['short_name'];
				}
				break;
			default:
				// dates, details (...)
				return get_metadata('taxonomy', $this->term_id, $property, true);
		}
	}
}

?>