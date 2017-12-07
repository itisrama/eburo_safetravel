<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelModelRef_Items extends GTModelList
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

		$menu_name = $this->menu->params->get('menu_name');

		// Adjust the context to support modal layouts.
		$layout = $this->input->get('layout', 'default');
		if ($layout) {
			$this->context.= '.'.$layout.'.'.$menu_name;
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
		//$profile = $this->user->profile->safetravel;
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
		$query->select('IF(DAY('.$db->quoteName('a.modified').'), '.$db->quoteName('a.modified').', '.$db->quoteName('a.created').') changed');
		$query->from($db->quoteName('#__gtsafetravel_'.$menu_name, 'a'));

		switch($menu_name) {
			case 'mob_comment':
				$query->select($db->quoteName('t21.full_name', 'client'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_sys_client', 't21').' ON'.
					$db->quoteName('a.client_id').' = '.$db->quoteName('t21.id')
				);
				break;
			case 'mob_emergency':
				$query->select($db->quoteName('t21.full_name', 'client'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_sys_client', 't21').' ON'.
					$db->quoteName('a.client_id').' = '.$db->quoteName('t21.id')
				);
				break;
			case 'mob_travel':
				$query->select($db->quoteName('t21.full_name', 'client'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_sys_client', 't21').' ON'.
					$db->quoteName('a.client_id').' = '.$db->quoteName('t21.id')
				);
				break;
			case 'ref_country':
				$query->select($db->quoteName('t9.name', 'indicator'));
				$query->select($db->quoteName('t9.id', 'indicator_id'));
				$query->join('LEFT', $db->quoteName('#__gtsafetravel_ref_country_indicator', 't22').' ON'.
					$db->quoteName('a.id').' = '.$db->quoteName('t22.country_id')
				);
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_indicator', 't9').' ON'.
					' IFNULL('.$db->quoteName('t22.indicator_id').',5) = '.$db->quoteName('t9.id')
				);
				break;
			case 'ref_embassy':
				$query->select($db->quoteName('t6.name', 'country'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_country', 't6').' ON'.
					$db->quoteName('a.country_id').' = '.$db->quoteName('t6.id')
				);
				break;
			case 'sys_user':
				$query->select($db->quoteName('t6.name', 'country'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_country', 't6').' ON'.
					$db->quoteName('a.country_id').' = '.$db->quoteName('t6.id')
				);
				$query->select($db->quoteName('t7.name', 'embassy'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_embassy', 't7').' ON'.
					$db->quoteName('a.embassy_id').' = '.$db->quoteName('t7.id')
				);
				break;
			case 'web_news':
				$query->select($db->quoteName('t6.name', 'country'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_country', 't6').' ON'.
					$db->quoteName('a.country_id').' = '.$db->quoteName('t6.id')
				);
				break;
			case 'web_notification':
				$query->select($db->quoteName('t21.full_name', 'client'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_sys_client', 't21').' ON'.
					$db->quoteName('a.client_id').' = '.$db->quoteName('t21.id')
				);
				$query->select($db->quoteName('t6.name', 'country'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_country', 't6').' ON'.
					$db->quoteName('a.country_id').' = '.$db->quoteName('t6.id')
				);
				break;
			case 'web_service':
				$query->select($db->quoteName('t6.name', 'country'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_country', 't6').' ON'.
					$db->quoteName('a.country_id').' = '.$db->quoteName('t6.id')
				);
				$query->select($db->quoteName('t7.name', 'embassy'));
				$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_embassy', 't7').' ON'.
					$db->quoteName('a.embassy_id').' = '.$db->quoteName('t7.id')
				);
				break;
		}
		
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
		
		switch ($this->menu->params->get('menu_name')) {
			default:
				$orderFields = array(
					2 => 'a.id', 3 => 'a.name'
				);
				break;
		}

		$fields = $this->getFields();

		foreach ($orders as $order) {
			$order = JArrayHelper::toObject($order);
			$ordername = @$orderFields[$order->column];
			if(!$ordername) continue;

			switch ($ordername) {
				case 'changed' :
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
		$agent 		= GTHelper::agent();
		$isTablet  	= $agent->isTablet();
		$isMobile  	= $agent->isMobile() && !$agent->isTablet();
		$maxCols	= $isMobile ? 1 : ($isTablet ? 2 : 5);
		$menuName	= $this->menu->params->get('menu_name');
		$checkrow	= '<input type="checkbox" name="checkall-toggle" value="" title="'.JText::_('COM_GTSAFETRAVEL_CHECK_ALL').'" onclick="Joomla.checkAll(this)" />';
		$form		= GTHelperDB::getForm($menuName, true);
		$fields		= array(
			array($checkrow, 'checkrow', 'text-center', '15px', false),
		);
		
		$count = 1;
		$countMain = 1;
		foreach ($form as $field => $fl) {
			if(in_array($fl->type, array('hidden', 'althidden'))){
				continue;
			}

			$align	= @$fl->align;
			$width	= @$fl->width;
			$align	= $align ? $align : 'left';
			$width	= $width ? $width : 'auto';
			$name	= str_replace('_id', '', $fl->name);
			
			if(!in_array($field, array('id', 'name', 'title'))) {
				if($count > $maxCols) {
					continue;
				}
				$count++; 
			} else {
				if($countMain > $maxCols+1) {
					continue;
				}
				$countMain++;
			}

			$fields[] = array(JText::_($fl->label), $name, 'text-'.$align, $width, false);
		}
		if(!$isMobile) {
			$fields[] = array(JText::_('COM_GTSAFETRAVEL_FIELD_CHANGED'), 'changed', 'dt-center', '120px', false);
			$fields[] = array(JText::_('COM_GTSAFETRAVEL_FIELD_ID'), 'id', 'dt-center', '20px', false);
		}
		
		return $fields;
	}

	public function getItems($is_table = false) {
		$menuName = $this->menu->params->get('menu_name');
		$items = parent::getItems($is_table);
		$popupExcludes = array('mob_travel');
		
		foreach ($items as $i => &$item) {
			$changed 			= $item->changed;
			$diff 				= intval($changed) ? GTHelperDate::diff($changed) : null;
			$item->changed		= $diff ? JHTML::tooltip($diff, '', '', GTHelperDate::format($changed, 'd/m/Y H:i')) : $changed;
			$item->checkrow	= JHtml::_('grid.id', $i, $item->id);

			$viewPopup	= GTHelper::getURL(array('view' => 'ref_item',	'layout' => 'view', 'id' => $item->id, 'tmpl' => 'component'));
			$viewDetail	= GTHelper::getURL(array('view' => 'ref_item',	'layout' => 'view', 'id' => $item->id));
			
			switch ($this->menu->params->get('menu_name')) {
				case 'ref_country':
					$flag		= GTHelper::verifyFile(GT_SAFETRAVEL_FLAG_URI.strtolower($item->code).'.png');
					$flag 		= $flag ? $flag : GTHelper::verifyFile(GT_SAFETRAVEL_FLAG_URI.'0.png');
					$flag		= sprintf('<img title="%s" src="%s" style="border:1px solid; margin-right:5px" height="30px" width="50px" />', $item->name, $flag);
					$indicator	= GTHelper::verifyFile(GT_SAFETRAVEL_INDICATOR_URI.$item->indicator_id.'.png');
					$indicator	= $indicator ? sprintf('<img title="%s" src="%s" height="30px" />', $item->name, $indicator) : null;

					$item->name 		= sprintf('<a href="%s"><strong>%s</strong></a>', $viewDetail, $item->name);
					$item->name			= $flag.$item->name;
					$item->indicator	= $indicator ? $indicator.'&nbsp;&nbsp;'.$item->indicator : $item->indicator;
					$item->latitude 	= GTHelperGeo::convDECtoDMS($item->latitude, 1);
					$item->longitude 	= GTHelperGeo::convDECtoDMS($item->longitude, 2);
					break;
				case 'mob_travel':
					$item->name  = sprintf('<a href="%s"><strong>%s</strong></a>', $viewDetail, $item->name);
					break;
				default:
					if(isset($item->name)) {
						$item->name = sprintf('<a href="%s"><strong>%s</strong></a>', $viewPopup, $item->name);
					}
					if(isset($item->title)) {
						$item->title = sprintf('<a href="%s"><strong>%s</strong></a>', $viewPopup, $item->title);
					}
					break;
			}


			$item = GTHelperArray::handleNull($item, '<div style="color:red">'.JText::_('COM_GTSAFETRAVEL_EMPTY').'</div>');
		}

		return $this->prepareItemsJson($items);
	}

	public function getTotal($cachable = true, $removeJoin = false) {
		return parent::getTotal($cachable, $removeJoin);
	}
}
