jQuery.noConflict();
(function($) {
	$(function() {
		var position = new google.maps.LatLng(25, 69);
		var markerClusterer	= null;
		var map = new google.maps.Map(document.getElementById('dashboardEmergencyMap'), {
			zoom: 2,
			minZoom: 2,
			center: position,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		});

		var tableHeight = $('#dashboardTravelTable').height() - 30;

		var tableData = $("#dashboardTravelTable table").DataTable( {
			"scrollY":tableHeight,
			"scrollCollapse": true,
			"sorting":false,
			"paging":false,
			"filter":false,
			"info":false,
			"columns":[
				{"title": Joomla.JText._('COM_GTSAFETRAVEL_NUM'), "class": "text-center"},
				{"title": Joomla.JText._('COM_GTSAFETRAVEL_FIELD_COUNTRY_ID')},
				{"title": Joomla.JText._('COM_GTSAFETRAVEL_FIELD_CODE'), "class": "text-center"},
				{"title": Joomla.JText._('COM_GTSAFETRAVEL_NUM_VISIT'), "class": "text-center"}
			]
		});

		//$('form > .date').hide();
		$('.date_type').each(function(){
			showDate($(this));
		});

		$('.date_type').change(function(){
			showDate($(this));
		});

		function showDate(el) {
			var form = el.closest('form');
			var dateType = el.val();

			$('> .date', form).hide();
			$('.'+dateType, form).show();
		}

		processTravel($('#dashboardTravel'));
		$('#dashboardTravel').submit(function(e) {
			e.preventDefault();
			processTravel($(this));
		})

		$('#dashboardEmergencyRefresh').click(function(){
			resetMap();
		})

		function processTravel(el) {
			var travelParams = el.serialize();
			var travelURL = el.prop('action');

			$.ajax({
				url: travelURL,
				data: travelParams,
				dataType: 'json',
				method: 'post',
				cache: false
			}).done(function(result) {
				setFlot(result.data, result.countries);

				tableData.clear().draw();
				$.each(result.tableData, function(k, tdata) {
					tableData.row.add([k+1, tdata[1], tdata[2], tdata[0]]).draw(false);
				});
			});
		}


		processEmergency($('#dashboardEmergency'));
		$('#dashboardEmergency').submit(function(e) {
			e.preventDefault();
			processEmergency($(this));
		})


		function makeInfoWindowEvent(map, infowindow, contentString, marker) {
			google.maps.event.addListener(marker, 'click', function() {
				infowindow.setContent(contentString);
				infowindow.open(map, marker);
			});
		}

		function resetMap() {
			map.setCenter(position);
			map.setZoom(2);
		}

		function processEmergency(el) {
			var travelParams	= el.serialize();
			var travelURL		= el.prop('action');
			var imageUrl		= 'http://chart.apis.google.com/chart?cht=mm&chs=24x32&chco=FFFFFF,008CFF,000000&ext=.png';
			var infowindow		= new google.maps.InfoWindow();

			$.ajax({
				url: travelURL,
				data: travelParams,
				dataType: 'json',
				method: 'post',
				cache: false
			}).done(function(result) {
				if (markerClusterer) {
					markerClusterer.clearMarkers();
				}
				resetMap();
				var markers			= [];
				var markerImage = new google.maps.MarkerImage(imageUrl,
					new google.maps.Size(24, 32));

				$.each(result, function(k, item) {
					var latLng = new google.maps.LatLng(item.latitude, item.longitude)
					var marker = new google.maps.Marker({
						position: latLng,
						draggable: true,
						icon: markerImage
					});

					makeInfoWindowEvent(map, infowindow, item.content, marker);
					markers.push(marker);
				});

				markerClusterer = new MarkerClusterer(map, markers, {
					imagePath: 'images/markers/m'
				});
			});
		}

		function setFlot(data, countries) {
			var dataSet = [
				{data: data[0] || [], color: "#333"},
				{data: data[1] || [], color: "#27AE60"},
				{data: data[2] || [], color: "#8CB938"},
				{data: data[3] || [], color: "#F1C40F"},
				{data: data[4] || [], color: "#D97F1D"},
				{data: data[5] || [], color: "#C0392B"}
			];

			var options = {
				series: {
					bars: {
						show: true
					}
				},
				bars: {
					align: "center",
					barWidth: 0.6
				},
				xaxis: {
					axisLabelUseCanvas: false,
					ticks: countries,
					color: '#2c3e50',
					tickLength: 0
				},
				yaxis: {
					axisLabelUseCanvas: false,
					axisLabelPadding: 0,
					tickFormatter: function (v, axis) {
						return $.number(v, 0, ',', '.');
					},
					color: '#2c3e50'
				},
				legend: {
					show: false
				},
				grid: {
					hoverable: true,
					borderWidth: 2,
					backgroundColor: 'rgba(23, 43, 74, 0.1)',
					borderColor: '#2c3e50',
				}
			};

			$('#dashboardTravelChart > div').empty();
			$.plot($('#dashboardTravelChart > div'), dataSet, options);

			function showTooltip(x, y, contents) {
				$('<div id="tooltip">' + contents + '</div>').css( {
					position: 'absolute',
					display: 'none',
					top: y + 5,
					left: x + 5,
					border: '1px solid #333',
					padding: '2px 4px',
					'background-color': '#555',
					opacity: 0.80,
					zIndex: 200,
					color: '#fff',
					fontSize: '110%',
					borderRadius: '3px'
				}).appendTo('body').fadeIn('normal');
			}
			var previousPoint = null;
			$('#dashboardTravelChart > div').bind('plothover', function (event, pos, item) {
				if (item) {
					if (previousPoint != item.dataIndex) {
						previousPoint = item.dataIndex;

						$('#tooltip').remove();
						var x = item.datapoint[0],
							y = item.datapoint[1];
						var xaxis = item.series.xaxis;
						showTooltip(item.pageX, item.pageY, '<strong>' + countries[x-1][2] + '</strong><br/>' + $.number(y, 0, ',', '.'));
					}
				}
				else {
					$('#tooltip').fadeOut('slow', function() { $(this).remove(); });
					previousPoint = null;
				}
			});
		}
	});
})(jQuery);