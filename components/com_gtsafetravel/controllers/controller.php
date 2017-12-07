<?php
/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelController extends GTController
{
	public function __construct($config = array())
	{
		$config['default_view'] = 'samples';
		parent::__construct($config);
		
		$view		= JRequest::getVar('view');
		$layout		= JRequest::getVar('layout');
		$is_allowed	= GTHelperAccess::checkViewPermission($view, $layout);
		
		if(!$is_allowed) {
			$this->setRedirect(GT_COMPONENT, 'Anda tidak memiliki hak untuk mengakses halaman ' . ucfirst($view), 'error');
		}
	}
	
	
}