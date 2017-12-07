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

class GTHelperFieldset {

	static $formData;

	public static function setData($data) {
		$formData = is_object($data) ? clone $data : JArrayHelper::toObject($data);

		$viewData = @$formData->view; unset($formData->view);
		$gridData = @$formData->grid; unset($formData->grid);

		foreach ($formData as $itemKey => $item) {
			if(is_object($item) && is_object(reset($item))) {
				$subFormData = clone $item;
				$itemViewData = @$subFormData->view; unset($subFormData->view);
				foreach ($subFormData as $subItemKey => $subItem) {
					$subItem = is_object($itemViewData) && property_exists($itemViewData, $subItemKey) ? $itemViewData->$subItemKey : $subItem;
					$subItem = $subItem ? $subItem : '<span style="color:red">['.JText::_('COM_GTSAFETRAVEL_EMPTY').']</span>';

					$subFormData->$subItemKey = $subItem;
				}

				$formData->$itemKey = $subFormData;
			} else {
				$item = is_object($viewData) && property_exists($viewData, $itemKey) ? $viewData->$itemKey : $item;
				$item = $item ? $item : '<span style="color:red">['.JText::_('COM_GTSAFETRAVEL_EMPTY').']</span>';

				$formData->$itemKey = $item;
			}
		}

		if(count($gridData)) {
			$grids = clone $gridData;
			foreach ($grids as $gridKey => $gridItem) {
				if(!$gridItem) continue;

				$gridItem = (array) JArrayHelper::fromObject(json_decode($gridItem));
				$gridViewData = @$gridItem['view']; unset($gridItem['view']);
				
				foreach ($gridItem as $gridFieldKey => &$gridField) {
					foreach ($gridField as $gridRowKey => &$gridRow) {
						$gridRowView = @$gridViewData[$gridRowKey][$gridFieldKey];
						$gridRow = $gridRowView ? $gridRowView : $gridRow;
						$gridRow = $gridRow ? $gridRow : '<span style="color:red">['.JText::_('COM_GTSAFETRAVEL_EMPTY').']</span>';
					}
				}
				$grids->$gridKey = json_encode($gridItem);
			}
			$formData->grid = $grids;
		}
		
		self::$formData = $formData;
	}

	public static function tplEditField($label, $input, $showNum = false, $num = 0) {
		$label = str_replace('*', '', $label);
		if($showNum) {
			$str = '
				<tr>
					<td width="40px" class="text-center">%num</td>
					<td style="width:250px"><div style="font-weight:bold; padding-top:7x">%label</div></td>
					%input
				</tr>
			';
		} else {
			$str = '
				<tr>
					<td style="width:250px"><div style="font-weight:bold; padding-top:7px">%label</div></td>
					%input
				</tr>
			';
		}

		return str_replace(array('%label', '%input', '%num'), array($label, $input, $num), $str);
	}

	public static function tplEditFieldset($tbody, $class = '') {
		$str = '
			<div>
				<table class="table table-striped table-hover '.$class.'">
					<tbody>%tbody</tbody>
				</table>
			</div>
		';

		return str_replace(array('%tbody'), array($tbody), $str);
	}

	public static function tplViewField($label, $input, $showNum = false, $num = 0) {
		$label = str_replace('*', '', $label);
		if($showNum) {
			$str = '
				<tr>
					<td width="40px" class="text-center">%num</td>
					<td style="width:250px"><strong>%label</strong></td>
					%input
				</tr>
			';
		} else {
			$str = '
				<tr>
					<td style="width:250px"><strong>%label</strong></td>
					%input
				</tr>
			';
		}

		return str_replace(array('%label', '%input', '%num'), array($label, $input, $num), $str);
	}

	public static function tplViewFieldset($tbody, $class = '') {
		$str = '
			<div>
				<table class="table table-striped table-hover '.$class.'">
					<tbody>%tbody</tbody>
				</table>
			</div>
		';

		return str_replace(array('%tbody'), array($tbody), $str);
	}

	public static function tplViewTable($thead, $tbody, $condensed = false, $width = 'auto') {
		$width = is_numeric($width) && $width > 0 ? $width.'px' : $width;
		$condensed = $condensed ? ' table-condensed' : '';
		$str = '
			<div class="table-responsive">
				<table class="table table-striped table-hover'.$condensed.'" style="min-width:'.$width.'">
					<thead><tr>%thead</tr></thead>
					<tbody>%tbody</tbody>
				</table>
			</div>
		';

		return str_replace(array('%thead', '%tbody'), array($thead, $tbody), $str);
	}

	public static function renderEdit($fieldset) {
		$editField = "";
		$editFieldset = "";

		$html	= array();
		foreach($fieldset as $field) {
			if(in_array($field->type, array('Hidden', 'AltHidden'))) {
				$html[]	= $field->input;
			} else {
				$html[]	= self::tplEditField($field->label, '<td>'.$field->input.'</td>');
			}
		}
		$html	= implode(PHP_EOL, $html);
		$html	= self::tplEditFieldset($html);

		return $html;
	}

