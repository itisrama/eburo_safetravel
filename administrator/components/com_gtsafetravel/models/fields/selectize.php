<?php
defined('_JEXEC') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldSelectize extends JFormFieldList
{
	
	protected $type = 'Selectize';
	
	protected function getOptions() {
		$this->value = is_numeric($this->value) ? array($this->value) : JArrayHelper::fromObject($this->value);
		$this->value = $this->value ? $this->value : array(0);

		$db		= JFactory::getDBO();
		
		$id			= $this->id;
		$query		= (string) $this->element['query'];
		$task		= (string) $this->element['task'];
		$requests	= (string) $this->element['requests'];
		$wheres		= (string) $this->element['wheres'];
		$parent_f	= (string) $this->element['parent_f'];
		$parent_v	= (string) $this->element['parent_v'];
		$parent_t	= (string) $this->element['parent_t'];
		$child		= (string) $this->element['child'];
		$create		= isset($this->element['create']) ? $this->element['create'] : 'false';
		$ordering	= isset($this->element['ordering']) ? $this->element['ordering'] : 'name';

		$childs		= explode(',', $child);
		$childs		= array_filter($childs);
		foreach ($childs as &$child) {
			$child = "
				$child.clear();
				$child.clearOptions();
				$child.onSearchChange('');
			";
		}

		$child 		= implode(PHP_EOL, $childs);
		
		$items = array();
		if($this->value) {
			$query		= str_replace('%s', '"'.implode('","', $this->value).'"', $query);
			$db->setQuery($query);
			$items		= $db->loadObjectlist();
		}
		
		$options	= array();
		$fieldname	= $this->fieldname;

		//echo nl2br(str_replace('#__','eburo_',$query)).'<br/>';

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

		$requests2 = '{}';
		if($parent_f) {
			$parent_px = str_replace($this->fieldname, '', $this->id);			
			if($parent_t == 'hidden') {
				$parent_v = sprintf('$("#%s").val()', $parent_px.$parent_v);
			} else {
				$parent_v = sprintf('%s.getValue()', $parent_v);
			}
			$requests2 = sprintf("{parent_field: '%s', parent_value: %s}", $parent_f, $parent_v);
		}

		$script		= "
			var $fieldname = null;
			(function ($){
				$(document).ready(function (){
					var $$fieldname = $('#$id').selectize({
						persist: false,
						valueField: 'id',
						labelField: 'name',
						searchField: 'name',
						sortField: '$ordering',
						create: $create,
						preload: true,
						load: function(query, callback) {
							data = $.extend($requests, $requests2, {ordering: '$ordering'});
							data.search = query;
							data.task = '$task';
							data.wheres = '$wheres'
							$.ajax({
								cache: false,
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
						}
					});
					$fieldname = $$fieldname.length > 0 ? $$fieldname"."[0].selectize : null;
				});
			})(jQuery);
		";
		$document->addScriptDeclaration($script);

		if($child) {
			$script2 = "
				(function ($){
					$(document).ready(function (){
						if($fieldname) {
							$fieldname.on('change', function(){
								$child
							});
						}
					});
				})(jQuery);
			";

			$document->addScriptDeclaration($script2);
		}

		if (!$this->multiple && is_array($this->value)) {
			$this->value = reset($this->value);
		}

		return $options;
	}


}
?>
