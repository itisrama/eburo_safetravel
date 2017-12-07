<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelModelDashboard extends GTModelList{
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 * @since   1.6
	 */
	
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array();
		}

		unset($config['ignore_request']);

		parent::__construct($config);
	}
	
	protected function populateState($ordering = null, $direction = null) {
		parent::populateState($ordering, $direction);

		$this->setState('list.start', 0);
		$this->setState('list.limit', 0);
		
		// Adjust the context to support modal layouts.
		$layout = $this->input->get('layout', 'default');
		if ($layout) {
			$this->context.= '.' . $layout;
		}

		$earliestTravelDate	= $this->getEarliestTravelDate();
		$latestTravelDate	= $this->getLatestTravelDate();
		$travel_date_type	= $this->getUserStateFromRequest($this->context . '.travel_date_type', 'travel_date_type', 'year');
		$travel_start_date	= $this->getUserStateFromRequest($this->context . '.travel_start_date', 'travel_start_date', JHtml::date($earliestTravelDate, 'Y-m-d'));
		$travel_end_date	= $this->getUserStateFromRequest($this->context . '.travel_end_date', 'travel_end_date', JHtml::date($latestTravelDate, 'Y-m-d'));
		$travel_dates		= array(JHtml::date($travel_start_date, 'Y-m-d'), JHtml::date($travel_end_date, 'Y-m-d'));
		$travel_month		= $this->getUserStateFromRequest($this->context . '.travel_month', 'travel_month', JHtml::date($latestTravelDate, 'm-Y'));
		$travel_year		= $this->getUserStateFromRequest($this->context . '.travel_year', 'travel_year', JHtml::date($latestTravelDate, 'Y'));
		
		$this->setState('travel_date_type', $travel_date_type);
		$this->setState('travel_start_date', min($travel_dates));
		$this->setState('travel_end_date', max($travel_dates));
		$this->setState('travel_month', $travel_month);
		$this->setState('travel_year', $travel_year);


		$earliestEmergencyDate	= $this->getEarliestEmergencyDate();
		$latestEmergencyDate	= $this->getLatestEmergencyDate();
		$emergency_date_type	= $this->getUserStateFromRequest($this->context . '.emergency_date_type', 'emergency_date_type', 'year');
		$emergency_start_date	= $this->getUserStateFromRequest($this->context . '.emergency_start_date', 'emergency_start_date', JHtml::date($earliestEmergencyDate, 'Y-m-d'));
		$emergency_end_date		= $this->getUserStateFromRequest($this->context . '.emergency_end_date', 'emergency_end_date', JHtml::date($latestEmergencyDate, 'Y-m-d'));
		$emergency_dates		= array(JHtml::date($emergency_start_date, 'Y-m-d'), JHtml::date($emergency_end_date, 'Y-m-d'));
		$emergency_month		= $this->getUserStateFromRequest($this->context . '.emergency_month', 'emergency_month', JHtml::date($latestEmergencyDate, 'm-Y'));
		$emergency_year			= $this->getUserStateFromRequest($this->context . '.emergency_year', 'emergency_year', JHtml::date($latestEmergencyDate, 'Y'));
		
		$this->setState('emergency_date_type', $emergency_date_type);
		$this->setState('emergency_start_date', min($emergency_dates));
		$this->setState('emergency_end_date', max($emergency_dates));
		$this->setState('emergency_month', $emergency_month);
		$this->setState('emergency_year', $emergency_year);
	}

	protected function getExtremeDate($type = 'min', $table) {
		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);

		switch ($table) {
			case 'emergency':
				$query->select('DATE('.strtoupper($type).'('.$db->quoteName('a.created').'))');
				$query->from($db->quoteName('#__gtsafetravel_mob_emergency', 'a'));
				break;
			
			default:
				$query->select(strtoupper($type).'('.$db->quoteName('a.start_date').')');
				$query->from($db->quoteName('#__gtsafetravel_mob_travel', 'a'));
				break;
		}
		

		$db->setQuery($query);

		$date = $db->loadResult();

		return $date ? $date : JHtml::date('yesterday', 'Y-m-d');
	}

	public function getEarliestTravelDate() {
		return $this->getExtremeDate('min', 'travel');
	}

	public function getLatestTravelDate() {
		return $this->getExtremeDate('max', 'travel');
	}

	public function getEarliestEmergencyDate() {
		return $this->getExtremeDate('min', 'emergency');
	}

	public function getLatestEmergencyDate() {
		return $this->getExtremeDate('max', 'emergency');
	}

	public function getTravel() {
		$travel_date_type	= $this->getState('travel_date_type');
		$travel_start_date	= $this->getState('travel_start_date');
		$travel_end_date	= $this->getState('travel_end_date');
		$travel_month		= $this->getState('travel_month');
		$travel_year		= $this->getState('travel_year');

		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName('c.code', 'country_code'));
		$query->select($db->quoteName('c.name', 'country'));
		$query->select('COUNT('.$db->quoteName('a.id').') count');
		$query->from($db->quoteName('#__gtsafetravel_mob_travel_destination', 'a'));
		
		$query->join('INNER', $db->quoteName('#__gtsafetravel_mob_travel', 'b').' ON '.
			$db->quoteName('a.travel_id').' = '.$db->quoteName('b.id')
		);

		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_country', 'c').' ON '.
			$db->quoteName('a.country_id').' = '.$db->quoteName('c.id')
		);
		
		$query->where($db->quoteName('a.country_id').' > 0');

		switch ($travel_date_type) {
			default:
				$query->where('('.
					$db->quoteName('b.start_date').' BETWEEN '.$db->quote($travel_start_date).' AND '.$db->quote($travel_end_date).' OR '.
					$db->quoteName('b.end_date').' BETWEEN '.$db->quote($travel_start_date).' AND '.$db->quote($travel_end_date).
				')');
				break;
			case 'month':
				list($travel_month, $travel_year) = explode('-', $travel_month);
				$query->where('('.
					'(MONTH('.$db->quoteName('b.start_date').') = '.$db->quote($travel_month).' AND YEAR('.$db->quoteName('b.start_date').') = '.$db->quote($travel_year).') OR '.
					'(MONTH('.$db->quoteName('b.end_date').') = '.$db->quote($travel_month).' AND YEAR('.$db->quoteName('b.end_date').') = '.$db->quote($travel_year).')'.
				')');
				break;
			case 'year':
				$query->where('('.
					'YEAR('.$db->quoteName('b.start_date').') = '.$db->quote($travel_year).' OR '.
					'YEAR('.$db->quoteName('b.end_date').') = '.$db->quote($travel_year).
				')');
				break;
		}

		$query->group($db->quoteName('a.country_id'));
		$query->order('COUNT('.$db->quoteName('a.id').') desc');
		$query->setLimit(10);

		//echo nl2br(str_replace('#__','eburo_',$query)); die;

		$db->setQuery($query);
		$items = $db->loadObjectList();
		sort($items);

		$counts = array();
		$countries = array();
		$tableData = array();

		foreach ($items as $k => $item) {
			$counts[] = $item->count;
			$countries[$k+1] = array($k+1, strtoupper($item->country_code), $item->country);
			$tableData[] = array($item->count, $item->country, strtoupper($item->country_code));
		}
		rsort($tableData);

		$data = array();
		foreach ($items as $k => $item) {
			$rank = $item->rank = $item->count == min($counts) ? 1 : ceil((($item->count - min($counts)) / (max($counts) - min($counts))) * 5);
			$data[$rank][] = array($k+1, $item->count);
		}

		$result = new stdClass();
		$result->data = $data;
		$result->countries = array_values($countries);
		$result->tableData = array_values($tableData);

		return $result;
	}

	public function getEmergency() {
		$emergency_date_type	= $this->getState('emergency_date_type');
		$emergency_start_date	= $this->getState('emergency_start_date');
		$emergency_end_date		= $this->getState('emergency_end_date');
		$emergency_month		= $this->getState('emergency_month');
		$emergency_year			= $this->getState('emergency_year');

		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.latitude', 'a.longitude')));
		$query->select('GROUP_CONCAT(CONCAT_WS("~",'.
			$db->quoteName('b.full_name').','.
			$db->quoteName('a.name').','. 
			$db->quoteName('a.description').','. 
			$db->quoteName('a.location').','. 
			$db->quoteName('a.created').
		') SEPARATOR "|") content');

		$query->from($db->quoteName('#__gtsafetravel_mob_emergency', 'a'));
		
		$query->join('INNER', $db->quoteName('#__gtsafetravel_sys_client', 'b').' ON '.
			$db->quoteName('a.client_id').' = '.$db->quoteName('b.id')
		);

		switch ($emergency_date_type) {
			default:
				$query->where('('.
					$db->quoteName('a.created').' BETWEEN '.$db->quote($emergency_start_date).' AND '.$db->quote($emergency_end_date).
				')');
				break;
			case 'month':
				list($emergency_month, $emergency_year) = explode('-', $emergency_month);
				$query->where('('.
					'MONTH('.$db->quoteName('a.created').') = '.$db->quote($emergency_month).
				')');
				break;
			case 'year':
				$query->where('('.
					'YEAR('.$db->quoteName('a.created').') = '.$db->quote($emergency_year).
				')');
				break;
		}

		$query->group($db->quoteName('a.latitude'));
		$query->group($db->quoteName('a.longitude'));
		$query->order($db->quoteName('a.created').' desc');
		$query->setLimit(500);

		//echo nl2br(str_replace('#__','eburo_',$query)); die;

		$db->setQuery('SET SESSION group_concat_max_len = 1000000;')->execute();
		$db->setQuery($query);

		$items = $db->loadObjectList();

		foreach ($items as &$item) {
			$content = array();
			$rows = explode('|', $item->content);
			foreach ($rows as $row) {
				list($client, $name, $desc, $loc, $date) = explode('~', $row);
				$date		= JHtml::date($date, 'j F Y - H:i');
				$content[]	= sprintf('<div><h6>%s</h6><h5>%s<br/><small>%s - %s</small></h5>%s</div>', $date, $name, $client, $loc, $desc);
			}
			$item->content = implode('<hr/>', $content);
		}
		return $items;
	}
}