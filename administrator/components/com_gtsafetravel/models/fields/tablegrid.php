<?php
defined('JPATH_PLATFORM') or die;

class JFormFieldTableGrid extends JFormField
{
	
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $subForm;
	
	protected $type = 'TableGrid';
	
	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null) {
		parent::setup($element, $value, $group);
		
		$this->subForm = $this->getSubForm();
		return true;
	}
	
	protected function getInput() {
		$names = array();
		$attributes = $this->element->attributes();
		$fields = $this->subForm->getFieldset($attributes->name . '_input');

		$id = $this->id;
		$class = $this->element['class'];
		$labeltext = trim(str_replace(array('*', '&#160;'), '', strip_tags($this->getLabel($this->name))));
		
		$str = array();

		$disableAdd = @$attributes->disableadd;
		$disableDel = @$attributes->disabledelete;

		if(!$disableAdd) {
			$str[] = '<button id="' . $id . '_toggle" class="btn btn-large btn-primary" type="button">';
			$str[] = '<i class="fa fa-plus"></i> ' . str_replace('%s', $labeltext, JText::_('COM_GTSAFETRAVEL_PT_NEW'));
			$str[] = '</button>';
		}

		$str[] = '<div id="' . $id . '_form" class="'.$class.' modal fade" data-backdrop="static">';
		$str[] = '<div class="modal-dialog" id="'.$attributes->name.'_form">';
		$str[] = '<div class="modal-content">';
		$str[] = '<div class="modal-header">';

		$str[] = '<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>';
		$str[] = '<h4 class="modal-title edit">'.str_replace('%s', $labeltext, JText::_('COM_GTSAFETRAVEL_PT_EDIT')).'</h4>';
		$str[] = '<h4 class="modal-title new">'.str_replace('%s', $labeltext, JText::_('COM_GTSAFETRAVEL_PT_NEW')).'</h4>';
		$str[] = '</div>';

		$str[] = '<div class="modal-body">';
		foreach ($fields as $field) {
			$required	= $field->element['grid-required'];
			$label		= $field->getLabel($field->name);
			$input		= $field->getInput();
			if($required == true) {
				$input	= str_replace('name="', 'tablegrid="required" name="', $input);
				$label 	= str_replace('</label>', '<span class="star">&nbsp;*</span></label>', $label);
			}
			$names[] = strval($field->fieldname);
			$resetClass = !@$field->element['disablereset'] ? ' reset' : null;
			if (in_array($field->type, array('Hidden', 'AltHidden'))) {
				$str[] = '<div class="control-group inputField'.$resetClass.'" style="display:none">';
				$str[] = '<div class="control-label"></div>';
				$str[] = '<div class="controls">' . $field->getInput() . '</div>';
				$str[] = '</div>';
				continue;
			}
			
			$str[]	= GTHelperFieldset::tplEditField($label, $input, 'inputField'.$resetClass);
		}
		
		$str[] = '</div>';
		$str[] = '<div class="modal-footer">';
		$str[]	= '<button class="addItem btn btn-primary" type="button"><i class="fa fa-plus"></i> ' . JText::_('COM_GTSAFETRAVEL_ADD_ITEM') . '</button>';
		$str[]	= '<button class="saveItem btn btn-primary" type="button" style="display:none"><i class="fa fa-save"></i> ' . JText::_('COM_GTSAFETRAVEL_SAVE_ITEM') . '</button>';
		$str[]	= '<button class="cancelItem btn btn-warning" type="button"><i class="fa fa-times"></i> ' . JText::_('COM_GTSAFETRAVEL_TOOLBAR_CLOSE') . '</button>';
	
		$width = 80;
		$formFields = array();
		foreach ($fields as $field) {
			$fwidth = @$field->element['width'];
			$fwidth = $fwidth ? $fwidth : '180px';
			$formFields[$field->fieldname] = $field;

			if (in_array($field->type, array('Hidden', 'AltHidden'))) continue;
			$width += intval($fwidth);
		}
		$str[] = '</div>';
		$str[] = '</div>';
		$str[] = '</div>';
		$str[] = '</div>';


		$str[] = '<div id="' . $id . '_table" class="'.$class.' tablegrid table-responsive" style="margin-top:20px;">';
		$str[] = '<table id="'.$attributes->name.'_table" class="table table-striped table-hover table-condensed table-sorted" style="min-width:'.$width.'px">';
		
		$str[] = '<thead>';
		$str[] = '<tr>';
		$str[] = '<th style="text-align:center" width="80px">' . JText::_('COM_GTSAFETRAVEL_ACTION') . '</th>';
		foreach ($fields as $k => $field) {
			$fwidth		= @$field->element['width'];
			$fwidth		= $fwidth ? $fwidth : '180px';
			$fldKeys	= array_keys($fields);
			$fwidth		= $k == end($fldKeys) ? 'auto' : $fwidth;
			if (in_array($field->type, array('Hidden', 'AltHidden'))) {
				$str[] = '<th style="display:none"></th>';
				continue;
			}
			$str[] = '<th style="text-align:center" width="' . $fwidth . '">';
			$str[] = strip_tags($field->getLabel($field->name)) . '</th>';
		}
		$str[] = '</tr>';
		$str[] = '<tr class="rowItem" style="display:none">';

		$str[] = '<td style="text-align:center; vertical-align:middle;">';
		$str[] = '<div class="btn-group">';
		$str[] = '<button class="editItem btn btn-info btn-sm" type="button"><i class="fa fa-edit"></i></button>';
		if(!$disableDel) {
		$str[] = '<button class="delItem btn btn-danger btn-sm" type="button"><i class="fa fa-trash-o"></i></button>';
		}
		$str[] = '</div>';
		$str[] = '</td>';
		foreach ($fields as $field) {
			if (in_array($field->type, array('Hidden', 'AltHidden'))) {
				$str[] = '<td class="' . $field->element['name'] . '" style="display:none;">';
				$str[] = '<span></span><input type="hidden"></input></td>';
				continue;
			}
			$style = array();
			$style[] = $field->element['align'] ? 'text-align:' . $field->element['align'] : 'text-align:left';
			$str[] = '<td class="' . $field->element['name'].($field->type == 'Textarea' ? ' longtext' : null).'" style="vertical-align:middle; white-space:normal;' . implode(';', array_filter($style)) . '">';
			$str[] = '<span></span><input type="hidden"></input></td>';
		}

		$str[] = '</tr>';
		$str[] = '</thead>';
		
		$str[] = '<tbody class="noItem"><tr><td style="text-align:center" colspan="' . (count($names)+1) . '">' . JText::_('COM_GTSAFETRAVEL_NO_DATA') . '</td></tr></tbody>';
		$str[] = '<tbody class="dataRows" style="display:none"></tbody>';
		
		$str[] = '</table>';
		$str[] = '</div>';
		
		
		$names = json_encode($names);
		
		// Set hidden value
		if (is_array($this->value)) {
			$this->value = array_shift($this->value);
		}
		
		$required = $this->required ? ' required aria-required="true"' : '';
		
		$formFieldNames = array_keys($formFields);
		$value = (array) JArrayHelper::fromObject(json_decode($this->value));

		$view = @$value['view']; unset($value['view']);

		foreach ($value as $kval => &$val) {
			if(!in_array($kval, $formFieldNames)) {
				unset($value[$kval]);
				continue;
			}
			$formField = $formFields[$kval];
			foreach ($val as $ksubval => &$subval) {
				if($formField->type == 'AltCalendar') {
					$format = $formField->format;
					$subval = GTHelperDate::format($subval, $format->php);
					continue;
				}
				$subval = $formField->format = implode(':', array_filter(array($subval, @$view[$ksubval][$kval])));
			}
		}
		$value = json_encode($value);
		$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		
		$str[] = str_replace('<label', '<label style="display:none" ', $this->getLabel());
		$str[] = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '"'.$required.' value="' . $value . '"/>';
		$str[] = '<input type="hidden" id="' . $this->id . '_editIndex' . '" />';
		
		// Load JSs
		$document = JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/tablegrid.js');
		
		// If a maximum value isn't set then we'll make the maximum amount of cells a large number
		$maximum = $this->element['maximum'] ? (int)$this->element['maximum'] : '20';
		
		$script = "(function ($){
			$(document).ready(function (){
				var tablegrid_$id = new $.JTableGrid('$id', $names, '$maximum');
			});
		})(jQuery);";
		$document->addScriptDeclaration($script);
		
		return implode(PHP_EOL, $str);
	}
	
	function getSubForm() {
		
		// Initialize variables.
		$subForm = new JForm($this->name, array('control' => 'jform'));
		
		$xml = $this->element->children()->asXML();
		
		$subForm->load($xml);
		
		// Needed for repeating modals in gmaps
		$subForm->repeatCounter = (int)@$this->form->repeatCounter;
		$children = $this->element->children();
		$subForm->setFields($children);
		
		return $subForm;
	}
}
