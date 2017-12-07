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

class GTHelperHTML
{
	
	static function loadHeaders() {
		$document = JFactory::getDocument();
		// Add Styles
		$document->addStylesheet(GT_GLOBAL_CSS . '/style.css');

		// Add Scripts
		$document->addScript(GT_ADMIN_JS . '/jquery.min.js');
		$document->addScript(GT_GLOBAL_JS . '/script.js');
		$document->addScript(GT_ADMIN_JS . '/script.js');
		$document->addScript(GT_ADMIN_JS . '/ResizeSensor.js');
		$document->addScript(GT_ADMIN_JS . '/ElementQueries.js');

		// Set JS Variables
		$component_url = GT_GLOBAL_COMPONENT;
		$assets_url = GT_GLOBAL_ASSETS;
		$document->addScriptDeclaration("
		// Set variables
			var component_url = '$component_url';
			var assets_url = '$assets_url';
		");

		// Set translation constant to JS
		JText::script('ERROR');
		JText::script('WARNING');
		JText::script('SUCCESS');
		JText::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
		JText::script('COM_GTSAFETRAVEL_CONFIRM_DELETE');
		
		$document->addScript(GT_ADMIN_JS . '/jquery-sortable-min.js');
	}
	
	static function setTitle($title = '') {
		$app = JFactory::getApplication();
		$position = $app->getCfg('sitename_pagetitles');
		$document = JFactory::getDocument();
		switch ($position) {
			case 1:
				$document->setTitle($app->getCfg('sitename') . ' - ' . $title);
				break;
			case 2:
				$document->setTitle($title . ' - ' . $app->getCfg('sitename'));
				break;
			default:
				$document->setTitle($title);
				break;
		}
	}

	static function gridSort($name, $field, $ordering, $direction) {
		$search		= array('icon-arrow-up-3', 'icon-arrow-down-3');
		$replace	= array('fa fa-caret-up', 'fa fa-caret-down');
		$gridSort	= JHtml::_('grid.sort', $name, $field, $direction, $ordering);

		return str_replace($search, $replace, $gridSort);
	}

	static function getField($type, $name, $value, $group = null, $attributes = array(), $options = array()) {
		$fieldXML = new SimpleXMLElement("<field></field>");
		$fieldXML->addAttribute('name', $name);
		$fieldXML->addAttribute('type', $type);

		foreach ($attributes as $attr => $attrVal) {
			$fieldXML->addAttribute($attr, $attrVal);
		}

		foreach ($options as $optionVal => $optionLb) {
			$option = $fieldXML->addChild('option', $optionLb);
			$option->addAttribute('value', $optionVal);
		}

		$field = JFormHelper::loadFieldType($type);
		$field->setup($fieldXML, $value, $group);

		return $field->input;
	}

	static function getDropdown($name, $label, $task, $options, $type='default', $isDown=true, $isList=true, $default='') {
		$label	= JText::_($label);
		$html	= array();
		$label 	= $label ? $label.' <span class="caret"></span>' : '<span class="cog"></span>';

		$html[] = '<button class="btn btn-'.$type.' dropdown-toggle" type="button" data-toggle="dropdown">'.$label.'</button>';
		$html[] = '<div style="display:none">';
		$html[] = '<input type="hidden" class="task" value="'.$task.'" />';
		$html[] = '<input type="hidden" class="is_list" value="'.$isList.'" />';
		$html[] = '<input type="hidden" class="input" name="'.$name.'" value="'.$default.'" />';
		$html[] = '</div>';
		$html[] = '<ul class="dropdown-menu dropdownButton">';
		foreach ($options as $kOpt => $option) {
			if(is_object($option)) {
				$value = $option->id;
				$label = $option->name;
			} else if(is_array($option)) {
				list($value, $label) = $option;
			} else {
				$value = $kOpt;
				$label = $option;
			}
			$html[] = '<li><a class="option" val="'.$value.'">'.$label.'</a></li>';
		}
		$html[] = '</ul>';

		return '<div class="btn-group">'.implode('', $html).'</div>';
	}

	static function getDropdownLink($label, $options, $type='default', $isModal=false, $isDown=true) {
		$label	= JText::_($label);
		$html	= array();
		$label 	= $label ? $label.' <span class="caret"></span>' : '<span class="cog"></span>';
		$upClass = $isDown ? null : ' dropup';

		$html[] = '<button class="btn btn-'.$type.' dropdown-toggle" type="button" data-toggle="dropdown">'.$label.'</button>';
		$html[]	= '<ul class="dropdown-menu">';

		$isModal = $isModal ? ' class="modalForm"' : null;

		foreach ($options as $kOpt => $option) {
			if(is_object($option)) {
				$link		= $option->link;
				$label		= $option->name;
			} else if(is_array($option)) {
				list($label, $link) = $option;
			} else {
				$value = $kOpt;
				$label = $option;
			}
			$html[] = $isModal ? '<li><a'.$isModal.' link="'.$link.'">'.$label.'</a></li>' : '<li><a href="'.$link.'">'.$label.'</a></li>';
		}
		$html[] = '</ul>';

		return '<div class="btn-group'.$upClass.'">'.implode('', $html).'</div>';
	}

	static function getSelectize($name, $value, $query, $class = null, $requests = null, $task = 'selectize.getItems', $attr = null) {
		$db		= JFactory::getDBO();
		
		$id			= $name;
		
		$db->setQuery(str_replace('%s', '"'.implode('","', $value).'"', $query));
		$items 		= $db->loadObjectlist();
		$options	= array();
		
		if ($items) {
			foreach ($items as $item) {
				$options[] = JHtml::_('select.option', $item->id, $item->name);
			}
		}
		
		// Merge any additional options in the XML definition.
		$options	= array_merge(parent::getOptions(), $options);
		
		// Load JSs
		$document	= JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/selectize.min.js');
		$document->addStylesheet(GT_ADMIN_CSS . '/selectize.bootstrap3.css');;
		
		$component_url = GT_GLOBAL_COMPONENT;

		$script		= "
			(static function ($){
				$(document).ready(static function (){
					$('#$id').selectize({
						persist: false,
						valueField: 'id',
						labelField: 'name',
						searchField: 'name',
						sortField: 'name',
						create: $create,
						preload: true,
						load: function(query, callback) {
							data = $requests;
							data.search = query;
							data.task = '$task';
							$.ajax({
								url: '$component_url',
								data: data,
								type: 'GET',
								error: function() {
									callback();
								},
								success: function(result) {
									callback($.parseJSON(result));
								}
							});
						},
					});
				});
			})(jQuery);
		";
		$document->addScriptDeclaration($script);

		return JHtml::_('select.genericlist', $options, $name, trim($attr), 'value', 'text', $value, $id);
	}

	static function radio($data, $id, $name, $key = 'value', $text = 'text', $class = "", $selected = false) {
		$layout = new JLayoutFile('joomla.form.field.radio');

		foreach ($data as &$item) {
			$item = array(
				'value'    => $item->$key,
				'text'     => $item->$text,
				'class'    => (string) $option['class'],
				'selected' => false,
				'checked'  => true
			);
			$item = JArrayHelper::toObject($item);
		}

		$data = array(
			'class' => 'bootstrap-radio '.$class,
			'id' => $id,
			'name' => $name,
			'options' => $data,
			'value'   => (string) $selected
		);

		return $layout->render($data);
	}

	static function getItemButtons($buttons) {
		foreach ($buttons as &$button) {
			list($label, $url, $disabled, $icon) = $button;
			$disabled = $disabled ? ' disabled' : null;
			$button = sprintf('<a title="%s" href="%s"%s class="btn btn-default btn-sm hasTooltip"><i class="fa fa-%s"></i></a>',
				$label, $url, $disabled, $icon
			);
		}
		$buttons = '<div class="btn-group">'.implode('', $buttons).'</div>';
		return $buttons;
	}

	static function nlToBullet($string, $type = 'disc') {
		$ulList = array('disc', 'circle', 'square');
		$string = explode(PHP_EOL, $string);
		$string = array_filter($string);
		foreach ($string as &$str) {
			$str = trim($str, '-');
			$str = trim($str);
		}
		$ulType = in_array($type, $ulList) ? 'ul' : 'ol';
		return $string ? '<'.$ulType.' type="'.$type.'"><li>'.implode('</li><li>', $string).'</li></'.$ulType.'>' : null;
	}

	static function smartWordwrap($string, $width = 50, $break = "\n") {
		// split on problem words over the line length
		$pattern = sprintf('/([^ ]{%d,})/', $width);
		$output = '';
		$words = preg_split($pattern, $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		foreach ($words as $word) {
			if (false !== strpos($word, ' ')) {
				// normal behaviour, rebuild the string
				$output .= $word;
			} else {
				// work out how many characters would be on the current line
				$wrapped = explode($break, wordwrap($output, $width, $break));
				$count = $width - (strlen(end($wrapped)) % $width);

				// fill the current line and add a break
				$output .= substr($word, 0, $count) . $break;

				// wrap any remaining characters from the problem word
				$output .= wordwrap(substr($word, $count), $width, $break, true);
			}
		}

		// wrap the final output
		return wordwrap($output, $width, $break);
	}

	static function setCommodities($rows, $categories, $commodities, $optgroup = false, $level = 0) {
		$data = array();
		foreach($rows as $category_id => $category) {
			$child_categories = (array) @$categories[$category_id];
			$child_commodities = (array) @$commodities[$category_id];

			if(count($child_categories)) {
				$countsub = 0;
				foreach (array_keys($child_categories) as $child_category) {
					$countsub += count((array) @$categories[$child_category]) + count((array) @$commodities[$child_category]);
				}
				if(!($countsub || count($child_commodities))) {
					continue;
				}
			} else {
				if(!count($child_commodities)) {
					continue;
				}
			}
			
			$row = new stdClass();
			$row->text = str_repeat('&nbsp;', $level * 6) . $category;
			$row->value = $optgroup ? '<OPTGROUP>' : 'cat-'.$category_id;
			$data[] = $row;
			if(isset($commodities[$category_id])) {
				foreach($commodities[$category_id] as $commodity_id => $commodity) {
					$multiplier = $optgroup ? $level : $level + 1;
					$row = new stdClass();
					$row->text = str_repeat('&nbsp;', $multiplier * 6) . $commodity;
					$row->value = $commodity_id;
					$data[] = $row;
				}
			}
			$row = new stdClass();
			if($optgroup) {
				$row->text = '';
				$row->value = '</OPTGROUP>';
				$data[] = $row;
			}
			if(isset($categories[$category_id])) {
				$data = array_merge($data, self::setCommodities($categories[$category_id], $categories, $commodities, $optgroup, $level+1));
			}
		}
		return $data;
	}

	
}
