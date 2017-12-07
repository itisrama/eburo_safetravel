<?php
/**
 * @package		GT Safe Travel
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Define DS
if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
}

// Load constants and helpers
require_once( JPATH_COMPONENT_ADMINISTRATOR . DS . 'loader.php' );

// Require the core
require_once( GT_ADMIN_CORE . DS . 'controller.php' );
require_once( GT_ADMIN_CORE . DS . 'controllerform.php');
require_once( GT_ADMIN_CORE . DS . 'controlleradmin.php');
require_once( GT_ADMIN_CORE . DS . 'model.php' );
require_once( GT_ADMIN_CORE . DS . 'modeladmin.php');
require_once( GT_ADMIN_CORE . DS . 'modellist.php');
require_once( GT_ADMIN_CORE . DS . 'view.php');
require_once( GT_ADMIN_CORE . DS . 'table.php');

// Load default controller
require_once( GT_ADMIN_CONTROLLERS . DS . 'controller.php' );

// By default, we use the tables specified at the back end.

// We treat the view as the controller. Load other controller if there is any.
$controller	= GTController::getInstance( 'GTSafeTravel' );

// Execute the task.
$controller->execute(JRequest::getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
