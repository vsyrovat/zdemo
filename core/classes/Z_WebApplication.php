<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 22, 2010
 * Time: 4:40:19 PM
 */
 
class Z_WebApplication implements iWebApplication {
	protected $factory, $router, $config, $viewer;

	function __construct(iFactory $factory, iConfig $config, iRouter $router){
		$factory->registerRouter($router);
		$factory->registerConfig($config);
		$this->factory = $factory;
		$this->router = $router;
		$this->config = $config;
		$this->viewer = $factory->getObjectByInterface('iViewer');
	}


	public function run(){
		if (empty($_SERVER['SERVER_PROTOCOL'])){
			$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
		}
		try {
//			ob_start();
			$route = $this->router->getRoute();
			try {
				$controller = $this->factory->getController($route['controller']) ;
			} catch (ClassNotFoundException $e){
				throw new Route404Exception($e->getMessage());
			}
			$data = $this->runAction($controller, $route['action']);
//			$buffer = ob_get_contents();
//			ob_end_clean();
			$content_type = 'text/html';
			$content_types = array(
				'xml' => 'application/xml',
				'rss' => 'applicaton/rss+xml',
				'rss+xml'=> 'application/rss+xml',
				'json' => 'application/json',
				'html' => 'text/html',
				'text' => 'text/plain',
			);
			if (array_key_exists($route['type'], $content_types)){
				$content_type = $content_types[$route['type']];
			}
			$mct = $this->router->getContentType();
			if ($mct){
				$content_type = $mct;
			}
			if (!empty($content_type)){
				header('Content-Type: '.$content_type);
			}
			switch ($route['type']){

				case 'xml':
					echo $data;
			    break;

				case 'rss': case 'rss+xml':
					echo $data;
			    break;

				case 'json':
					echo json_encode($data);
			    break;

				case 'raw':
					echo $data;
			    break;

				case 'html': case 'text': default:
					if (!is_array($data)){
						$data = (array)$data;
					}
					$view = APP_VIEWS_PATH . $route['controller'] . DS . $route['action'].'.php';
					$content = $this->viewer->fetch($view, $data);
					echo $content;
			    
			}
//			echo $buffer;
		} catch (Route404Exception $e){
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
		} catch (Route403Exception $e){
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		} catch (Exception $e){
			header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
			if (RUN_MODE == 'development'){
				print $e->getMessage();
			}
		}
	}


	protected function runAction($controller, $action){
		if (empty($action)){
			$action = 'index';
		}
		return $controller->$action();
	}


}