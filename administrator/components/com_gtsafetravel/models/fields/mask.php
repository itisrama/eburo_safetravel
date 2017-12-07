<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
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
class JFormFieldMask extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Mask';

	protected function getInput() {
		// Load JSs
		$mask		= @$this->element['mask'];
		$greedy		= intval(@$this->element['greedy']) > 0 ? 'true' : 'false';
		
		$deflang	= explode('-', JComponentHelper::getParams('com_languages')->get('site'));
		$deflang	= reset($deflang);
		$document	= JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/inputmask/jquery.inputmask.js');
		$document->addScript(GT_ADMIN_JS . '/inputmask/inputmask.js');
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
