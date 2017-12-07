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

jimport('joomla.application.component.modeladmin');

class GTModelAdmin extends JModelAdmin
{
	
	public $app;
	public $input;
	public $context;
	public $prevName;
	public $item;
	public $user;
	public $menu;
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		// Set variables
		$this->app		= JFactory::getApplication();
		$this->input	= $this->app->input;
		$this->user		= JFactory::getUser();
		$this->menu		= $this->app->getMenu()->getActive();

		// Set User Profile
		$userProfiles	= JUserHelper::getProfile($this->user->id);
		foreach ($userProfiles as &$userProfile) {
			if(is_array($userProfile)) {
				$userProfile = JArrayHelper::toObject($userProfile, 'stdClass', false);
			}
		}
		$this->user->profile = $userProfiles;

		// Adjust the context to support modal layouts.
		$layout = $this->input->get('layout', 'default');
		$this->context	= implode('.', array($this->option, $this->getName(), $layout));

		// Add table path
		$this->addTablePath(GT_TABLES);
	}
	
	protected function populateState() {
		parent::populateState();
	}

	public function sanitizeItem($data, $name, $all = false, $set_null = true) {
		$data		= is_object($data) ? JArrayHelper::fromObject($data) : (array) $data;
		$db			= $this->_db;
		$table 		= '#__gtsafetravel_'.$name;
		$fields		= $db->getTableColumns($table, false);
		$item		= new stdClass();
		$dtFields	= array_keys($data);
		foreach ($fields as $field => $fieldDt) {
			if(!in_array($field, $dtFields) && !$all) {
				continue;
			}
			$type = explode('(', $fieldDt->Type);
			$type = reset($type);
			$value = @$data[$field];
			switch ($type) {
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'bigint':
				case 'bit':
					$item->$field = GTHelper::isEmpty($value) && $set_null ? null : intval($value);
					break;
				case 'float':
				case 'double':
				case 'decimal':
					$item->$field = GTHelper::isEmpty($value) && $set_null ? null : floatval($value);
					break;
				default:
					$item->$field = $value;
					break;
			}
		}
		return $item;
	}

	public function getItemExternal($key, $name, $fieldKey = 'id', $set_null = true, $is_table = false) {
		$table	= $name ? $name : strtolower($this->getName());	
		if($is_table) {
			$table = $this->getTable($table);
			$table = $table->getTableName();
		} else {
			$table = '#__gtsafetravel_'.$table;
		}

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('a.*');		
		$query->from($db->quoteName($table, 'a'));
		$query->where($db->quoteName('a.'.$fieldKey) . ' = ' . $db->quote($key));

		//echo nl2br(str_replace('#__','eburo_',$query)).'<br/>';
		$db->setQuery($query);

		$result	= $db->loadObject();
		return $this->sanitizeItem($result, $name, true, $set_null);
	}

	public function getItemMax($key, $name, $fieldKey = 'id', $fieldMax = 'id', $is_table = false) {
		$table	= $name ? $name : strtolower($this->getName());	
		if($is_table) {
			$table = $this->getTable($table);
			$table = $table->getTableName();
		} else {
			$table = '#__gtsafetravel_'.$table;
		}

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('a.*');		
		$query->from($db->quoteName($table, 'a'));

		$query2 = $db->getQuery(true);
		$query2->select('MAX('.$db->quoteName('a.'.$fieldMax).')');
		$query2->from($db->quoteName($table, 'a'));
		$query2->where($db->quoteName('a.'.$fieldKey) . ' = ' . $db->quote($key));

		$query->where($db->quoteName('a.'.$fieldMax).' = ('.$query2.')');
		//echo nl2br(str_replace('#__','eburo_',$query)).'<br/>';
		$db->setQuery($query);

		$result	= $db->loadObject();
		return $this->sanitizeItem($result, $name, true, false);
	}

	protected function loadFormData() {
		GTHelperFieldset::setData($this->data);
		return $this->data;
	}

	protected function getFormData() {
		$layout        = $this->app->getUserStateFromRequest($this->getName() . '.layout', 'layout');
		$context	= implode('.', array($this->option, $layout, $this->getName()));
		
		$data	= JFactory::getApplication()->getUserState($context . '.data', array());
		$data	= empty($data) ? $this->item : JArrayHelper::toObject($data);

		return $data;
	}
	
	public function getForm($data = array(), $loadData = true, $control = 'jform') {
		$component_name = $this->input->get('option');
		$model_name = $this->getName();
		
		$data = $data ? $data : $this->getFormData();
		$this->data = $data;

		// Get the form.
		$form = $this->loadForm($component_name . '.' . $model_name, $model_name, array('control' => $control, 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		
		return $form;
	}

	public function searchItem($table = null, $params = array(), $return_id = false) {
		$table = $table ? $table : $this->getName();
		$table = '#__gtsafetravel_'.$table;

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		if($return_id) {
			$query->select($db->quoteName('a.id'));
		} else {
			$query->select('a.*');
		}
		
		$query->from($db->quoteName($table, 'a'));

		foreach ($params as $pfield => $pvalue) {
			$query->where($db->quoteName('a.'.$pfield) . ' = ' . $db->quote($pvalue));
		}

		$db->setQuery($query);
		
		if(count($params) > 0) {
			if($return_id) {
				return intval($db->loadResult());
			} else {
				$result = $db->loadObject();
				return $result ? $result : new stdClass();
			}
		} else {
			return false;
		}
	}

	public function getFormExternal($name, $data = array(), $loadData = true, $control = 'jform') {
		$this->name	= $name;
		$return		= $this->getForm($data, $loadData, $control);
		$this->name	= $this->prevName;
		return $return;
	}

	public function save($data) {
		$data = is_object($data) ? JArrayHelper::fromObject($data) : (array) $data;
		foreach ($data as $k => $dat) {
			$dat = is_string($dat) ? trim($dat) : $dat;
			$dat = is_array($dat) ? array_filter($dat) : $dat;
			if($dat === '' || $dat === '[]' || $dat == array()) {
				$data[$k] = null;
			}
		}
		return parent::save($data);
	}

	public function saveExternal($data, $name = null, $fieldKey = null, $set_null = true, $is_table = false, $date = null) {
		$db			= $this->_db;
		$date		= $date ? $date : JFactory::getDate()->toSql();
		$user_id	= JFactory::getUser()->get('id');
		$data		= is_object($data) ? JArrayHelper::fromObject($data) : (array) $data;
		$table		= $name ? $name : strtolower($this->getName());

		if($is_table) {
			$tb		= $this->getTable($table);
			$table	= $tb->getTableName();
		} else {
			$table	= '#__gtsafetravel_'.$table;
		}

		$fields = $db->getTableColumns($table, false);
		$fields = array_keys($fields);
		
		$insert	= array();
		foreach ($data as $field => $value) {
			if(!in_array($field, $fields)) {
				continue;
			}
			$insert[$field] = GTHelper::isEmpty($value) && $set_null ? 'NULL' : $db->quote($value);
		}
		$updateCols = array_keys($insert);

		if(in_array('created', $fields)) {
			$insert['created'] = $db->quote($date);
		}
		if(in_array('created_by', $fields)) {
			$insert['created_by'] = $db->quote($user_id);
		}
		if(in_array('modified', $fields)) {
			$insert['modified'] = $db->quote($date);
		}
		if(in_array('modified_by', $fields)) {
			$insert['modified_by'] = $db->quote($user_id);
		}
		$insertCols = array_keys($insert);

		foreach ($updateCols as &$column) {
			$column = $db->quoteName($column).' = VALUES('.$db->quoteName($column).')';
		}
		if(in_array('modified', $fields)) {
			$updateCols[] = $db->quoteName('modified').' = '.$db->quote($date);
		}
		if(in_array('modified_by', $fields)) {
			$updateCols[] = $db->quoteName('modified_by').' = '.$db->quote($user_id);
		}
		$updateCols = implode(', ', $updateCols);

		$query = $db->getQuery(true);
		$query->insert($db->quoteName($table));
		$query->columns($insertCols);
		$query->values(implode(',', $insert));

		$query .= ' ON DUPLICATE KEY UPDATE '.$updateCols;
		//echo nl2br(str_replace('#__','eburo_',$query));
		$db->setQuery($query);
		$db->execute();
		return $fieldKey ? $this->getItemExternal(@$data[$fieldKey], $name, $fieldKey, $set_null, $is_table) : true;
	}
	
	public function saveReference($value, $type) {
		$table = GTHelper::pluralize($type);
		$id = $this->getReference($value, $table);
		if($id) {
			return $id;
		} else {
			$data		= new stdClass();
			$data->id	= 0;
			$data->name	= $value;
			return $this->saveExternal($data, $type, true);
		}
	}

	public function saveBulk($items, $table = null, $meta = true, $is_table = false, $set_null = true) {
		$db			= JFactory::getDbo();
		$date		= JFactory::getDate()->toSql();
		$user_id	= JFactory::getUser()->get('id');
		$user_id	= $this->input->get('user_id', $user_id);
		$user_id	= $user_id ? $user_id : null;
		$user_id	= GTHelper::isEmpty($user_id) ? 'NULL' : $db->quote($user_id);

		if($is_table) {
			$table = $this->getTable($table);
			$table = $table->getTableName();
		} else {
			$table = $table ? $table : $this->getName();
			$table = '#__gtsafetravel_'.$table;
		}
		
		$items = is_object($items) ? JArrayHelper::fromObject($items) : $items;
		if(!count($items) > 0) {
			return true;
		}

		$items = (array) array_chunk($items, 500);
		foreach ($items as $chunks) {
			$query = $db->getQuery(true);

			// Insert columns.
			$columns = reset($chunks);
			$columns = is_object($columns) ? JArrayHelper::fromObject($columns) : $columns;
			$columns = array_keys($columns);

			foreach ($chunks as &$item) {
				$item = is_object($item) ? JArrayHelper::fromObject($item) : $item;
				foreach ($item as &$val) {
					$val = GTHelper::isEmpty($val) && $set_null ? 'NULL' : $db->quote($val);
				}
				if($meta) {
					$item[]	= $db->quote($date);
					$item[]	= $user_id;
					$item[]	= $db->quote($date);
					$item[]	= $user_id;
				}
				$item	= implode(', ', $item);
			}

			// Prepare the insert query.
			$insert_cols = $meta ? array_merge($columns, array('created', 'created_by', 'modified', 'modified_by')) : $columns;
			$query->insert($db->quoteName($table));
			$query->columns($db->quoteName($insert_cols));
			$query->values($chunks);

			foreach ($columns as &$column) {
				$column = $db->quoteName($column).' = VALUES('.$db->quoteName($column).')';
			}
			if($meta) {
				$columns[]	= $db->quoteName('modified').' = '.$db->quote($date);
				$columns[]	= $db->quoteName('modified_by').' = '.$user_id;
			}
			
			$columns	= implode(', ', $columns);

			$query = $query . ' ON DUPLICATE KEY UPDATE ' . $columns;

			//echo nl2br(str_replace('#__','eburo_',$query)); die;

			// Set the query using our newly populated query object and execute it.
			$db->setQuery($query);

			$db->execute();
		}

		return true;
	}

	public function getReference($value, $type) {
		$table = '#__gtsafetravel_' . $type;
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))->from($table);
		$query->where('(' . $db->quoteName('id') . ' = ' . $db->quote($value) 
			. ' OR LOWER(' . $db->quoteName('name') . ') = LOWER(' . $db->quote($value) . '))');

		$db->setQuery($query);

		return @$db->loadObject()->id;
	}

	public function deleteExternal(&$pks, $table, $is_table = true) {
		if(!count(array_filter($pks)) > 0) {
			return true;
		}

		if($is_table) {
			$table = $this->getTable($table);
			$table = $table->getTableName();
		} else {
			$table = $table ? $table : $this->getName();
			$table = '#__gtsafetravel_'.$table;
		}
		
		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName($table));

		$pks = array_map(array($db, 'quote'), $pks);
		$pks = implode(',', $pks);
		$query->where($db->quoteName('id').' IN ('.$pks.')');

		$db->setQuery($query)->execute();
		
		return true;
	}

	public function getLastID($table, $component_prefix = true) {
		$prefix = $component_prefix ? '#__gtsafetravel_' : '#__';
		$table = $table ? $table : $this->getName();
		$table = $prefix.$table;
		
		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('MAX('.$db->quoteName('a.id').')');
		$query->from($db->quoteName($table, 'a'));

		$db->setQuery($query);
		return $db->loadResult();
	}
}
