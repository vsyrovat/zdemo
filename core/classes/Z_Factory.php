<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 22, 2010
 * Time: 4:15:13 PM
 */
 
class Z_Factory implements iFactory {
	protected
		$factory_config, // dependencies config
		$app_config, // application config object
		$dependencies = array(),
		$loaded_instances = array(),
		$loaded_controllers = array(),
		$loaded_models = array(),
		$router;
	
	function __construct(iConfig $config){
		$this->factory_config = $config;
		$this->dependencies = $config['/DEPENDENCIES'];
		$this->instances = $config['/INSTANCES'];
	}


	public function getObjectByInterface($interface){

		/* getting trace */
		$trace = $this->getTrace();

		$point = $this->dependencies;
		foreach ($trace as &$c){
			if (array_key_exists('class', $c)){
				if (array_key_exists('class '.$c['class'], $point)){
					$point = $point['class '.$c['class']];
			  } else {
					foreach (array_keys($point) as $parent_class_string){
						if (substr($parent_class_string, 0, 6) == 'class '){
							$classname = substr($parent_class_string, 6);
							if (is_subclass_of($c['class'], $classname)){
								$point = $point[$parent_class_string];
							}
						}
					}
				}
			} elseif (array_key_exists('interface '.$interface, $point)) {
				$point = $point['interface '.$interface];
				break;
			}
		}

//		print_r ($c);
		if (is_array($point) && array_key_exists('interface '.$interface, $point)){
			$point = $point['interface '.$interface];
		}
//		print_r ($point);

//		if (array_key_exists('interface '.$interface, $point)){
//			$point = &$point['interface '.$interface];
			if (is_scalar($point) && substr($point, 0, 9) == 'instance '){
				$instance_name = substr($point, 9);
				if (array_key_exists($instance_name, $this->loaded_instances)){
					return $this->loaded_instances[$instance_name];
				}
				$class_name = $this->factory_config['/INSTANCES/'.$instance_name.'/class'];
				if ($class_name){
					$instance = new $class_name;
					if (!($instance instanceof $interface)){
						trigger_error('Expected that class '.$class_name.' implements interface '.$interface, E_USER_WARNING);
					}
					if (!($instance instanceof iObject)){
						trigger_error('Expected that class '.$class_name.' implements interface iObject', E_USER_WARNING);
					} else {
						$instance->setFactory($this);
						$config_config = $this->factory_config['/INSTANCES/'.$instance_name.'/config'];
						if (!empty($config_config)){
							if (!empty($config_config['instance'])){
								$config_config['instance'] = $this->loaded_instances[$config_config['instance']];
							}
							$instance->setConfig(new Z_Config($config_config));
						}
						$instance->afterSet();
					}
					$this->loaded_instances[$instance_name] = $instance;
					return $this->loaded_instances[$instance_name];
				} else {
					trigger_error('Not found instance '.$instance_name.' for trace '.$this->traceToStr($trace), E_USER_WARNING);
				}
			} else {
				trigger_error('Not found instance for trace '.$this->traceToStr($trace).', required interface '.$interface, E_USER_WARNING);
			}
//		} else {
//			trigger_error('Not found interface '.$interface.' for trace '.$this->traceToStr($trace), E_USER_WARNING);
//		}
		
	}


	public function getObjectByClassName($classname){

	}


	public function getController($controller_name){
		$controller_name = ucfirst($controller_name);
		$controller_class = $controller_name.'Controller';
		if (!class_exists($controller_class, false)){
			$controller_file = APP_CONTROLLER_PATH . $controller_name .'.php';

			if (RUN_MODE == 'development'){
				if (file_exists($controller_file)){
					include_once ($controller_file);
				} else {
					throw new ClassNotFoundException('Not found controller file '.$controller_file);
				}
				if (!class_exists($controller_class, false)){
					throw new ClassNotFoundException('Not found controller class '.$controller_class);
				}
				$reflection = new ReflectionClass($controller_class);
				if (!$reflection->implementsInterface('iController')){
					trigger_error("It's expected class $controller_class implements iController", E_USER_WARNING);
				}
			} else {
				include_once ($controller_file);
			}

			$instance = new $controller_class($this, $this->router, $this->app_config);
			$this->loaded_controllers[$controller_class] = $instance;
		} else {
			if (array_key_exists($controller_class, $this->loaded_controllers)){
				$instance = $this->loaded_controllers[$controller_class];
			}
		}
		return $instance;
	}


