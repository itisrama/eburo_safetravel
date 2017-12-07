<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class GTHelperDB
{
	public function __construct() {
		echo 'test';
	}

	public static function getColumns($table, $alias = false) {
		$table = $alias ? strstr($table.' ', ' ', true) : $table;

		$db		= JFactory::getDbo();
		$table	= '#__gtsafetravel_'.$table;
		return $db->getTableColumns($table, false);
	}

	public static function getPrimaryKeys($cols, $all = false) {
		$cols = is_array($cols) ? $cols : self::getColumns($cols);
		$keys = array();
		foreach ($cols as $col) {
			if($col->Key == 'PRI') {
				$keys[] = $col->Field;
			}
		}
		$keys = $all ? $keys : reset($keys);
		return $keys;
	}

	public static function quoteName($val) {
		$db	 = JFactory::getDbo();

		if(is_numeric(strpos($val, '('))) {
			$alias	= substr(strrchr($path, ' '), 1);
			$val	= self::strToQuery($val);

			return $val.' AS '.$db->quoteName($alias);
		}

		$val = trim($val);
		$val = explode(' ', $val.' ');
		list($val, $alias) = $val;

		return $alias ? $db->quoteName($val, $alias) : $db->quoteName($val);
	}

	public static function quote($val) {
		$db	 = JFactory::getDbo();

		$isNull		= is_null($val);
		$isNumber	= is_numeric($val);
		$isString	= strpos($val, '[') === 0;
		$isArray	= strpos($val, ',') === 0;

		preg_match('/\(([^)]+)\)/', $val, $func);
		if($func) {
			$val1 = end($func);
			echo "<pre>"; print_r($val1); echo "</pre>";
			$val2 = self::quote($val1);
			return str_replace($val1, $val2, $val);
		} elseif($isNull) {
			return 'NULL';
		} elseif($isNumber || $isString) {
			return $db->quote($val);
		} elseif($isArray) {
			$val = str_getcsv($val, ',');
			$val = array_map('self::quote', $val);
			$val = implode(',', $val);
			return $val;
		} else {
			return $db->quoteName($val);
		}
	}

	public static function setUpdateCol($col) {
		$db = JFactory::getDbo();
		$col = $db->quoteName($col).' = VALUES('.$db->quoteName($col).')';
		return $col;
	}

	public static function strToQuery($str) {
		$db = JFactory::getDbo();
		$exceptions = array(
			',','-','!','!=','%','&','&&',')','*','/',':=','^','|','||',
			'~','+','<','<<','<=','<=>','<>','=','>','->','>=','>>','->>',
			'add','all','alter','and','as','asc','begin','between','binary',
			'by','case','column','create','database','delete','desc','distinct',
			'div','drop','else','end','exists','from','full','group','having',
			'if','in','index','inner','insert','into','is','join','left',
			'like','mod','not','null','on','or','order','regexp','right',
			'rlike','select','set','sounds','table','top','truncate',
			'union','unique','update','values','view','where','xor'
		);

		// Clean comma
		$str = str_replace(',', ' , ', $str);

		// Check parentheses
		$matches = GTHelperStr::matchParentheses($str);
		list($matchesR, $matchesV) = $matches;
		$matchesR2 = array_map(function($v){ return '[P'.str_replace(' ','',$v).']'; }, array_keys($matchesR));
		foreach ($matchesV as &$matV) {
			$matV = self::strToQuery($matV);
			$matV = '('.$matV.')';
		}

		// Check quotation
		$quotes = GTHelperStr::matchQuotes($str, '"', '"');
		list($quotesR, $quotesV) = $quotes;
		$quotesR2 = array_map(function($v){ return '[Q1'.str_replace(' ','',$v).']'; }, array_keys($quotesR));
		$quotesV = array_map(array($db, 'quote'), $quotesV);
		
		$str	= strtr($str, array_combine($matchesR, $matchesR2));
		$str	= strtr($str, array_combine($quotesR, $quotesR2));

		// Iterate string to quote
		$str = explode(' ', $str);
		$str = array_filter($str);
		foreach ($str as &$tuple) {
			$isQNm	= !in_array(strtolower($tuple), $exceptions);
			$isQNm2	= !is_numeric($tuple);
			$isQNm3	= strpos($tuple, '[') !== 0;
			$isQNm4 = strtoupper($tuple) !== $tuple;

			if($isQNm && $isQNm2 && $isQNm3 && $isQNm4) {
				$tuple = $db->quoteName($tuple);
			}

			if(!$isQNm) {
				$tuple = strtoupper($tuple);
			}
		}
		$str = implode(' ', $str);

		$str	= strtr($str, array_combine($matchesR2, $matchesV));
		$str	= strtr($str, array_combine($quotesR2, $quotesV));

		// Clean text
		$str = str_replace(' , ', ', ', $str);
		$str = str_replace('\\', '', $str);
		return $str;
	}

	public static function buildQuery($table, $wheres = '', $selects = '', $limit = '', $joins = '', $orders = '', $groups = '') {
		// Get a db connection.
		$db		= JFactory::getDbo();
		$cols	= self::getColumns($table, true);
		$pk		= self::getPrimaryKeys($cols);

		// Create a new query object.
		$query	 = $db->getQuery(true);
		$selects = is_array($selects) ? $selects : explode(',', $selects);
		$selects = array_filter($selects);
		$selects = array_map('trim', $selects);
		if($selects) {
			array_unshift($selects, $pk);
			$selects = array_filter($selects);
			foreach ($selects as $selCol) { 
				$query->select(self::quoteName($selCol));
			}
		} else {
			$query->select('*');
		}
		
		$query->from(self::quoteName('#__gtsafetravel_'.$table));
		
		foreach ((array) $joins as $join) {
			if(is_string($join)) {
				$join = str_replace(', ', ',', $join);
				list($jtable, $crit, $type) = explode(',', $join.',INNER,');
				if($jtable) $query->join($type, self::quoteName('#__gtsafetravel_'.$jtable).' ON '.self::strToQuery($crit));
			} elseif(is_array($join)) {
				array_push($join, 'INNER');
				$jtable = array_shift($join);
				if(is_string($jtable)) {
					list($crit, $type) = $join;
					$query->join($type, self::quoteName('#__gtsafetravel_'.$jtable).' ON '.self::strToQuery($crit));
				} else {
					list($crit, $alias, $type) = $join;
					$query->join($type, sprintf('(%s) AS %s', $jtable, $alias));
				}
			}
		}

		if(is_array($wheres)) {
			foreach ($wheres as $param) {
				$param = self::strToQuery($param);
				$query->where($param);
			}
		} else {
			$wheres = self::strToQuery($wheres);
			$query->where($wheres);
		}

		if($orders) {
			$orders = is_array($orders) ? $orders : explode(',', $orders);
			$orders = array_filter($orders);
			foreach ($orders as $order) {
				$order = trim($order);
				list($order, $sort) = explode(' ', $order.' asc');
				$query->order(self::quoteName($order).' '.$sort);
			}
		}

		if($groups) {
			$groups = is_array($groups) ? $groups : explode(',', $groups);
			$groups = array_filter($groups);
			foreach ($groups as $group) {
				$group = trim($group);
				$query->group(self::quoteName($group));
			}
		}

		if($limit) {
			$limit = is_array($limit) ? $limit : explode(',', $limit);
			array_push($limit, 0);
			list($limit, $offset) = $limit;
			$query->setLimit($limit, $offset);
		}
		

		return $query;
	}

	/**
	 * Function to get one record/item from a table
	 * @param 	string 		$table 		Table name [REQUIRED]
	 * @param 	mixed		$keyVal 	Key value of an item [REQUIRED]
	 * @param 	string		$key 		Primary/unique key
	 * @param 	boolean		$null 		Convert empty string to NULL, will keep zero
	 * @param 	boolean		$single		Will return single record if true
	 */

	public static function getItem($table, $keyVal, $key = 'id', $selects = array(), $null = true) {
		// Get a db connection.
		$db		= JFactory::getDbo();
		$cols	= self::getColumns($table);

		// Create a new query object.
		$query = $db->getQuery(true);

		if((array) $selects) {
			$selects = is_array($selects) ? $selects : array($selects);
			$selects = array_filter($selects);
			$query->select($db->quoteName($selects));
		} else {
			$query->select('*');
		}

		$query->from($db->quoteName('#__gtsafetravel_'.$table));
		$query->where($db->quoteName($key) . ' = ' . $db->quote($keyVal));

		$db->setQuery($query);

		switch (count($selects)) {
			case 0:
				$item = $db->loadObject();
				return self::validate($item, $cols, true, $null);
				break;
			case 1:
				return $db->loadResult();
				break;
			default:
				$item = $db->loadObject();
				return self::validate($item, $cols, false, $null);
				break;
		}
	}

	/**
	 * Function to get multiple record with parameters from table
	 * @param 	string 		$table 		Table name [REQUIRED]
	 * @param 	array		$params		Parameter for query [REQUIRED]
	 * @param 	string		$selCols	Selected columns
	 * @param 	string		$orders 	Column order
	 * @param 	boolean		$limit		Result limit
	 * @param 	boolean		$offset		Offset of which row starts
	 */

	public static function getItems($table, $wheres = '', $selects = '', $limit = '100', $joins = '', $orders = '', $groups = '') {
		$db			= JFactory::getDbo();
		$selects	= is_array($selects) ? $selects : explode(',', $selects);
		$selects	= array_filter($selects);
		$selects 	= array_map('trim', $selects);
		$cols		= self::getColumns($table, true);
		$pk			= self::getPrimaryKeys($cols);
		$query		= self::buildQuery($table, $wheres, $selects, $limit, $joins, $orders, $groups);
		//var_dump($query);
		//echo nl2br(str_replace('#__','eburo_',$query)).'<br/><br/>'; die;
		$db->setQuery($query);

		switch (count($selects)) {
			case 0:
				$items = $db->loadObjectList($pk);
				foreach ($items as &$item) {
					$item = self::validate($item, $cols, true);
				}
				return $items;
				break;
			case 1:
			case 2:
				$select1 = reset($selects);
				$select1 = explode('.', $select1);
				$select1 = end($select1);
				$select2 = end($selects);
				$select2 = explode('.', $select2);
				$select2 = end($select2);
				$select1 = $select1 == $select2 ? $pk : $select1;
				return $db->loadAssocList($select1, $select2);
				break;
			default:
				$items = $db->loadObjectList($pk);
				foreach ($items as &$item) {
					$item = self::validate($item, $cols, false);
				}
				return $items;
				break;
		 }
	}
	/**
	 * Function to insert item into a table
	 * @param 	string		$table		Table name [REQUIRED]
	 * @param 	mixed		$item		Item to insert [REQUIRED]
	 * @param 	string		$key		Table pk, func will return inserted data if provided
	 * @param 	string		$unique		Unique column, if provided will return last insert data 
	 * @param 	datetime	$date		Overrides datetime used in created and modified field
	 * @param 	boolean		$null		Convert empty string to NULL, will keep zero
	 */

	public static function insertItem($table, $item, $key = 'id', $unique = '', $null = true) {
		$db			= JFactory::getDbo();
		$query		= $db->getQuery(true);
		$date		= JFactory::getDate()->toSql();
		$item		= is_object($item) ? $item : JArrayHelper::toObject($item);
		$user_id	= JFactory::getUser()->get('id');
		$cols 		= self::getColumns($table);
		$pKey 		= self::getPrimaryKeys($cols, false);

		if(!@$item->$pKey) {
			$item->created		= @$item->created ? $item->created : $date;
			$item->created_by	= @$item->created_by ? $item->created_by : $user_id;
		} else {
			$item->modified		= @$item->modified ? $item->modified : $date;
			$item->modified_by	= @$item->modified_by ? $item->modified_by : $user_id;
		}

		$item		= self::validate($item, $cols, false, $null);
		$itemCols	= get_object_vars($item);
		$itemCols	= array_keys($itemCols);
		$updateCols = array_map('self::setUpdateCol', $itemCols);
		$updateCols	= implode(', ', $updateCols);

		$item = JArrayHelper::fromObject($item);
		$item = array_map('self::quote', $item);
		$item = implode(', ', $item);
		
		$query->insert($db->quoteName('#__gtsafetravel_'.$table));
		$query->columns($itemCols);
		$query->values($item);

		$query .= ' ON DUPLICATE KEY UPDATE '.$updateCols;

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);
		$db->query();

		if($unique == $pKey) {
			return $db->insertid();
		} else {
			return true;
		}
	}

	/**
	 * Function to insert multiple items into table
	 * @param 	string		$table		Table name [REQUIRED]
	 * @param 	mixed		$item		Item to insert [REQUIRED]
	 * @param 	string		$key		Table pk, func will return inserted data if provided
	 * @param 	integer		$buffer		Items will be split into chunks to be inserted every chunk
	 * @param 	boolean		$null		Convert empty string to NULL, will keep zero
	 */

	public static function insertItems($table, $items, $key = 'id', $buffer = 500, $null = true) {
		$db			= JFactory::getDbo();
		$date		= JFactory::getDate()->toSql();
		$user_id	= JFactory::getUser()->get('id');
		$cols 		= self::getColumns($table);

		$item		= reset($items);
		$updateCols	= get_object_vars($item);
		$updateCols	= array_keys($updateCols);

		foreach ($updateCols as &$col) {
			$col = $db->quoteName($col).' = VALUES('.$db->quoteName($col).')';
		}
		$updateCols[]	= in_array('modified', $cols) ? $db->quoteName('modified').' = '.$db->quote($date) : null;
		$updateCols[]	= in_array('modified_by', $cols) ? $db->quoteName('modified_by').' = '.$db->quote($user_id) : null;
		$updateCols		= array_filter($updateCols);
		$updateCols		= implode(',', $updateCols);


		$item->created		= $date;
		$item->created_by	= $user_id;
		$itemCols = get_object_vars($item);
		$itemCols = array_keys($itemCols);

		$chunks = (array) array_chunk($items, 500);
		foreach ($chunks as $items) {
			$query = $db->getQuery(true);

			// Insert columns.
			$columns = reset($chunks);
			$columns = is_object($columns) ? JArrayHelper::fromObject($columns) : $columns;
			$columns = array_keys($columns);

			$itemCols = array();
			foreach ($items as &$item) {
				$item = is_object($item) ? $item : JArrayHelper::toObject($item);

				$item->created		= @$item->created;
				$item->created_by	= @$item->created_by;
				$item->modified		= @$item->modified;
				$item->modified_by	= @$item->modified_by;

				if(!@$item->$key) {
					$item->created		= $item->created ? $item->created : $date;
					$item->created_by	= $item->created_by ? $item->created_by : $user_id;
				} else {
					$item->modified		= $item->modified ? $item->modified : $date;
					$item->modified_by	= $item->modified_by ? $item->modified_by : $user_id;
				}

				$item = self::validate($item, $cols, false, $null);
				$item = JArrayHelper::fromObject($item); $itemCols = $item;
				$item = array_map('self::quote', $item);
				$item = implode(',', $item);
			}
			
			$itemCols	= array_keys($itemCols);
			$updateCols	= array_map('self::setUpdateCol', $itemCols);
			$updateCols	= implode(', ', $updateCols);
			
			$query->insert($db->quoteName('#__gtsafetravel_'.$table));
			$query->columns($itemCols);
			$query->values($item);
			$query .= ' ON DUPLICATE KEY UPDATE '.$updateCols;

			//echo nl2br(str_replace('#__','eburo_',$query)); die;
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * Function to clean data from every column of an item
	 * @param 	mixed		$item 		Item to clean [REQUIRED]
	 * @param 	mixed 		$cols 		Table columns [REQUIRED]
	 * @param 	boolean		$all 		Will keep foreign columns if true
	 * @param 	boolean		$null 		Convert empty string to NULL, will keep zero
	 */

	public static function validate($item, $cols, $all = false, $null = true) {
		$item		= is_object($item) ? JArrayHelper::fromObject($item) : (array) $item;
		$db			= JFactory::getDbo();
		$cols		= is_array($cols) ? $cols : self::getColumns($cols);
		$fields		= array_keys($item);
 
		$validItem = new stdClass();
		foreach ($cols as $col => $colItem) {
			if(!in_array($col, $fields) && !$all) {
				continue;
			}
			$type = explode('(', $colItem->Type);
			$type = reset($type);
			$value = @$item[$col];
			switch ($type) {
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'bigint':
				case 'bit':
					$validItem->$col = GTHelper::isEmpty($value) && $null ? null : intval($value);
					break;
				case 'float':
				case 'double':
				case 'decimal':
					$validItem->$col = GTHelper::isEmpty($value) && $null ? null : floatval($value);
					break;
				default:
					$validItem->$col = $value;
					break;
			}
		}
		return $validItem;
	}

	/**
	 * Function to get aggregation value of a table
	 * @param 	string 		$table 		Table name [REQUIRED]
	 * @param 	string		$key 		Primary/unique key
	 * @param 	string		$agg 		Aggregation function (MAX/MIN/AVG)
	 * @param 	string		$aggBy 		Grouping column for aggregation
	 */

	public static function aggregate($table, $key = 'id', $agg = 'max', $params = array()) {
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);
		$query->select($aggType.'('.$db->quoteName($key).')');
		$query->from($db->quoteName($table));

		foreach ($params as $param) {

			$query->where($db->quoteName($key) . ' = ' . $db->quote($keyVal));
		}
		
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Function to get aggregation value of a table
	 * @param 	string 		$table 		Table name [REQUIRED]
	 * @param 	mixed		$keyVals 	Keys value of an item [REQUIRED]
	 * @param 	string		$key 		Primary/unique key
	 */

	public static function delete($table, $keyVals, $key = 'id') {
		$keyVals = is_array($keyVals) ? $keyVals : array($keyVals);
		$keyVals = array_filter($keyVals);

		if(!$keyVals) return false;

		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__gtsafetravel_'.$table));

		$keyVals = array_map(array($db, 'quote'), $keyVals);
		$keyVals = implode(',', $keyVals);
		$query->where($db->quoteName($key).' IN ('.$keyVals.')');

		return $db->setQuery($query)->query();
	}


	public static function getForm($name, $merge = false) {
		$name .= '.xml';

		$filepath1	= GT_MODELS.DS.'forms'.DS.$name;
		$filepath2	= GT_ADMIN_MODELS.DS.'forms'.DS.$name;
		$filepath	= file_exists($filepath1) ? $filepath1 : $filepath2;

		if(!file_exists($filepath)) {
			return false;
		}

		$xml = simplexml_load_file($filepath);
		$fields = $xml->fields;

		$oFieldsets = array();
		foreach ($fields as $fieldset) {
			foreach ($fieldset as $field) {
				$oFieldset = array();
				foreach ($field as $fl) {
					$oFl = new stdClass();
					foreach ($fl->attributes() as $k => $v) {
						$oFl->$k = reset($v);
					}
					if($merge) {
						$oFieldsets[$oFl->name] = $oFl;
					} else {
						$oFieldset[$oFl->name] = $oFl;
					}
				}
				if(!$merge) {
					$oFieldsets[] = $oFieldset;
				}
			}
		}
		
		return $oFieldsets;
	}
}
