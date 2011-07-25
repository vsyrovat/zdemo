<?php
/**
 * User: Master
 * Date: 24.07.11
 * Time: 5:57
 */
 
class Z_ToolApplicationPrototype extends Z_CliApplication {

	function __call($name, $params){
		echo "Unknown command. Availaible commands:\n";
		$this->printMethods();
	}


	protected function printMethods(){
		$ref = new ReflectionObject($this);
		$methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($methods as &$method){
			$method = $method->name;
		}
		$methods = array_diff($methods, array('__call', '__construct', 'run', '__destruct', '__get', ));
		foreach ($methods as &$method){
			$method = preg_replace('#_#', ':', $method, 1);
			$method = preg_replace('#_#', '-', $method, 1);
		}
		sort($methods);
		print (join("\n", $methods));
		print "\n";
	}

	public function run(){
		try{
			if ($this->argc > 1){
				$command = $this->argv[1];
				$command = str_replace(array(':', '-'), '_', $command);
				call_user_func_array(array($this, $command), array_slice($this->argv, 2));
//				$this->$command(array_slice($this->argv, 2));
			} else {
				print "Available commands:\n";
				$this->printMethods();
			}
		} catch (Exception $e){
			echo "Error: ".$e->getMessage();
		}
	}


}
