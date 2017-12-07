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

jimport('joomla.application.component.modellist');

class GTModelList extends JModelList {

	public $app;
	public $input;
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

		// Add table path
		$this->addTablePath(GT_TABLES);
	}

	protected function populateState($ordering = null, $direction = null) {
		parent::populateState($ordering, $direction);

		$search		= $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$search_by	= $this->getUserStateFromRequest($this->context . '.filter.search_by', 'filter_search_by');
		$published	= $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published');

		$this->setState('filter.search', $search);
		$this->setState('filter.search_by', $search_by);
		$this->setState('filter.published', $published);

		$limit	= $this->getUserStateFromRequest($this->context . '.limit', 'limit', $this->app->getCfg('list_limit'), 'uint');
		$limit	= $limit == 0 || $limit > 50 ? 50 : $limit;
		$this->setState('list.limit', $limit);		
		
		$page	= $this->app->getUserStateFromRequest($this->context . '.page', 'page');
		if($page) {
			$start	= ($page-1) * $limit;
			$this->setState('list.start', $start);
		}
	}

	public function getItems($is_table=false) {
		$items = parent::getItems();
		

		if($is_table) {
			$table = $this->getTable();
			foreach ($items as $k => $item) {
				$table->bind(JArrayHelper::fromObject($item));
				$pk = $table->getProperties(1);
				$items[$k] = JArrayHelper::toObject($pk); 
			}
		}
		
		return $items;
	}

	protected function prepareItemsJson($items) {
		$fields = $this->getFields();
		foreach ($items as &$item) {
			$col = new stdClass;
			foreach ($fields as $field) {
				list($title, $key, $class, $width) = $field;
				$value = @$item->$key;
				$value = GTHelperStr::trim($value);
				$width = $width == 'auto' ? '150px' : $width;
				$col->$key = sprintf('<div class="%s" style="min-width:%s">%s</div>', $class, $width, $value);
			}
			$item = $col;
		}

		$total	= $this->getTotal();
		//$total = $total > 1000 ? 1000 : $total;
		$result	= new stdClass();

		$result->draw				= $this->input->get('draw');
		$result->recordsTotal		= $total;
		$result->recordsFiltered	= $total;
		$result->data				= $items;
		return json_encode($result);
	} 

	public function getFilterData() {
		$data				= new stdCLass();
		$data->search		= $this->getState('filter.search');
		$data->search_by	= $this->getState('filter.search_by');
		$data->published	= $this->getState('filter.published');
		$data->published 	= is_numeric($data->published) ? $data->published : null;
		$data->ordering		= $this->getState('list.ordering');
		$data->direction	= $this->getState('list.direction');

		return $data;
	}

	public function getTotal($cachable = true, $removeJoin = true) {
		if(!$cachable) {
			return parent::getTotal();
		}

		$query = $this->_getListQuery();
		$query = clone $query;
		$isCount = 0;

		if($removeJoin) {
			$query->clear('join');
		}

		if ($query instanceof JDatabaseQuery
			&& $query->type == 'select'
			&& $query->group === null
			&& $query->union === null
			&& $query->unionAll === null
			&& $query->having === null)
		{
			$isCount = 1;
			$query = clone $query;
			$query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('COUNT(*)');
		}

		if ($query instanceof JDatabaseQuery)
		{
			$query = clone $query;
			$query->clear('limit')->clear('offset');
		}

		$query = str_replace('#__', $this->_db->getPrefix(), $query->__toString());

		$cache = JFactory::getCache($this->input->get('option'));
		$cache->setCaching(1);

		$count = $cache->call(array('GTHelper', 'getListCount'), $query, $isCount);

		return $count;
	}
	

}
