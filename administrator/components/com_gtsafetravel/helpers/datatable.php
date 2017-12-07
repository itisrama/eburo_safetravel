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

class GTHelperDataTable {

	static function load()
	{
		$document = JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/datatables.min.js');
		$document->addStyleSheet(GT_ADMIN_CSS . '/datatables.min.css');
		$document->addStyleSheet(GT_ADMIN_CSS . '/dataTables.fontAwesome.css');
	}
	
	static function server($id, $url, $columns, $data) {
		$data = JArrayHelper::toObject($data, 'stdCLass', false);

		$ordering		= @$data->ordering;
		$searching		= @$data->searching;
		$start			= @$data->start ? $data->start : 0;
		$length			= @$data->length ? $data->length : 10;
		$lengthChange	= @$data->lengthChange;
		$orderMulti		= @$data->orderMulti;
		$orders			= (array) @$data->order;
		$left			= intval(@$data->left);
		$right			= intval(@$data->right);

		foreach ($orders as &$order) {
			$order = array_values($order);
		}

		$orders = array();
		
		//$orders = count($orders) > 0 ? $orders : array(array(intval(@$data->orderIndex), 'desc'));

		$document = JFactory::getDocument();
		foreach ($columns as &$column) {
			list($title, $data, $class, $width, $orderable) = $column;
			$column = new stdCLass();
			$column->title = $title;
			$column->data = $data;
			$column->width = $width;
			$column->orderable = $orderable == 'true';
			$column->className = $class.' text-center';
		}

		$fixedColumns = array_filter(array(
			'left-columns' => $left,
			'right-columns' => $right
		));

		$params = new stdCLass();
		$params->dom = 't<"dataTables_footer" lfpi>r';
		$params->order = $orders;
		$params->ordering = $ordering;
		$params->searching = $searching;
		$params->processing = true;
		$params->serverSide = true;
		$params->scrollX = true;
		$params->autoWidth = true;
		$params->pageLength = $length;
		$params->displayStart = $start;
		$params->lengthChange = $lengthChange;
		$params->orderMulti = $orderMulti;
		$params->ajax = new stdCLass();
		$params->ajax->url = $url;
		$params->ajax->type = 'POST';
		$params->drawCallback = '"function ( settings  ) { $(\'#'.$id.' .hasTooltip\').tooltip({\'html\': true,\'container\': \'body\'}); }"';
		$params->columns = $columns;
		if($fixedColumns) {
			$params->fixedColumns = $fixedColumns;
		}

		$params = json_encode($params);
		$params = str_replace(array('"\"', '\""'), '', $params);

		$document->addScriptDeclaration("
			jQuery.noConflict();
			var $id = null;
			(function($) {
				$(function() {
					$id = $('#$id').DataTable($params);
				});
			})(jQuery);
		");
	}
}
