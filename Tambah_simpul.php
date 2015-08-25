<?php
include "Count_Bobot_Tambah_Simpul.php";

class Tambah_simpul extends Count_Bobot_Tambah_Simpul{
	
	// koneksi DB
	public $koneksi;
	public $koneksiPDO;
	
	//  tampung id baru dari DB
	public $temporary_id = [];
	
	/**
	* before Action
	* set CONNECTION
	*/
	function __construct(){
		$k = new Koneksi();
		$this->koneksi 		= $k->connect();
		$this->koneksiPDO 	= $k->connectPDO();
	}
	
	/**
	 * @FUNGSI
	 *  MEYISIPKAN SIMPUL BARU 
	 *  misal simpul 5-4, disisipkan menjadi 5-6-4
	 *  dan simpul 4-5, disisipkan menjadi 4-6-5
	 * @PARAMETER
	 *  nodes0 : misal {"nodes": "5-4"} maka nodes_awal0 = 5
	 *  nodes1 : misal {"nodes": "5-4"} maka nodes_awal1 = 4
	 *  index_koordinat_json : index array koordinat di JSON
	 *  graph[][] : array untuk menampung graph dari DB
	 *   			example output : graph[5][0] = 4->439.281
	 *   							 graph[6][0] = 1->216.281 
	 * @RETURN json
	 *  simpul_lama = nodes0 + "-" + nodes1
	 *  simpul_baru = simpul awal
	 *  new_id
	 *  graph[][]
	 */
	public function dobelSimpul($nodes0, $nodes1, $index_koordinat_json, $graph){

		// HITUNG SIMPUL YANG ASLI DULU (5-4), BUKAN YANG DOBEL (4-5)
		//==============================================================		
		$index_kolom_graph = (count($graph[$nodes0])); // new index --> graph[][new_index]
		$index_kolom_graph = "";
		
		// cari index kolomnya nodes1 (4) dari graph[baris][index kolom]
		// JUMLAH BARIS PER KOLOM SIMPUL
		$jumlah_baris = count($graph[$nodes0]);
		
		for($l = 0; $l < $jumlah_baris; $l++){

			if(isset($graph[$nodes0][$l])){
				
				// [5][0] = 4->721.666
				$simpulAwal = $graph[$nodes0][$l];
				
				// 4->721.666
				$explode = explode('->', $simpulAwal);				
				$simpul_tujuan_ = $explode[0]; // 4
				
				// jika 4 == 4 (node1)
				if(trim($simpul_tujuan_) == trim($nodes1))
				{					
					// index kolom; example graph[baris][kolom]
					$index_kolom_graph = $l;
				}
			}
			else break;		
		}// for
		
		
		// index dari graph[baris][kolom] yang akan di edit
		$baris = $nodes0;
		$kolom = $index_kolom_graph;

		// ambil koordinatnya dari simpul 5-4
		$select	= "SELECT jalur FROM graph where simpul_awal = ".$nodes0." and simpul_tujuan = ".$nodes1;
		$query 	= mysqli_query($this->koneksi, $select);
		$fetch 	= mysqli_fetch_array($query, MYSQLI_ASSOC);		
		
		// --
		// get coordinates JSON
		$json_coordinates 	= $fetch['jalur'];
		$jsonOneRoute 		= json_decode($json_coordinates, true);		
		// data json
		$dataNode			= $jsonOneRoute['nodes'][0];
		$nodeSplit			= explode('-', $dataNode);
		$node0				= $nodeSplit[0];
		$node1				= $nodeSplit[1];
		$dataBobot			= $jsonOneRoute['distance_metres'][0];
		$countCoordinate	= count($jsonOneRoute['coordinates']);	
		$jArrCoordinates	= $jsonOneRoute['coordinates'];
		// --
		
		// cari maksimal simpul, (buat penomoran simpul baru)
		$select1 = "SELECT max(CONVERT(simpul_awal, SIGNED INTEGER)) as max_sa, max(CONVERT(simpul_tujuan, SIGNED INTEGER)) as max_st FROM graph";
		$query1  = mysqli_query($this->koneksi, $select1);
		$fetch1  = mysqli_fetch_array($query1, MYSQLI_ASSOC);		
		
		$max_simpul_db			= 0;
		$max_simpulAwal_db 		= $fetch1['max_sa'];			
		$max_simpulTujuan_db 	= $fetch1['max_st'];
		if($max_simpulAwal_db >= $max_simpulTujuan_db){
			$max_simpul_db = $max_simpulAwal_db;
		}else{
			$max_simpul_db = $max_simpulTujuan_db;
		}
		
		// pecah koordinat dari AWAL->TENGAH			
		$limit 	= $index_koordinat_json;
		// @extends
		$bobot 	= $this->Count_Bobot_Tambah_Simpul(0, $limit, $jArrCoordinates); // 0, koordinat tengah, jSON coordinates
		
		//replace array graph[5][0] = 6->888.6
		$graph[$baris][$kolom] = ($max_simpul_db+1) . "->" . $bobot;				
		
		// buat dan simpan (new record) json koordinat yang baru ke DB
		$start_loop = 0;
		// @local
		$this->createAndSave_NewJsonCoordinate($start_loop, $limit, $jArrCoordinates, $nodes0, ($max_simpul_db + 1), $bobot);

		// reset bobot
		$bobot = 0;
			
		// pecah koordinat dari TENGAH->AKHIR
		$start_loop1 	= $index_koordinat_json;
		$limit1 		= (count($jArrCoordinates) - 1); // - 1 karena array mulai dari 0
		// @extends
		$bobot 	= $this->Count_Bobot_Tambah_Simpul($index_koordinat_json, $limit1, $jArrCoordinates); // coordinate tengah sampai akhir
		
		
		// new array graph[6][0] = 4->777.4
		$graph[($max_simpul_db+1)][0] = $nodes1 . "->" . $bobot; //didefinisikan [0] karena index baru di graph[][]
		
		// buat dan simpan (new record) json koordinate yang baru ke DB
		$this->createAndSave_NewJsonCoordinate($start_loop1, $limit1, $jArrCoordinates, ($max_simpul_db + 1), $nodes1, $bobot);
		
		// reset bobot
		$bobot = 0;


		// HITUNG SIMPUL YANG DOBEL (4-5), BUKAN YANG ASLI (5-4)
		//==============================================================
		// dibalik, nodes0 jadi nodes1; example (5-4) jadi (4-5)
		$t_nodes0 = $nodes1; // 4
		$t_nodes1 = $nodes0; // 5

		$index_kolom_graph1 = count($graph[$t_nodes0]); // new index --> graph[][new_index]
		$index_kolom_graph1 = "";
		$nodes_inside_kolom = "";
		
		// cari index kolomnya dari graph[4][index kolomnya]
		// JUMLAH BARIS PER KOLOM SIMPUL
		$jumlah_baris1 = count($graph[$t_nodes0]);		
		for($l = 0; $l < $jumlah_baris1; $l++){

			if(isset($graph[$t_nodes0][$l])){

				// == dapatkan simpul tujuan, example : 5->9585.340
				$simpulAwal 	= $graph[$t_nodes0][$l];
				$explode1 		= explode('->', $simpulAwal);
				
				$nodes_inside_kolom = $explode1[0];
				
				if(trim($nodes_inside_kolom) == $t_nodes1){
					$index_kolom_graph1 = $l;
				}
				
			}else break;
		}//for
		
		
		// index dari graph[baris1][kolom1] yang akan di edit
		$baris1 = $t_nodes0;
		$kolom1 = $index_kolom_graph1;

		// ambil koordinatnya dari simpul 4-5
		$select2	= "SELECT jalur FROM graph where simpul_awal = " . $t_nodes0 . " and simpul_tujuan = " . $t_nodes1;
		$query2 	= mysqli_query($this->koneksi, $select2);
		$fetch2		= mysqli_fetch_array($query2, MYSQLI_ASSOC);
		
		// --
		// get coordinates JSON
		$json_coordinates1 	= $fetch2['jalur'];
		$jsonOneRoute1		= json_decode($json_coordinates1, true);	
		// data json
		$dataNode1			= $jsonOneRoute1['nodes'][0];
		$nodeSplit1			= explode('-', $dataNode1);
		$node01				= $nodeSplit1[0];
		$node11				= $nodeSplit1[1];
		$dataBobot1			= $jsonOneRoute1['distance_metres'][0];
		$countCoordinate1	= count($jsonOneRoute1['coordinates']);	
		$jArrCoordinates1	= $jsonOneRoute1['coordinates'];
		// --

		// pecah koordinat dari AWAL->TENGAH
		$index_dobel_koordinat_json = ( (count($jArrCoordinates1)-1) - $index_koordinat_json );
		// @extends
		$bobot = $this->Count_Bobot_Tambah_Simpul(0, $index_dobel_koordinat_json, $jArrCoordinates1); // 0, koordinat awal ke tengah, JSONArray coordinate

		//replace array graph[4][0] = 6->777.4
		$graph[$baris1][$kolom1] = ($max_simpul_db+1) . "->" . $bobot;
		
		
		// buat dan simpan (new record) json koordinate yang baru ke DB
		$start_loop2 = 0;
		// @local
		$this->createAndSave_NewJsonCoordinate($start_loop2, $index_dobel_koordinat_json, $jArrCoordinates1, $baris1, ($max_simpul_db + 1), $bobot);
		
		
		// reset bobot
		$bobot = 0;
	
		// pecah koordinat dari TENGAH->AKHIR
		$limit2 = (count($jArrCoordinates1) - 1); // - 1 karena array mulai dari 0
		// @extends
		$bobot 	= $this->Count_Bobot_Tambah_Simpul($index_dobel_koordinat_json, $limit2, $jArrCoordinates1); // koordinat tengah sampai akhir
		
		//replace array graph[6][1] = 5->888.6
		$graph[($max_simpul_db+1)][1] = $t_nodes1 . "->" . $bobot; // didefinisikan [1] karena sdh ada index 0 di graph[][]
		
		// buat dan simpan (new record) json koordinate yang baru ke DB
		// @local
		$this->createAndSave_NewJsonCoordinate($index_dobel_koordinat_json, $limit2, $jArrCoordinates1, ($max_simpul_db + 1), $t_nodes1, $bobot);		
		
		// return
		$simpul_lama = $nodes0 . "-" . $nodes1;
		$simpul_baru = ($max_simpul_db + 1);

		$jadi_json['simpul_lama'] = $simpul_lama;
		$jadi_json['simpul_baru'] = $simpul_baru;
		$jadi_json['temporary_id']= $this->temporary_id;
		$jadi_json['graph']		  = json_encode($graph);
		
		return json_encode($jadi_json);
	}// function dobelSimpul


	
	/*
	 * @FUNGSI
	 *  MEYISIPKAN SIMPUL BARU 
	 *  misal simpul 5-4, disisipkan menjadi 5-6-4
	 * @PARAMETER
	 *  nodes_awal0 : misal {"nodes": "5-4"} maka nodes_awal0 = 5
	 *  nodes_awal1 : misal {"nodes": "5-4"} maka nodes_awal1 = 4
	 *  index_koordinat_json : index array koordinat di JSON
	 *  graph[][] : array untuk menampung graph dari DB
	 *   			example output : graph[5][0] = 4->439.281
	 *   							 graph[6][0] = 1->216.281 
	 * @RETURN
	 *  simpul_lama
	 *  simpul_baru
	 *  new_id
	 */	
	 
