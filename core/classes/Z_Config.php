<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 24.12.2010
 * Time: 7:16:05
 */
 
class Z_Config implements iConfig, ArrayAccess {
	protected
		$config = array();


	function __construct(){
		$params = func_get_args();
		call_user_func_array(array($this, 'loadConfig'), $params);
	}


	/* iConfig implementation */

	function loadConfig(){
		$files = func_get_args();
		foreach ($files as $file){
			if (is_scalar($file)){
				$bim = include $file;
			} else {
				if (!empty($file['instance'])){
					if (!is_object($file['instance'])){
						throw new Exception('Param instance should be an object');
					}
					if (!($file['instance'] instanceof iConfig)){
						throw new Exception('Param instance should implements the iConfig interface');
					}
					$bim = $file['instance']->getItem($file['branch']);
				} else {
					$bim = include $file['file'];
					$bim = $this->getItemHelper($file['branch'], $bim);
				}
			}
			$this->config = array_replace_recursive($this->config, $bim);
		}
	}


	public function getItem($path){
		return $this->getItemHelper($path);
	}


	protected function getItemHelper($path, array $source_data = array()){
		$path = trim($path);
		if ($path == '/'){
			if (empty($source_data)){
				return $this->config;
			} else {
				return $source_data;
			}
		} elseif ($path == '') {
			return null;
		} else {
			$ps = explode('/', trim($path, '/'));
			if (empty($source_data)){
				$c = &$this->config;
			} else {
				$c = &$source_data;
			}
			foreach ($ps as $p){
				if (is_array($c) && array_key_exists($p, $c)){
					$c = &$c[$p];
				} else {
					trigger_error('Not found config path '.$path, E_USER_NOTICE);
					return null;
				}
			}
			return $c;
		}
	}


	/* ArrayAccess implementation */

	public function offsetExists($offset){}


	public function offsetGet($offset){
		return $this->getItem($offset);

	}


	public function offsetSet ($offset, $value){}


	public function offsetUnset ($offset){}

}
