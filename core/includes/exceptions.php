<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 28.12.2010
 * Time: 17:02:22
 */
 
class RouteException extends Exception {}
class Route404Exception extends RouteException {}
class Route403Exception extends RouteException {}

class ClassNotFoundException extends Exception {}

class EmulateException extends Exception {

	public function setFile($file){
		$this->file = $file;
	}


	public function setLine($line){
		$this->line = $line;
	}

}

class DatabaseException extends Exception {};