(function ($) {

	'use strict';
	$.fn.travelRoutesMap = function () {
	
		/* Globals */
		var travel = $(this), resize = false;
		
		/* DOM */
		travel.map = $('.travel-map', travel);
		travel.routes = $('.travel-route', travel);
		travel.locations = $('.travel-location', travel);
		travel.locations.extrem = $('.first, .last', travel);
		travel.locations.intermediate = $('.travel-location', travel).not(travel.locations.extrem);
		travel.countries = $('.travel-country', travel.map);
		
		/* Map box */
		travel.map.width = travel.map.data('width');
		travel.map.height = travel.map.data('height');
		
		/* Initialize */
		travel.init = function () {
			travel.setBoxes();
			travel.setTriggers();
			travel.transform(travel.initial);
		}; // travel.init();
		
		/* Set scales */
		travel.setBoxes = function () {
			var globalBox = [];
			travel.initial = false;
			travel.map.box = { scale: (travel.width() / travel.map.width), x: 0, y: Math.round((travel.height() - travel.map.height) / 2 * 100) / 100 };
			if (travel.routes.length) {
				travel.routes.each(function (i, route) {
					route.post = $(route).data('post');
					// We define the route's box
					if ($(route).is('polyline')) { travel.setPolylineBox(route); }
					else { travel.setCircleBox(route); }
					// Points of all boxes
					globalBox.push(route.box.left + ',' + route.box.top, route.box.right + ',' + route.box.bottom);
					// If the current page is attached to this route, we set it as initial
					if (!travel.initial && $('body.postid-' +  route.post).length) { travel.initial = route; }
				});
				travel.locations.each(function (i, location) {
					location.term = $(location).data('term');
					// We define the location's box
					travel.setCircleBox(location);
					// If the current page is attached to this location, we set it as initial
					if (!travel.initial && $('body.term-' +  location.term).length) { travel.initial = location; }
				});
				travel.countries.each(function (i, country) {
					country.code = $(country).data('country');
					// Do we need to focus on this one ?
					if (permalinks.countries[country.code]) {
						// We define the country's box
						travel.setPathBox(country);
					}
					// If the current page is attached to this country, we set it as initial
					// WORK ON THAT ONE
					if (!travel.initial && $('body.country-' +  country.code).length) { travel.initial = country; }
				});
				// The global box, defined from all boxes points
				travel.map.box = travel.getPointsBox(globalBox.join(' '));
				travel.setBoxScale(travel.map.box);
			}
			// If no initial scale was set, let's focus on the global one
			if (!travel.initial) { travel.initial = travel.map; }
		}; // travel.setBoxes();
		
		/* Get box from points */
		travel.getPointsBox = function (points) {
			var box = {}, coords, i, j;
			points = points.split(' ');
			box.left = travel.map.width;
			box.top = travel.map.height;
			box.right = 0;
			box.bottom = 0;
			for (i in points) {
				coords = points[i].split(',');
				for (j in coords) { coords[j] = parseFloat(coords[j]); }
				if (coords[0] < box.left) { box.left = coords[0]; }
				if (coords[1] < box.top) { box.top = coords[1]; }
				if (coords[0] > box.right) { box.right = coords[0]; }
				if (coords[1] > box.bottom) { box.bottom = coords[1]; }
			}
			return box;
		} // travel.getPointsBox(points);
		
		/* Set a polyline box */
		travel.setPolylineBox = function (polyline) {
			polyline.box = travel.getPointsBox( $(polyline).attr('points') );
			travel.setBoxScale(polyline.box);
		}; // travel.getPolylineBox(polyline);
		
		/* Set a circle box */
		travel.setCircleBox = function (circle) {
			// We scale our location on its related country and get the lines points from the path
			var lines = travel.pathToLines($('#travel-country-' + $(circle).data('country')).attr('d')),
			cx = $(circle).attr('cx'), cy = $(circle).attr('cy'), i;
			for (i in lines) {
				circle.box = travel.getPointsBox(lines[i]);
				// Let's make sure that our point is located within this box ... Ugly hey ?!
				if (cx > circle.box.left - 1 && cy > circle.box.top - 1 && cx < circle.box.right + 1 && cy < circle.box.bottom + 1) {
					travel.setBoxScale(circle.box);
					return;
				}
			}
		}; // travel.getCircleBox(circle);
		
		/* Set a path box */
		travel.setPathBox = function (path) {
			var lines = travel.pathToLines($(path).attr('d')), i, box;
			path.box = {};
			path.box.scale = 20;
			for (i in lines) {
				box = travel.getPointsBox(lines[i]);
				// Let's focus on the largest box... May have to work on that one too
				travel.setBoxScale(box);
				if (path.box.scale >= box.scale) { path.box = box; }
			}
		}; // travel.getpathBox(path);
		
		/* Set the scale and translation of a box */
		travel.setBoxScale = function (box) {
			var width = box.right - box.left,
			height = box.bottom - box.top,
			xScale = Math.round((travel.width() * 0.8) / width * 100) / 100,
			yScale = Math.round((travel.height() * 0.8) / height * 100) / 100;
			box.scale = (xScale <= yScale) ? xScale : yScale;
			if (box.scale < 0.5) { box.scale = 0.5; }
			if (box.scale > 20) { box.scale = 20; }
			box.x = Math.round(parseFloat(((travel.width() / box.scale - (width)) / 2) - box.left) * 100) / 100;
			box.y = Math.round(parseFloat(((travel.height() / box.scale - (height)) / 2) - box.top) * 100) / 100;
		}; // travel.setBoxScale(box);
		
		/* Set the triggers for zooms and styles */
		travel.setTriggers = function () {
			travel.routes.each(function (i, route) {
				if (route !== travel.initial) {
					$('#post-' +  route.post).each(function (i, link) {
					// Over a post content
						$(link).hover(function () {
							travel.zoom(route);
						}, function () {
							travel.zoom(travel.initial);
						});
					});
					$('a[href$="' +  permalinks.routes[route.post] + '"]').not('#post-' + route.post + ' a').each(function (i, link) {
					// Over a post link
						$(link).hover(function () {
							travel.zoom(route);
						}, function () {
							travel.zoom(travel.initial);
						});
					});
					// Over a route on the map
					$(route, travel).hover(function () {
						travel.style(route);
					}, function () {
						travel.style(travel.initial);
					});
					// Click on a route on the map
					$(route, travel).click(function () {
						window.location = permalinks.site + permalinks.routes[route.post];
					});
				}
			});
			travel.locations.each(function (i, location) {
				if (location !== travel.initial) {
					// Over a term link
					$('a[href$="' +  permalinks.locations[location.term] + '"]').hover(function () {
						travel.zoom(location);
					}, function () {
						travel.zoom(travel.initial);
					});
					// Over a location on the map
					$(location, travel).hover(function () {
						travel.style(location);
					}, function () {
						travel.style(travel.initial);
					});
					// Click on a location on the map
					$(location, travel).click(function () {
						window.location = permalinks.site + permalinks.locations[location.term];
					});
				}
			});
			travel.countries.each(function (i, country) {
				// Over a term link
					
				if (permalinks.countries[country.code]) {
					$('a[href$="' +  permalinks.countries[country.code] + '"]').hover(function () {
						travel.zoom(country);
					}, function () {
						travel.zoom(travel.initial);
					});
				}
			});
			// Over a homepage link
			$('a[href="' +  permalinks.site + '/"]').hover(function () {
				travel.zoom(travel.map);
			}, function () {
				travel.zoom(travel.initial);
			});
		}; // travel.setTriggers();
		
		/* Modify the scale and translation */
		travel.transform = function (target) {
			$('g', travel).attr('transform', 'scale(' +  target.box.scale + ') translate (' +  target.box.x + ',' +  target.box.y + ')');
			travel.box = target.box;
			travel.style(target);
		}; // travel.transform(target);
		
		/* Animate the scale and translation */
		travel.zoom = function (target) {
			if (target.box !== travel.box) {
				$({
					scale: travel.box.scale,
					x: travel.box.x,
					y: travel.box.y
				}).animate({
					scale: target.box.scale,
					x: target.box.x,
					y: target.box.y
				}, {
					duration: 'normal',
					step: function () {
						$('g', travel).attr('transform', 'scale(' +  this.scale + ') translate (' +  this.x + ',' +  this.y + ')');
						travel.box = this;
						travel.style(target);
					}
				});
			}
		}; // travel.zoom(target);
		
		/* Style the polylines and circles */
		travel.style = function (target) {
			travel.locations.attr('r', travel.scaleRound(4)).css('stroke-width', travel.scaleRound(2));
			travel.locations.intermediate.hide();
			$('.dashed', travel).css('stroke-dasharray', travel.scaleRound(8));
			if (target == travel.map) {
				travel.routes.css({
					'opacity': 0.67,
					'stroke-width': travel.scaleRound(4)
				}).attr('r', travel.scaleRound(6));
				travel.locations.css('opacity', 0.67);
			} else {
				travel.routes.css({
					'opacity': 0.33,
					'stroke-width': travel.scaleRound(4)
				}).attr('r', travel.scaleRound(6));
				travel.locations.css('opacity', 0.33);
				$(target, travel).not(travel.countries).css({
					'opacity': 1,
					'stroke-width': travel.scaleRound(6)
				}).attr('r', travel.scaleRound(8)).show();
				if (target.box.scale == travel.box.scale) {
					$('.travel-location.route-' + target.post, travel).show();
				}
				$('.travel-location.route-' + target.post, travel).css('opacity', 1);
			}
		}; // travel.style(target);
		
		travel.scaleRound = function (n) {
			return Math.round(n / travel.box.scale * 100) / 100;
		};
		
		travel.pathToLines = function (path) {
			var paths = path.split(/z|Z/), points = [], i, j, k;
			for (i = 0; i < paths.length - 1; i++) {
				i = parseInt(i, 10);
				path = paths[i].split(/l|L/);
				path[0] = path[0].replace(/m|M/, '');
				for (j in path) {
					j = parseInt(j, 10);
					path[j] = path[j].split(',');
					for (k in path[j]) {
						k = parseInt(k, 10);
						path[j][k] = parseFloat(path[j][k]);
						if (j) { path[j][k] += path[j - 1][k]; }
					}
				}
				for (j in path) {
					j = parseInt(j, 10);
					path[j] = path[j].join(',');
				}
				points[i] = path.join(' ');
			}
			return points;
		};
		
		$(window).resize(function () {
			if (resize !== false) { clearTimeout(resize); }
			resize = setTimeout(function () {
				travel.setBoxes();
				travel.transform(travel.initial);
			}, 'normal');
		});
		
		
		travel.init();
	};
	
	$(document).ready(function () {
		$('.travel-routes-map').each(function () {
			$(this).travelRoutesMap();
		});
	});
	
})(jQuery);