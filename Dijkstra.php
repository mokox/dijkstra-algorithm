<?php
class Dijkstra
{
	/**
	* MENCARI 1 JALUR TERPENDEK DENGAN ALGORITMA DIJSKTRA DARI GRAPH ARRAY
	* GRAPH ARRAY SEPERTI DIBAWAH INI :
	  [0][0] = '1->10';
	  [0][1] = '2->11';
	  [0][2] = '3->40';
	  
	  [1][0] = '0->10';
	  [1][1] = '2->55';
	  [1][2] = '4->20';
	  
	  [2][0] = '0->11';
	  [2][1] = '1->55';
	  [2][3] = '1->54';
	  
	  [3][0] = '4->89';
	  [3][0] = '4->89';
	  
	  [4][0] = '0->90';
	  [4][3] = '3->89';
	* GRAPH ARRAY YANG SUDAH DIKERJAKAN DALAM MPERHITUNGAN ALGORITMA DIJKSTRA :
	  [0][0] = '1->10->y';
	  [0][1] = '2->11->y';
	  [0][2] = '3->40->y';
	  [1][0] = '0->10->y';	
	  ...
	* @param arg_graph array[][]
	* @param simpulAwal int
	* @param simpulTujuan int
	* @return string, contoh 0->1->4
	*/
	function jalurTerpendek($arg_graph, $simpulAwal, $simpulTujuan){
		
		// SIMPUL_AWAL == SIMPUL_TUJUAN, MAKA DIE()
		// 0 == 0
		if($simpulAwal == $simpulTujuan){
			return json_encode(['status'=>'error','error'=>'lokasi_anda_sudah_dekat','teks'=>'Lokasi Anda Sudah Dekat','content'=>'']);
		}
		
		// SIMPUL_AWAL OR SIMPUL_TUJUAN NOT FOUND
		if(!array_key_exists($simpulAwal, $arg_graph) || !array_key_exists($simpulTujuan, $arg_graph)){
			return print_r(json_encode(['status'=>'error','error'=>'simpul_input_tidak_ditemukan','teks'=>"could not find the input : $simpulAwal or $simpulTujuan", 'content'=>'']));
		}
		
		$graph 		 	= $arg_graph;
        $simpul_awal 	= $simpulAwal;
        $simpul_maju 	= $simpulAwal;
        $simpul_tujuan 	= $simpulTujuan;
		$jml_simpul 	= count($arg_graph);
		
		/**
		* TANDAI SIMPUL YANG AKAN DIKERJAKAN DENGAN TANDA BINTANG (*)
		* MISAL SOAL : CARI JALUR TERPENDEK DARI SIMPUL 0 KE SIMPUL 4 !
		   --------- ---------- --------- --------- ---------
		  |   0(*)  |   1(*)   |  2(*)   |    3    |   4(*)  | <-- KOLOM SIMPUL
		   --------- ---------- --------- --------- ---------
		  | 0->1=10 | 1->0=10  | 2->0=11 | 3->4=89 | 4->0=90 | <-- BARIS; BOBOT = 90
		  | 0->2=11 | 1->2=55  | 2->1=55 |     	   | 4->3=89 |
		  | 0->3=40 | 1->4=20  | 2->3=54 |         |         |
		   --------- ---------- --------- --------- ---------
		* MAKA HASILNYA $simpulYangDikerjakan = array(0, 1, 2, 4);
		*/
		$simpulYangDikerjakan = array(); 
		
		// UNTUK MENYIMPAN NILAI-NILAI * YANG DITANDAI
		$simpulYangSudahDikerjakan_bawah = array();

		$nilaiSimpulYgDitandai 		= 0;
		$nilaiSimpulFixYgDitandai 	= 0;
	
	
		// #HANDLE PERULANGAN
		// PERULANGAN INI TIDAK AKAN BERHENTI (--$perulangan;) SAMPAI ALGORITMA DIJKSTRA MENEMUKAN 1 JALUR TERPENDEK
		// 
		for($perulangan = 0; $perulangan < 1; $perulangan++)
		{
			// UNTUK MNDAPATKAN 1 BOBOT PALING MINIMUM DARI SETIAP SIMPUL
			$perbandinganSemuaBobot = array();
			
			// DAFTARKAN SIMPUL pertama YANG AKAN DIKERJAKAN KE DALAM ARRAY
			if(!in_array($simpul_maju, $simpulYangDikerjakan)){
				array_push($simpulYangDikerjakan, $simpul_maju);
			}
			
			/** 
			* PERULANGAN (KOLOM) SIMPUL-SIMPUL YANG DITANDAI
			   --------- ---------- --------- --------- ---------
			  |   0(*)  |   1(*)   |  2(*)   |    3    |   4(*)  | <-- KOLOM SIMPUL
			   --------- ---------- --------- --------- ---------
			  | 0->1=10 | 1->0=10  | 2->0=11 | 3->4=89 | 4->0=90 |
			  | 0->2=11 | 1->2=55  | 2->1=55 |     	   | 4->3=89 |
			  | 0->3=40 | 1->4=20  | 2->3=54 |         |         |
			   --------- ---------- --------- --------- ---------
			* PERULANGANNYA : array(0, 1, 2, 4);
			*/
			for($perulanganSimpul = 0; $perulanganSimpul < count($simpulYangDikerjakan); $perulanganSimpul++)
			{
				// HITUNG JUMLAH BARIS PER KOLOM SIMPUL
				// 0(*) = 3 BARIS; (0->1=10; 0->2=11; 0->3=40)
				// 1(*) = 3 BARIS; 
				// 2(*) = 3 BARIS;
				// 4(*) = 2 BARIS;
				$jumlah_baris = count($graph[ $simpulYangDikerjakan[$perulanganSimpul] ]);

				// TAMPUNG BOBOT MINIMUM DARI SETIAP KOLOM SIMPUL BERDASARKAN BARIS SCR URUT[0][0],[0][1] DST
				$bobot_baris = array();

				// JUMLAH BARIS YANG BELUM DIKERJAKAN
				$baris_belum_dikerjakan = 0;

				
				/**
				* PERULANGAN BARIS TABEL, CARI BOBOT DARI 1 KOLOM SIMPUL
				   --------- 
				  |   0(*)  | <-- KOLOM SIMPUL
				   --------- 
				  | 0->1=10 | <-- baris; bobot = 10;
				  | 0->2=11 | <-- baris; bobot = 11;
				  | 0->3=40 | <-- baris; bobot = 40;
				   --------- 
				*/
				for($start_baris = 0; $start_baris < $jumlah_baris; $start_baris++)
				{
					// AMBIL VALUE ARRAY graph[][]
					// MISAL graph[0][0] = '1->10'; VALUENYA BERARTI '1->10'
					$ruas_dan_bobot = $graph[ $simpulYangDikerjakan[$perulanganSimpul] ][$start_baris];//pasti berurutan [0][0],[0][1] dst
					
					// PECAH RUAS & BOBOT BERDASARKAN '->'
					// RUAS  : explode[0] = 1
					// BOBOT : explode[1] = 10
					$explode = explode('->', $ruas_dan_bobot);
					
					/**
					* CARI BOBOT YG BELUM DIKERJAKAN (YG TIDAK ADA TANDA ->Y)
					* MISAL : 
					  [0][0] = '1->10->y';
					  [0][1] = '2->11';   <---- ini yang belum dikerjakan / tidak ada tanda y
					*/
					if(count($explode) == 2)
					{						
						// TOTAL BARIS YG BELUM ->Y
						$baris_belum_dikerjakan += 1;
		
						// CEK KOLOM SIMPUL APAKAH SUDAH DITANDAI (*) APA BLOM, KLO UDH BERARTI NILAI * TIDAK DITAMBAH LAGI / 0
						// KALO BLM DITANDAI, BERARTI NILAI * BERNILAI $nilaiSimpulYgDitandai
						if(!empty($simpulYangSudahDikerjakan_bawah))
						{
							if(in_array($simpulYangDikerjakan[$perulanganSimpul], $simpulYangSudahDikerjakan_bawah)){
							   $nilaiSimpulYgDitandai = 0;           
							}else{
							  $nilaiSimpulYgDitandai = $nilaiSimpulFixYgDitandai;
							}
						}

						/** 
						* MASUKKAN BOBOT BARIS YANG SUDAH DIUPDATE KE DALAM ARRAY
						* ILUSTRASI MENGUPDATE BOBOT DALAM TABEL :
						   -------------- 
						  |     0(*)     | <-- KOLOM SIMPUL
						   -------------- 
						  | 0->1=10 (13) | <-- bobot diupdate = 13;
						  | 0->2=11 (12) | <-- bobot diupdate = 12;
						  | 0->3=40 (45) | <-- bobot diupdate = 45;
						   -------------- 
						*/
						array_push($bobot_baris, ($explode[1]+$nilaiSimpulYgDitandai)); // (bobot+0) or (bobot+232)
						
						// UPDATE JUGA BOBOT BARIS PADA graph[][]
						// MISAL : '1->13'
						$graph[ $simpulYangDikerjakan[$perulanganSimpul] ][$start_baris] = $explode[0] . "->" . $explode[1] . $nilaiSimpulYgDitandai;                                
					}
				}
				
				
				// JIKA ADA BARIS DI KOLOM BELUM ->Y SEMUA, MAKA LAKUKAN IF DI BAWAH INI :
				if($baris_belum_dikerjakan > 0)
				{
					// DAPATKAN BOBOT MINIMUM
					for($index_bobot = 0; $index_bobot < count($bobot_baris); $index_bobot++){
					   if($bobot_baris[$index_bobot] <= $bobot_baris[0]){
						   $bobot_baris[0] = $bobot_baris[$index_bobot];
					   }
					} 

					// BOBOT TERKECIL DARI SETIAP KOLOM SIMPUL
					array_push($perbandinganSemuaBobot, $bobot_baris[0]);
					
				}// end if jika ->y atau ->t belum semua dikerjakan
				else{
					// Jika baris di kolom sudah ->y semua, maka lakukan else di bawah ini
					// System.out.println("=======||Baris sudah ->y semua||=======");
				}   
				
				// DAFTARKAN SIMPUL SIMPUL YANG baru selesai DIKERJAKAN
				if(!in_array($simpulYangDikerjakan[$perulanganSimpul], $simpulYangSudahDikerjakan_bawah)){
					array_push( $simpulYangSudahDikerjakan_bawah, $simpulYangDikerjakan[$perulanganSimpul] );
				}
			}// end for perulanganSimpul                
			
			
			/**
			* DAPATKAN 1 BOBOT PALING MINIMUM DARI SIMPUL YG DITANDAI (*)
			   ------------- ------------ --------- 
			  |    0(*) 	|    1(*)    |  2(*)   |
			   ------------- ------------ --------- 
			  | 0->1=10(*) 	| 1->0=10  	 | 2->0=11 |
			  | 0->2=11(*)	| 1->2=55  	 | 2->1=55 |
			  | 0->3=40 	| 1->4=20(*) | 2->3=54 |
			   ------------- ------------ --------- 
			*/
			for($min_indexAntarBobotYgDitandai = 0; $min_indexAntarBobotYgDitandai < count($perbandinganSemuaBobot); $min_indexAntarBobotYgDitandai++)
			{
				if($perbandinganSemuaBobot[$min_indexAntarBobotYgDitandai] <= $perbandinganSemuaBobot[0]){
					$perbandinganSemuaBobot[0] = $perbandinganSemuaBobot[$min_indexAntarBobotYgDitandai];
				}
			}
						
			// DAPATKAN INDEX SIMPUL+BOBOTNYA YG ASLI DARI SIMPUL YG DITANDAI
			$indexAwalAsli 				= 0; // index simpulnya
			$baris_belum_dikerjakan1 	= 0;                
			$dapat_indexAsliBobot 		= 0;
			$simpul_lama 				= 0;
			//$length_baris				= count($graph[$simpulYangDikerjakan[$indexAwalAsli]]);
			
			foreach($simpulYangDikerjakan as $idx => $v)
			{
				/**
				  JUMLAH BARIS per KOLOM SIMPUL
				   -------------- 
				  |     0(*)     | <-- KOLOM SIMPUL
				   -------------- 
				  | 0->1=10 (13) | <-- baris 1
				  | 0->2=11 (12) | <-- baris 2
				  | 0->3=40 (45) | <-- baris 3
				   --------------
				*/
				$length_baris = $graph[$simpulYangDikerjakan[$idx]];
				
				for($baris1 = 0; $baris1 < $length_baris; $baris1++)
				{
					if( isset($graph[ $simpulYangDikerjakan[$indexAwalAsli] ][$baris1]) )
					{
						$bobot_baris_dan_ruas1 = $graph[ $simpulYangDikerjakan[$indexAwalAsli] ][$baris1];
						$explode1 = array();
						$explode1 = explode('->', $bobot_baris_dan_ruas1);
						if(count($explode1) == 2)
						{
							if($perbandinganSemuaBobot[0] == $explode1[1])
							{
								$dapat_indexAsliBobot = $baris1;
								$simpul_lama = $simpulYangDikerjakan[$indexAwalAsli];
								$simpul_maju = $explode1[0];
								$baris_belum_dikerjakan1 += 1;            
							}                                         
						}// end if cek ->y atau ->t
					}// end if cek baris != null
					else{
						break;
					}
				}// end for limit baris = 100
				
				$indexAwalAsli++; // index simpul di tambah 1
			}// end for simpul yang dikerjakan
			
			
			// BULETIN BOBOT MINIMUM YANG UDH DIDAPAT dan HAPUS RUAS YANG BERHUBUNGAN              
			if($baris_belum_dikerjakan1 > 0){                    
				$graph[$simpul_lama][$dapat_indexAsliBobot] = $graph[$simpul_lama][$dapat_indexAsliBobot] . "->y";

				// HAPUS RUAS LAIN
				for($min_kolom = 0; $min_kolom < $jml_simpul; $min_kolom++)
				{
					// JUMLAH BARIS PER KOLOM SIMPUL
					$length_baris1 = count($graph[$min_kolom]);
					
					for($min_baris = 0; $min_baris < $length_baris1; $min_baris++)
					{
						if(isset($graph[$min_kolom][$min_baris]))
						{
							$ruasYgAkanDihapus = $graph[$min_kolom][$min_baris];
							$explode3 = explode('->', $ruasYgAkanDihapus);
							if(count($explode3) == 2){
								if($explode3[0] == $simpul_maju){
									$graph[$min_kolom][$min_baris] = $graph[$min_kolom][$min_baris]+"->t";                                        
								}
							}
						}// end if cek baris != null
					}// end for baris
				}// end for kolom  
			}// end if cek baris_belum_dikerjakan sudah ->y atau ->t semua apa belum
			
			// ======================================================
			// # JIKA ALUR GRAPH ANDA SALAH, MAKA DIE() SAMPAI IF INI
			// ======================================================
			if(!isset($perbandinganSemuaBobot[0]))
				return json_encode(['status'=>'error', 'error'=>'alur_graph_anda_salah', 'teks'=>'Alur graph Anda Salah', 'content'=>'']);
			
			
			// NILAI * YG DITANDAI
			$nilaiSimpulFixYgDitandai = $perbandinganSemuaBobot[0];
			
			// LOOPING $perulangan lagi jika SIMPUL_MAJU != SIMPUL_TUJUAN
			if($simpul_maju != $simpul_tujuan){
			  --$perulangan; 
			}
			else{
				break; // akhiri perulangan
			}
		}// end for handle perulangan  		
		
		

		// TARUH SIMPUL GABUNGAN KE ARRAY; MISAL : SIMPUL 6-10
		$gabungSimpulPilihan = array();
		for($h = 0; $h < $jml_simpul; $h++)
		{
			// JUMLAH BARIS PER KOLOM SIMPUL
			$length_baris2 = count($graph[$h]);
			
			for($n = 0; $n < $length_baris2; $n++)
			{
				if(isset($graph[$h][$n]))
				{
					$str_graph = $graph[$h][$n];
					if( substr($str_graph, (strlen($str_graph)-1), strlen($str_graph)) == "y" ){
						$explode4 = explode('->', $graph[$h][$n]);
						$simpulGabung = $h . "-" . $explode4[0];
						
						array_push($gabungSimpulPilihan, $simpulGabung);
					}
				}// end if cek isi graph != null
			}// end for looping baris
		}// end looping kolom (simpul)
		
		
		// UNTUK MEMASUKKAN SIMPUL YG SUDAH DIURUTKAN (DARI SIMPUL TUJUAN KE SIMPUL AWAL). (NANTI DIREVERSE ARRAYNYA)
		$simpulFix_finish = array();
		
		// MASUKKAN PERTAMA KALI SIMPUL TUJUAN (SIMPUL AKHIR) KE ARRAY DGN INDEX 0. (NANTI DIBALIK(REVERSE) ARRAYNYA)
		array_push($simpulFix_finish, $simpul_tujuan);
		
		$simpul_explode = $simpul_tujuan;
		for($v = 0; $v < 1; $v++)
		{
			for($w = 0; $w < count($gabungSimpulPilihan); $w++)
			{
				$explode_simpul = $gabungSimpulPilihan[$w];
				$explode5 = explode('-', $explode_simpul);
				if($simpul_explode == $explode5[1])
				{
					array_push($simpulFix_finish, $explode5[0]);
					$simpul_explode = $explode5[0];
				}
				if($simpul_explode == $simpul_awal){
					break;
				}
			}
			
			if($simpul_awal != $simpul_explode){
				--$v;
			}else{
				break;
			}
		}// end for cari simpul yang dibuletin lalu dibandingkan dgn simpul_tujuan

		
		// ARRAY DI BALIK INDEXNYA; JADI SIMPUL TUJUAN DI PINDAH POSISI KE AKHIR INDEX ARRAY
		$simpulFix_finish_reverse = array_reverse($simpulFix_finish);
		
		$jalur_terpendek = "";		
		for($x = 0; $x < count($simpulFix_finish_reverse); $x++)
		{
			if($x == (count($simpulFix_finish_reverse)-1))
			{
				$jalur_terpendek .= $simpulFix_finish_reverse[$x];
			}else{
				$jalur_terpendek .= $simpulFix_finish_reverse[$x] . "->";
			}
		}
		
		$json['status'] 	= 'success';
		$json['success'] 	= 'generate_jalur_terpendek';
		$json['teks'] 		= 'Jalur berhasil dibuat';
		$json['content']	= $jalur_terpendek;
		
		return json_encode($json);
		
	}// end function jalurTerpendek
}
?>