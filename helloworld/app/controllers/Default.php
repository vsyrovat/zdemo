<?php
/**
 * User: Master
 * Date: 26.07.11
 * Time: 3:59
 */
 
class DefaultController extends Z_Controller {

	function index(){
		$bar = $this->Foo->bar(); // Обращаемся к модели

		return compact('bar');
	}
	
}