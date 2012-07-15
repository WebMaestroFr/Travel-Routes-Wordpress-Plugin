(function($) {

	$.fn.travelRoutesMap = function() {
	
		var travel = $( this );
		travel.map = $( '.travel-map', travel );
		travel.routes = $( '.travel-route', travel );
		travel.locations = $( '.travel-location', travel );
		travel.locations.extrem = $( '.first, .last', travel );
		travel.locations.intermediate = $( '.travel-location', travel ).not( travel.locations.extrem ),
		notSingle = $( '.post' ).length > 1;
		
		travel.init = function() {
			travel.setScales();
			travel.setTriggers();
		}
		
		travel.setScales = function() {
			travel.default = false;
			if ( !travel.routes.length ) {
				travel.box = {
					scale: ( travel.width() / travel.data('width') ),
					x: 0,
					y: ( ( travel.height() - travel.data('height') ) / 2 )
				};
				travel.default = travel;
			} else {
				var boxes = new Array();
				travel.routes.each(function(i, route) {
					route.post = $( route ).attr( 'id' ).match( /\d+/ );
					if ( $( route ).is('polyline') ) {
						route.box = travel.getBox( $( route ).attr( 'points' ).split( ' ' ) );
					} else {
						route.box = travel.getLocationBox( route );
					}
					boxes.push( route.box.left+','+route.box.top, route.box.right+','+route.box.bottom );
					if ( !travel.default && $( 'body.postid-' + route.post ).length ) travel.default = route;
				});
				travel.locations.each(function(i, location) {
					location.term = $( location ).attr( 'id' ).match( /\d+/ );
					location.box = travel.getLocationBox( location );
					if ( !travel.default && $( 'body.term-' + location.term ).length ) travel.default = location;
				});
				travel.routes.box = travel.getBox( boxes );
				if ( !travel.default ) {
					travel.default = travel.routes;
				}
			}
			travel.transform( travel.default );
		}
		
		travel.getBox = function( points ) {
			var box = {}, coords, width, height, xScale, yScale;
			box.left = travel.map.data('width'), box.top = travel.map.data('height'), box.right = 0, box.bottom = 0,
			fontSize = parseFloat( travel.css( 'font-size' ) );
			for ( var i in points ) {
				coords = points[i].split( ',' );
				for ( var j in coords) {
					coords[j] = parseFloat(coords[j]);
				}
				if ( coords[0] < box.left ) box.left = coords[0];
				if ( coords[1] < box.top ) box.top = coords[1];
				if ( coords[0] > box.right ) box.right = coords[0];
				if ( coords[1] > box.bottom ) box.bottom = coords[1];
			}
			width = box.right - box.left;
			height = box.bottom - box.top;
			xScale = ( travel.width() * .8 ) / width;
			yScale = ( travel.height() * .8 ) / height;
			box.scale = ( xScale <= yScale ) ? xScale : yScale;
			if ( box.scale < .5 ) box.scale = .5;
			if ( box.scale > 20 ) box.scale = 20;
			box.x = parseFloat( ( ( travel.width() / box.scale - ( width ) ) / 2 ) - box.left );
			box.y = parseFloat( ( ( travel.height() / box.scale - ( height ) ) / 2 ) - box.top );
			return box;
		}
		
		travel.getLocationBox = function( location ) {
			var country = $( location ).attr( 'class' ).split( ' ' ), points, box,
			x = $( location ).attr( 'cx' ), y = $( location ).attr( 'cy' );
			for ( var i in country ) {
				if ( country[i].match( /^country-/ ) ) {
					points = pathToPoints( $( '#travel-'+country[i] ).attr( 'd' ) );
					for ( var j in points ) {
						box = travel.getBox( points[j] );
						if ( x > box.left-1 && y > box.top-1 && x < box.right+1 && y < box.bottom+1 ) return box;
					}
				}
			}
		}
		
		travel.setTriggers = function() {
			var location/*, scrollTrigger = .33 * $( window ).height()*/;
			travel.routes.each(function(i, route) {
				$( 'a[href$="' + permalinks['routes'][route.post] + '"]' ).each( function(i, link) {
					$( link ).hover( function() {
						travel.zoom( route );
					/*}, function() {
						travel.zoom( travel.default );*/
					});
				});
				$( route, travel ).hover( function() {
					travel.style( route );
				/*}, function() {
					travel.style( travel.default );*/
				});
				$( route, travel ).click( function () {
					window.location = permalinks['site'] + permalinks['routes'][route.post];
				});
				/*if ( notSingle && $( '#post-' + route.post ).length ) {
					route.top = $( '#post-' + route.post ).offset().top, route.bottom = route.top + $( '#post-' + route.post ).height();
					$( window ).scroll(function() {
						if ( travel.box != route.box && ( route.top - $( window ).scrollTop() < scrollTrigger ) && ( route.bottom - $( window ).scrollTop() > scrollTrigger ) ) {
							travel.zoom( route );
						}
					});
				}*/
			});
			travel.locations.each(function(i, location) {
				if (location != travel.default) {
					$( 'a[href$="' + permalinks['locations'][location.term] + '"]' ).hover( function() {
						travel.zoom( location );
					/*}, function() {
						travel.zoom( travel.default );*/
					});
				}
			});
			$( 'a[href="' + permalinks['site'] + '/"]' ).hover(
				function() {
					travel.zoom( travel.routes );
				/*}, function() {
					travel.zoom( travel.default );*/
				}
			);
		}
		
		travel.transform = function( routes ) {
			$( 'g', travel ).attr( 'transform', 'scale(' + routes.box.scale + ') translate (' + routes.box.x + ',' + routes.box.y + ')' );
			travel.box = routes.box;
			travel.style( routes );
		}
		
		travel.zoom = function( to ) {
			if ( to.box != travel.box ) {
				$({
					scale: travel.box.scale,
					x: travel.box.x,
					y: travel.box.y
				}).animate({
					scale: to.box.scale,
					x: to.box.x,
					y: to.box.y
				}, {
					duration: 'normal',
					step: function() {
						$( 'g', travel ).attr( 'transform', 'scale(' + this.scale + ') translate (' + this.x + ',' + this.y + ')' );
						travel.box = this;
						travel.style( to );
					}
				});
			}
		}
		
		travel.style = function( routes ) {
			travel.locations.attr( 'r', ( 4 / travel.box.scale ) ).css( 'stroke-width', ( 2 / travel.box.scale ) );
			travel.locations.intermediate.hide();
			$( '.dashed', travel ).css( 'stroke-dasharray', ( 8 / travel.box.scale) )
			if ( routes.length == travel.routes.length ) {
				travel.routes.css({
					'opacity': .67,
					'stroke-width': ( 4 / travel.box.scale )
				}).attr( 'r', ( 6 / travel.box.scale ) );
				travel.locations.css( 'opacity', .67 );
			} else {
				travel.routes.css({
					'opacity': .33,
					'stroke-width': ( 4 / travel.box.scale )
				}).attr( 'r', ( 6 / travel.box.scale ) );
				travel.locations.css( 'opacity', .33 );
				/*if ( travel.box.scale == routes.box.scale ) {*/
					$( routes, travel ).css({
						'opacity': 1,
						'stroke-width': ( 6 / travel.box.scale )
					}).attr( 'r', ( 8 / travel.box.scale ) ).show();
					$( '.travel-location.route-'+routes.post, travel ).show().css( 'opacity', 1 );
				/*}*/
			}
		}
		
		travel.init();
		
		var resize = false;
		$( window ).resize( function() {
			if ( resize !== false ) clearTimeout( resize );
			resize = setTimeout( travel.setScales, 'slow' );
		});
	}
	
	$(document).ready(function(){
		$( '.travel-routes-map' ).each( function() {
			$( this ).travelRoutesMap();
		});
	});

})(jQuery);
	
function pathToPoints( path ) {
	var paths = path.split( /z|Z/ ), points = new Array();
	for ( var i = 0; i < paths.length-1; i++ ) {
		path = paths[i].split( /l|L/ );
		path[0] = path[0].replace( /m|M/, '' );
		for ( var j in path ) {
			path[j] = path[j].split( ',' );
			for ( var k in path[j] ) {
				path[j][k] = parseFloat( path[j][k] );
				if ( j != 0 ) {
					path[j][k] += path[j-1][k];
				}
			}
		}
		for ( var j in path ) {
			path[j] = path[j].join( ',' );
		}
		// points[i] = path.join( ' ' );
		points[i] = path;
	}
	return points;
}