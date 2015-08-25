<?php
class Koneksi{
	public $db = "dijkstra";
	
	public function connect(){
		return mysqli_connect('localhost', 'root', 'abcd.123', $this->db);
	}
	
	public function connectPDO(){
		return $conn = new PDO("mysql:host=localhost;dbname=$this->db", 'root', 'abcd.123');
	}
}
?>