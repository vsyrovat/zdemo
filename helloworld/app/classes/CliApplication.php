<?php
/**
 * User: Master
 * Date: 24.07.11
 * Time: 4:48
 */
 
class CliApplication extends Z_CliApplication {

	function run(){
		println('Hello World!');
		$this->hi();
		$this->Printer->hi(); // Вызов модели
	}

	protected function hi(){
		println("Hi, I'm instance of ".__CLASS__." class ^__^");
	}

}
