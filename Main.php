<?php
//# Call DB in GraphToArray.php
include "Koneksi.php";
include "DistanceTo.php";

include "Get_koordinat_awal_akhir.php";
include "GraphToArray.php";
include "Tambah_simpul.php";
include "Dijkstra.php";
include "Angkot.php";

class Main extends GraphToArray
{
	public $koneksi;
	public $graph;
	public $id_buang;
	
	public $maxRow0;
	public $maxRow1;
	
	public $old_simpul_awal;
	public $old_simpul_akhir;
	public $simpul_awal;
	public $simpul_akhir;
	
	public function __construct()
	{
		// GLOBAL KONEKSI
		$koneksi = new Koneksi();
		$this->koneksi = $koneksi->connect();
		
		// DELETE TEMPORARY ID
		$k = $koneksi->connect();
		mysqli_query($k, "DELETE FROM graph where temp = 'Y'");
		
		// MEMPREDIKSI 2 SIMPUL BARU
		$this->maxRowDB();
		
		// GET ARRAY GRAPH[..][..]
		$graph 			= new GraphToArray();
		$graphArray 	= $graph->graphArray();
		$this->graph 	= $graphArray;	
		
		// id_old yg gak dikerjakan pas Get_simpul() didalam fungsi getSimpulAwalAkhirJalur()
		$this->id_buang = 0;

	}
	
	public function core($lat0, $lng0, $lat1, $lng1)
	{
		$this->getSimpulAwalAkhirJalur($lat0, $lng0, 'awal', /*tambahan-->*/ $this->id_buang); 
		$this->getSimpulAwalAkhirJalur($lat1, $lng1, 'akhir', /*tambahan-->*/ $this->id_buang);
		
		// ALGORITMA DIJKSTRA
		// GET SHORTEST PATH ( ex : 1->0->4 )		
		$dijkstra 	= new Dijkstra();
		// {"status":"error","error":"simpul_input_tidak_ditemukan","teks":"could not find the input : 10 or ","content":""}
		// {"status":"error","error":"lokasi_anda_sudah_dekat","teks":"Lokasi Anda Sudah Dekat","content":""}
		// {"status":"success","success":"generate_jalur_terpendek","content":"10->3->2->11"}

		$json 		= $dijkstra->jalurTerpendek($this->graph, $this->simpul_awal, $this->simpul_akhir);
		$decode 	= json_decode($json, true);	
		$status		= $decode['status'];
		$content	= $decode['content'];

		// ERROR ALGORITMA DIJKSTRA 
		if($status == 'error'){
			//echo 'ERROR :: [' . $decode['error'] .']';			
			$jsonPolyline = json_encode(['jalur_shortest_path'=>[], 'error'=>$decode]);
			return $jsonPolyline;
		}
		// ALGORITMA DIJKSTRA LANCAR JAYA
		else{
			$jsonPolyline = $this->drawRoute($content);
			return $jsonPolyline;
		}
	}
	