	public function singleSimpul($nodes0, $nodes1, $index_koordinat_json, $graph){

		// HITUNG SIMPUL YANG ASLI (5-4)
		//==============================================================	
		$index_kolom_graph = count($graph[$nodes0]); // new index --> graph[][new_index]
		$index_kolom_graph = "";
		
		// cari index kolomnya nodes_akhir1 (4) dari graph[baris][kolom]
		// JUMLAH BARIS PER KOLOM SIMPUL
		$jumlah_baris = count($graph[$nodes0]);		
		for($l = 0; $l < $jumlah_baris; $l++){

			if(isset($graph[$nodes0][$l])){

				$simpulAwal = $graph[$nodes0][$l]; // [5][0] = 4->721.666
				$explode = explode('->', $simpulAwal);
				
				// 6->721.666
				$value_node_array = $explode[0];
				
				// jika 4 == 4 (node_akhir1)
				if( trim($value_node_array) == trim($nodes1) ){
					
					// index kolom; example graph[baris][kolom]
					$index_kolom_graph = $l;
				}				
			}else break;
		}//for

		
		// index dari graph[baris][kolom] yang akan di edit
		$baris 	= $nodes0;
		$kolom 	= $index_kolom_graph;

		// ambil koordinatnya dari simpul 5-4
		$select	= "SELECT jalur FROM graph where simpul_awal = ".$nodes0." and simpul_tujuan = ".$nodes1;
		$query 	= mysqli_query($this->koneksi, $select);
		$fetch 	= mysqli_fetch_array($query, MYSQLI_ASSOC);
		
		// cocokan jalur, misal :
		// ada jalur 10-3, tapi di input 3-10, maka die()
		if($fetch['jalur'] == "") die(json_encode(['status'=>'jalur_not_found']));
		
		// --
		// get coordinates JSON
		$json_coordinates 	= $fetch['jalur'];
		$jsonOneRoute 		= json_decode($json_coordinates, true);	
		// data json
		$dataNode			= $jsonOneRoute['nodes'][0];
		$nodeSplit			= explode('-', $dataNode);
		$node0				= $nodeSplit[0];
		$node1				= $nodeSplit[1];
		$dataBobot			= $jsonOneRoute['distance_metres'][0];
		$countCoordinate	= count($jsonOneRoute['coordinates']);	
		$jArrCoordinates	= $jsonOneRoute['coordinates'];
		// --
		
		// cari maksimal simpul, (buat penomoran simpul baru)
		$select1 = "SELECT max(CONVERT(simpul_awal, SIGNED INTEGER)) as max_sa FROM graph";
		$query1  = mysqli_query($this->koneksi, $select1);
		$fetch1  = mysqli_fetch_array($query1, MYSQLI_ASSOC);

		// max nodes field['simpul_awal']
		$max_simpul_db = $fetch1['max_sa'];
		
		// pecah koordinat dari AWAL->TENGAH
		$limit = $index_koordinat_json;	
		// @extends
		$bobot = $this->Count_Bobot_Tambah_Simpul(0, $limit, $jArrCoordinates); // 0, koordinat tengah, jSON coordinates
		
		//replace array graph[5][0] = 6->888.6
		$graph[$baris][$kolom] = ($max_simpul_db+1) . "->" . $bobot;

		
		// buat dan simpan (new record) json koordinat yang baru ke DB
		$start_loop = 0;		
		// @local
		$this->createAndSave_NewJsonCoordinate($start_loop, $limit, $jArrCoordinates, $nodes0, ($max_simpul_db + 1), $bobot);
		
		
		// reset bobot
		$bobot = 0;
				
		// pecah koordinat dari TENGAH->AKHIR
		$start_loop1 = $index_koordinat_json; // - 1 karena array mulai dari 0
		$limit1 = (count($jArrCoordinates) - 1); // - 1 karena array mulai dari 0
		// @extends
		$bobot = $this->Count_Bobot_Tambah_Simpul($index_koordinat_json, $limit1, $jArrCoordinates); // coordinate tengah sampai akhir
		
		
		// new array graph[6][0] = 4->777.4
		$graph[($max_simpul_db+1)][0] = $nodes1 . "->" . $bobot; //didefinisikan [0] karena index baru di graph[][]
		
		// buat dan simpan (new record) json koordinate yang baru ke DB
		// @local
		$this->createAndSave_NewJsonCoordinate($start_loop1, $limit1, $jArrCoordinates, ($max_simpul_db + 1), $nodes1, $bobot);

		// return
		$simpul_lama = $nodes0 . "-" . $nodes1;
		$simpul_baru = ($max_simpul_db + 1);

		$jadi_json['simpul_lama'] = $simpul_lama;
		$jadi_json['simpul_baru'] = $simpul_baru;
		$jadi_json['temporary_id']= $this->temporary_id;
		$jadi_json['graph']		  = json_encode($graph);
		
		return json_encode($jadi_json);
	}
	
