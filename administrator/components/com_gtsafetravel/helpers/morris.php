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

class GTHelperMorris {

	static function load()
	{
		$document = JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/raphael-min.js');
		$document->addScript(GT_ADMIN_JS . '/morris.min.js');
		$document->addStyleSheet(GT_ADMIN_CSS . '/morris.css');

		$document->addStyleDeclaration("
			.morris svg {
				width:100% !important;
			}
		");

		$document->addScriptDeclaration("
			jQuery.noConflict();
			(function($) {
				$(window).load(function() {
					$('a[data-toggle=tab]').on('shown.bs.tab', static function (e) {
						var morrisId = $('.morris', $($(e.target).attr('href'))).attr('id');

						if(morrisId) {
							eval(morrisId + '.redraw()');
						}
					});
				});
			})(jQuery);
		");
	}
	
	static function donut($data, $formatter, $colors = null) {
		if(!$data) return null;
		$document = JFactory::getDocument();
		$id = rand(1000,9999);
		
		if(!is_array($colors)) {
			$colors = array(
				'#2980b9', '#27ae60', '#16a085', 
				'#c0392b', '#d35400', '#f39c12', 
				'#34495e', '#3498db', '#2ecc71', 
				'#1abc9c', '#e74c3c', '#e67e22', 
				'#f1c40f', '#5c4383', '#172b4a'
			);
		}
		$colors	= json_encode(array_slice($colors, 0, count($data)));
		$data	= json_encode($data);

		$document->addScriptDeclaration("
			jQuery.noConflict();
			(function($) {
				$(function() {
					donut$id = Morris.Donut({
						element: 'donut$id',
						data: $data,
						colors: $colors,
						resize: true,
						formatter: static function (x) { return $formatter}
					});
				});
			})(jQuery);
		");

		return '<div id="donut'.$id.'" class="morris" style="width:100%"></div>';
	}

	static function bar($data, $xkey, $ykeys, $ylabels, $stacked = 'false', $colors = null) {
		if(!$data) return null;
		$document = JFactory::getDocument();
		$id = rand(1000,9999);
		
		if(!is_array($colors)) {
			$colors = array(
				'#2980b9', '#27ae60', '#16a085', 
				'#c0392b', '#d35400', '#f39c12', 
				'#34495e', '#3498db', '#2ecc71', 
				'#1abc9c', '#e74c3c', '#e67e22', 
				'#f1c40f', '#5c4383', '#172b4a'
			);
		}
		$colors		= json_encode(array_slice($colors, 0, count($data)));
		$data		= json_encode($data);
		$ykeys		= json_encode(array_values($ykeys));
		$ylabels	= json_encode(array_values($ylabels));

		$document->addScriptDeclaration("
			jQuery.noConflict();
			(function($) {
				$(function() {
					bar$id = Morris.Bar({
						element: 'bar$id',
						data: $data,
						barColors: $colors,
						xkey: '$xkey',
						ykeys: $ykeys,
						labels: $ylabels,
						stacked: $stacked,
						resize: true
					});
				});
			})(jQuery);
		");

		return '<div id="bar'.$id.'" class="morris" style="width:100%"></div>';
	}
}
