<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('file');

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
class JFormFieldAltFile extends JFormFieldFile
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'AltFile';

	protected function getInput() {
		$input = parent::getInput();
		$name = $this->fieldname . '_radio';
		$radio = sprintf('
			<br/><div style="margin-top:5px">
				<label class="radio-inline"><input type="radio" value="0" name="%s" id="%s" /> %s</label>
				<label class="radio-inline"><input type="radio" value="1" checked="1" name="%s" id="%s" /> %s</label>
			</div>
		', $name, $name . '0', JText::_('COM_GTSAFETRAVEL_REPLACE'), $name, $name . '1', JText::_('COM_GTSAFETRAVEL_ADD'));

		$files = explode(PHP_EOL, $this->value);
		foreach ($files as $k => $file) {
			$filename = explode('/', $file);
			$filename = end($filename);
			$files[$k] = sprintf('<a href="%s" target="_blank">%s</a>', GT_GLOBAL_FILE_URI . '/' . $this->fieldname . '/' . $file, $filename);
		}
		$hidden = sprintf('
			<div style="margin-top:5px"><input type="hidden" name="%s" value="%s" /> %s</div>
		', $this->fieldname, $this->value, implode('<br/>', $files));
		return $input . $radio . $hidden;		
	} 
}
