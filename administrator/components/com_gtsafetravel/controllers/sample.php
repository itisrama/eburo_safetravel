<?php

/**
 * @package		JK Docuno
 * @author		Yudhistira Ramadhan
 * @link		http://www.joomlaku.net
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2016 Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelControllerSample extends GTControllerForm{

	public function getModel($name = 'Sample', $prefix = '', $config = array('ignore_request' => true)) {
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
}
