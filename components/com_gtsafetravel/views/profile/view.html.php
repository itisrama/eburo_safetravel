<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

defined('_JEXEC') or die;

class GTSafeTravelViewProfile extends GTView {

	public $item;
	public $itemView;
	public $form;
	public $state;
	public $canDo;
	public $params;
	public $buttons;
	public $item_title;

	public function ___construct($config = array()) {
		parent::__construct($config);
	}

	public function display($tpl = null) {
		$this->menu_name		= $this->menu->params->get('menu_name');

		// Get model data.
		$this->state		= $this->get('State');
		$this->params		= $this->state->params;
		
		$layout 			= $this->getLayout();
		
		$this->item			= $this->get('Item');
		$this->form			= $this->get('Form');

		$this->isNew		= intval((isset($this->item->id) && $this->item->id > 0) == 0);
		$this->isTrashed	= $this->item->published == -2;
		$this->checkedOut	= $this->isNew ? 0 : isset($this->item->checked_out) && (!($this->item->checked_out == 0 || $this->item->checked_out == $this->user->id));
		
		// Set page title
		if($layout == 'edit') {
			$this->page_title = $this->isNew ? JText::_('COM_GTSAFETRAVEL_PT_NEW') : JTEXT::_('COM_GTSAFETRAVEL_PT_EDIT');
			$this->page_title = str_replace('%s', JText::_('COM_GTSAFETRAVEL_PT_PROFILE'), $this->page_title);
			GTHelperHTML::setTitle($this->page_title);
		} else {
			$this->page_title	= $this->item->user->name;
			GTHelperHTML::setTitle($this->page_title);
		}

		// Assign additional data
		if (isset($this->item->id) && $this->item->id) {
			$this->canDo = GTHelperAccess::getActions($this->item->id, $this->getName());
		} else {
			$this->canDo = GTHelperAccess::getActions();
		}

		// Add pathway
		$pathway	= $this->app->getPathway();
		$pathway->addItem($this->page_title);
		
		// Check permission and display
		$created_by	= isset($this->item->created_by) ? $this->item->created_by : 0;
		GTHelperAccess::checkPermission($this->canDo, $created_by);

		parent::display($tpl);
	}

}
