<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 22, 2010
 * Time: 3:45:36 PM
 */
 
interface iFactory {
	function getObjectByInterface($interface);
	function getObjectByClassName($classname);
	function getController($controller_name);
	function getModel($model_name);
	function registerInstance($instance, $instance_name);
	function registerRouter(iRouter $router);
	function registerConfig(iConfig $config);
}


interface iObject {
	function setFactory(iFactory $factory);
	function setConfig(ArrayAccess $config);
	function afterSet();
}


interface iWebApplication {
    function run();
}


interface iCliApplication {
	function run();
}


interface iRouter {
  function getRoute();
	function url(array $params = array());
	function getDomain();

	/**
	 * @abstract
	 * @param string $content_type
	 * @return void
	 */
	function setContentType($content_type);

	/**
	 * @abstract
	 * @return string|null
	 */
	function getContentType();
}


interface iConfig {
	function loadConfig();
	function getItem($path);
}


interface iLogger {
	function logMessage($message);
	function logError($message);
	function write($message, $use_ts = true);
	function writeln($message, $use_ts = true);
	function endln($message);
}


interface iCurl {
	function httpGet($url, array $curl_opts = array(), array $headers = array());
	function httpGetMulti($urls, array $curl_opts = array(), array $headers = array());
	function httpPost($url, array $data = array(), array $curl_opts = array(), array $headers = array());
	function httpRawPost($url, $raw_data = '', array $curl_opts = array(), array $headers = array());
	function lastRequestInfo();
	function lastRequestError();
	function lastRequestHeaders();
}


interface iController {
	function __construct(iFactory $factory, iRouter $router, iConfig $config);
}


interface iInput {
	function get($name, $type = 'string', $default = null);
	function post($name, $type = 'mixed', $default = null);
	function cookie($name, $type = 'string', $default = null);
	function request($name, $type = 'mixed', $default = null);
}


interface iViewer {
	function fetch($template, array $data = array());
}


interface iXMLManager {
	/**
	 * @abstract
	 * @param string $root_node_name
	 * @param array $params
	 * @return simpleXMLElement
	 */
	function createXML($root_node_name, array $params = array());

	/**
	 * @abstract
	 * @param array $data
	 * @return simpleXMLElement
	 */
	function arrayToXML(array $data = array());

	/**
	 * @abstract
	 * @param array $data
	 * @return string
	 */
	function arrayToXMLStr(array $data = array());

	/**
	 * @abstract
	 * @param string $dirty_string
	 * @return simpleXMLElement
	 */
	function dirtyStringToXML($dirty_string);

	/**
	 * @abstract
	 * @param string $dirty_string
	 * @param string $encoding
	 * @return string
	 */
	function dirtyStringToXMLStr($dirty_string, $encoding = 'utf8');

	/**
	 * Find and return first attribute with given name in any case ('href', 'hReF', 'HREF' and any)
	 * @param simpleXMLElement $e
	 * @param string $param_name
	 * @return simpleXMLElement|null
	 */
	public function getDirtyAttribute(simpleXMLElement $e, $param_name);
}


interface iModel {
	
}


interface iDatabase {
	function query($query_string, array $bind = array());
	function lastQuery();
	function insert_id();
	function affected_rows();
	function escape($str);
	function isTableExists($table_name);
}


interface iDBModel {
	function getEmptyItem();
	function getItem($id, $fields = '*');
	function getItemsList($fields = '*', $params = array(), $use_indexes = false);
}