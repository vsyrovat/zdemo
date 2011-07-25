<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 22, 2010
 * Time: 4:27:53 PM
 */
 
define ('APP_PATH', dirname(__FILE__) . DS);
define ('APP_CONFIG_PATH', APP_PATH . 'config' . DS);
define ('APP_CONTROLLER_PATH', APP_PATH . 'controllers'.DS);
define ('APP_VIEWS_PATH', APP_PATH . 'views'.DS);
define ('APP_MODEL_PATH', APP_PATH . 'models' . DS);
define ('APP_LOG_PATH', realpath(APP_PATH . '..').DS.'logs'.DS);
define ('APP_TMP_PATH', realpath(APP_PATH . '..').DS.'tmp'.DS);

define ('RUN_MODE', include APP_CONFIG_PATH . 'run_mode.php');

switch (RUN_MODE){
	case 'development': case 'test':
    error_reporting (E_ALL | E_STRICT);
    break;
	case 'production': default:
    error_reporting (E_NONE);
}

require APP_PATH . 'config/constants.php';
require APP_PATH . 'interfaces.php';