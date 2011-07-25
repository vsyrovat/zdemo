<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 21, 2010
 * Time: 6:25:41 PM
 */

if (version_compare(PHP_VERSION, '5.2.0', '<')){
	die ('Requires PHP version 5.2.0 or higher');
}

define ('DS', DIRECTORY_SEPARATOR);
defined ('CORE_PATH') or define('CORE_PATH', dirname(__FILE__) . DS);

require CORE_PATH . 'includes/interfaces.php';
require CORE_PATH . 'includes/exceptions.php';
require CORE_PATH . 'includes/compability.php';

function __autoload ($classname){

    if (substr($classname, 0, 2) == 'Z_'){
	    $file = CORE_PATH . 'classes' . DS . $classname . '.php';
    } elseif (substr($classname, -10) == 'Controller') {
	    $file = APP_PATH . 'controllers' . DS . $classname . '.php';
    } else {
        $file = APP_PATH . 'classes' . DS . $classname . '.php';
    }
   if (file_exists($file)){
		include_once $file;
   } else {
	   throw new ClassNotFoundException('file '.$file.' not found');
   }

}


function zErrorHandler($errno, $errmsg, $filename, $linenum, $vars){
    // timestamp for the error entry
    $dt = date("Y-m-d H:i:s (T)");

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
                E_ERROR              => 'Error',
                E_WARNING            => 'Warning',
                E_PARSE              => 'Parsing Error',
                E_NOTICE             => 'Notice',
                E_CORE_ERROR         => 'Core Error',
                E_CORE_WARNING       => 'Core Warning',
                E_COMPILE_ERROR      => 'Compile Error',
                E_COMPILE_WARNING    => 'Compile Warning',
                E_USER_ERROR         => 'User Error',
                E_USER_WARNING       => 'User Warning',
                E_USER_NOTICE        => 'User Notice',
                E_STRICT             => 'Runtime Notice',
                E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
                );
    // set of errors for which a var trace will be saved
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

	$e = new EmulateException($errortype[$errno].': '.$errmsg);
	$e->setFile($filename);
	$e->setLine($linenum);
	throw $e;
	return true;
}

set_error_handler('zErrorHandler');


function zExceptionHandler(Exception $e){
	echo 'Catched exception: '.$e->getMessage().' in '.$e->getFile().' '.$e->getLine();
}

//set_exception_handler('zExceptionHandler');