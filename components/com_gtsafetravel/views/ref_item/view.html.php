<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

defined('_JEXEC') or die;

class GTSafeTravelViewRef_Item extends GTView {

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
		// Get model data.
		$this->state		= $this->get('State');
		$this->params		= $this->state->params;
		
		$layout 			= $this->getLayout();
		
		$item				= $this->get('Item');
		$this->form			= $this->get('Form');

		$this->isNew		= intval((isset($item->id) && $item->id > 0) == 0);
		$this->isTrashed	= $item->published == -2;
		$this->checkedOut	= $this->isNew ? 0 : isset($item->checked_out) && (!($item->checked_out == 0 || $item->checked_out == $this->user->id));
		
		// Set page title
		$pageTitle = '';
		if($layout == 'edit') {
			$pageTitle = $this->isNew ? JText::_('COM_GTSAFETRAVEL_PT_NEW') : JTEXT::_('COM_GTSAFETRAVEL_PT_EDIT');
			$pageTitle = str_replace('%s', JText::_('COM_GTSAFETRAVEL_PT_ITEM'), $this->page_title);
		} elseif($item->id > 0) {
			$pageTitle = @$item->view->page_title;
			$pageTitle = $pageTitle ? $pageTitle : @$item->name;
			$pageTitle = $pageTitle ? $pageTitle : @$item->title;
		}

		if($pageTitle) {
			GTHelperHTML::setTitle($pageTitle);
		}

		// Assign additional data
		if (isset($item->id) && $item->id) {
			$this->canDo = GTHelperAccess::getActions($item->id, $this->getName());
		} else {
			$this->canDo = GTHelperAccess::getActions();
		}

		$this->item = $item;
		$this->page_title = $pageTitle;
		
		// Check permission and display
		$created_by	= isset($item->created_by) ? $item->created_by : 0;
		GTHelperAccess::checkPermission($this->canDo, $created_by);

		$this->document->addScript(GT_ADMIN_JS . '/inputmask/jquery.inputmask.js');
		$this->document->addScript(GT_ADMIN_JS . '/inputmask/inputmask.js');
		$this->document->addScript(GT_ADMIN_JS . '/inputmask/inputmask.extensions.js');
		$this->document->addScript(GT_JS . '/ref_item.js');

		$tpl = @$item->tpl;
		parent::display($tpl);
	}

}
