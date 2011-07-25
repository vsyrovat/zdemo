<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 28.12.2010
 * Time: 15:28:31
 */
 
class Z_Controller implements iController {
	protected
		$factory,
		$router,
		$config,
		$input;


	function __construct(iFactory $factory, iRouter $router, iConfig $config){
		$this->factory = $factory;
		$this->router = $router;
		$this->config = $config;
		$this->input = $factory->getObjectByInterface('iInput');
	}


	function __call($name, $params){
		throw new Route404Exception('no such action: '.$name);
	}


	function __get($name){
		if (preg_match('#^[A-Z]#', $name)){
			return $this->$name = $this->factory->getModel($name);
		}
	}


}
