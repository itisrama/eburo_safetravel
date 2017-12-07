<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelControllerRef_Item extends GTControllerForm
{
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->getViewItem(array('id'));
	}
}
