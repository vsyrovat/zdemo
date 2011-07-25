<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 06.01.2011
 * Time: 1:51:19
 */


class Z_DBModel extends Z_Model implements iDBModel {
	protected
		$db_fields = array('id'), # поля таблицы, по которым производится фильтрация при сохранении
		$notrim_fields = array(), # Поля, к которым не применяется trim() при сохранении
		$order_field = 'abs_order', # Поле сортировки
		$order_type = 'ASC', # тип сортировки, asc или desc
		$primary_key = 'id', # Первичный ключ
		$insert_id = null, # Индекс последней вставки, использовать только для чтения
		$error = false, # возникла ли ошибка, использовать только для чтения
		$serialize_field = null, # хрен его знает, тут не используется
		$alias_field = null, # название поля-алиаса
		$indexes = array(), #
		$db_name = '', # Название рабочей базы данных. если не задано - используется база данных, определённая в соответствующей конфигурации
		$db_table = '', # Название таблицы, в которой хранятся записи
		$q = null,
		$preloaded_items = array(),
		$preload_done = false,
		$allow_create_pk = false, # Можно ли перезаписывать primary key при создании записи, true - можно вручную указывать, false - создаётся на основе auto increment таблицы
		$exists_ids_cache = array(), // элементы, которые уже существуют. для кэша
		$database = null, # основной рабочий объект базы данных
		$dbconf = ''; # Название конфигурации базы данных


	public function afterSet(){
		$this->database = $this->factory->getObjectByInterface('iDatabase');
		$this->db_table = $this->config['/table'];
		$this->order_field = $this->config['/order_field'];
		$this->db_fields = $this->config['/db_fields'];
	}

	/**
	 * Эскейпинг
	 * @param scalar $string
	 */
	public function dbescape($string){
		return $this->database->escape($string);
	}


	protected function dbescape_str($string){
		return $this->database->escape_str($string);
	}


	/**
	 * Заключить в кавычки данные для инструкции SQL SET
	 *
	 * @param unknown_type $data
	 * @return unknown
	 */
	protected function _dataToSqlSet($data, $additional_fields = array()) {
		$data_r = $set = array();
		$_allowed_fields = array_merge($this->db_fields, $additional_fields);
		foreach ($_allowed_fields as $field) {
			if (array_key_exists($field, $data) && !empty($field)) {
				$data_r[$field] = $this->database->escape($data[$field]);
				if (!in_array($field, $this->notrim_fields)) $data_r[$field] = trim($data_r[$field]);
			}
		}
		foreach ($data_r as $field_name=>$field_value)
			$set[] = '`' . $field_name . '`=' . $field_value;
		return join(',', $set);
	}

	/**
	 * Заключить в кавычки данные для инструкции SQL SELECT
	 *
	 * @param unknown_type $s
	 * @return unknown
	 */
	protected function _stringToSqlSelect($s) {
		$s = trim($s);
		if ($s == '*') return '*';
		$s = explode(',', $s);
		foreach ($s as $k=>$v) {
			$s[$k] = '`' . trim($v) . '`';
		}
		return join(',', $s);
	}


	protected function _prepareTableName($tablename){
		$tablename = '`'.$tablename.'`';
		if (!empty($this->db_name)){
			$tablename = '`'.$this->db_name.'`.'.$tablename;
		}
		return $tablename;
	}


	/**
	 * Выполнить запрос. Запоминает insert_id
	 */
	protected function _query($instruction, array $binds = array(), $return_object = TRUE) {
		$instruction = ltrim($instruction);
		$querytype = (strtoupper(substr($instruction, 0, 6)) === 'INSERT') ? 'i' : 'o';
		$this->q = $this->database->query($instruction, $binds, $return_object);
		$this->insert_id = ($querytype === 'i') ? $this->database->insert_id() : null;
	}


	function __call($name, $args){
		if (substr($name, 0, 8) == 'find_by_'){
			$field = substr($name, 8);
			return $this->getItemsList('*', array('where'=>'`'.$this->dbescape_str($field).'`='.$this->dbescape($args[0])), true);
		} elseif (substr($name, 0, 9) == 'count_by_'){
			$field = substr($name, 9);
			$this->_query("SELECT COUNT(*) AS `count` FROM `{$this->db_table}` WHERE `{$field}`=".$this->dbescape($args[0]));
			$r = $this->q->row_array();
			return $r['count'];
		}
	}

	public function getInsertID(){
		return $this->insert_id;
	}

