<?php
defined('_JEXEC') or die('Restricted access');
$menu_id		= JRequest::getVar('Itemid');
$menu_id		= $menu_id ? '&Itemid=' . $menu_id : NULL;

/* GLOBAL PATHS
 ---------------------------------- */
// Component Name
if(!defined('GT_COMPONENT_NAME')) 
	define('GT_COMPONENT_NAME', JRequest::getVar('option'));

// Component Name
if(!defined('GT_MENU_ID')) 
	define('GT_MENU_ID', $menu_id);

// Component Root Path
if(!defined('GT_GLOBAL_ROOT')) 
	define('GT_GLOBAL_ROOT', JPATH_BASE . DS . 'components' . DS . GT_COMPONENT_NAME);

// Temporary Path
if(!defined('GT_GLOBAL_TEMP')) 
	define('GT_GLOBAL_TEMP', GT_GLOBAL_ROOT . DS . 'temp');

// File Path
if(!defined('GT_GLOBAL_FILE')) 
	define('GT_GLOBAL_FILE', JPATH_BASE . DS . 'media' . DS . GT_COMPONENT_NAME);
if(!defined('GT_GLOBAL_FILE_URI')) 
	define('GT_GLOBAL_FILE_URI', JURI::root() . 'media/' . GT_COMPONENT_NAME);

// URI
if(!defined('GT_GLOBAL_URI')) 
	define('GT_GLOBAL_URI', JURI::root() . 'components/' . GT_COMPONENT_NAME);

// Component URI
if(!defined('GT_GLOBAL_COMPONENT')) 
	define('GT_GLOBAL_COMPONENT', JURI::root() . '?option=' . GT_COMPONENT_NAME);

// Media URI
if(!defined('GT_MEDIA_URI')) 
	define('GT_MEDIA_URI', JURI::root() . 'media/'. GT_COMPONENT_NAME);

// Assets URI
if(!defined('GT_GLOBAL_ASSETS')) 
	define('GT_GLOBAL_ASSETS', GT_GLOBAL_URI . '/assets');

// Image URI
if(!defined('GT_GLOBAL_IMAGES')) 
	define('GT_GLOBAL_IMAGES', GT_GLOBAL_ASSETS . '/images');

// Javascript URI
if(!defined('GT_GLOBAL_JS')) 
	define('GT_GLOBAL_JS', GT_GLOBAL_ASSETS . '/js');

// CSS URI
if(!defined('GT_GLOBAL_CSS')) 
	define('GT_GLOBAL_CSS', GT_GLOBAL_ASSETS . '/css');

/* SITE PATHS
 ---------------------------------- */

// Component Root Path
if(!defined('GT_ROOT')) 
	define('GT_ROOT', JPATH_ROOT . DS . 'components' . DS . GT_COMPONENT_NAME);

// Table Path
if(!defined('GT_TABLES')) 
	define('GT_TABLES', GT_ROOT . DS . 'tables');

// Model Path
if(!defined('GT_MODELS')) 
	define('GT_MODELS', GT_ROOT . DS . 'models');

// View Path
if(!defined('GT_VIEWS')) 
	define('GT_VIEWS', GT_ROOT . DS . 'views');

// Controller Path
if(!defined('GT_CONTROLLERS')) 
	define('GT_CONTROLLERS', GT_ROOT . DS . 'controllers');

// Temporary Path
if(!defined('GT_TEMP')) 
	define('GT_TEMP', GT_ROOT . DS . 'temp');

// Temporary Path
if(!defined('GT_FILES')) 
	define('GT_FILES', GT_ROOT . DS . 'files');

// URI
if(!defined('GT_URI')) 
	define('GT_URI', JURI::root() . 'components/' . GT_COMPONENT_NAME);

// Component URI
if(!defined('GT_COMPONENT')) 
	define('GT_COMPONENT', 'index.php/?option=' . GT_COMPONENT_NAME . GT_MENU_ID);

// Assets URI
if(!defined('GT_ASSETS')) 
	define('GT_ASSETS', GT_URI . '/assets');

