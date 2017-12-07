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

class GTHelperAccess
{
	public static function getUser($user_id = NULL) {
		if(!$user_id) {
			return JFactory::getUser();
		}
		
		// Get a db connection.
		$db = JFactory::getDbo();
		
		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id','a.name','a.email','a.sendEmail','a.registerDate','a.lastvisitDate')));
		$query->from($db->quoteName('#__users', 'a'));

		// Join message
		$query->select('GROUP_CONCAT('.$db->quoteName('b.group_id').') groups');
		$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'b'). ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.user_id'));

		$query->where($db->quoteName('a.id').' = '.$db->quote($user_id));

		$db->setQuery($query);
		$data = $db->loadObject();
		
		$user					= new stdClass();
		$user->id				= intval(@$data->id);
		$user->name				= @$data->name;
		$user->email			= @$data->email;
		$user->sendEmail		= @$data->sendEmail;
		$user->registerDate		= @$data->registerDate;
		$user->lastvisitDate	= @$data->lastvisitDate;
		$user->groups			= array_filter(explode(',', @$data->groups));

		return $user;
	}
	
	public static function isAdmin($user_id = NULL) {
		$user = self::getUser($user_id);
		$admin_groups = array(7, 8);

		foreach ($admin_groups as $group_id) {
			if (in_array($group_id, $user->groups)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the actions
	 */
	public static function getActions()
	{
		jimport('joomla.access.access');
		$user		= JFactory::getUser();
		$result		= new JObject;

		$assetName = 'com_gtsafetravel';
		$level = 'component';

		$actions = JAccess::getActions('com_gtsafetravel', $level);

		foreach ($actions as $action) {
			$result->set($action->name,	$user->authorise($action->name, $assetName));
		}
		return $result;
	}

	/**
	 * Check user permission for accessing edit view directly.
	 */
	public static function checkPermission($canDo, $created_by = 0, $toIndex = false) {
		$app		= JFactory::getApplication();
		$user		= JFactory::getUser();
		$jinput		= $app->input;

		$id			= $jinput->get('id');
		$option		= $jinput->get('option');
		$view		= $jinput->get('view');
		$viewList	= GTHelper::pluralize($view);
		$layout		= $jinput->get('layout');
		$canEdit	= $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $created_by == $user->id);
		$canCreate	= $canDo->get('core.create');

		$urlRedirect = $toIndex ? JURI::base() : JRoute::_('index.php?option=' . $option . '&view=' . $viewList);
		
		if ($layout == 'edit' && !$canEdit && $id) {
			$app->redirect(
				$urlRedirect, JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error'
			);
		} else if($layout == 'edit' && !$canCreate && !$id) {
			$app->redirect(
				$urlRedirect, JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error'
			);
		}
	}

	/**
	 * Check user permission for accessing a view
	 */
	public static function checkViewPermission($view, $layout)
	{
		$admin_only = array('import');
		$user_only = array('upload');
		$user = JFactory::getUser();
		$view = $layout ? implode(';', array($view, $layout)) : $view;
		if(in_array($view, $admin_only)) {
			if(GTHelperAccess::isAdmin()) {
				return true;
			} else {
				return false;
			}
		} else if(in_array($view, $user_only)) {
			if($user->id) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

}
