<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

class GTModel extends JModelLegacy {

	public $app;
	public $input;
	public $user;
	public $menu;

	public function __construct($config = array()) {
		parent::__construct($config);

		if(@$config['load_params'] !== false) {
			// Set variables
			$this->app		= JFactory::getApplication();
			$this->input	= $this->app->input;
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
		}
	}

	protected function populateState() {
		$offset = $this->input->get('limitstart');
		$this->setState('list.offset', $offset);

		// Load the parameters.
		$params = $this->app->getParams();
		$this->setState('params', $params);
	}

}