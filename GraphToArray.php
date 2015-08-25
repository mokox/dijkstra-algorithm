<?php
/**
* ==============================================
* KONVERSI GRAPH KE DALAM BENTUK ARRAY 2 DIMENSI
* ==============================================
*
* ALGORITMA DIJKSTRA YANG DIGUNAKAN ADALAH DALAM BENTUK TABEL, SEPERTI CONTOH DIBAWAH :
   --------- ---------- --------- --------- ---------
  |   0     |    1     |    2    |    3    |    4    |
   --------- ---------- --------- --------- ---------
  | 0->1=10 | 1->0=10  | 2->0=11 | 3->4=89 | 4->0=90 |
  | 0->2=11 | 1->2=55  | 2->1=55 |     	   | 4->3=89 |
  | 0->3=40 | 1->4=20  | 2->3=54 |         |         |
   --------- ---------- --------- --------- ---------
* DARI TABEL DI ATAS DIKONVERSI MENJADI :
  [0][0] = '=> 1->10';
  [0][1] = '=> 2->11';
  [0][2] = '=> 3->40';
  
  [1][0] = '=> 0->10';
  [1][1] = '=> 2->55';
  [1][2] = '=> 4->20';
  
  [2][0] = '=> 0->11';
  [2][1] = '=> 1->55';
  [2][3] = '=> 1->54';
  
  [3][0] = '=> 4->89';
  [3][0] = '=> 4->89';
  
  [4][0] = '=> 0->90';
  [4][3] = '=> 3->89';
*/

class GraphToArray
{
	// menampung graph array 2 dimensi 
	public $graph = array(array());
	
	// koneksi DB
	public $koneksi;
	
	/**
	* before Action
	* set CONNECTION
	*/
	function __construct(){
		$k = new Koneksi();
		$this->koneksi = $k->connect();
	}
	
	/**
	* konversi graph tabel ke graph array
	* @return array[][]
	*/
	public function graphArray()
	{		
		// set index baris dan kolom array
		$temp_baris = "";
		$kolom 		= 0;
		
		$select = mysqli_query($this->koneksi, "SELECT * FROM graph order by CONVERT(simpul_awal, SIGNED INTEGER), CONVERT(simpul_tujuan, SIGNED INTEGER) asc");
		while($field = mysqli_fetch_array($select, MYSQL_ASSOC)){

			// baris array
			$baris = $field['simpul_awal'];
			
			// cek index baris; graph[index_baris][..]
			if($temp_baris == ""){
				// pertama kali dijalankan
				$temp_baris = $baris;
			}else{
				// baris berikutnya tidak sama dgn sebelumnya ( graph[0][..] -> graph[1][..] )
				// maka reset kolom = 0 ( graph[0][..] -> graph[1][0] )
				if($temp_baris != $baris){
					$kolom = 0;
					$temp_baris = $baris;
				}
			}
						
			//tidak ada derajat keluar
			$value = "";
			if($field['simpul_tujuan'] == "" && $field['jalur'] == "" && $field['bobot'] == ""){
				// masukkan ke graph array
				$value = ";";
			}
			// ada derajat keluar
			else{		
				// example output : 2->789.98
				// masukkan ke graph array
				$value = $field['simpul_tujuan'] . "->" . $field['bobot']; //simpul_tujuan dan bobot
			}
			
			$this->graph[$baris][$kolom] = $value;
			$kolom++;
		}
		
		return $this->graph;
	}
}
?>