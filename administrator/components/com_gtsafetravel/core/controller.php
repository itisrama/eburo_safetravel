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

jimport('joomla.application.component.controller');

class GTController extends JControllerLegacy
{
	public $app;
	public $user;
	public $menu;

	public function __construct($config = array()) {
		GTHelperHTML::loadHeaders();
		parent::__construct($config);

		if(@$config['load_params'] !== false) {
			// Set variables
			$this->app	= JFactory::getApplication();
			$this->user	= JFactory::getUser();
			$this->menu	= $this->app->getMenu()->getActive();

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

	function display($cachable = false, $urlparams = false)
	{
		$view   = $this->input->get('view', 'banners');
		$layout = $this->input->get('layout', 'default');
		$id     = $this->input->getInt('id');

		// Check for edit form.
		if ($layout == 'edit' && !$this->checkEditId('com_gtsafetravel.edit.'.$view, $id)) {

			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_gtsafetravel&view='.$view, false));

			return false;
		}
		
		parent::display($cachable, $urlparams);
		
		
	}
}