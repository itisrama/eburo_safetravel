<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;


class GTSafeTravelModelLP_User_Filter extends GTModelAdmin
{

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	protected function populateState() {
		parent::populateState();

		$id = $this->input->getInt('id', 0);
		$this->setState($this->getName().'.id', intval($id));
	}

	public function getForm($data = array(), $loadData = true, $control = false) {
		return parent::getForm($data, $loadData, $control);
	}
	
	public function getItem($pk = null) {
		return false;
	}

	public function save($data){
		return true;
	}

	public function delete(&$pks) {
		return true;
	}
}