	/**
	 * Задать подключение к базе данных. должно быть задано до первого запроса
	 * @param string $conf_name
	 */
	public function setDBConf($conf_name){
		$this->dbconf = (string)$conf_name;
	}

	/**
	 * Получить пустой элемент (матрицу)
	 *
	 * @return array
	 */
	public function getEmptyItem() {
		$result = array_fill_keys($this->db_fields, '');
		return $result;
	}

	/**
	 * Получить элемент из таблицы БД
	 *
	 * @param int $id
	 * @param string $fields - поля, разделённые запятой
	 * @return array
	 */
	public function getItem($id, $fields = '*') {
		$result = false;
		$id = (int)$id;
		if (!empty($id)){
			$this->_query("SELECT " . $this->_stringToSqlSelect($fields) . " FROM ".$this->_prepareTableName($this->db_table)." WHERE `id`=".(int)$id);
			$q = &$this->q;
			$result = ($q->num_rows > 0) ? $q->fetch_assoc() : null;
			$q->free();
		} else {
			$result = null;
		}
		return $result;
	}

	/**
	 * Получить список элементов
	 *
	 * @param string $fields
	 * @param hash $params
	 * @return array of hash
	 */
	public function getItemsList($fields = '*', $params = array(), $use_indexes = false){
		$result = false;
		if ($use_indexes && !empty($this->indexes)){
			return array();
		}
		$select = $this->_stringToSqlSelect($fields);
		if ($select){
			$limit_str = isset($params['limit']) ? ' LIMIT '.$params['limit'] : '';
			$where = array();
			if (isset($params['where'])){
				$where[] = $params['where'];
			}
			if (count($this->indexes)){
				$where[] = '`'.$this->primary_key.'` IN ('.join(',', array_unique($this->indexes)).')';
			}
			$where = count($where) ? ' WHERE '.join(' AND ', $where) : '';
			if (isset($params['order'])){
				$orderby = 'ORDER BY '.$params['order'];
			} elseif (!empty($this->order_field)){
				$orderby = 'ORDER BY `'.$this->order_field.'` '.$this->order_type;
			} else {
				$orderby = '';
			}
			if (isset($params['group'])){
				$groupby = 'GROUP BY '.$params['group'];
			} else {
				$groupby = '';
			}
			$this->_query("SELECT " . $this->_stringToSqlSelect($fields) . " FROM " .$this->_prepareTableName($this->db_table)."$where $groupby $orderby ".$limit_str);
			$result = $this->q->result_array($this->primary_key);
			$this->q->free();
		}
		$this->flushIndexes();
		return $result;
	}

	/**
	 * Возвращает количество элементов в таблице
	 */
	public function getItemsCount($params = array()){
		$result = false;
		$where = array();
		if (isset($params['where'])){
			$where[] = $params['where'];
		}
		$where = count($where) ? ' WHERE '.join(' AND ', $where) : '';
		$this->_query("SELECT COUNT(*) AS `count` FROM ". $this->_prepareTableName($this->db_table).$where);
		$result = $this->q->fetch_assoc();
		$result = $result['count'];
		return $result;
	}

	/**
	 * Создать в БД новый элемент
	 *
	 * @param hash $data
	 * @return bool
	 */
	public function createItem($data) {
		$result = false;
		if (is_array($data)) {
			if (!empty($this->order_field)){
				$this->_query("SELECT MAX(`{$this->order_field}`) AS `max_of` FROM ".$this->_prepareTableName($this->db_table));
				$res = $this->q->row_array();
				$max_of = $res['max_of'];
				$data[$this->order_field] = $max_of + 1;
			}
			if (!$this->allow_create_pk){
				unset($data['id']);
			}
			if ($set = $this->_dataToSqlSet($data, array($this->order_field))) {
				$this->_query("INSERT INTO ".$this->_prepareTableName($this->db_table)." SET $set");
				$result = ($this->database->affected_rows() > 0);
				if ($result){
					$this->exists_ids_cache[$this->insert_id] = true;
				}
			}
		}
		return $result;
	}