	public function getSimpulAwalAkhirJalur($lat, $lng, $kerjain, $id_buang)
	{
		// DAPATKAN KOORDINAT ANGKUTAN UMUM TERDEKAT DARI POSISI KITA / POSISI TUJUAN
		$get 		= new Get_koordinat_awal_akhir();
		$jsonPosisi = $get->Get_simpul($lat, $lng, $id_buang);		
		
		// DECODE JSON
		// {"status":"tidak_tambah_simpul","node_simpul_awal0":"5","node_simpul_awal1":"6","index_coordinate_json":0}
		// {"status":"tambah_simpul_double","node_simpul_awal0":"5","node_simpul_awal1":"8","index_coordinate_json":3}
		// {"status":"tidak_tambah_simpul","node_simpul_awal0":"10","node_simpul_awal1":"3","index_coordinate_json":0}
		$j = json_decode($jsonPosisi, true);
		$status 			= $j['status'];
		$this->id_buang		= $j['row_id']; // id_lama yg gak dikerjakan pas getSimpulAwalAkhirJalur('akhir') jika ada simpul baru : #4->5 --> 4->6->5
		$node_simpul_awal0 	= $j['node_simpul_awal0'];
		$node_simpul_awal1 	= $j['node_simpul_awal1'];
		$index_coordinate 	= $j['index_coordinate_json'];
		
		// CEK JALUR ANGKUTAN UMUM
		// tidak perlu tambah simpul
		if( $status == 'tidak_tambah_simpul' )
		{
			// tentukan simpul awal atau akhir yg dekat dgn posisi user
			($index_coordinate == 0) ? /*awal*/$fix_simpul_awal = $node_simpul_awal0 : /*akhir*/$fix_simpul_awal = $node_simpul_awal1;
			
			// kerjain simpul awal
			if($kerjain == "awal"){	
				
				// return
				$this->old_simpul_awal 	= $node_simpul_awal0 . "-" . $node_simpul_awal1;
				$this->simpul_awal 		= $fix_simpul_awal; // misal 0
			}
			// kerjain simpul akhir
			else{
				
				// return
				$this->old_simpul_akhir = $node_simpul_awal0 . "-" . $node_simpul_awal1;
				$this->simpul_akhir 	= $fix_simpul_awal; // misal 0
			}
		}
		// perlu tambah simpul karena double
		else if( $status == 'tambah_simpul_double' )
		{			
			// kerjain simpul awal
			if($kerjain == "awal"){				
		
				// cari simpul (5,4) dan (4-5) di Tambah_simpul.php
				$tb = new Tambah_simpul();
				//echo $node_simpul_awal0 .', '.$node_simpul_awal1 .','. $index_coordinate;
				$jadi_json = $tb->dobelSimpul($node_simpul_awal0, $node_simpul_awal1, $index_coordinate, $this->graph);
				
				// decode json
				$d = json_decode($jadi_json, true);

				// return
				$this->old_simpul_awal 	= $d['simpul_lama'];
				$this->simpul_awal 		= $d['simpul_baru']; // misal 6
				$this->graph 			= json_decode($d['graph'], true); // graph[][]

			}else{
				
				// cari simpul (5,4) dan (4-5) di Tambah_simpul.php
				$tb = new Tambah_simpul();
				$jadi_json = $tb->dobelSimpul($node_simpul_awal0, $node_simpul_awal1, $index_coordinate, $this->graph);
				
				// decode json
				$d = json_decode($jadi_json, true);
				
				// return
				$this->old_simpul_akhir = $d['simpul_lama'];
				$this->simpul_akhir 	= $d['simpul_baru']; // misal 4			
				$this->graph 			= json_decode($d['graph'], true); // graph[][]
						
			}
		}
		// perlu tambah simpul karena single
		else if( $status == 'tambah_simpul_single' )
		{
			if($kerjain == "awal"){

				// cari simpul (5,4) dan (4-5) di Tambah_simpul.php
				$tb = new Tambah_simpul();
				$jadi_json = $tb->singleSimpul($node_simpul_awal0, $node_simpul_awal1, $index_coordinate, $this->graph);
				
				// decode json
				$d = json_decode($jadi_json, true);
				
				// return
				$this->old_simpul_awal 	= $d['simpul_lama'];
				$this->simpul_awal 		= $d['simpul_baru']; // misal 6
				$this->graph 			= json_decode($d['graph'], true); // graph[][]
				
			}else{
				
				// cari simpul (5,4) di Tambah_simpul.php
				$tb = new Tambah_simpul();
				$jadi_json = $tb->singleSimpul($node_simpul_awal0, $node_simpul_awal1, $index_coordinate, $this->graph);
				
				// decode json
				$d = json_decode($jadi_json, true);
				
				// return
				$this->old_simpul_akhir = $d['simpul_lama'];
				$this->simpul_akhir 	= $d['simpul_baru']; // misal 4			
				$this->graph 			= json_decode($d['graph'], true); // graph[][]	
			}			
		}		
	}
	
