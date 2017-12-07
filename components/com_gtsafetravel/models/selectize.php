<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelModelSelectize extends GTModelList
{
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 * @since   1.6
	 */
	
	public function __construct($config = array()) {	
		parent::__construct($config);
	}
	
	public function searchItems() {
		$type			= $this->input->get('type');
		$id_field		= $this->input->get('id_field', 'id');
		$name_field		= $this->input->get('name_field', 'name');
		$code_field		= $this->input->get('code_field');
		$parent_field	= $this->input->get('parent_field');
		$parent_value	= $this->input->get('parent_value');
		$ordering		= $this->input->get('ordering', 'name');
		$wheres			= array_filter(explode('|', $this->input->get('wheres', '', true)));

		// Get a db connection.
		$db		= $this->_db;
		
		// Create a new query object.
		$query	= $db->getQuery(true);
		
		// Select fields from main table
		$query->select('a.*');
		$query->select($db->quoteName('a.'.$id_field, 'id'));
		if(!in_array($ordering, array('name', 'id'))) {
			$query->select($db->quoteName('a.'.$ordering));
		}
		if($code_field) {
			$query->select('CONCAT('.$db->quoteName('a.'.$code_field).', " - ", '.$db->quoteName('a.'.$name_field).') name');
		} else {
			$query->select($db->quoteName('a.'.$name_field, 'name'));
		}
		$query->from($db->quoteName('#__gtsafetravel_'.GTHelper::pluralize($type), 'a'));
		
		// Filter search
		$search		= JRequest::getVar('search');
		if (!empty($search)) {
			
			// If contains spaces, the words will be used as keywords.
			if (preg_match('/\s/', $search)) {
				$search = str_replace(' ', '%', $search);
			}
			$search			= $db->quote('%' . $search . '%');
			
			$search_query	= array();
			$search_query[]	= $db->quoteName('a.'.$name_field) . 'LIKE ' . $search;
			if($code_field) {
				$search_query[]	= $db->quoteName('a.'.$code_field) . 'LIKE ' . $search;
			}
			$query->where('(' . implode(' OR ', $search_query) . ')');
		}

		if($parent_field) {
			$query->where($db->quoteName($parent_field) . ' = ' . $db->quote($parent_value));
		}

		$query->where($db->quoteName('a.published') . ' = 1');

		if(count($wheres)>0) {
			foreach ($wheres as $where) {
				$query->where($where);
			}
		}

		$query->order($db->escape('RAND()'));

		//echo nl2br(str_replace('#__','eburo_',$query)); die;

		$data = $this->_getList($query, 0, 100);

		
		return $data;
	}
}
