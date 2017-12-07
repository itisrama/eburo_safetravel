<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

defined('_JEXEC') or die;

class GTSafeTravelViewAPI extends GTView {
	public function ___construct($config = array()) {
		parent::__construct($config);
	}

	public function display($tpl = null) {
		
		$this->app->close();
	}

}
