<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 29.12.2010
 * Time: 3:22:15
 */
 
class Z_Input extends Z_Object implements iInput {

	public function get($name, $type = 'string', $default = null){
		return $this->getField($_GET, $name, $type);
	}


	public function post($name, $type = 'mixed', $default = null){
		return $this->getField($_POST, $name, $type);
	}


	public function cookie($name, $type = 'string', $default = null){
		return $this->getField($_COOKIE, $name, $type);
	}


	public function request($name, $type = 'mixed', $default = null){
		return $this->getField($_REQUEST, $name, $type);
	}


	public function rawPostData(){
		return file_get_contents('php://input');
	}


	protected function getField($source, $name, $type='mixed'){
		if (array_key_exists($name, $source)){
			$result = $source[$name];
			
			// We strip slashes if magic quotes is on to keep things consistent
			if (get_magic_quotes_gpc()) {
				array_walk_recursive($result, 'stripslashes');
			}

		} else {
			$result = null;
		}
		if (in_array($type, array('boolean', 'bool', 'integer', 'int', 'float', 'string', 'array', 'object'))){
			settype($result, $type);
		}
		return $result;
	}

}
