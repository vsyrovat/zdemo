<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 22, 2010
 * Time: 4:26:04 PM
 */
 
return array(
	'offsets' => array(
		'down' => 0,
		'up' => 1,
	),

	'INSTANCES' => array(
//		'main_config' => array('class' => 'Z_Config'),
		'request' => array('class' => 'Z_Input', 'config' => array()),
		'viewer' => array('class' => 'Z_Viewer', 'config' => array()),
		'foo_model' => array('config' => null),
	),

	'DEPENDENCIES' => array(
		'class Z_WebApplication' => array(
			'interface iInput' => 'instance request',
			'interface iViewer' => 'instance viewer',
			'controllers' => array(

			),
			'models' => array(

			),
		)
	)
);