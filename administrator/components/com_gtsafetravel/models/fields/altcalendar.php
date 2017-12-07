<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('text');

defined('JPATH_PLATFORM') or die;

/**
 * Form Field class for the Joomla Platform.
 * Provides a hidden field
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @link        http://www.w3.org/TR/html-markup/input.hidden.html#input.hidden
 * @since       11.1
 */
class JFormFieldAltCalendar extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'AltCalendar';

	public function __get($name)
	{
		switch ($name)
		{
			case 'format':
				return $this->getFormat();
		}

		return parent::__get($name);
	}

	protected function getFormat() {
		$format		= (string) $this->element['format'] ? (string) $this->element['format'] : '%d-%m-%Y';

		if(is_numeric(strpos($format, '%'))) {
			$format = str_replace(array('%', 'd', 'm', 'Y'), array('', 'dd', 'mm', 'yyyy'), $format);
		}

		$formatParts = array('d', 'j', 'l', 'D', 'm', 'n', 'F', 'M', 'Y', 'y');
		$formatPartKeys = array();
		foreach (array_keys($formatParts) as $formatPart) {
			$formatPartKeys[] = '['.$formatPart.']';
		}
		$phpFormat	= str_replace(
			array('dd', 'd', 'DD', 'D', 'mm', 'm', 'MM', 'M', 'yyyy', 'yy'), 
			$formatPartKeys, 
			$format
		);

		$phpFormat	= str_replace(
			$formatPartKeys, 
			$formatParts, 
			$phpFormat
		);

		$result			= new stdClass();
		$result->js		= $format;
		$result->php	= $phpFormat;

		return $result;
	}

	protected function getInput() {
		// Load JSs
		$future 	= @$this->element['future'];
		$format 	= $this->getFormat();
		switch ($format->php) {
			case 'Y-m-d':
			case 'd-m-Y':
				$width = ' style="width: 170px !important"';
				break;
			default:
				$width = null;
				break;
		}
		$this->class = $this->class ? $this->class : ' ';
		$this->value = strtotime($this->value) > 0 ? GTHelperDate::format($this->value, $format->php) : null;

		$itemId		= $this->id ? $this->id : $this->fieldname;
		$deflang	= explode('-', JComponentHelper::getParams('com_languages')->get('site'));
		$deflang	= reset($deflang);
		$minView	= isset($this->element['minView']) ? $this->element['minView'] : '0';
		$maxView	= isset($this->element['maxView']) ? $this->element['maxView'] : '2';
		$position	= isset($this->element['position']) ? $this->element['position'] : 'auto';
		$document	= JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/datepicker/bootstrap-datepicker.js');
		$document->addScript(GT_ADMIN_JS . '/datepicker/locales/bootstrap-datepicker.' . $deflang . '.js');
		$document->addStylesheet(GT_ADMIN_CSS . '/bootstrap-datepicker3.min.css');
		$document->addScriptDeclaration("
			(function ($){
				$(document).ready(function (){
					$('#". $itemId ."_container').datepicker({ 
						format: '". $format->js ."', 
						".($future ? null : "endDate: 'now',")."
						language: '". $deflang ."', 
						minViewMode: '". $minView ."', 
						maxViewMode: '". $maxView ."',
						orientation: '". $position ."'
					})
				});
			})(jQuery);
		");
		
		$input = '<span class="input-group date '.trim($this->class).'" id="'.$itemId.'_container"'.$width.'>';
		$input .= '<span class="input-group-addon btn" onclick="jQuery(this).next().val(null)"><i class="fa fa-times"></i></span>';
		$input .= preg_replace('/class=".*?"/', 'class="form-control" readonly="" style="background:none"', parent::getInput());
		$input .= '<span class="input-group-addon btn iconcal"><i class="fa fa-calendar"></i></span></span>';
		return $input;		
	} 
}
