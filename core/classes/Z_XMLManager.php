<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 04.01.2011
 * Time: 4:22:35
 */
 
class Z_XMLManager extends Z_Object implements iXMLManager {

	public function createXML($root_node_name, array $params = array()){
		$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$root_node_name />");
		return $xml;
	}


	public function arrayToXML(array $data = array()){
		return simplexml_load_string($this->arrayToXMLStr($data));
	}


	public function arrayToXMLStr(array $data = array()){
		$result = "";
		if (count($data) == 0){
			return '';
		}
		if (count($data) > 1){
			trigger_error('Expected to be one item on first level in $data', E_USER_NOTICE);
		}
		$data2 = (array)reset($data);
		$root_node_name = key($data);

		$result = "<?xml version='1.0' encoding='utf-8'?><$root_node_name>".$this->hashToNodeStr($data2)."</$root_node_name>";
		return $result;
	}

	/**
	 * @param  $dirty_string
	 * @return simpleXMLElement
	 */
	public function dirtyStringToXML($dirty_string){
		$xml_string = $this->dirtyStringToXMLStr($dirty_string);
		$result = simplexml_load_string($xml_string);
		return $result;
	}


	public function dirtyStringToXMLStr($dirty_string, $encoding = 'utf8'){
		static $tidy = null;
		if ($tidy === null){
			$tidy = new tidy;
		}
		$tidy_config = array(
			'indent' => false,
			'input-xml' => false,
			'output-xml' => true,
			'wrap' => 1000,
			'bare'=>true,
//			'clean'=>true,
			'drop-empty-paras' => true,
			'drop-proprietary-attributes' => true,
			'escape-cdata' => true,
			'fix-backslash' => true,
			'fix-bad-comments' => true,
			'hide-comments' => true,
			'quote-nbsp' => true,
//			'word-2000' => true,
		); 

		$dirty_string = preg_replace('#[[:cntrl:]]+#u', '', $dirty_string);
		$dirty_string = trim($dirty_string);
		$dirty_string = preg_replace('#^<!DOCTYPE\s+[^>]*>#i', '', trim($dirty_string));
		if (!stripos($dirty_string, '<html')){
			$dirty_string = '<html>'.$dirty_string.'</html>';
		}
		$dirty_string = preg_replace('#<!(--)?\[if[^>]*>.*?<!\[endif[^>]*>#ius', '', $dirty_string);
/*		$dirty_string = preg_replace(array('#<(\w+):\w+#', '#</(\w+):\w+#'), array('<$1', '</$1'), $dirty_string);*/
		$dirty_string = preg_replace('#<\?.*?\?>#ius', '', $dirty_string);
//		if (stripos($dirty_string, '<!--') !== false){
//			$dirty_string = preg_replace('#<!--.*?-->#us', '', $dirty_string);
//		}
		$tidy->parseString($dirty_string, $tidy_config, $encoding);
		$tidy->cleanRepair();
		$xml_string = tidy_get_output($tidy);
		$xml_string = $this->replaceEntities($xml_string);
		$xml_string = '<?xml version="1.0" encoding="UTF-8"?>'.$xml_string;
		return $xml_string;
	}

	/**
	 * Find and return first attribute with given name in any case ('href', 'hReF', 'HREF' and any)
	 * @param simpleXMLElement $e
	 * @param string $param_name
	 * @return simpleXMLElement|null
	 */
	public function getDirtyAttribute(simpleXMLElement $e, $param_name){
		$param_name = strtolower($param_name);
		foreach ($e->attributes() as $a=>$b){
			if (strtolower($a) == $param_name){
				return $b;
			}
		}
		return null;
	}


	protected function hashToNodeStr(array $data){
		$result = '';
		foreach($data as $key=>$value){
			if (is_array($value)){
				if (!$this->isAllKeysNumeric($value) || count($value) == 0){
					$result .= "<$key>".$this->hashToNodeStr($value)."</$key>";
				} else {
					$result .= $this->arrayToNodeStr($value, $key);
				}
			} else {
				$result .= $this->scalarToNodeStr($value, $key);
			}
		}
		return $result;
	}


	protected function arrayToNodeStr(array $data, $key){
		$result = '';
		foreach ($data as $value){
			if (is_scalar($value)){
				$result .= $this->scalarToNodeStr($value, $key);
			} else {
				$result .= "<$key>".$this->hashToNodeStr($value)."</$key>";
			}
		}
		return $result;
	}


	protected function scalarToNodeStr($value, $key){
		return '<'.$key.'>'.$this->escapeField($value).'</'.$key.'>';
	}


