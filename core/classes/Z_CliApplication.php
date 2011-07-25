<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 23.12.2010
 * Time: 19:29:49
 */
 
class Z_CliApplication implements iCliApplication {
	protected
		$factory,
		$config,
		$argv,
		$argc;

	function __construct(iFactory $factory, iConfig $config, array $argv = array()){
		if (!ini_get('register_argc_argv')){
			trigger_error("It's required register_argc_argv to be enabled in php.ini", E_USER_ERROR);
		}
		$this->factory = $factory;
		$this->config = $config;
		$this->factory->registerConfig($config);
		$this->argv = $argv;
		$this->argc = count($argv);
	}

	function __destruct(){

	}

	function __get($name){
		if (preg_match('#^[A-Z]#', $name)){
			return $this->$name = $this->factory->getModel($name);
		}
	}


	public function run(){
		
	}

	/** Locks */

	protected function isAlreadyLocked(){
		$lock_time = (int)$this->getLock();
		return ($lock_time >= time() - $this->config['/lock/timeout']);
	}


	protected function getLock(){
		if (is_file($this->config['/lock/file'])){
			return file_get_contents($this->config['/lock/file'], 0, null, 0, 14);
		}
		return null;
	}


	protected function setLock(){
		$result = false;
		$handle = fopen($this->config['/lock/file'], 'w');
		if (fwrite($handle, time())){
			$result = true;
		}
		fclose($handle);
		return $result;
	}


	protected function releaseLock(){
		unlink($this->config['/lock/file']);
	}


	protected function updateLock(){
		$this->setLock();
	}

	
}
