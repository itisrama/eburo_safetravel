<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('hidden');

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
class JFormFieldAltHidden extends JFormFieldHidden
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'AltHidden';

	public function setup(SimpleXMLElement $element, $value, $group = null) {
		parent::setup($element, $value, $group);

		$this->hidden = true;
		return true;
	}

	protected function getLabel() {
		$this->hidden = false;
		$label = parent::getLabel();
		$label = strip_tags($label);
		$label = '<span style="display:none">'.$label.'</span>';

		$this->hidden = true;
		return $label;
	} 
}
