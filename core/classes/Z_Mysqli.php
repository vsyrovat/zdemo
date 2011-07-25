<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 04.01.2011
 * Time: 19:40:11
 */
 
class Z_Mysqli extends Z_Object implements iDatabase {
	protected
		$mysqli_obj = null,
		$last_result = null,
		$bind_marker = '?',
		$last_query = '',
		$swap_pre = '#__',
		$tables_prefix = null;

	public function afterSet(){
		$this->tables_prefix = $this->config['/prefix'];
	}


	protected function checkConnection(){
		if (!$this->mysqli_obj){
			$this->mysqli_obj = new mysqli($this->config['/host'], $this->config['/username'], $this->config['/password'], $this->config['/database'], $this->config['/port']);
			if ($this->mysqli_obj->errno){
				throw new DatabaseException('Cannot connect to database');
			}
			$this->mysqli_obj->set_charset($this->config['/charset']);
			if ($this->mysqli_obj->errno){
				throw new DatabaseException('Error setting charset');
			}
		}
	}

	/**
	 * @throws DatabaseException
	 * @param string $query_string
	 * @param array $binds
	 * @return mysqli_result
	 */
	public function query($query_string, array $binds = array()){
		$this->checkConnection();

		if (($this->swap_pre != '') && ($this->tables_prefix != $this->swap_pre) ) {
			$prepared_string = $this->replacePrefix($query_string, $this->swap_pre);
		}

		$prepared_string = $this->compileBinds($prepared_string, $binds);
		$this->last_query = $prepared_string;
		$this->mysqli_obj->real_query($prepared_string);
		if ($this->mysqli_obj->errno){
			throw new DatabaseException('Error code '.$this->mysqli_obj->errno.' in query '.$prepared_string.': '.$this->mysqli_obj->error);
		}
		$result = new Z_Mysqli_result($this->mysqli_obj);
		$this->last_result = $result;
		return $result;
	}


	public function lastQuery(){
		return $this->last_query;
	}


	public function insert_id(){
		return $this->mysqli_obj->insert_id;
	}


	public function affected_rows(){
		return $this->mysqli_obj->affected_rows;
	}

	/**
	 * @param string $query_string
	 * @param array $binds
	 * @return string
	 */
	protected function compileBinds($query_string, array $binds = array()){
		if (count($binds) == 0 || strpos($query_string, $this->bind_marker) === false){
			return $query_string;
		}

		// Get the sql segments around the bind markers
		$segments = explode($this->bind_marker, $query_string);

		// The count of bind should be 1 less then the count of segments
		// If there are more bind arguments trim it down
		if (count($binds) >= count($segments)) {
			$binds = array_slice($binds, 0, count($segments)-1);
		}
		
		// Construct the binded query
		$result = $segments[0];
		$i = 0;
		foreach ($binds as $bind) {
			$result .= $this->escape($bind);
			$result .= $segments[++$i];
		}

		return $result;
	}


	/**
	 * "Smart" Escape String
	 *
	 * Escapes data based on type
	 * Sets boolean and null types
	 *
	 * @access	public
	 * @param	string
	 * @return mixed
	 */
	public function escape($str)	{
		switch (gettype($str)) {
			case 'string'	:	$str = "'".$this->escape_str($str)."'";
				break;
			case 'boolean'	:	$str = ($str === FALSE) ? 0 : 1;
				break;
			case 'array':
				foreach ($str as &$s)
					$s = $this->escape($s);
				$str = join(',', $str);
				break;
			default			:	$str = ($str === NULL) ? 'NULL' : $str;
				break;
		}
		return $str;
	}


	/**
	 * Escape String
	 *
	 * @access	public
	 * @param	string
	 * @param	bool	whether or not the string will be used in a LIKE condition
	 * @return string
	 */
	public function escape_str($str, $like = FALSE){
		$this->checkConnection();
//		if (is_array($str))	{
//			foreach($str as $key => $val)	{
//				$str[$key] = $this->escape_str($val, $like);
//	   	}
//   		return $str;
//	  }
		$str = $this->mysqli_obj->real_escape_string($str);

		// escape LIKE condition wildcards
		if ($like === TRUE)	{
			$str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
		}
		
		return $str;
	}


	/**
	 * Escape LIKE String
	 *
	 * Calls the individual driver for platform
	 * specific escaping for LIKE conditions
	 *
	 * @access	public
	 * @param	string
	 * @return string
	 */
	protected function escape_like_str($str) {
    return $this->escape_str($str, TRUE);
	}


	/**
	 * This function replaces a string identifier <var>$prefix</var> with the
	 * string held is the <var>_table_prefix</var> class variable.
	 *
	 * @param string The SQL query
	 * @param string The common table prefix
	 * @author thede, David McKinnis
	 */
	protected function replacePrefix($sql, $prefix='#__') {
		$sql = trim( $sql );

		$escaped = false;
		$quoteChar = '';

		$n = strlen( $sql );

		$startPos = 0;
		$literal = '';
		while ($startPos < $n) {
			$ip = strpos($sql, $prefix, $startPos);
			if ($ip === false) {
				break;
			}

			$j = strpos( $sql, "'", $startPos );
			$k = strpos( $sql, '"', $startPos );
			if (($k !== FALSE) && (($k < $j) || ($j === FALSE))) {
				$quoteChar	= '"';
				$j = $k;
			} else {
				$quoteChar	= "'";
			}

			if ($j === false) {
				$j = $n;
			}

			$literal .= str_replace($prefix, $this->tables_prefix, substr($sql, $startPos, $j - $startPos));
			$startPos = $j;

			$j = $startPos + 1;

			if ($j >= $n) {
				break;
			}

			// quote comes first, find end of quote
			while (TRUE) {
				$k = strpos( $sql, $quoteChar, $j );
				$escaped = false;
				if ($k === false) {
					break;
				}
				$l = $k - 1;
				while ($l >= 0 && $sql{$l} == '\\') {
					$l--;
					$escaped = !$escaped;
				}
				if ($escaped) {
					$j	= $k+1;
					continue;
				}
				break;
			}
			if ($k === FALSE) {
				// error in the query - no end quote; ignore it
				break;
			}
			$literal .= substr( $sql, $startPos, $k - $startPos + 1 );
			$startPos = $k+1;
		}
		if ($startPos < $n) {
			$literal .= substr( $sql, $startPos, $n - $startPos );
		}
		return $literal;
	}


	public function isTableExists($table_name){
		$table_name = str_replace('#__', $this->config['prefix'], $table_name);
		$r = $this->query("SHOW TABLES LIKE '$table_name'");
		$num_rows = $r->num_rows();
		$r->free();
		if ($num_rows > 0){
			return true;
		} else {
			return false;
		}
	}
}
