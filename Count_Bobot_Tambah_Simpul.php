<?php
class Count_Bobot_Tambah_Simpul extends DistanceTo{

	public function Count_Bobot_Tambah_Simpul($index, $limit, $jArrCoordinates){		
		
		$bobot = 0;
		
		// CUMA DIJALANIN SEKALI, KALO $LIMIT = 1, MAKA 1-0 = 0
		// 0 == 0 (FIRST INDEX) or 136 == 136 (LAST INDEX)
		if($index == $limit){
			// get JSON coordinate
			$latlngs = $jArrCoordinates[0];
			
			$lat_0 = $latlngs[0];
			$lng_0 = $latlngs[1];

			// get coordinate again
			$latlngs1 = $jArrCoordinates[++$index];

			$lat_1 = $latlngs1[0];
			$lng_1 = $latlngs1[1];
			
			// simpan jarak
			// @extends
			$bobot += $this->distanceTo($lat_0, $lng_0, $lat_1, $lng_1);
		}else{
			for($i = 0; $i < 1; $i++){

				// get JSON coordinate
				$latlngs = $jArrCoordinates[$index];
				
				$lat_0 = $latlngs[0];
				$lng_0 = $latlngs[1];

				// get coordinate again
				$latlngs1 = $jArrCoordinates[++$index];

				$lat_1 = $latlngs1[0];
				$lng_1 = $latlngs1[1];

				// simpan jarak
				// @extends
				$bobot += $this->distanceTo($lat_0, $lng_0, $lat_1, $lng_1);

				if($index == $limit) break; //jika dah smpe ke tengah, break; misal 0-72
				else --$i;
			}
		}
		
		return $bobot;
	}
}
?>