// Image URI
if(!defined('GT_IMAGES')) 
	define('GT_IMAGES', GT_ASSETS . '/images');

// Javascript URI
if(!defined('GT_JS')) 
	define('GT_JS', GT_ASSETS . '/js');

// CSS URI
if(!defined('GT_CSS')) 
	define('GT_CSS', GT_ASSETS . '/css');

/* ADMIN PATHS
 ---------------------------------- */

// Component Root Path
if(!defined('GT_ADMIN_ROOT')) 
	define('GT_ADMIN_ROOT', JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . GT_COMPONENT_NAME);

// Core Path
if(!defined('GT_ADMIN_CORE')) 
	define('GT_ADMIN_CORE', GT_ADMIN_ROOT . DS . 'core');

// Table Path
if(!defined('GT_ADMIN_TABLES')) 
	define('GT_ADMIN_TABLES', GT_ADMIN_ROOT . DS . 'tables');

// Model Path
if(!defined('GT_ADMIN_MODELS')) 
	define('GT_ADMIN_MODELS', GT_ADMIN_ROOT . DS . 'models');

// View Path
if(!defined('GT_ADMIN_VIEWS')) 
	define('GT_ADMIN_VIEWS', GT_ADMIN_ROOT . DS . 'views');

// Controller Path
if(!defined('GT_ADMIN_CONTROLLERS')) 
	define('GT_ADMIN_CONTROLLERS', GT_ADMIN_ROOT . DS . 'controllers');

// Helper Path
if(!defined('GT_ADMIN_HELPERS')) 
	define('GT_ADMIN_HELPERS', GT_ADMIN_ROOT . DS . 'helpers');

// Temporary Path
if(!defined('GT_ADMIN_TEMP')) 
	define('GT_ADMIN_TEMP', GT_ADMIN_ROOT . DS . 'temp');

// URI
if(!defined('GT_ADMIN_URI')) 
	define('GT_ADMIN_URI', JURI::root() . 'administrator/components/' . GT_COMPONENT_NAME);

// Component URI
if(!defined('GT_ADMIN_COMPONENT')) 
	define('GT_ADMIN_COMPONENT', JURI::root() . 'administrator/?option=' . GT_COMPONENT_NAME);

// Assets URI
if(!defined('GT_ADMIN_ASSETS')) 
	define('GT_ADMIN_ASSETS', GT_ADMIN_URI . '/assets');

// Image URI
if(!defined('GT_ADMIN_IMAGES')) 
	define('GT_ADMIN_IMAGES', GT_ADMIN_ASSETS . '/images');

// Javascript URI
if(!defined('GT_ADMIN_JS')) 
	define('GT_ADMIN_JS', GT_ADMIN_ASSETS . '/js');

// CSS URI
if(!defined('GT_ADMIN_CSS')) 
	define('GT_ADMIN_CSS', GT_ADMIN_ASSETS . '/css');

// LIBRARIES Path
if(!defined('GT_ADMIN_LIB_PATH')) 
	define('GT_ADMIN_LIB_PATH', GT_ADMIN_ROOT . DS . 'libraries');

// LIBRARIES URI
if(!defined('GT_ADMIN_LIBRARIES')) 
	define('GT_ADMIN_LIBRARIES', GT_ADMIN_URI . '/libraries');

/* USER DEFINED
 ---------------------------------- */
define('GT_SAFETRAVEL_FLAG_URI', GT_MEDIA_URI . '/flag/');
define('GT_SAFETRAVEL_LANDMARK_URI', GT_MEDIA_URI . '/landmark/');
define('GT_SAFETRAVEL_INDICATOR_URI', GT_MEDIA_URI . '/indicator/');
define('GT_SAFETRAVEL_NEWS_URI', GT_MEDIA_URI . '/news/');
define('GT_SAFETRAVEL_WEATHER_URI', GT_MEDIA_URI . '/weather/');
define('GT_SAFETRAVEL_PLACE_URI', GT_MEDIA_URI . '/place/');
