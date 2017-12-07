<?php

/**
 * @package		GT Safe Travel
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2016 Gamatechno. All Rights Reserved.
 */

defined('_JEXEC') or die;

class GTSafeTravelViewSample extends GTView {

	var $form;
	var $item;
	var $state;
	
	public function display($tpl = null) {
		// Initialiase variables.
		$this->item			= $this->get('Item');
		$this->form			= $this->get('Form');
		$this->state		= $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}
		$this->addToolbar();

		parent::display($tpl);
	}
	
	protected function addToolbar()	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);

		$title = $isNew ? JText::_('COM_GTSAFETRAVEL_PT_NEW') : JText::_('COM_GTSAFETRAVEL_PT_EDIT');
		$title = sprintf($title, JText::_('COM_GTSAFETRAVEL_PT_SAMPLE'));
		JToolbarHelper::title($title, 'list menus');

		// If not checked out, can save the item.
		if ($this->canEdit)
		{
			JToolbarHelper::apply('sample.apply');
			JToolbarHelper::save('sample.save');

			if ($this->canCreate)
			{
				JToolbarHelper::save2new('sample.save2new');
			}
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $this->canCreate)
		{
			JToolbarHelper::save2copy('sample.save2copy');
		}

		if (empty($this->item->id))
		{
			JToolbarHelper::cancel('sample.cancel');
		}
		else
		{
			if ($this->state->params->get('save_history', 0) && $this->canEdit)
			{
				JToolbarHelper::versions('com_gtsafetravel.sample', $this->item->id);
			}

			JToolbarHelper::cancel('sample.cancel', 'JTOOLBAR_CLOSE');
		}
	}

}
