<?php
class Angkot
{
	// koneksi DB
	public $koneksi;
	
	/**
	* before Action
	*/
	function __construct(){
		$k = new Koneksi();
		$this->koneksi = $k->connect();
	}
	
	/**
	* MENENTUKAN JENIS ANGKUTAN UMUM YANG MELEWATI JALUR SHORTEST PATH
	* @RETURN JSON KOORDINAT ANGKOT
	*/
	public function angkot_shortest_path($exp_dijkstra, $old_simpul_awal, $old_simpul_akhir, $maxRow0, $maxRow1){

		// misal exp_dijkstra[] = 1->5->6->7
		
		$m 			= 0;
		$old_awal	= explode('-', $old_simpul_awal); // misal 4-5
		$old_akhir 	= explode('-', $old_simpul_akhir); // misal 8-7

		$ganti_a = 0;
		$ganti_b = 0;
		$simpulAwalDijkstra = $exp_dijkstra[0]; // 1

		$gabungSimpul_all = "";
    	$listAngkutanUmum = array();
    	$listSimpulAngkot = array();

    	// CARI SIMPUL_OLD (misal 4->6->5) SEBELUM KOORDINAT DIPECAH
    	// misal 4-5 dipecah menjadi 4-6-5, berarti simpul_old awal = 4, simpul_old akhir = 5
		// total perulangannnya dikurang 1
        for($e = 0; $e < (count($exp_dijkstra) - 1); $e++)
		{
        	if($e == 0) // awal
			{
        		// dijalankan jika hasil algo hanya 2 simpul, example : 4->5
        		if(count($exp_dijkstra) == 2 /* 2 simpul (4->5)*/)
				{
        			// ada simpul baru di awal (10) dan di akhir (11), example 10->11
        			if( $exp_dijkstra[0] == $maxRow0 && $exp_dijkstra[1] == $maxRow1 ){
        				
    					$ganti_b = ($maxRow0 == $old_akhir[0]) ? $old_akhir[1] : $old_akhir[0];
						
						$ganti_a = ($ganti_b == $old_awal[0]) ? $old_awal[1] : $old_awal[0];
        			}
        			else{
        				// ada simpul baru di awal (10), example 10->5
        				// maka cari simpul awal yg oldnya
        				if( $exp_dijkstra[0] == $maxRow0){
        					
            				$ganti_a = ($exp_dijkstra[1] == $old_awal[1]) ? $old_awal[0] : $old_awal[1];
	            			
							$ganti_b = $exp_dijkstra[1];
        				}
        				// ada simpul baru di akhir (10), example 5->10
        				// maka cari simpul akhir yg oldnya
        				else if( $exp_dijkstra[1]== $maxRow0 ){
        					
        					$ganti_b = ($exp_dijkstra[0] == $old_akhir[0]) ? $old_akhir[1] : $old_akhir[0];

        					$ganti_a = $exp_dijkstra[0];
        				}
        				// tidak ada penambahan simpul sama sekali
        				else{
        					$ganti_a = $exp_dijkstra[0];
        					$ganti_b = $exp_dijkstra[1];
        				}
        			}        			
        		}
        		// hasil algo lebih dr 2 : 4->5->8->7->9 etc ..
        		else{
					
					// ada simpul baru di awal (10), example 10->5
					// maka cari simpul awal yg oldnya
					/*5 == 5*/
					if( $exp_dijkstra[0] == $maxRow0){
						$ganti_a = ($exp_dijkstra[1] == $old_awal[1]) ? $old_awal[0]/*4*/ : $old_awal[1] /*5*/;
					}
					// tidak ada simpul baru di awal
					else{
						$ganti_a = $exp_dijkstra[0];
					}
            		
        			$ganti_b = $exp_dijkstra[++$m];
        		}
        	}
			// akhir
        	else if($e == count($exp_dijkstra) - 2)
			{
				// simpul terkhir dijkstra
				$simpul_akhir_dijkstra = $exp_dijkstra[(count($exp_dijkstra) - 1)];
				
				// simpul sebelum terakhir dijkstra
				$simpul_sblm_akhir_dijkstra = $exp_dijkstra[(count($exp_dijkstra) - 2)];

				// ga ada simpul baru
				// array_search() sama seperti in_array(), 
				// bedanya return-nya $key/index yg dicari.
				if (false !== $key = array_search( $simpul_akhir_dijkstra, $old_akhir )) 
				{
					$ganti_b = $old_akhir[$key]; // hasil 8 or 7
				}
				// ada simpul baru
				else if($simpul_akhir_dijkstra == $maxRow0 || $simpul_akhir_dijkstra == $maxRow1)
				{
					$ganti_b = ($simpul_sblm_akhir_dijkstra == $old_akhir[0]) ? $old_akhir[1] : $old_akhir[0];
				} 
				/*else
				{
					$ganti_b = $old_akhir[1]; // hasil 7
				}*/
        		
        		$ganti_a = $exp_dijkstra[$m];
        		
        	}else // tengah tengah
			{	
        		$ganti_a = $exp_dijkstra[$m];
        		$ganti_b = $exp_dijkstra[++$m];
        	}

			// GABUNG SIMPUL GAK BOLEH SAMA! --> ,5-5,
			//if($ganti_a != $ganti_b){
				$gabung_a_b = "," . $ganti_a . "-" . $ganti_b . ","; // ,1-5,
			//}else{
			//	$gabung_a_b = "";
			//}
			// GABUNG SIMPUL
			$gabungSimpul_all .= $gabung_a_b;
			$gabungSimpul = $gabung_a_b;

			// GET NOMOR TRAYEK ANGKOT
			$select = "SELECT * FROM angkutan_umum where simpul like '%" . $gabungSimpul . "%'";
			$query  = mysqli_query($this->koneksi, $select);
			//$fetch  = mysqli_fetch_array($query, MYSQLI_ASSOC);

			$listAngkutan = array();			
			while($fetch = mysqli_fetch_array($query, MYSQLI_ASSOC)){
				array_push($listAngkutan, $fetch['no_trayek']);
			}
        	
			// add no_trayek angkot
			$listAngkutanUmum["angkutan".$e] = $listAngkutan;			
			// add simpul angkot
			array_push($listSimpulAngkot, $exp_dijkstra[$e]); 
        }
 
        $replace_jalur = str_replace(',,', ',', $gabungSimpul_all); //  ,1-5,,5-6,,6-7, => ,1-5,5-6,6-7,
		$select1	= "SELECT count(*) as jml_angkot, no_trayek FROM angkutan_umum where simpul like '%" . $replace_jalur . "%'";
		$query1 	= mysqli_query($this->koneksi, $select1);
		$fetch1		= mysqli_fetch_array($query1);
		
		
		// SEKALI NAIK ANGKOT
		// ADA 1/LEBIH ANGKOT YG MELEWATI JALUR DARI AWAL SAMPEK AKHIR
		// cukup gambar 1 koordinat angkot saja
		// die() sampai if ini
		if($fetch1['jml_angkot'] >= 1){
			
			$siAngkot = $fetch1['no_trayek'];

			// get coordinate
			$select2 	= "SELECT jalur FROM graph where simpul_awal = '" . $simpulAwalDijkstra . "'";
			$query2 	= mysqli_query($this->koneksi, $select2);
			$fetch2		= mysqli_fetch_array($query2);
			
			$json_coordinate = $fetch2['jalur'];

			// manipulate JSON
			$jObject = json_decode($json_coordinate, true);
			$jArrCoordinates = $jObject["coordinates"];
			$latlngs = $jArrCoordinates[0];
			
			// first latlng
			$lats = $latlngs[0];
			$lngs = $latlngs[1];
			
			$return_array = [['koordinat_angkot'=>['lat'=>$lats, 'lng'=>$lngs], 'no_angkot'=>$siAngkot]];
			
			return $return_array;
			die();
		}
		
		// BERKALI-KALI GANTI ANGKOT
		// ADA 1/LEBIH ANGKOT YG MELEWATI JALUR DARI AWAL SAMPEK AKHIR
		// PERINGKAS NOMOR TRAYEK
		$banyakAngkot 		= 0;
		$indexUrut 			= 0;
		$indexSimpulAngkot 	= 1;
        $lengthAngkutan 	= count($listAngkutanUmum);
        $angkotFix 			= array();

        for($en = 0; $en < $lengthAngkutan; $en++ )
		{
			// FIRST LOOPING
        	// temporary sementara sebelum di array_intersect()
        	$temps = array();
        	for($u = 0; $u < count($listAngkutanUmum['angkutan0']); $u++){
        		array_push($temps, $listAngkutanUmum['angkutan0'][$u]);
        	}
			
			// SENCOND LOOPING
        	if($en > 0 ){
	    		$listSekarang 		= $listAngkutanUmum['angkutan0'];
				$listSelanjutnya 	= $listAngkutanUmum['angkutan'.$en];
				
				// INTERSECTION
				// cari elemen yg ada di kedua array, yg tidak ada dihapus di $listSekarang
				// http://php.net/manual/en/function.array-intersect.php
				$listSekarang = array_intersect($listSekarang, $listSelanjutnya);
				$listSekarang = array_values($listSekarang); // 'reindex' array
				
	            if(count($listSekarang) > 0){
	            	
	            	unset($listSimpulAngkot[$indexSimpulAngkot]);
					$listSimpulAngkot = array_values($listSimpulAngkot); // 'reindex' array
	            	--$indexSimpulAngkot;

	            	unset($listAngkutanUmum['angkutan'.$en]);
					
					// sebelum akhir
	            	if($en == ($lengthAngkutan - 1)){
		            	$tempDalam = array();

		            	for($es = 0; $es < count($listSekarang); $es++){
		            		array_push( $tempDalam, $listSekarang[$es] );
		            	}
		            	
	            		$angkotFix['angkutanFix'.$indexUrut] = $tempDalam;
		            	++$indexUrut;
	            	}
	            }	            
	            else if(count($listSekarang) == 0)
				{
	            	$angkotFix['angkutanFix'.$indexUrut] = $temps;
	            	
	            	$tempDalam = array();
	            	for($es = 0; $es < count($listSelanjutnya); $es++){
	            		array_push($tempDalam, $listSelanjutnya[$es]);
	            	}
	            	
	            	$listAngkutanUmum['angkutan0'] = $tempDalam;
	            	unset($listAngkutanUmum['angkutan'.$en]);
	            	
		            ++$indexUrut;
		            
	            	if($en == ($lengthAngkutan - 1))
					{
		            	$tempDalam2 = array();
		            	for($es = 0; $es < count($listSelanjutnya); $es++){
		            		array_push($tempDalam2, $listSelanjutnya[$es]);
		            	}
		            	
	            		$angkotFix['angkutanFix'.$indexUrut] = $tempDalam2;
		            	++$indexUrut;
	            	}		            
	            }	        	
	        	++$indexSimpulAngkot;
        	}
        }

		// buat return
		$return_array = [];

		// GAMBAR 2 ATAU LEBIH KOORDINAT ANGKUTAN UMUM
        foreach($listSimpulAngkot as $r => $simpulx)
		{
			// get coordinate simpulAngkutan
			$select = "SELECT jalur FROM graph where simpul_awal = '" . $simpulx . "'";
			$query	= mysqli_query($this->koneksi, $select);
			$fetch	= mysqli_fetch_array($query, MYSQLI_ASSOC);
			
			// dapatkan koordinat Lat,Lng dari field koordinat (3)
			$json_db = $fetch['jalur'];

			// get JSON
			$jObject = json_decode($json_db, true);
			$jArrCoordinates = $jObject['coordinates'];

			// get first coordinate JSON
			$latlngs = $jArrCoordinates[0];
			$lats = $latlngs[0];
			$lngs = $latlngs[1];

			$json['lat'] 	= $lats;
			$json['lng'] 	= $lngs;
			$angkot			= $angkotFix['angkutanFix'.$r];
			
			$gabung_array	= ['koordinat_angkot'=>$json, 'no_angkot'=>$angkot];			
			array_push($return_array, $gabung_array);
        }

		return $return_array;
	}
}