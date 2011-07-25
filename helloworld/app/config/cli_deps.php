<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 24.12.2010
 * Time: 6:57:26
 */
 
return array(
	'offsets' => array(
		'down' => 0,
		'up' => 1,
	),

	'INSTANCES' => array(
//		'logger' => array('class' => 'Z_Logger', 'config' => array('file' => APP_CONFIG_PATH.'config.php', 'branch'=>'/logs/cli')),
		'printer_model' => array('config' => null),
	),

	'DEPENDENCIES' => array(
//		'class CliApplication' => array(
//			'interface iLogger' => 'instance logger',
//			'interface iXMLManager' => 'instance xmlman',
//			'interface iDatabase' => 'instance db1'
//		),
	)
);