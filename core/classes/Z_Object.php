<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 24.12.2010
 * Time: 23:25:16
 */
 
class Z_Object implements iObject {
	protected
		$factory,
		$config;

	public function afterSet(){}


	public function setFactory(iFactory $factory){
		$this->factory = $factory;
	}


	public function setConfig(ArrayAccess $config){
		$this->config = $config;
	}

}
