<?php
/**
 * User: Master
 * Date: 18.01.2011
 * Time: 2:33:58
 */
 
class Z_ArrayClass extends stdClass implements arrayAccess {

	function __construct(array $data){
		foreach ($data as $key=>$value){
			$this->$key = $value;
		}
	}

	function __get($var){
		return null;
	}

	public function offsetExists($offset) {
		return property_exists($this, $offset);
	}

	public function offsetGet($offset) {
		return $this->$offset;
	}

	public function offsetSet($offset, $value) {
		$this->$offset = $value;
	}

	public function offsetUnset($offset) {
		unset ($this->$offset);
	}
}
