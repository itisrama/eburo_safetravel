<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelControllerDashboard extends GTControllerAdmin {
	
	public function __construct($config = array()) {
		parent::__construct($config);
	}
	
	public function travel() {
		$model = $this->getModel();
		$data = $model->getTravel();

		header('Content-type: application/json; charset=utf-8');
		echo GTHelper::encodeJSON($data);
		$this->app->close();
	}

	public function emergency() {
		$model = $this->getModel();
		$data = $model->getEmergency();

		header('Content-type: application/json; charset=utf-8');
		echo GTHelper::encodeJSON($data);
		$this->app->close();
	}
}
