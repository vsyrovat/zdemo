<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 22, 2010
 * Time: 5:01:23 PM
 */
 
return array(

	'/' => array('controller' => 'default', 'action'=>'index'),
	'/:action' => array('controller'=>'default'),
	'/:controller/:action' => array(),
	'/:controller/:action/:id' => array(),
	'404' => array('controller' => 'default', 'action' => 'page404'),

);