	/**
	* GAMBAR JALUR SHORTEST PATH & ANGKOTNYA
	* @PARAM $shortest_path string; misal 1->9->0
	* @RETURN $semua_latlng json; misal [{'lat':6, 'lng':10},{dst..}]
	*/
	public function drawRoute($shortest_path)
	{
		$exp_shortest_path = explode("->", $shortest_path);
		$start = 0;
		$semua_latlng = array();

		for($i = 0; $i < (count($exp_shortest_path)-1); $i++){
			
			$select = "SELECT jalur FROM graph where simpul_awal =" . $exp_shortest_path[$start] . " and simpul_tujuan =" . $exp_shortest_path[(++$start)];
			$query  = mysqli_query($this->koneksi, $select);			
			$fetch	= mysqli_fetch_array($query, MYSQLI_ASSOC);
			
			$json = json_decode($fetch['jalur'], true);
			$koordinat = $json['coordinates'];
			
			// DAPATKAN KOORDINAT LAT,LNG DARI FIELD JALUR
			// get coordinate JSON
			for($w = 0; $w < count($koordinat); $w++){
				
				$latlngs = $koordinat[$w];
				$lats = $latlngs[0];
				$lngs = $latlngs[1];
				
				$lat_lng['lat'] = $lats;
				$lat_lng['lng'] = $lngs;
				
				array_push($semua_latlng, $lat_lng);
			}
		}
		
		// CARI ANGKOT YANG LEWAT JALUR SHORTEST PATH
		$a = new Angkot();
		// [{"koordinat_angkot":{"lat":-6.2880200009082,"lng":106.91497564316},"no_angkot":["T04"]}]
		$angkot_array = $a->angkot_shortest_path($exp_shortest_path, $this->old_simpul_awal, $this->old_simpul_akhir, /*tambahan-->*/ $this->maxRow0, $this->maxRow1);
		
		// return
		$return_json = ['jalur_shortest_path'=>$semua_latlng, 'angkot'=>$angkot_array];		
		return json_encode($return_json);
	}
	
	/**
	* MEMPREDIKSI 2 SIMPUL BARU SEBELUM DILAKUKAN PENAMBAHAN SIMPUL
	* @RETURN $maxRow0, $maxRow1 : int
	*/
	public function maxRowDB(){
		
		$select = "SELECT max(CONVERT(simpul_awal, SIGNED INTEGER)) as max_sa, max(CONVERT(simpul_tujuan, SIGNED INTEGER)) as max_st FROM graph";
		$query  = mysqli_query($this->koneksi, $select);
		$fetch  = mysqli_fetch_array($query, MYSQLI_ASSOC);
		
		$max_simpul_db			= 0;
		$max_simpulAwal_db 		= $fetch['max_sa'];
		$max_simpulTujuan_db 	= $fetch['max_st'];
		
		if( $max_simpulAwal_db >= $max_simpulTujuan_db ){
			$max_simpul_db = $max_simpulAwal_db;
		}else{
			$max_simpul_db = $max_simpulTujuan_db;
		}
		
		// return
		$this->maxRow0 = ($max_simpul_db+1);
		$this->maxRow1 = ($max_simpul_db+2);
	}
}// end CLASS

if(isset($_POST['koord_user'], $_POST['koord_destination'])){
	
	$koord_user 		= json_decode($_POST['koord_user'], true);
	$koord_destination 	= json_decode($_POST['koord_destination'], true);
	
	//print_r($koord_user);
	//echo $koord_destination;
	//die();
	$a = new Main();
	$shortest_path = $a->core($koord_user['lat'], $koord_user['lng'], $koord_destination['lat'], $koord_destination['lng']);
	//$shortest_path = $a->core(-6.338843516774621, 106.85749053955078, -6.35914593243388, 106.86967849731445);# terakhir
	//$shortest_path = $a->core(-6.365543586486009, 106.87787532806396, -6.35914593243388, 106.86967849731445);#fix/25-08
	//$shortest_path = $a->core(-6.297895039696718, 106.86504364013672, -6.2927762525228745, 106.8698501586914);#fix/25-08
	//$shortest_path = $a->core(-6.355136695311239, 106.8585205078125, -6.35914593243388, 106.86967849731445);
	
	// return
	echo $shortest_path;
	//echo "ere";
}
?>