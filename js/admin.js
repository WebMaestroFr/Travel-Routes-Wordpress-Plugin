(function($) {

	$.fn.postRouteForm = function() {
	
		var formTable = this;
		
		formTable.settings = {
			datepicker: {
				dateFormat: 'yy-mm-dd',
				autoSize: true,
				changeYear: true
			},
			map: {
				center: new google.maps.LatLng(0, 0),
				zoom: 1,
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scrollwheel: false
			},
			route: {
				strokeColor: $('#route-color').val(),
				strokeOpacity: 1.0,
				strokeWeight: 2
			}
		}
		
		formTable.init = function() {
			formTable.settings.route.map = new google.maps.Map(document.getElementById('route-map'), formTable.settings.map);
			formTable.route = new google.maps.Polyline(formTable.settings.route);
			$('.add-location', formTable).click(formTable.addRow);
			$('.sort-dates', formTable).click(formTable.sortDates);
			if ($('tr.location', formTable).length) {
				$('.autocomplete', formTable).each(formTable.setAutoComplete);
				$('.datepicker', formTable).each(formTable.setDatePicker);
				$('.delete-location', formTable).click(formTable.deleteRow);
				formTable.updateRoute();
			} else formTable.addRow();
			$('#route-locations tbody', formTable).sortable({
				axis: 'y',
				update: formTable.updateRoute
			});
			$('#route-colorpicker').hide().farbtastic('#route-color');
			$('#route-color').focus(function() {
				$('#route-colorpicker').show('fast');
			});
			$('#route-color').focusout(function() {
				formTable.route.setOptions({strokeColor: $(this).val()})
				$('#route-colorpicker').hide('fast');
			});
		}

		formTable.setAutoComplete = function() {
			var place;
			var row = $(this).parents('tr.location');
			var autocomplete = new google.maps.places.Autocomplete(this);
			var marker = new google.maps.Marker({map: formTable.settings.route.map, position: formTable.getPosition(row.index())});
			row.data('marker', marker);
			google.maps.event.addListener(autocomplete, 'place_changed', function() {
				place = autocomplete.getPlace();
				marker.setPosition(place.geometry.location);
				$('input.latitude', row).val(place.geometry.location.lat());
				$('input.longitude', row).val(place.geometry.location.lng());
				$('small.parents', row).remove();
				formTable.updateRoute();
			});
		}

		formTable.setDatePicker = function() {
			var datepicker = $(this).datepicker(formTable.settings.datepicker);
		}
		
		formTable.addRow = function() {
			var row = $('<tr class="location"><td><input type="hidden" name="route_location_latitude[]" value="" class="latitude" /><input type="hidden" name="route_location_longitude[]" value="" class="longitude" /></td><td></td><td></td></tr>').hide().appendTo($('#route-locations tbody', formTable));
			var autocomplete = $('<input name="route_location_place[]" type="text" value="" class="autocomplete" />').appendTo($('td', row).eq(0));
			var datepicker = $('<input name="route_location_date[]" type="text" value="" class="datepicker" />').appendTo($('td', row).eq(1));
			var deletebutton = $('<input type="button" class="button delete-location" value="Delete" />').appendTo($('td', row).eq(2));
			autocomplete.each(formTable.setAutoComplete);
			datepicker.each(formTable.setDatePicker);
			deletebutton.click(formTable.deleteRow);
			row.show('fast', formTable.updateRoute);
		}
	
		formTable.deleteRow = function() {
			$(this).parents('tr.location').animate({backgroundColor: '#dc143c'}, 'fast').hide('fast', function() {
				$(this).data('marker').setMap(null);
				$(this).remove();
				formTable.updateRoute();
			});
		};
	
		formTable.updateRoute = function() {
			var position;
			var empty = false;
			var positions = new Array();
			var zoom = new google.maps.LatLngBounds();
			$('tr.location', formTable).each(function(i) {
				if (position = formTable.getPosition(i)) {
					positions.push(position);
					zoom.extend(position);
				} else {
					empty = true;
				}
			});
			if (positions.length) formTable.settings.route.map.fitBounds(zoom);
			formTable.route.setPath(positions);
			if (!empty) {
				$('.add-location', formTable).attr('disabled', false).animate({opacity: 1});
			} else {
				$('.add-location', formTable).attr('disabled', true).animate({opacity: .5});
			}
			if ($('tr.location', formTable).length > 1) {
				$('.delete-location', formTable).attr('disabled', false).animate({opacity: 1});
			} else {
				$('.delete-location', formTable).attr('disabled', true).animate({opacity: .5});
			}
		};
		
		formTable.sortDates = function() {
			var rows = $('tr.location', formTable).get();
			rows.sort(function(a, b) {
				var dateA = $('input.datepicker', a).val();
				var dateB = $('input.datepicker', b).val();
				return (dateA < dateB) ? -1 : (dateA > dateB) ? 1 : 0;
			});
			$.each(rows, function(i, row) {
				$('#route-locations tbody', formTable).append(row);
			});
			formTable.updateRoute();
		};
		
		formTable.getPosition = function(i) {
			var latitude, longitude;
			latitude = $('input.latitude', formTable).eq(i).val();
			longitude = $('input.longitude', formTable).eq(i).val();
			if (!latitude || !longitude) return null;
			return new google.maps.LatLng(parseFloat(latitude),parseFloat(longitude));
		}
		
		formTable.init();
	}
	
	$(document).ready(function(){
		$('#post-travel-route').postRouteForm();
	});

})(jQuery);