	/**
	 * обновить элемент (перезаписать в БД)
	 *
	 * @param hash $data
	 * @return bool
	 */
	public function updateItem($data) {
		$result = false;
		if (is_array($data) && !empty($data['id'])) {
			$id = (int) $data['id'];
			unset($data['id']);
			if ($set = $this->_dataToSqlSet($data)) {
				$this->_query("UPDATE ".$this->_prepareTableName($this->db_table)." SET $set WHERE `id`=" . $id);
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * Обновить или создать элемент (если не существует)
	 *
	 * @param hash $data
	 * @return int 1=update, 2=create, 0=error
	 */
	public function saveCreateItem($data) {
		$result = 0;
		if (is_array($data)){
			if (!empty($data['id']) && $this->isItemExists($data['id'])){
				if ($this->updateItem($data)) $result = 1;
			} else {
				if ($this->createItem($data)) $result = 2;
			}
		}
		return $result;
	}

	/**
	 * Проверить, существует ли элемент с заданным id
	 *
	 * @param int $id
	 * @return bool
	 */
	public function isItemExists($id) {
		if (array_key_exists($id, $this->exists_ids_cache)){
			$result = $this->exists_ids_cache[$id];
		} else {
			$this->_query("SELECT `id` FROM ".$this->_prepareTableName($this->db_table)." WHERE `id`=" . (int) $id);
			$result = $this->q->num_rows() > 0;
			$this->q->free_result();
			$this->exists_ids_cache[$id] = $result;
		}
		return $result;
	}

	/**
	 * Загрузить в кэш список id существующих записей
	 */
	public function precacheExistsIDs(){
		$this->_query("SELECT `{$this->primary_key}` FROM `{$this->db_table}`");
		foreach ($this->q->column_array($this->primary_key) as $id){
			$this->exists_ids_cache[$id] = true;
		}
	}

	/**
	 * Переместить страницу на 1 позицию вверх или вниз
	 *
	 * @param int $id
	 * @param str $direction
	 */
	public function moveItem($id, $direction, $condition=null) {
		$result = false;
		$id = (int) $id;
		if ($this->isItemExists($id)) {
			$p = $this->getItem($id);
			if ($condition){
				$condition = ' AND ($condition)';
			}
			switch ($direction) {
				case 'up' :
					$this->_query("SELECT * FROM `{$this->db_table}` WHERE `{$this->order_field}`<" . $p['abs_order'] . $condition . " ORDER BY `{$this->order_field}` DESC LIMIT 1");
					break;
				case 'down' :
					$this->_query("SELECT * FROM `{$this->db_table}` WHERE `{$this->order_field}`>" . $p['abs_order'] . $condition . " ORDER BY `{$this->order_field}` ASC LIMIT 1");
					break;
				default:
			}
			if ($this->q->num_rows()) {
				$p2 = $this->q->row_array();
				$this->_query("UPDATE `{$this->db_table}` SET `{$this->order_field}`=" . $p2[$this->order_field] . " WHERE `id`=" . $p['id']);
				$this->_query("UPDATE `{$this->db_table}` SET `{$this->order_field}`=" . $p[$this->order_field] . " WHERE `id`=" . $p2['id']);
			}
//			$this->_abs_reorder();
			$result = true;
		}
		return $result;
	}

	/**
	 * Удалить элемент
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function deleteItem($id){
		$this->_query("DELETE FROM `{$this->db_table}` WHERE `id`=".(int)$id);
		$result = $this->database->affected_rows() > 0;
		if ($result){
			$this->exists_ids_cache[$id] = false;
		}
		return $result;
	}

	/**
	 * Сделать предзагрузку элементов в кэш
	 *
	 * @param array $ids
	 * @param string $fields
	 */
	public function preloadItems($ids, $fields = '*'){
		$ids_where = array();
		if (is_array($ids)){
			foreach ($ids as $id){
				$ids_where[] = (int)$id;
			}
			$ids_where = array_unique($ids_where);
		} else {
			$ids_where[] = (int)$ids;
		}
		if (count($ids_where)){
			$this->_query("SELECT ".$this->_stringToSqlSelect($fields)." FROM `{$this->db_table}` WHERE `id` IN (".join(',', $ids_where).")");
			$data = $this->q->result_array();
			foreach ($data as $d){
				$this->preloaded_items[$d['id']] = $d;
			}
			$this->preload_done = true;
		}
	}

	/**
	 * Добавить поле в список разрешённых полей
	 */
	protected function addField($fieldname){
		$this->db_fields[] = $fieldname;
	}

	/**
	 * Удалить поле из списка разрешённых полей
	 */
	protected function removeField($fieldname){
		unset ($this->db_fields[$fieldname]);
	}

	/**
	 * Сбросить накопитель индексов. Автоматически вызывается после каждой операции getItemsList
	 */
	public function flushIndexes(){
		$this->indexes = array();
	}

	/**
	 * Добавить индекс в накопитель
	 */
	public function addIndexes($indexes){
		if (is_array($indexes)){
			foreach ($indexes as $index){
				$this->indexes[] = $index;
			}
		} elseif (is_scalar($indexes)){
			$this->indexes[] = $indexes;
		}
	}

}