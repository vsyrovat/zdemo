<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 22, 2010
 * Time: 5:13:31 PM
 */
 
class Z_Router implements iRouter {
	protected
		$routes,
		$custom_content_type = null;


	function __construct($routefile){
			$this->routes = include $routefile;
	}


	public function getRoute(){
		$uri = $this->getRequestURI();
		foreach ($this->routes as $route_rule => $route){
			if ($this->match($route_rule, $uri) || $route_rule == '*'){
				$route = $this->getRouteVars($route_rule, $uri);
				return $route;
			}
		}
		throw new Route404Exception('not found rule');
	}


	public function url(array $params = array()){
		if (empty($params['controller'])){
			$x = debug_backtrace(true);
			$point = null;
			foreach ($x as &$r){
				if ($r['object'] instanceof iController && substr($r['class'], -10) == 'Controller'){
					$point = $r;
				}
			}
			if ($point){
				$params['controller'] = strtolower(substr($point['class'], 0, -10));
			}
		} else {
			if (empty($params['action'])){
				$params['action'] = 'index';
			}
		}

		if (!array_key_exists('action', $params)){
			$params['action'] = $point['function'];
		}
		if (empty($params['action'])){
			$params['action'] = 'index';
		}

		$test_params = array();
		foreach($params as $key=>$value){
			$test_params[':'.$key] = $value;
		}

		foreach ($this->routes as $rule=>$route){
			if (preg_match_all('#:(\w+)#', $rule, $matches)){
				$m = $matches[1];
//				$n = array_fill_keys($m, '');
//				$test_params = array_replace($n, $test_params);
				$test_url = strtr($rule, $test_params);
				if ($this->match($rule, $test_url)){
					$test_route_vars = $this->getRouteVars($rule, $test_url);
					$m_keys = array_keys($m);
					$params_keys = array_keys($params);
					if (array_intersect($m, $params_keys) == $m){
						$m2 = array_flip(array_merge($m, array('controller', 'action')));
						$params2 = array_intersect_key($params, $m2);
						$test_route_vars2 = array_intersect_key($test_route_vars, $m2);
						if ($test_route_vars2 == $params2){
							if ($test_params[':action'] == 'index'){
								$test_params[':action'] = '';
							}
							$url = strtr($rule, $test_params);
							$get_params = array_diff_key($params, $m2);
							if (!empty($get_params)){
								$url .= '?'.http_build_query($get_params);
							}
							return $url;
						}
					}
				}
			}
		}
		return null;
	}


	public function fullUrl(array $params = array()){
		$url = $this->url($params);
		if (empty($params['protocol'])){
			$params['protocol'] = 'http';
		}
		if (empty($params['domain'])){
			$params['domain'] = $_SERVER['HTTP_HOST'];
		}
		$full_url = $params['protocol'].'://'.$params['domain'].$url;
		return $full_url;
	}

		/**
	 * @param string $route_condition
	 * @param string $uri
	 * @return bool
	 */
	protected function match($route_condition, $uri){
		if (strpos($route_condition, ':') !== false){
			$route_segments = explode('/', ltrim($route_condition, '/'));
			$uri_segments = explode('/', ltrim($uri, '/'));
			if (count($route_segments) == count($uri_segments)){
				$xx = array_combine($route_segments, $uri_segments);
				foreach ($xx as $k=>$v){
					if (!(substr($k, 0, 1) == ':' && strpos($k, ':', 1) == false) && ($k != $v)){
						return false;
					}
				}
				return true;
			} else {
				return false;
			}

		} else {
			if ($uri == $route_condition){
				return true;
			}
		}
		return false;
	}



	protected function getRouteVars($route_rule, $uri){
		if (!array_key_exists($route_rule, $this->routes)){
			throw new Exception;
		}
		$route = $this->routes[$route_rule];
		if ($route_rule != '*'){
			$route_segments = explode('/', ltrim($route_rule, '/'));
			$uri_segments = explode('/', ltrim($uri, '/'));
			$xx = array_combine($route_segments, $uri_segments);
			foreach ($xx as $k=>$v){
				if (substr($k, 0, 1) == ':' && strpos($k, ':', 1) == false){
					$route[substr($k, 1)] = $v;
				} elseif ($k != $v) {
					throw new Exception;
				}
			}
		}
		if (empty($route['action'])){
			$route['action'] = 'index';
		}
		if (empty($route['type'])){
			$route['type'] = 'html';
		}
		$route['domain'] = $_SERVER['HTTP_HOST'];
//		if (empty($route['content-type'])){
//			$route['content-type'] = 'text/html';
//		}
		return $route;
	}


	protected function getRequestURI(){
		$ru = $_SERVER['REQUEST_URI'];
		$ru = explode('?', $ru);
		$ru = reset($ru);
		$ru = trim($ru);
		return $ru;
	}


	public function getDomain(){
		return $_SERVER['HTTP_HOST'];
	}


	public function setContentType($content_type){
		$this->custom_content_type = $content_type;
	}


	public function getContentType(){
		return $this->custom_content_type;
	}

}