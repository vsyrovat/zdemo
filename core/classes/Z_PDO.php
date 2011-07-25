<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 04.01.2011
 * Time: 21:21:51
 */
 
class Z_PDO extends Z_Object implements iDatabase {
	protected
		$pdo = null;

	protected function initPDO(){
		if ($this->pdo === null){
			$this->pdo = new PDO($this->config['/connection_string'], $this->config['/username'], $this->config['/password']);
		}
	}

	function query($query_string, array $bind = array()){
		$this->initPDO();
		$stmt = $this->pdo->prepare($query_string);
		foreach ($bind as $b){
			
		}
	}

}
