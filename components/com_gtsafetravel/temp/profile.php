<?php

/**
 * @package		GT PIHPS
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class TableProfile extends GTTable{
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct(&$db) {
		// Get Feeder ID
		$input		= JFactory::getApplication()->input;

		parent::__construct('#__gtsafetravel_users' , 'id', $db);
	}
	
	/**
	 * Stores a contact
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
		
		if(!@$row->id) 
			return parent::bind($array, $ignore);

		$province		= $this->getTable('ref_province'); $province->load(@$row->province_id);
		$province		= $province->getProperties(1);
		$province		= JArrayHelper::toObject($province);

		$row->view				= new stdCLass();
		$row->view->province_id	= $province->name;

		$row->regency_ids	= @$row->regency_ids;
		$row->regency_ids 	= is_string($row->regency_ids) ? explode(',', $row->regency_ids) : $row->regency_ids;

		$regencies = $this->getTable('ref_regency');
		$regencies = $regencies->getList(array('id' => $row->regency_ids));
		$regencies = array_map(function($com) { return $com['name']; }, $regencies);
		$regencies = implode('<br/>', $regencies);

		$row->view					= new stdCLass();
		$row->view->regency_ids	= $regencies;

		$array = JArrayHelper::fromObject($row);
		return parent::bind($array, $ignore);
	}
}
