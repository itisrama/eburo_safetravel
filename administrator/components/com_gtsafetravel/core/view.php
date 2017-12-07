<?php
/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// no direct access
defined( '_JEXEC' ) or die('Restricted access');

jimport('joomla.application.component.view');

class GTView extends JViewLegacy
{
	public $app;
	public $input;
	public $document;
	public $params;
	public $page_title;
	public $canDo;
	public $user;
	public $menu;

	public function __construct($config = array()) {
		parent::__construct($config);

		// Set variables
		$this->app		= JFactory::getApplication();
		$this->document	= JFactory::getDocument();
		$this->input	= $this->app->input;
		$this->params	= $this->app->isSite() ? $this->app->getParams() : NULL;
		$this->user		= JFactory::getUser();
		$this->menu		= $this->app->getMenu()->getActive();

		// Set User Profile
		$userProfiles	= JUserHelper::getProfile($this->user->id);
		foreach ($userProfiles as &$userProfile) {
			if(is_array($userProfile)) {
				$userProfile = JArrayHelper::toObject($userProfile, 'stdClass', false);
			}
		}
		$this->user->profile = $userProfiles;

		//$page_title		= $page_title ? $page_title : '';

		// Privilege
		$this->canDo		= GTHelperAccess::getActions();
		$this->canCreate	= $this->canDo->get('core.create');
		$this->canEdit		= $this->canDo->get('core.edit') || $this->canDo->get('core.edit.own');
		$this->canEditOwn 	= $this->canDo->get('core.edit.own');
		$this->canEditState	= $this->canDo->get('core.edit.state');
		$this->canDelete	= $this->canDo->get('core.delete');
		$this->isAdmin 		= $this->user->authorise('core.admin', 'com_gtpihps');

		foreach (array('create','edit','delete') as $priv) {
			$priv = 'can'.ucwords($priv);
			$pVal = @$this->get($$priv);
			$this->$priv = $pVal ? $pVal : $this->$priv;
		}

		// Set Title
		if($this->app->isSite()) {
			$this->page_title = $this->params->get('show_page_heading', 1) && $this->params->get('page_heading') ? $this->params->get('page_heading') : $this->document->getTitle();
		}
	}

	public function display($tpl = null) {		
		// Add pathway
		GTHelperHTML::setTitle($this->page_title);
		
		$urlMenuVar = parse_url($this->app->getMenu()->getActive()->link);
		parse_str($urlMenuVar['query'], $urlMenuVar);

		$urlMenuVar = array(
			'option'	=> $urlMenuVar['option'],
			'view'		=> $urlMenuVar['view'],
			'layout'	=> @$urlMenuVar['layout']
		);
		$urlMenuVar = http_build_query(array_filter($urlMenuVar));

		$urlVar = array(
			'option'	=> $this->input->get('option'),
			'view'		=> $this->input->get('view'),
			'layout'	=> @$this->input->get('layout')
		);
		$urlVar = http_build_query(array_filter($urlVar));

		if($urlMenuVar != $urlVar) {
			$pathway = $this->app->getPathway();
			$pathway->addItem($this->page_title);
		}

		$tpl = $tpl ? $tpl : $this->input->get('tpl');
		parent::display($tpl);
	}
}
