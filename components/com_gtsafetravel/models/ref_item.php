<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelModelRef_Item extends GTModelAdmin
{

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	protected function populateState() {
		parent::populateState();

		$id = $this->input->getInt('id', 0);
		$this->setState($this->getName().'.id', intval($id));
	}
	
	public function getItem($pk = null) {
		$data = parent::getItem();
		if(!is_object($data)) return false;

		// Set Page Title
		$data->page_title	= array_filter(array(@$data->name, @$data->full_name));
		$data->page_title	= reset($data->page_title);

		// Get Data By Menu
		$menu = $this->menu->params->get('menu_name');
		$menuClass = 'get'.str_replace('_', '', ucwords($menu, '_'));
		$data = method_exists($this, $menuClass) ? $this->$menuClass($data) : $data;
		$this->item	= $data;
		return $data;
	}

	public function getRefCountry($data) {
		$layout = $this->input->get('layout');
		if($layout != 'view') {
			return $data;
		}
		$dView	= @$data->view;
		$dView	= $dView ? $dView : clone $data;
		$dView->id = $data->id;

		// Get additional data
		$dView->name_local		= GTHelperDB::getItems('ref_country_name', 'country_id = '.$dView->id, 'name_loc', 2);
		$dView->indicator_id	= GTHelperDB::getItem('ref_country_indicator', $dView->id, 'country_id', 'indicator_id');
		$dView->indicator_id	= $dView->indicator_id ? $dView->indicator_id : 5;
		$dView->indicator		= GTHelperDB::getItem('ref_indicator', $dView->indicator_id, 'id');
		$dView->infos			= GTHelperDB::getItems('web_country_info', 'country_id = '.$dView->id, 'info_id, info');
		$dView->ref_infos		= GTHelperDB::getItems('ref_info', 'published = 1');
		$dView->latitude		= GTHelperGeo::convDECtoDMS($dView->latitude, 1);
		$dView->longitude		= GTHelperGeo::convDECtoDMS($dView->longitude, 2);
		
		// Images
		$dView->flag			= GTHelper::verifyFile(GT_SAFETRAVEL_FLAG_URI.strtolower($dView->code).'.png');
		$dView->flag			= $dView->flag ? $dView->flag : GTHelper::verifyFile(GT_SAFETRAVEL_FLAG_URI.'0.png');
		$dView->landmark		= GTHelper::verifyFile(GT_SAFETRAVEL_LANDMARK_URI.strtolower($dView->code).'.jpg');
		$dView->status			= GTHelper::verifyFile(GT_SAFETRAVEL_INDICATOR_URI.$dView->indicator_id.'.png');

		// Set Data View
		$data->view = $dView;
		$data->tpl = 'ref_country';

		// Set Page Title
		$nameLocal = implode(', ', $dView->name_local);
		$nameLocal = $nameLocal && $nameLocal != $data->name ? '('.$nameLocal.')' : null;
		$data->page_title = $data->name.' '.$nameLocal;

		return $data;
	}

	public function getMobTravel($data) {
		$dView = $data->view;

		// Set Page Title
		$data->page_title = sprintf(
			'%s (%s - %s)', 
			$dView->client_id, 
			JHtml::date($data->start_date, 'j M Y'), 
			JHtml::date($data->end_date, 'j M Y')
		);
		return $data;
	}

	public function getForm($data = array(), $loadData = true, $control = 'jform') {
		$component_name = $this->input->get('option');
		$model_name = $this->getName();
		
		$data = $data ? JArrayHelper::toObject($data) : $this->getFormData();
		$this->data = $data;

		$menu_name	= $this->menu->params->get('menu_name');
		
		// Get the form.
		$form = $this->loadForm($component_name . '.' . $model_name, $menu_name, array('control' => $control, 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		return $form;
	}

	public function getTable($name = '', $prefix = 'Table', $options = array()) {
		if (empty($name)) {
			$menu_name	= $this->menu->params->get('menu_name');
		}
		return parent::getTable($menu_name, $prefix, $options);
	}

	public function save($data){
		$data	= JArrayHelper::toObject($data);
		$menu_name	= $this->menu->params->get('menu_name');

		sort($data->commodity_ids);

		switch ($menu_name) {
			case 'ref_country':
				break;
		}

		if(!parent::save($data)) return false;

		switch ($menu_name) {
			case 'ref_country':
				break;
		}

		return true;
	}

	public function delete(&$pks) {
		//return parent::delete($pks);
		return true;
	}
}
