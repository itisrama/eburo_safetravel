<?php

/**
 * @package		GT Component 
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelViewUser_Items extends GTView {

	protected $items;
	protected $pagination;
	protected $state;

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	function display($tpl = null) {
		$this->menu_name		= $this->menu->params->get('menu_name');
		
		// Get model data.
		$this->state		= $this->get('State');
		$this->modal 		= new JLayoutFile('modal.default');

		// Get Filter Form
		$filter_model		= JModelLegacy::getInstance('Ref_Filter', 'GTSafeTravelModel');
		$filter_data		= $this->get('FilterData');
		$this->filter_form	= $filter_model->getForm($filter_data);

		if($this->input->get('ajax')) {
			$this->items	= $this->get('Items');
			echo $this->items;
			$this->app->close();
		} else {
			$url = GTHelper::getURL(array('ajax' => 1));
			$cols = $this->get('Fields');
			GTHelperDataTable::load();
			GTHelperDataTable::server('adminlist', $url, $cols, array(
				'start'			=> $this->state->get('list.start'),
				'length'		=> $this->state->get('list.limit'),
				'searching'		=> false,
				'lengthChange'	=> true,
				'ordering'		=> true,
				'order'			=> $this->state->get('filter.orders'),
				'orderIndex'	=> 2,
				'orderMulti'	=> true
			));
			parent::display($tpl);
		}
	}

}
