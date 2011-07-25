<?php

require dirname(__FILE__).'/../../core/core.php';
require dirname(__FILE__).'/app.php';

function println($message){
	echo "$message\n";
}

$config = new Z_Config(APP_CONFIG_PATH.'config.php', APP_CONFIG_PATH.'run_modes/'.RUN_MODE.'.php');

$factory = new Z_Factory(new Z_Config(APP_CONFIG_PATH . 'cli_deps.php'));
$factory->registerInstance($config, 'main_config');

$application = new CliApplication($factory, $config, $argv);

$application->run();