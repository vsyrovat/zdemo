<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 23.12.2010
 * Time: 19:24:55
 */

return array(

	'logs' => array(
		'cli' => array(
			'logfile' => APP_LOG_PATH . 'cli.log',
			'errorfile' => APP_LOG_PATH . 'cli_error.log',
		),
		'web' => array(
			'logfile' => APP_LOG_PATH . 'web.log',
			'errorfile' => APP_LOG_PATH .'web_error.log',
		),
	),


);