	public static function renderView($fieldset, $showNum = false, $class = '') {
		$data = self::$formData;
		$html	= array();
		$k = 0;
		foreach($fieldset as $field) {
			if(in_array($field->type, array('Hidden', 'AltHidden'))) {
				continue;
			}

			$group = $field->group;
			$fieldname = $field->fieldname;
			$value = $group ? @$data->$group->$fieldname : $data->$fieldname;

			$value = in_array($field->type, array('AltEditor', 'Editor')) ? $value : nl2br($value);
			$k++;
			$html[]	= self::tplViewField(strip_tags($field->label), '<td>'.$value.'</td>', $showNum, $k);
		}
		$html	= implode(PHP_EOL, $html);
		$html	= self::tplViewFieldset($html, $class);
		return $html;
	}

	public static function renderSubView($field, $showNum = true, $condensed = false) {
		$formData 	= self::$formData;
		$fieldname	= $field->fieldname;
		$data		= $formData->grid->$fieldname;
		$data		= @JArrayHelper::fromObject(json_decode($data));
		if(!$data) {
			$html = '<p>'.JText::_('COM_GTSAFETRAVEL_NO_DATA').'</p>';
			return $html;
		}
		$keys = array_keys($data);
		$subForm = @$field->subForm;
		$subFields = @self::getSubField($field->fieldname, $keys, $subForm);
		$thead = array();
		$totalWidth = 40;
		if($showNum) {
			$thead[] = '<th class="text-center" style="width:'.$totalWidth.'px">'.JText::_('COM_GTSAFETRAVEL_NUM').'</th>';
		}
		foreach($subFields as $subField) {
			if(@$subField->fieldname == 'id') continue;
			$width		= isset($subField->width) ? $subField->width : '180px';
			$totalWidth	+= intval($width);
			$thead[]	= '<th class="text-center" style="width:'.$width.'">'.$subField->label.'</th>';
		}

		$tbody = array();
		$k = 0;
		foreach(reset($data) as $row => $value) {
			$k++;
			$tbody[] = '<tr>';
			if($showNum) {
				$tbody[] = '<td style="text-align:center">'.$k.'</k>';
			}
			foreach($subFields as $subField) {
				if(@$subField->fieldname == 'id') continue;
				$align = isset($subField->align) ? $subField->align : 'left';
				$tbody[] = '<td style="text-align:'.$align.'">'.@$data[$subField->fieldname][$row].'</td>';
			}
			$tbody[] = '</tr>';
		}
		$thead = implode('', $thead);
		$tbody = implode('', $tbody);
		$table = self::tplViewTable($thead, $tbody, $condensed, $totalWidth);
		return $table;
	}

	public static function renderSubVerticalView($field) {
		$formData 	= self::$formData;
		$fieldname	= $field->fieldname;
		$data		= $formData->grid->$fieldname;
		$data		= @JArrayHelper::fromObject(json_decode($data));

		if(!$data) {
			$html = '<p>'.JText::_('COM_GTSAFETRAVEL_NO_DATA').'</p>';
			return $html;
		}

		$keys = array_keys($data);
		$subForm = $field->subForm;
		$subFields = self::getSubField($field->fieldname, $keys, $subForm);

		$table = array();
		$k = 0;
		foreach($subFields as $subField) {
			if(@$subField->fieldname == 'id') continue;
			if(!in_array($subField->fieldname, $keys)) continue;

			$k++;
			$label	= $subField->label;
			$columns = array();
			$width = count($data[$keys[0]]); 
			$width = 73 / $width .'%';
			foreach($data[$keys[0]] as $row => $value) {
				if(end($data[$keys[0]]) == $value) $width = 'auto';
				$value		= $data[$subField->fieldname][$row];
				$columns[]	= '<td width="'.$width.'">'.$value.'</td>';
			}
			$columns = implode('', $columns);
			$table[] = self::tplViewField($k, $label, $columns);

		}
		$table	= implode(PHP_EOL, $table);
		$table	= self::tplViewFieldset($table);

		return $table;
	}

	public static function getSubField($name, $keys, $form) {
		$subFields = array();
		
		if(is_object($form)) {
			$fields = $form->getFieldset($name.'_input');
			foreach ($fields as $k => $field) {
				$subFields[$k]['fieldname']	= $field->fieldname;
				$subFields[$k]['label']		= strip_tags($field->label);
				$subFields[$k]['type']		= $field->type;
				$subFields[$k]['align']		= $form->getFieldAttribute($field->fieldname, 'align', 'left', $field->group);
				$subFields[$k]['width']		= $form->getFieldAttribute($field->fieldname, 'width', '180px', $field->group);
			}
		} else {
			foreach ($keys as $k => $fl) {
				$field			= explode('__', $fl);
				$name			= @$field[0];
				$align			= @$field[1];
				$width			= @$field[2];
				$notranslate	= @$field[3];

				$subFields[$k]['fieldname']	= $fl;
				$subFields[$k]['label']		= $notranslate == 1 ? str_replace('_', ' ', $name) : JText::_('COM_GTSAFETRAVEL_FIELD_'.strtoupper($name));
				$subFields[$k]['align']		= $align ? $align : 'left';
				$subFields[$k]['width']		= $field == reset($keys) ? '25%' : 'auto';
				$subFields[$k]['width']		= $width ? $width : $subFields[$k]['width'];
			}
		}
		foreach ($subFields as $k => $subField) {
			$subFields[$k] = JArrayHelper::toObject($subField);
		}
		return $subFields;
	}
}
