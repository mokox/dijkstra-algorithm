<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
<meta charset="utf-8">
<style>
html, body, #map-canvas {
	width: 90%;
	height: 90%;
	//margin: 0px;
	//padding: 0px
}
a{
	cursor: pointer;
	text-decoration: underline;
}
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>	
<script>
// map
var poly = '';
var map;
var markeruser = '';
var markerdestination = '';

// boolean
var __global_user		 = false;
var __global_destination = false;
var update_timeout;

// temporary list angkot
var temp_list_angkot = [];

/**
* INITIALIZE GOOGLE MAP
*/
function initialize() {	
	/* setup map */
	var mapOptions = {
		zoom: 13,
		center: new google.maps.LatLng(-6.2858667, 106.8719382)
	};
	map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
  
	/* create marker and line by click */
	google.maps.event.addListener(map, 'click', function(event) 
	{	
		icons = 'http://latcoding.com/domains/dijkstra.latcoding.com/imgs/user_min.png';
		var location = event.latLng;	

		update_timeout = setTimeout(function()
		{
			if(__global_user == false){
				markeruser = new google.maps.Marker({
					position: location,
					map: map,
					icon: icons,
					draggable: true,
					title: 'test drag',
				});
				
				// update 
				__global_user = true;
			}else{
				markeruser.setPosition(location);
			}

		}, 200); 

	});	

	// handle click and dblclick same time
	google.maps.event.addListener(map, 'dblclick', function(event) {       
		clearTimeout(update_timeout);
	});	
}

/** 
* PILIH DESTINATION (SEKOLAH) VIA <SELECT>
*/
function choose_destination(value){
	// teks option
	var teks = $("#select_tujuan option:selected").text();
	
	// -- PILIH -- dipilih
	if(value == 'pilih') return false;
	
	// reset polyline
	if(poly != '') poly.setMap(null);
	
	// RESET ANGKOT SEBELUMNYA
	$(temp_list_angkot).each(function(w, x){
		// x = marker0, marker1 dst
		window[x].setMap(null);
	});			
	
	var location = JSON.parse(value);
	icons = 'http://latcoding.com/domains/dijkstra.latcoding.com/imgs/school_24.png';
	
	if(__global_destination == false){
		markerdestination = new google.maps.Marker({
			position: location,
			map: map,
			icon: icons,
			draggable: false,
			title: 'TUJUAN : ' + teks,
		});
		
		__global_destination = true;
	}else{
		markerdestination.setPosition(location);
		markerdestination.setTitle('TUJUAN : ' + teks);
	}
}

/**
* GET JSON DIJSKTRA VIA AJAX
*/
function send_dijkstra(){
	
	if(markeruser == '' || markerdestination == ''){
		alert('Isi dulu koordinat user & tujuan');
		return false;
	}
	
	console.log(markeruser.position.lat());
	console.log(markeruser.position.lng());
	now_koord_user 			= '{"lat": ' + markeruser.position.lat() + ', "lng": ' + markeruser.position.lng() + '}';
	now_koord_destination 	= '{"lat": ' + markerdestination.position.lat() + ', "lng": ' + markerdestination.position.lng() + '}';

	// loading
	$('#run_dijkstra').hide();
	$('#loading').show();
	
	$.ajax({
		method:"POST",
		url : "Main.php",
		data: {koord_user: now_koord_user, koord_destination: now_koord_destination},
		success:function(response){
			
			// remove loading
			$('#run_dijkstra').show();
			$('#loading').hide();
						
			var json = JSON.parse(response);
			console.log(response);
			
			// RESET POLYLINE
			if(poly != '') poly.setMap(null);
			
			// RESET ANGKOT SEBELUMNYA
			$(temp_list_angkot).each(function(w, x){
				// x = marker0, marker1 dst
				window[x].setMap(null);
			});

			// ERROR ALGORITMA DIJKSTRA
			if(json.hasOwnProperty("error")) alert(json['error']['teks']);
			
			// GAMBAR JALUR SHORTEST PATH
			/* setup polyline */
			var polyOptions = {				
				/*path: [
				{"lat": 37.772, "lng": -122.214},
				{"lat": 21.291, "lng": -157.821},
				{"lat": -18.142, "lng": 178.431},
				{"lat": -27.467, "lng": 153.027}],
				*/
				path: json['jalur_shortest_path'],
				geodesic: true,
				strokeColor: 'rgb(20, 120, 218)',
				strokeOpacity: 1.0,
				strokeWeight: 2,
			};			
			poly = new google.maps.Polyline(polyOptions);
			poly.setMap(map);
			
			// GAMBAR KOORDINAT ANGKOT
			$(json['angkot']).each(function(i, v)
			{
				// no_angkot
				no_angkot = JSON.stringify(v['no_angkot']);
				window['infowindow'+i] = new google.maps.InfoWindow({
					content: '<div>'+ no_angkot +'</div>'
				});
				
				// koordinat angkot
				koordinat_angkot = v['koordinat_angkot'];
				window['marker'+i] = new google.maps.Marker({
					position: koordinat_angkot,
					map: map,
					title: 'title',
					icon: 'http://latcoding.com/free_download/implementasi_dijkstra_di_android/car.png'
				});
				
				// popup
				window['marker'+i].addListener('click', function() {
					window['infowindow'+i].open(map, window['marker'+i]);
				});
				
				// temporary list angkot
				temp_list_angkot[i] = 'marker'+i;
			});
		},
		error:function(er){
			alert('error: '+er);
			
			// remove loading
			$('#run_dijkstra').show();
			$('#loading').hide();
		}
	});	
}

/* load google maps v3 */
google.maps.event.addDomListener(window, 'load', initialize);
</script>

<?php
include "Main.php";

// koneksi
$m = new Main();
$koneksi = $m->koneksi;

// query
$sql 	= "SELECT * FROM sekolah";
$query 	= mysqli_query($koneksi, $sql);

// select option
echo 'TUJUAN : <select id="select_tujuan" onchange="choose_destination(this.value)">';
echo '<option value="pilih">-- PILIH --</option>';
	while($fetch = mysqli_fetch_array($query, MYSQLI_ASSOC))
	{
		$koordinat 		= $fetch['koordinat'];
		$exp_koordinat 	= explode(',', $koordinat);
		$json_koordinat	= '{"lat": '.$exp_koordinat[0].', "lng": '.$exp_koordinat[1].'}';
		
		echo "<option value='$json_koordinat'>$fetch[tujuan]</option>";
	}
echo '</select>';
?>
<span><button onclick="send_dijkstra()" id='run_dijkstra'>RUN</button><span id='loading' style='display:none'>membuat rute ..</span></span>
<div id="map-canvas" style="float:left;"></div>
<div id='DEBUG'></div>	