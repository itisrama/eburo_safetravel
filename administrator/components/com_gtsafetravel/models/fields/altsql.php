<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('sql');

/**
 * Form Field class for the Joomla Platform.
 * Provides radio button inputs
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @link        http://www.w3.org/TR/html-markup/command.radio.html#command.radio
 * @since       11.1
 */
class JFormFieldAltSQL extends JFormFieldSQL
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'AltSQL';

	protected function getOptions() {
		$options	= array(JHtml::_('select.option', null, JText::_('COM_GTSAFETRAVEL_OPT_SELECT_ITEM')));
		$options	= array_merge($options, parent::getOptions());

		return $options;
	}
}