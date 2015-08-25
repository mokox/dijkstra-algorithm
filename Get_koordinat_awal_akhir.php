<?php
class Get_koordinat_awal_akhir extends DistanceTo{

	// koneksi DB
	public $koneksi = "";
	
	/**
	* before Action
	*/
	function __construct(){
		$k = new Koneksi();
		$this->koneksi = $k->connect();
	}	
	
	/**
	 * @FUNGSI
	 *  MENYELEKSI SIMPUL YG AKAN DIKERJAKAN
	 *  jika ada simpul 1-0 dan 0-1 maka yg dikerjakan hanya 1-0 ( karena koordinat 1-0 sama dengan 0-1 (koordinat hanya dibalik) )
	 * @PARAMETER
	 *   latx : latitude user atau SMK
	 *   lngx : longitude user atau destination
	 *   context : MainActivity context
	 * @RETURN
	 *   JSON (index coordinates, nodes0, nodes1)
	 */	
	public function Get_simpul($latx, $lngx, $id_buang)
	{
		// your coordinate
		$user_lat = $latx;
		$user_lng = $lngx;

		// TAMPUNG NODE DARI FIELD SIMPUL_AWAL DAN TUJUAN DI TABEL GRAPH, TRS DIGABUNG; contoh 1,0 DAN MASUKKAN KE ARRAY
		$barisDobel 			= array();
		$indexBarisYgDikerjakan = array();

		// filter simpul yg akan dikerjakan
		$select = mysqli_query($this->koneksi, "SELECT * FROM graph where id not in($id_buang) and simpul_awal != '' and simpul_tujuan != '' and jalur != '' and bobot != ''");

		// AMBIL SIMPUL DARI FIELD SIMPUL_AWAL DAN SIMPUL_TUJUAN DI TABEL GRAPH, TRS DIGABUNG; contoh 1,0 TRS MASUKKAN KE ARRAY		
		// LOOPING DI BAWAH INI UNTUK MEMERIKSA BARIS DOBEL {SIMPULNYA YG DOBEL} 1,0 -> 0,1 {1,0 DIHITUNG TAPI 0,1 GAK DIHITUNG}
		$i = 0;
		while($field = mysqli_fetch_array($select, MYSQLI_ASSOC))
		{
			// node dari field simpul_awal
			$fieldSimpulAwal = $field['simpul_awal'];
			
			// node dari field simpul_akhir
			$fieldSimpulTujuan = $field['simpul_tujuan'];
			
			$gabungSimpul = $fieldSimpulAwal . "," . $fieldSimpulTujuan;			
			$gabung_balikSimpul = $fieldSimpulTujuan . "," . $fieldSimpulAwal;

			// SELEKSI RUAS YANG DOBEL; CONTOH : 1,0 == 0,1
			// PILIH SALAH SATU, MISAL : 1,0
			if(empty($barisDobel))
			{
				array_push($barisDobel, $gabung_balikSimpul);
				
				// field id pada tabel graph
				array_push($indexBarisYgDikerjakan, $field['id']);
			}else
			{
				if(!in_array($gabungSimpul, $barisDobel))
				{	
					array_push($barisDobel, $gabung_balikSimpul);
					
					// field id pada tabel graph
					array_push($indexBarisYgDikerjakan, $field['id']);
				}
			}
		}
		
		// QUERY BARIS YANG GAK DOBEL
		$id_where_in = implode(',', $indexBarisYgDikerjakan);//echo $id_where_in;		
		$selectRow = mysqli_query($this->koneksi, "SELECT * FROM graph where id in($id_where_in)");
		
		// CARI JARAK
		// LOOPING SEMUA RECORD
		$obj = new stdClass();
		for($index_obj = 0; $kolom = mysqli_fetch_array($selectRow, MYSQLI_ASSOC); $index_obj++){

			// VARIABEL BUAT CARI 1 JARAK DALAM 1 RECORD (1 record isinya banyak koordinat)			
			// simpan jarak user ke koordinat simpul dalam meter
			$jarakUserKeKoordinatSimpul = array();

			// dapatkan koordinat Lat,Lng dari field koordinat (3)
			$json = $kolom['jalur'];

			// decode JSON	coordinate
			$jsonOneRoute = json_decode($json, true);

			// data json
			$dataNode		= $jsonOneRoute['nodes'][0];
			$nodeSplit		= explode('-', $dataNode);
			$node0			= $nodeSplit[0];
			$node1			= $nodeSplit[1];
			$dataBobot		= $jsonOneRoute['distance_metres'][0];
			$countCoordinate= (count($jsonOneRoute['coordinates']) - 1); // dikurang 1 karena buat index array
			
			// hitung jarak user ke koordinat angkutan umum
			foreach($jsonOneRoute['coordinates'] as $coordinate){
				$json_lat 	= $coordinate[0];
				$json_lng 	= $coordinate[1];
				
				$jarak 		= $this->distanceTo($user_lat, $user_lng, $json_lat, $json_lng);				
				array_push($jarakUserKeKoordinatSimpul, $jarak);
			}

			// CARI bobot yg paling kecil
			$index_koordinatSimpul = 0;
			for($m = 0; $m < count($jarakUserKeKoordinatSimpul); $m++)
			{				
				if($jarakUserKeKoordinatSimpul[$m] <= $jarakUserKeKoordinatSimpul[0])
				{
					$jarakUserKeKoordinatSimpul[0] = $jarakUserKeKoordinatSimpul[$m];
					
					// index array dari value yg terkecil
					$index_koordinatSimpul = $m;
				}			
			}

			// field id dari table graph
			$row_id = $kolom['id'];
			
			// masukkan index koordinat array, bobot terkecil dan jumlah koordinat ke JSON
			$list = array();
			$list['row_id'] 		= (int)$row_id;
			$list['index'] 			= $index_koordinatSimpul;
			$list['bobot'] 			= $jarakUserKeKoordinatSimpul[0];
			$list['nodes']			= $dataNode;
			$list['count_koordinat']= $countCoordinate;

			// Create json
			// example output : 
			// {"0" : [{"row_id":17, "index":"7", "bobot":"427.66", "count_koordinat":"15", "nodes":"0-1"}]}
			$obj->$index_obj = [$list];
			
		}//end looping baris DB

		//echo "<pre>";
		//print_r($obj);
		//echo "</pre>";
		$x = 0;
		$y = 0;
		$stdCount = count((array)$obj);
		
		// cari bobot terkecil dari JSON
		for($s = 0; $s < $stdCount; $s++){
			
			if($s == 0){				
				// std Object
				$x 					= $obj->{$s}[0]['bobot'];
				$rowId 				= $obj->{$s}[0]['row_id'];
				$indexCoordinate 	= $obj->{$s}[0]['index'];
				$countCoordinate 	= $obj->{$s}[0]['count_koordinat'];
				$nodes			 	= $obj->{$s}[0]['nodes'];				
			}else
			{
				$y 	= $obj->{$s}[0]['bobot'];				
				// dapatkan value terkecil (bobot)
				if($y <= $x){
					$x = $y;					
					// std Object
					$rowId 				= $obj->{$s}[0]['row_id'];
					$indexCoordinate 	= $obj->{$s}[0]['index'];
					$countCoordinate 	= $obj->{$s}[0]['count_koordinat'];
					$nodes			 	= $obj->{$s}[0]['nodes'];				
				}
			}	
		}	
		
		
		// nodes : 0-1
		$exp_nodes 				= explode('-', $nodes);		
		$field_simpul_awal 		= $exp_nodes[0];
		$field_simpul_tujuan 	= $exp_nodes[1];

		// Koordinat yg didapat di awal atau diakhir, maka gak perlu nambah simpul
		if($indexCoordinate == 0 || $indexCoordinate == $countCoordinate){
			
			//tentukan simpul awal atau akhir yg dekat dgn posisi user
			if($indexCoordinate == 0)
			{
				// nodes di field simpul_awal
				$fix_simpul_awal = $field_simpul_awal; 
			}else if($indexCoordinate == $countCoordinate)
			{
				// nodes di field simpul_akhir
				$fix_simpul_awal = $field_simpul_tujuan;
			}
			
			$jadi_json['status'] = "tidak_tambah_simpul";
		}
		//Koordinat yang didapat berada ditengah2 simpul 0 - 1 (misal)
		else
		{
			// cari simpul dobel, simpulnya dibalik
			$select	= "SELECT count(id) as jum_id FROM graph where simpul_awal = ".$field_simpul_tujuan." and simpul_tujuan = ".$field_simpul_awal;
			$query	= mysqli_query($this->koneksi, $select);
			$dobel	= mysqli_fetch_array($query, MYSQLI_ASSOC);

			//ada simpul yg dobel (1,0) dan (0,1)
			if($dobel['jum_id'] == 1){
				$jadi_json['status'] = "tambah_simpul_double";
			}
			//gak dobel, hanya (1,0)
			else if($dobel['jum_id'] == 0){
				$jadi_json['status'] = "tambah_simpul_single";
			}
		}
		
		// get ID berdasarkan simpul yg dobel
		$selectx= "SELECT count(id) as simpul_dobel, id FROM graph where simpul_awal = ".$field_simpul_tujuan." and simpul_tujuan = ".$field_simpul_awal;
		$queryx	= mysqli_query($this->koneksi, $selectx);
		$dobelx	= mysqli_fetch_array($queryx, MYSQLI_ASSOC);
		// jika ada IDnya dan ada simpul baru ditengah2 jalur
		if($dobelx['simpul_dobel'] == 1 && ($jadi_json['status'] == 'tambah_simpul_single' || $jadi_json['status'] == 'tambah_simpul_double')){
			$rowId .= ','.$dobelx['id'];
		}else{
			// tidak ada id yg dibuang
			$rowId = 0;
		}
		
		// JSON
		$jadi_json['row_id'] 				= $rowId;
		$jadi_json['node_simpul_awal0'] 	= $field_simpul_awal;
		$jadi_json['node_simpul_awal1'] 	= $field_simpul_tujuan;
		$jadi_json['index_coordinate_json'] = $indexCoordinate;

		
		return json_encode($jadi_json);
	}//public
}
?>