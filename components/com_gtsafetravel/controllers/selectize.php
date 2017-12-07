<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelControllerSelectize extends GTControllerAdmin {
	
	public function __construct($config = array()) {
		parent::__construct($config);
	}
	
	/**
	 * Proxy for getModel.
	 *
	 * @param	string	$name	The name of the model.
	 * @param	string	$prefix	The prefix for the PHP class name.
	 *
	 * @return	JModel
	 * @since	1.6
	 */
	public function getModel($name = 'Selectize', $prefix = '', $config = array('ignore_request' => true)) {
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	public function getItems() {
		$model		= $this->getModel();
		$items		= $model->searchItems();
		echo GTHelper::encodeJSON($items);
		$this->app->close();
	}

	public function getMasterRegencies() {
		$profil = $this->user->profile->pihpssurvey;
		$wheres = array($this->input->get('wheres'));
		if(@$profil->province_id > 0) {
			$wheres[] = 'province_id = '.$profil->province_id;
		}
		$this->input->set('wheres', implode('|', $wheres));
		$this->getItems();
	}

	public function getRegencies() {
		$profil = $this->user->profile->pihpssurvey;
		$wheres = array($this->input->get('wheres'));
		if(@$profil->province_id > 0) {
			$wheres[] = 'province_id = '.$profil->province_id;
		}
		if(@$profil->regency_ids) {
			$wheres[] = 'id IN ('.$profil->regency_ids.')';
		}
		$this->input->set('wheres', implode('|', $wheres));
		$this->getItems();
	}

	public function getRegions() {
		$profil = $this->user->profile->pihpssurvey;
		$wheres = array($this->input->get('wheres'));
		if(@$profil->region_id > 0) {
			$wheres[] = 'id = '.$profil->region_id;
		}
			
		$this->input->set('wheres', implode('|', $wheres));
		$this->getItems();
	}
}