	protected function replaceEntities($text){
		$search_replace = array(
			'&quot;'	=> '&#34;', # "
			'&amp;'	=> '&#38;', # &
			'&lt;'	=> '&#60;', # <
			'&gt;'	=> '&#62;', # >
			'&nbsp;'	=> '&#160;', #
			'&iexcl;'	=> '&#161;', # ¡
			'&cent;'	=> '&#162;', # ¢
			'&pound;'	=> '&#163;', # £
			'&curren;'	=> '&#164;', # ¤
			'&yen;'	=> '&#165;', # ¥
			'&brvbar;'	=> '&#166;', # ¦
			'&sect;'	=> '&#167;', # §
			'&uml;'	=> '&#168;', # ¨
			'&copy;'	=> '&#169;', # ©
			'&ordf;'	=> '&#170;', # ª
			'&laquo;'	=> '&#171;', # «
			'&not;'	=> '&#172;', # ¬
			'&shy;'	=> '&#173;', #
			'&reg;'	=> '&#174;', # ®
			'&macr;'	=> '&#175;', # ¯
			'&deg;'	=> '&#176;', # °
			'&plusmn;'	=> '&#177;', # ±
			'&sup2;'	=> '&#178;', # ²
			'&sup3;'	=> '&#179;', # ³
			'&acute;'	=> '&#180;', # ´
			'&micro;'	=> '&#181;', # µ
			'&para;'	=> '&#182;', # ¶
			'&middot;'	=> '&#183;', # ·
			'&cedil;'	=> '&#184;', # ¸
			'&sup1;'	=> '&#185;', # ¹
			'&ordm;'	=> '&#186;', # º
			'&raquo;'	=> '&#187;', # »
			'&frac14;'	=> '&#188;', # ¼
			'&frac12;'	=> '&#189;', # ½
			'&frac34;'	=> '&#190;', # ¾
			'&iquest;'	=> '&#191;', # ¿
			'&Agrave;'	=> '&#192;', # À
			'&Aacute;'	=> '&#193;', # Á
			'&Acirc;'	=> '&#194;', # Â
			'&Atilde;'	=> '&#195;', # Ã
			'&Auml;'	=> '&#196;', # Ä
			'&Aring;'	=> '&#197;', # Å
			'&AElig;'	=> '&#198;', # Æ
			'&Ccedil;'	=> '&#199;', # Ç
			'&Egrave;'	=> '&#200;', # È
			'&Eacute;'	=> '&#201;', # É
			'&Ecirc;'	=> '&#202;', # Ê
			'&Euml;'	=> '&#203;', # Ë
			'&Igrave;'	=> '&#204;', # Ì
			'&Iacute;'	=> '&#205;', # Í
			'&Icirc;'	=> '&#206;', # Î
			'&Iuml;'	=> '&#207;', # Ï
			'&ETH;'	=> '&#208;', # Ð
			'&Ntilde;'	=> '&#209;', # Ñ
			'&Ograve;'	=> '&#210;', # Ò
			'&Oacute;'	=> '&#211;', # Ó
			'&Ocirc;'	=> '&#212;', # Ô
			'&Otilde;'	=> '&#213;', # Õ
			'&Ouml;'	=> '&#214;', # Ö
			'&times;'	=> '&#215;', # ×
			'&Oslash;'	=> '&#216;', # Ø
			'&Ugrave;'	=> '&#217;', # Ù
			'&Uacute;'	=> '&#218;', # Ú
			'&Ucirc;'	=> '&#219;', # Û
			'&Uuml;'	=> '&#220;', # Ü
			'&Yacute;'	=> '&#221;', # Ý
			'&THORN;'	=> '&#222;', # Þ
			'&szlig;'	=> '&#223;', # ß
			'&agrave;'	=> '&#224;', # à
			'&aacute;'	=> '&#225;', # á
			'&acirc;'	=> '&#226;', # â
			'&atilde;'	=> '&#227;', # ã
			'&auml;'	=> '&#228;', # ä
			'&aring;'	=> '&#229;', # å
			'&aelig;'	=> '&#230;', # æ
			'&ccedil;'	=> '&#231;', # ç
			'&egrave;'	=> '&#232;', # è
			'&eacute;'	=> '&#233;', # é
			'&ecirc;'	=> '&#234;', # ê
			'&euml;'	=> '&#235;', # ë
			'&igrave;'	=> '&#236;', # ì
			'&iacute;'	=> '&#237;', # í
			'&icirc;'	=> '&#238;', # î
			'&iuml;'	=> '&#239;', # ï
			'&eth;'	=> '&#240;', # ð
			'&ntilde;'	=> '&#241;', # ñ
			'&ograve;'	=> '&#242;', # ò
			'&oacute;'	=> '&#243;', # ó
			'&ocirc;'	=> '&#244;', # ô
			'&otilde;'	=> '&#245;', # õ
			'&ouml;'	=> '&#246;', # ö
			'&divide;'	=> '&#247;', # ÷
			'&oslash;'	=> '&#248;', # ø
			'&ugrave;'	=> '&#249;', # ù
			'&uacute;'	=> '&#250;', # ú
			'&ucirc;'	=> '&#251;', # û
			'&uuml;'	=> '&#252;', # ï
			'&yacute;'	=> '&#253;', # ý
			'&thorn;'	=> '&#254;', # þ
			'&yuml;'	=> '&#255;', # ÿ
			'&euro;'	=> '&#8364;', # €
		);
		$text = strtr($text, $search_replace);
		return $text;
	}


	protected function escapeField($text){
		$text = $this->replaceEntities($text);
		if (strpos($text, '<') !== false || strpos($text, '>') !== false || strpos($text, '"') !== false || strpos($text, '&') !== false) {
			$trans = array("]]>" => "]]&gt;");
			$text = '<![CDATA[' . strtr($text, $trans) . ']]>';
		}
		return $text;
	}


	protected function isAllKeysNumeric(array $data){
		$keys = array_keys($data);
		$is_all_numeric = true | (count($data) > 0);
		foreach ($keys as $key){
			if (!is_numeric($key)){
				$is_all_numeric = false;
				break;
			}
		}
		return $is_all_numeric;
	}

}
