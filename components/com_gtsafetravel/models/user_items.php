<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelModelUser_Items extends GTModelList
{
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 * @since   1.6
	 */
	
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array('a.id', 'a.name', 'a.date');
		}
		
		parent::__construct($config);
	}
	
	protected function populateState($ordering = 'a.id', $direction = 'desc') {
		parent::populateState($ordering, $direction);

		// Adjust the context to support modal layouts.
		$layout = $this->input->get('layout', 'default');
		if ($layout) {
			$this->context.= '.' . $layout;
		}
		
		$start	= $this->getUserStateFromRequest($this->context.'.filter.start', 'start', 0);
		$length	= $this->getUserStateFromRequest($this->context.'.filter.limit', 'length', 10);
		$orders	= $this->getUserStateFromRequest($this->context.'.filter.orders', 'order', array(), 'array');
		$this->setState('list.start', $start);
		$this->setState('list.limit', $length);
		$this->setState('filter.orders', $orders);

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		
		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '1');
		$this->setState('filter.published', $published);

		$profil		= @$this->user->profile->pihpssurvey;
		$filters	= array();

		if(@$profil->id_wil_dir1 == 1) {
			$profil->id_wil_dir1 = null;
		}

		foreach ($filters as $filter) {
			$profilVal = @$profil->$filter;
			$state = $this->getUserStateFromRequest($this->context.'.filter.'.$filter, $filter, $profilVal);
			$this->setState('filter.'.$filter, $profilVal ? $profilVal : $state);
		}
	}

	public function getFilterData() {
		$data = parent::getFilterData();

		$filters = array();
		foreach ($filters as $filter) {
			$data->$filter = $this->getState('filter.'.$filter);
		}
		return $data;
	}
	
	protected function getListQuery() {
		$profile = $this->user->profile->pihpssurvey;
		$menu_name = $this->menu->params->get('menu_name');
		switch($menu_name) {
			default:
				$filters = array();
				break;
		}
		
		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select item
		$query->select('a.*');
		$query->select('IF(DAY('.$db->quoteName('a.modified').'), '.$db->quoteName('a.modified').', '.$db->quoteName('a.created').') date');
		$query->from($db->quoteName('#__gtsafetravel_users', 'a'));
		
		$query->select($db->quoteName(array('b.username', 'b.email')));
		$query->join('INNER', $db->quoteName('#__users', 'b').' ON'.
			$db->quoteName('a.user_id').' = '.$db->quoteName('b.id')
		);

		$query->select($db->quoteName('c.name', 'region'));
		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_regions', 'c').' ON'.
			$db->quoteName('a.region_id').' = '.$db->quoteName('c.id')
		);
		
		// Publish filter
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('a.published = ' . (int)$published);
		} else {
			$query->where('a.published IN (0, 1)');
		}
		
		foreach ($filters as $filter) {
			$filterVal = $this->getState('filter.'.$filter);
			if(!$filterVal) continue;
			
			$query->where($db->quoteName('a.'.$filter).' = '.$db->quote($filterVal));		
		}

		$query->where($db->quoteName('a.type').' = '.$db->quote($menu_name));
		
		$region_id = @$profile->region_id;
		if($region_id) {
			$query->where($db->quoteName('a.region_id').' = '.$db->quote($region_id));
		}
		
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			
			// If contains spaces, the words will be used as keywords.
			if (preg_match('/\s/', $search)) {
				$search = str_replace(' ', '%', $search);
			}
			$search = $db->quote('%' . $search . '%');
			
			$search_query = array();
			$search_query[] = $db->quoteName('a.name') . 'LIKE ' . $search;
			$query->where('(' . implode(' OR ', $search_query) . ')');
		}
		
		$query->group($db->quoteName('a.id'));

		// Add the list ordering clause.
		$orders = (array) $this->getState('filter.orders');
		//echo "<pre>"; print_r($orders); echo "</pre>";
		switch ($this->menu->params->get('menu_name')) {
			default:
				$orderFields = array(
					2 => 'a.id', 3 => 'a.name', 4 => 'b.username', 5 => 'c.name', 6 => 'a.phone', 7 => 'b.email', 8 => 'date'
				);
				break;
		}

		$fields = $this->getFields();

		foreach ($orders as $order) {
			$order = JArrayHelper::toObject($order);
			$ordername = @$orderFields[$order->column];
			if(!$ordername) continue;

			switch ($ordername) {
				case 'date' :
					$query->order('IF(DAY('.$db->quoteName('a.modified').'), '.$db->quoteName('a.modified').', '.$db->quoteName('a.created').') ' . $order->dir);
					$query->order($db->quoteName('a.id') . ' ' . $order->dir);
					break;
				default:
					$query->order($db->quoteName($ordername).' '.$order->dir);
					break;
			}
		}
		
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $query;
	}

	public function getFields() {
		$checkrow	= '<input type="checkbox" name="checkall-toggle" value="" title="'.JText::_('COM_GTSAFETRAVEL_CHECK_ALL').'" onclick="Joomla.checkAll(this)" />';

		switch ($this->menu->params->get('menu_name')) {
			case 'surveyor':
			case 'validator':
				return array(
					array($checkrow, 'checkrow', 'text-center', '15px', false),
					array(JText::_('COM_GTSAFETRAVEL_ACTION'), 'action', 'text-center', '50px', false),
					array(JText::_('JGLOBAL_FIELD_ID_LABEL'), 'id', 'text-center', '80px', true),
					array(JText::_('COM_GTSAFETRAVEL_FIELD_NAME'), 'name', 'text-left', 'auto', true),
					array(JText::_('COM_GTSAFETRAVEL_FIELD_USERNAME'), 'username', 'text-left', '100px', true),
					array(JText::_('COM_GTSAFETRAVEL_FIELD_REGION'), 'region', 'text-left', 'auto', true),
					array(JText::_('COM_GTSAFETRAVEL_FIELD_PHONE'), 'phone', 'text-left', '200px', true),
					array(JText::_('COM_GTSAFETRAVEL_FIELD_EMAIL'), 'email', 'text-left', 'auto', true),
					array(JText::_('COM_GTSAFETRAVEL_FIELD_DATE'), 'date', 'text-left', '300px', true)
				);
				break;
		}
	}

	public function getItems($is_table = false) {
		$menu_name = $this->menu->params->get('menu_name');
		$items = parent::getItems($is_table);
		
		foreach ($items as $i => &$item) {
			$date 			= $item->date;

			switch ($menu_name) {
				case 'fitur':
					$item->tanggal = GTHelperDate::format($item->tanggal, 'd M Y');
					break;
				default:
					break;
			}

			$item->date		= GTHelperDate::format($date, 'd M Y H:i');
			
			$editUrl  = GTHelper::getURL(array(
				'task'	=> 'user_item.edit',
				'id'	=> $item->id,
				'tmpl'	=> 'component'
			));
			$viewUrl = GTHelper::getURL(array(
				'view'		=> 'user_item',
				'layout'	=> 'view',
				'id'		=> $item->id,
				'tmpl'		=> 'component'
			));

			$item->checkrow	= JHtml::_('grid.id', $i, $item->id);
			$item->action = '<div class="text-left" style="width:60px">'.implode('', array(
				sprintf('<button link="%s" class="modalForm btn btn-default btn-xs btn-%s" style="margin: 0 3px 5px !important;"><i class="fa fa-fw fa-%s"></i> %s</button>', $viewUrl, 'primary', 'eye', JText::_('COM_GTSAFETRAVEL_TOOLBAR_VIEW'))
			)).'</div>';

			$item = GTHelperArray::handleNull($item, '<div class="text-center" style="color:red">- '.JText::_('COM_GTSAFETRAVEL_EMPTY').' -</div>');
			$item->diff = intval($date) ? GTHelperDate::diff($date) : null;
		}

		return $this->prepareItemsJson($items);
	}

	public function getTotal($cachable = true, $removeJoin = false) {
		return parent::getTotal($cachable, $removeJoin);
	}
}
