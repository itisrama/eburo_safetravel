<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;


class GTSafeTravelModelRef_Filter extends GTModelAdmin
{

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	protected function populateState() {
		parent::populateState();

		$id = $this->input->getInt('id', 0);
		$this->setState($this->getName().'.id', intval($id));
	}

	public function getForm($data = array(), $loadData = true, $control = false) {
		$component_name = $this->input->get('option');
		$model_name = $this->getName();
		$data = $data ? $data : $this->getFormData();
		$this->data = $data;

		$menu_name	= $this->menu->params->get('menu_name');
		$form = $model_name.'_'.$menu_name;

		// Get the form.
		$form = $this->loadForm($component_name . '.' . $model_name, $form, array('control' => $control, 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		
		if(!@$data->id_lama > 0 || $tpl) {
			return $form;
		}
		
		$fieldsets = $form->getFieldsets();
		foreach ($fieldsets as $fieldset) {
			$fieldset = $form->getFieldset($fieldset->name);
			foreach ($fieldset as $field) {
				$form->setFieldAttribute($field->fieldname, 'required', 'false', $field->group);
			}
		}

		return $form;
	}
	
	public function getItem($pk = null) {
		return false;
	}

	public function save($data){
		return true;
	}

	public function delete(&$pks) {
		return true;
	}
}
