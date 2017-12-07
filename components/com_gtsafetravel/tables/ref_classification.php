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

class Tableref_classification extends GTTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct(&$db) {
		parent::__construct('#__gtsafetravel_ref_classification', 'id', $db);
	}
	
	/**
	 * Stores a ref_classification
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
		
		if(!$row->id)
			return parent::bind($array, $ignore);
		
		$array = JArrayHelper::fromObject($row);
		return parent::bind($array, $ignore);
	}
}
