<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
jimport('joomla.form.helper');

defined('JPATH_PLATFORM') or die;

class JFormFieldTime extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Time';

	public function __get($name)
	{
		return parent::__get($name);
	}

	protected function getInput() {
		// Load JSs
		$this->class = $this->class ? $this->class : ' ';

		$placement	= isset($this->element['placement']) ? $this->element['placement'] : 'bottom';

		$itemId		= $this->id ? $this->id : $this->fieldname;
		$document	= JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/bootstrap-clockpicker.min.js');
		$document->addStylesheet(GT_ADMIN_CSS . '/bootstrap-clockpicker.min.css');
		$document->addScriptDeclaration("
			(function ($){
				$(document).ready(function (){
					$('#". $itemId ."_container').clockpicker({
						autoclose: true,
						placement: '".$placement."'
					});
				});
			})(jQuery);
		");
		
		$input = '<span class="input-group date '.trim($this->class).'" id="'.$itemId.'_container" style="width: 140px !important">';
		$input .= '<span class="input-group-addon btn btn-danger" onclick="jQuery(this).next().val(null)"><i class="fa fa-times"></i></span>';
		$input .= preg_replace('/class=".*?"/', 'class="form-control" readonly="" style="background:none"', parent::getInput());
		$input .= '<span class="input-group-addon btn btn-info"><i class="fa fa-clock-o"></i></span></span>';

		$options = $this->getOptions();
		$timezone_id = (string) $this->element['timezone_id'];
		if(count($options) > 0 && $timezone_id) {
			$radioData = parent::getLayoutData();
			$radioData = array_merge($radioData, array(
				'id' => $timezone_id,
				'class' => trim($radioData['class'].' bootstrap-radio'),
				'name' => str_replace($this->fieldname, $timezone_id, $radioData['name']),
				'options' => $this->getOptions(),
				'value'   => (string) $this->form->getValue($timezone_id)
			));
			$input = '<div style="display:inline-block; vertical-align:top; margin-right:10px;">'.$input.'</div>'.$this->getRenderer('joomla.form.field.radio')->render($radioData);
		}

		return $input;		
	} 
}
