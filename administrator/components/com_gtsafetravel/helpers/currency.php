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

class GTHelperCurrency
{
	static $symbol;
	static $decimal_symbol;
	static $digit_group_symbol;

	static function setCurrency($symbol = 'Rp ', $decimal_symbol = ',', $digit_group_symbol = '.') {
		$document = JFactory::getDocument();
		
		self::$symbol				= $symbol;
		self::$decimal_symbol		= $decimal_symbol;
		self::$digit_group_symbol	= $digit_group_symbol;

		$document->addScript(GT_ADMIN_JS . '/jquery.formatCurrency.js');
		$document->addScript(GT_ADMIN_JS . '/jquery.formatCurrency.custom.js');
		/*$document->addScriptDeclaration("
			// Initiate currency locale
			jQuery.noConflict();
			(function($) {
				$.formatCurrency.regions['custom'] = {
					symbol: '$symbol',
					positiveFormat: '%s%n',
					negativeFormat: '(%s%n)',
					decimalSymbol: '$decimal_symbol',
					digitGroupSymbol: '$digit_group_symbol',
					groupDigits: true,
					roundToDecimalPlace: 0
				};

				$.formatCurrency.regions['numeric'] = {
					symbol: '',
					positiveFormat: '%s%n',
					negativeFormat: '-%s%n',
					groupDigits: false,
					roundToDecimalPlace: -1
				};
			})(jQuery);
		");*/
	}
	
	static function fromNumber($number, $symbol = false) {
		if(!is_numeric($number)) return $number;

		$is_negative		= $number < 0;
		$number 			= $number < 0 ? $number * -1 : $number;
		$symbol				= is_string($symbol) ? $symbol : self::$symbol;
		$decimal_symbol		= self::$decimal_symbol;
		$digit_group_symbol	= self::$digit_group_symbol;

		$number				= $symbol . number_format(floatval($number), 0, $decimal_symbol, $digit_group_symbol);
		$number				= $is_negative ? "($number)" : $number;
		return $number;
	}
	
	static function toNumber($currency) {
		if($currency == '') return null;

		$symbol				= self::$symbol;
		$decimal_symbol		= self::$decimal_symbol;
		$digit_group_symbol	= self::$digit_group_symbol;
		
		$replacee = array($symbol, $digit_group_symbol, $decimal_symbol);
		$replacer = array('', '', '.');
		
		$currency = str_replace($replacee, $replacer, $currency);
		
		return $currency;
	}
	
	

}
