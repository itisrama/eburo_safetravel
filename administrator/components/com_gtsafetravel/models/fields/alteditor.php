<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('editor');

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

class JFormFieldAltEditor extends JFormFieldEditor
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'AltEditor';

	public function setup(SimpleXMLElement $element, $value, $group = null) {
		if(is_null($this->form)) {
			$this->form = new FakeObject;
			$this->form->getValue = function() { return null; };
		}

		$result = parent::setup($element, $value, $group);

		if ($result == true) {
			$this->height = @$this->element['row'] > 0 ? ($this->element['row']) * 50 . 'px' : $this->element['height'];
			$this->height = $this->height ? $this->height : '150px';
		}

		return $result;
	}

	protected function getInput() {
		$editor = parent::getInput();

		return '<div class="editor '.$this->class.'" style="max-width:100% !important">'.$editor.'</div>';
	} 
}
