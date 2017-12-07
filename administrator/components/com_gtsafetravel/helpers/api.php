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

class GTHelperAPI {

	public static function formatLocation() {
		$json = array();

		$json['location_country']['country_id']						= @$locSys->country_id;
		$json['location_country']['country_code']					= @$locSys->country->code;
		$json['location_country']['country_name']					= @$locSys->country->name;
		$json['location_country']['country_name_english']			= @$locSys->country->name_en;
		$json['location_country']['country_official_name']			= @$locSys->country->official_name;
		$json['location_country']['country_official_name_english']	= @$locSys->country->official_name_en;
		
		$json['location_adm1']['adm1_id']			= @$locSys->adm1_id;
		$json['location_adm1']['adm1_name']			= @$locSys->adm1->name;
		$json['location_adm1']['adm1_name_english']	= @$locSys->adm1->name_en;

		$json['location_adm2']['adm2_id']			= @$locSys->adm2_id;
		$json['location_adm2']['adm2_name']			= @$locSys->adm2->name;
		$json['location_adm2']['adm2_name_english']	= @$locSys->adm2->name_en;

		$json['location_adm3']['adm3_id']			= @$locSys->adm3_id;
		$json['location_adm3']['adm3_name']			= @$locSys->adm3->name;
		$json['location_adm3']['adm3_name_english']	= @$locSys->adm3->name_en;

		$json['location_city']			= @$locSys->city;
		$json['location_address']		= @$locSys->address;
		$json['location_postal_code']	= @$locSys->postal_code;
		$json['location_latitude']		= @$locSys->latitude;
		$json['location_longitude']		= @$locSys->longitude;
		$json['location_accuracy']		= @$locSys->dist.' km';

		$this->prepareJSON($json);
		return $info;
	}

	public static function geocodeLocation($query, $type = 'latlng') {
		
	}
}
