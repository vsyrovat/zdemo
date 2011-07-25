<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 30.12.2010
 * Time: 22:08:00
 */

require dirname(__FILE__).'/../../core/core.php';
require dirname(__FILE__).'/app.php';

$config = new Z_Config(APP_CONFIG_PATH.'config.php', APP_CONFIG_PATH.'run_modes/'.RUN_MODE.'.php');

$factory = new Z_Factory(new Z_Config(APP_CONFIG_PATH . 'cli_deps.php'));
$factory->registerInstance($config, 'main_config');

$ztool = new ToolApplication($factory, $config, $argv);

$ztool->run();