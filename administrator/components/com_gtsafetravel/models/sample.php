<?php

/**
 * @package		GT JSON
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2016 Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;


class GTSafeTravelModelSample extends GTModelAdmin{

	protected function populateState() {
		parent::populateState();
	}
	
	public function getItem($pk = null) {
		$data = parent::getItem();
		if(!is_object($data)) return false;
		
		$this->item = $data;
		return $data;
	}

	public function getItemView() {
		$data = parent::getItem();
		if(!is_object($data)) return false;

		$this->item = $data;
		return $data;
	}

	public function save($data, $return_num = false) {
		$data = JArrayHelper::toObject($data);

		$return = parent::save($data);
		return $return;
	}
}
