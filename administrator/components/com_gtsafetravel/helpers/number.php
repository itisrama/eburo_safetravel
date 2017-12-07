<?php
/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
JLoader::import('libphonenumber.autoloader');

// no direct access
defined('_JEXEC') or die('Restricted access');

class GTHelperNumber
{

	public static function format($number) {
		$decimal_symbol = ',';
		$digit_group_symbol = '.';
		$num = is_numeric($number) ? number_format($number, 0, $decimal_symbol, $digit_group_symbol) : $number;
		return $num;
	}

	public static function toRoman($num) {
		$n = intval($num);
		$res = '';
		// roman_numerals array 
		$roman_numerals = array(
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1);

		foreach ($roman_numerals as $roman => $number) {
			// divide to get  matches
			$matches = intval($n / $number);
			// assign the roman char * $matches
			$res .= str_repeat($roman, $matches);
			// substract from the number *
			$n = $n % $number;
		}
		return $res;
	}

	public static function clean($msisdn) {
		return preg_replace('/\D/', '', $msisdn);
	}

	public static function setMSISDN($msisdn) {
		$defCallCode = '62';

		$msisdn = explode(':', $msisdn);
		
		$nat = $msisdn[1];
		$int = $msisdn[2];
		$callCode1 = @$msisdn[3];
		$callCode2 = @$msisdn[3].@$msisdn[4];

		$msisdn = $defCallCode == $callCode1 || $defCallCode == $callCode2 ? $nat : $int;

		return $msisdn;
	}
	
	public static function toMSISDN($msisdn) {
		$params				= JComponentHelper::getParams('com_gtsafetravel');
		$country_code		= $params->get('def_calling_code', '62');
		$msisdn_filtered	= preg_replace("/[^0-9]/", "", $msisdn);
		
		$msisdn = strlen($msisdn_filtered)>8 ? $msisdn_filtered : trim($msisdn);
		$msisdn = substr($msisdn, 0, 1) == '0' && strlen($msisdn > 8) ? $country_code.ltrim($msisdn, '0') : $msisdn;
		return $msisdn;
	}

	public static function lookupMSISDN($msisdn) {
		$params			= JComponentHelper::getParams('com_gtsafetravel');
		$country_code	= $params->get('def_calling_code', '62');
		
		$data			= new stdClass();
		$data->carrier	= 'Unknown';
		$data->location	= 'Unknown';
		if(strlen($msisdn) > 10 && substr($msisdn, 0, strlen($country_code)) == $country_code) {
			$msisdn				= substr($msisdn, strlen($country_code));
			$data->calling_code	= $country_code;
			$data->area_code	= substr($msisdn, 0, 3);
			$submsisdn 			= substr($msisdn, 3);
			$segment_len 		= strlen($submsisdn) > 6 ? 4 : 3;
			$msisdn 			= str_split($submsisdn, $segment_len); array_unshift($msisdn, $data->area_code);
			$msisdn 			= implode('-', $msisdn);

			$data->msisdn_int = '+'.$country_code.' '.$msisdn;
			$data->msisdn_nat = '0'.$msisdn;
		} else {
			$data->msisdn_int = $msisdn;
			$data->msisdn_nat = $msisdn;
		}

		return $data;
	}

}
