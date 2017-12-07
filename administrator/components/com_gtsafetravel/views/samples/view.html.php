<?php

/**
 * @package		GT JSON
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2016 Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelViewSamples extends GTView {

	protected $items;
	protected $pagination;
	protected $state;

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	function display($tpl = null) {
		// Get model data.
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->state		= $this->get('State');
		
		GTHelper::addSubmenu('samples');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		
		parent::display($tpl);
	}

	protected function addToolbar() {
		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');

		JToolbarHelper::title(JText::_('COM_GTSAFETRAVEL_PT_SAMPLES'), 'list menu');

		if ($this->canCreate)
			JToolbarHelper::addNew('sample.add');

		if ($this->canEdit)
			JToolbarHelper::editList('sample.edit');

		if ($this->canEditState)
		{
			JToolbarHelper::publish('samples.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('samples.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::archiveList('samples.archive');
			JToolbarHelper::checkin('samples.checkin');
		}

		if ($this->state->get('filter.published') == -2 && $this->canDelete)
		{
			JToolbarHelper::deleteList('', 'samples.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($this->canEditState)
		{
			JToolbarHelper::trash('samples.trash');
		}

		if ($this->isAdmin)
			JToolbarHelper::preferences('com_gtsafetravel');

		JToolbarHelper::help('JHELP_COMPONENTS_CONTACTS_CONTACTS');

		JHtmlSidebar::setAction('index.php?option=com_joborder');

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_PUBLISHED'),
			'filter_published',
			JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
		);
	}

	protected function getSortFields() {
		return array(
			'a.id'=> JText::_('JGRID_HEADING_ID'), 
			'a.name'=> JText::_('COM_GTSAFETRAVEL_FIELD_NAME'),
		);
	}
}