	/* @FUNGSI
	 *  MEMBUAT DAN MENYIMPAN COORDINATES BARU DALAM BENTUK JSON KE DB
	 * @PARAMETER 
	 *  mulai : mulai looping, misal 0
	 * 	limit : index array koordinat, misal i[7] maka limit = 7
	 *  jArrCoordinates : Koordinat dari DB dalam bentuk JSONArray
	 *  new_id : id record baru
	 *  //baris : baris multidimensi array, misal i[baris][kolom]
	 *  //max_simpul_db : jumlah max record pada tabel graph
	 *  new_bobot : bobot baru dari pemecahan koordinat jalur
	 *  dbInsert : insert ke database
	 *  dbRead : baca record database
	 * @RETURN 
	 *  no return
	 */
	public function createAndSave_NewJsonCoordinate($mulai, $limit, $jArrCoordinates, $field_simpul_awal, $field_simpul_akhir, $new_bobot){
		
		// JSON for save new coordinate
		$json_baru = array();
		$new_root_coordinates = array();
		
		/**
		# looping dari coordinate awal sampai ke coordinate tengah
		# atau 
		# looping dari coordinate tengah sampai ke coordinate akhir
		# then, move old coordinate to new coordinate
		*/
		for($ne = $mulai; $ne <= $limit; $ne++){			
			$latlng = $jArrCoordinates[$ne];
			// cut coordinates
			array_push($new_root_coordinates, $latlng);			
		}

		// gabung nodes
		$gabung_nodes = $field_simpul_awal . '-' . $field_simpul_akhir;
		
		// create new JSON
		$json_baru['nodes'] = [$gabung_nodes];
		$json_baru['coordinates'] = $new_root_coordinates;
		$json_baru['distance_metres'] = [$new_bobot];
		
		// json coordinates
		$jalur_baru = json_encode($json_baru);
		
		// INSERT USING PDO
		try {			
			$conn = $this->koneksiPDO;

			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			// get MAX row ID
			$gid = $conn->prepare("SELECT MAX(id) as newid FROM graph");
			$gid->execute();
			$new_id = $gid->fetch(PDO::FETCH_ASSOC);

			$sql = "INSERT INTO graph values(($new_id[newid]+1), '$field_simpul_awal', '$field_simpul_akhir', '$jalur_baru', '$new_bobot', 'Y')";

			// Prepare statement
			$stmt = $conn->prepare($sql);

			// execute the query
			$stmt->execute();

			// echo a message to say the UPDATE succeeded
			//echo $stmt->rowCount() . " records INSERTED successfully";
			
			// save new id
			array_push($this->temporary_id, $conn->lastInsertId('id'));
		}
		catch(PDOException $e)
		{
			echo $e->getMessage();
		}
	}
}
?>