	public function getModel($model_name){
		$instance_name = strtolower($model_name).'_model';
		$model_name = ucfirst($model_name);
		$model_class = $model_name.'Model';
		if (!class_exists($model_class, false)){
			$model_file = APP_MODEL_PATH . $model_name .'.php';

			if (RUN_MODE == 'development'){
				if (file_exists($model_file)){
					include_once ($model_file);
				} else {
					throw new ClassNotFoundException('Not found model file '.$model_file);
				}
				if (!class_exists($model_class, false)){
					throw new ClassNotFoundException('Not found controller class '.$model_class);
				}
				$reflection = new ReflectionClass($model_class);
				if (!$reflection->implementsInterface('iModel')){
					trigger_error("It's expected class $model_class implements iModel", E_USER_WARNING);
				}
				if (!$reflection->implementsInterface('iObject')){
					trigger_error("It's expected class $model_class implements iObject", E_USER_WARNING);
				}
			} else {
				include_once ($model_file);
			}

			$instance = new $model_class();
			$instance->setFactory($this);
			$config_config = $this->factory_config['/INSTANCES/'.$instance_name.'/config'];
			if (!empty($config_config)){
				if (!empty($config_config['instance'])){
					$config_config['instance'] = $this->loaded_instances[$config_config['instance']];
				}
				$instance->setConfig(new Z_Config($config_config));
			}
			$instance->afterSet();
			$this->loaded_models[$model_class] = $instance;
		} else {
			if (array_key_exists($model_class, $this->loaded_models)){
				$instance = $this->loaded_models[$model_class];
			}
		}
		return $instance;
	}


	public function registerInstance($instance, $instance_name){
		$this->loaded_instances[$instance_name] = $instance;
	}


	public function registerRouter(iRouter $router){
		$this->router = $router;
	}


	public function registerConfig(iConfig $config){
		$this->app_config = $config;
	}


	protected function traceToStr($trace){
		$result = array();
		foreach ($trace as $c){
			$result[] = $c['class'];
		}
		return '/'.join('/', $result);
	}


	protected function getTrace(){
		$trace = debug_backtrace();
		if ((int)$this->factory_config['/offsets/down'] > 0){
			$trace = array_slice($trace, (int)$this->factory_config['/offsets/up'], -(int)$this->factory_config['/offsets/down']);
		} else {
			$trace = array_slice($trace, (int)$this->factory_config['/offsets/up']);
		}
		foreach ($trace as $k=>&$t){
			if (!empty($t['class']) && $t['class'] == __CLASS__){
				unset($trace[$k]);
			}
		}
		unset($t);
		$trace = array_reverse($trace);
//		print_r ($trace);
		$merge_deep = 0;
		$merges_stack_ids = array();
		foreach ($trace as $k=>$t){
			if (in_array($t['function'], array('call_user_func', 'call_user_func_array', 'call_user_method', 'call_user_method_array')) && !array_key_exists('class', $t)){
				array_push($merges_stack_ids, $k);
				$merge_deep++;
				continue;
			}
			if ($merge_deep){
				$prev_k = array_pop($merges_stack_ids);
				$trace[$prev_k]['function'] = $t['function'];
				$trace[$prev_k]['class'] = $t['class'];
				$trace[$prev_k]['type'] = $t['type'];
				$trace[$prev_k]['object'] = $t['object'];
				$trace[$prev_k]['args'] = $t['args'];
				unset($trace[$k]);
				$merge_deep--;
			}
		}
		unset($t);
		$prev_k = null;
		foreach ($trace as $k=>$t){
			if ($prev_k === null){
				$prev_k = $k;
				continue;
			}
			if ($t['object'] === $trace[$prev_k]['object']){
				if ($t['function'] === '__get'){
					unset($trace[$k]);
				} else {
					unset($trace[$prev_k]);
					$prev_k = $k;
				}
			}
		}
//		$xtrace = $trace;
//		foreach ($xtrace as $k=>&$t){
//			if (!empty($t['args'])) $t['args'] = 'hidden for debug...';
//			if (!empty($t['object'])) $t['object'] = 'hidden for debug, instance of '.get_class($t['object']);
//		}
//		unset($t);
//		print_r ($xtrace);
		foreach ($trace as $k=>&$t){
			$t['class'] = get_class($t['object']);
		}
		unset($t);
		return $trace;
	}

	
	public function getDebugTrace(){
		$trace = $this->getTrace();
		return $this->traceToStr($trace);
	}

}