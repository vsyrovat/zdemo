<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 24.12.2010
 * Time: 6:46:18
 */
 
class Z_Logger extends Z_Object implements iLogger {
	protected
		$logfile_handler;


	public function afterSet(){
		$this->logfile_handler = fopen($this->config['/logfile'], 'a');
	}


	function __destruct(){
		if ($this->logfile_handler){
			fclose($this->logfile_handler);
		}
	}


	public function logMessage($message){
		$ts = date('r');
		$logstring = "$ts: $message\r\n";
		fwrite($this->logfile_handler, $logstring);
	}


	public function logError($message){
		
	}


	public function write($message, $use_ts = true){
		if ($use_ts){
			$ts = date('r');
			$logstring = "$ts: $message";
			fwrite($this->logfile_handler, $logstring);
		} else {
			fwrite($this->logfile_handler, $message);
		}
	}


	public function writeln($message, $use_ts = true){
		$this->write($message."\r\n", $use_ts);
	}


	public function endln($message){
		$this->writeln($message, false);
	}

}
