<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 24.12.2010
 * Time: 9:11:40
 */
 
class Z_Curl extends Z_Object implements iCurl {
	protected
		$default_opts = array(),
		$last_info,
		$last_error,
		$last_headers = array(),
		$last_multi_info = array(),
		$last_multi_error = array(),
		$last_multi_headers = array(),
		$curl_map = array();


	function __construct(){
		$this->default_opts = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)',
//			CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)',
//			CURLOPT_REFERER => 'http://www.google.com',
			CURLOPT_CONNECTTIMEOUT => 0,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_ENCODING => 'gzip,deflate',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_HEADER => array(
//				"Accept-Language: en-us,en;q=0.5"
			),
			CURLOPT_AUTOREFERER => true,
			CURLOPT_FAILONERROR => true,
		);
	}


	public function httpGet($url, array $curl_opts = array(), array $headers = array()){
		$this->last_headers = array();
		$ch = curl_init($url);
		$mandatory_opts = array(
			CURLOPT_HTTPGET => true,
			CURLOPT_HEADERFUNCTION => array(&$this, 'saveHeadersCallback'),
		);
		if (!empty($headers)){
			$mandatory_opts[CURLOPT_HTTPHEADER] = $headers;
		}
		$curl_opts = array_replace($this->default_opts, $curl_opts, $mandatory_opts);
		curl_setopt_array($ch, $curl_opts);
		$output = curl_exec($ch);
		$this->last_info = curl_getinfo($ch);
		$this->last_error = curl_error($ch);
		curl_close($ch);
		return $output;
	}


	public function httpGetMulti($urls, array $curl_opts = array(), array $headers = array()){
		$this->last_multi_info = array();
		$this->last_multi_headers = array();
		$this->curl_map = array();
		$result = array();
		$ch = array();
		$this->last_multi_error = array();
		$mandatory_opts = array(
			CURLOPT_HTTPGET => true,
			CURLOPT_HEADERFUNCTION => array(&$this, 'saveHeadersMultiCallback'),
		);
		$curl_opts = array_replace($this->default_opts, $curl_opts, $mandatory_opts);
		if (!empty($headers)){
			$mandatory_opts = array(CURLOPT_HTTPHEADER => $headers);
			$curl_opts = array_replace($curl_opts, $mandatory_opts);
		}
		$mh = curl_multi_init();
		foreach ($urls as $k=>$url){
			$this->last_multi_headers[$k] = array();
			$ch[$k] = curl_init($url);
			$this->curl_map[$k] = $ch[$k];
			curl_setopt_array($ch[$k], $curl_opts);
			curl_multi_add_handle($mh, $ch[$k]);
		}
		$active = null;
		// start performing the request
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			// wait for network
			if (curl_multi_select($mh) != -1) {
				// pull in any new data, or at least handle timeouts
				do {
					$mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}

		if ($mrc != CURLM_OK) {
			$this->last_error = "Curl multi read error $mrc";
		}

		foreach ($urls as $k=>$url){
			if (($err = curl_error($ch[$k])) == '') {
				$result[$k] = curl_multi_getcontent($ch[$k]);
				$this->last_multi_error[$k] = null;
			} else {
				$result[$k] = null;
				$this->last_multi_error[$k] = "Curl error on handle $k: $err";
			}
			$this->last_multi_info[$k] = curl_getinfo($ch[$k]);
			curl_multi_remove_handle($mh, $ch[$k]);
			curl_close($ch[$k]);
		}
		return $result;
	}


	public function httpPost($url, array $data = array(), array $curl_opts = array(), array $headers = array()){
		
	}


	public function httpRawPost($url, $raw_data = '', array $curl_opts = array(), array $headers = array()){
		$this->last_headers = array();
		$raw_data = (string)$raw_data;
		$headers[] = 'Content-Type: text/plain';
		$ch = curl_init($url);
		$mandatory_opts = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $raw_data,
			CURLOPT_HEADERFUNCTION => array(&$this, 'saveHeadersCallback'),
		);
		if (!empty($headers)){
			$mandatory_opts[CURLOPT_HTTPHEADER] = $headers;
		}
		$curl_opts = array_replace($this->default_opts, $curl_opts, $mandatory_opts);
		curl_setopt_array($ch, $curl_opts);
		$output = curl_exec($ch);
		$this->last_info = curl_getinfo($ch);
		$this->last_error = curl_error($ch);
		curl_close($ch);
		return $output;

	}

	/**
	 * @return string
	 */
	public function lastRequestInfo(){
		return $this->last_info;
	}

	/**
	 * @return string
	 */
	public function lastRequestError(){
		return $this->last_error;
	}

	/**
	 * @return array
	 */
	public function lastRequestHeaders(){
		return $this->last_headers;
	}


	public function lastRequestMultiInfo(){
		return $this->last_multi_info;
	}


	public function lastRequestMultiErrors(){
		return $this->last_multi_error;
	}

	/**
	 * @return array of array
	 */
	public function lastRequestMultiHeaders(){
		return $this->last_multi_headers;
	}

	/**
	 * Callback for CURLOPT_HEADERFUNCTION for singlemode requests
	 * @param resource $ch
	 * @param string $header
	 * @return int
	 */
	protected function saveHeadersCallback($ch, $header){
		if (trim($header) != ''){
			$this->last_headers[] = trim($header);
		}
		return strlen($header);
	}

	/**
	 * Callback for CURLOPT_HEADERFUNCTION for multimode requests
	 * @param resource $ch
	 * @param string $header
	 * @return int
	 */
	protected function saveHeadersMultiCallback($ch, $header){
		if (trim($header) != ''){
			$k = array_search($ch, $this->curl_map);
			if ($k !== false){
				$this->last_multi_headers[$k][] = trim($header);
			}
		}
		return strlen($header);
	}

}