<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('textarea');

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
class JFormFieldTextarealist extends JFormFieldTextArea
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Textarealist';

	protected function getInput() {
		// Load JSs
		$document	= JFactory::getDocument();
		$document->addScriptDeclaration("
			(function ($){
				$(document).ready(function (){
					$('#". $this->id ."').inputmask({ mask: '". $mask ."', greedy: ". $greedy .", clearIncomplete: true})
				});
			})(jQuery);
		");

		$this->class .= ' inputmask';

		$input 		= array();
		$input[]	= JText::_(@$this->element['prefix']);
		$input[]	= parent::getInput();
		$input[]	= JText::_(@$this->element['suffix']);
		$input 		= array_filter($input);

		return implode('&nbsp;&nbsp;', $input);
	} 
}
