<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 05.01.2011
 * Time: 20:38:58
 */
 
class Z_Mysqli_result extends mysqli_result {

	/**
	 * @param int $resulttype
	 * @return array  
	 */
	public function fetch_all($resulttype = MYSQLI_ASSOC, $keyfield = null){
		if (method_exists('mysqli_result', __FUNCTION__)){
			return parent::fetch_all();
		}
		$result = array();
		while($row = $this->fetch_array($resulttype)){
			if ($keyfield !== null && array_key_exists($keyfield, $row)){
				$result[$row[$keyfield]] = $row;
			} else {
				$result[] = $row;
			}
		}
		return $result;
	}


	public function result_array($keyfield = null){
		return $this->fetch_all(MYSQLI_ASSOC, $keyfield);
	}


	/* Выбрать столбец в массив */
	public function column_array($column){
		$result = array();
		while ($row = $this->fetch_assoc()){
			$result[] = $row[$column];
		}
		return $result;
	}


	public function num_rows(){
		return $this->num_rows;
	}
}
