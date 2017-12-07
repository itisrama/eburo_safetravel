<?php

/**
 * @package		GT Safe Travel
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class Tablemob_travel extends GTTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct(&$db) {
		parent::__construct('#__gtsafetravel_mob_travel', 'id', $db);
	}
	
	/**
	 * Stores a mob_travel
	 *
	 * @param	boolean	True to update fields even if they are null.
	 * @return	boolean	True on success, false on failure.
	 * @since	1.6
	 */
	public function store($updateNulls = false) {
		// Attempt to store the data.
		return parent::store($updateNulls);
	}
	
	public function bind($array, $ignore = '') {
		$row = JArrayHelper::toObject($array);
		
		if(!$row->id) return parent::bind($array, $ignore);

		$sys_client			= $this->getTable('sys_client'); $sys_client->load(@$row->client_id);
		$sys_client			= $sys_client->getProperties(1);
		$sys_client			= JArrayHelper::toObject($sys_client);

		$row->view				= new stdCLass();
		$row->view->start_date	= GTHelperDate::format(@$row->start_date, 'j F Y');
		$row->view->end_date	= GTHelperDate::format(@$row->end_date, 'j F Y');
		$row->view->client_id	= $sys_client->full_name;
		
		$array = JArrayHelper::fromObject($row);
		return parent::bind($array, $ignore);
	}
}
