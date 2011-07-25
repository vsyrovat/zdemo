<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 21, 2010
 * Time: 5:10:42 PM
 */
 
require dirname(__FILE__).'/../../core/core.php';
require dirname(__FILE__).'/../app/app.php';

$router = new Z_Router(APP_CONFIG_PATH . 'routes.php');

$config = new Z_Config(APP_CONFIG_PATH.'config.php', APP_CONFIG_PATH.'run_modes/'.RUN_MODE.'.php');

$factory = new Z_Factory(new Z_Config(APP_CONFIG_PATH . 'web_deps.php'));
$factory->registerInstance($config, 'main_config');

$application = new WebApplication($factory, $config, $router);

$application->run();