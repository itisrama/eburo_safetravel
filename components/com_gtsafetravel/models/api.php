<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;


class GTSafeTravelModelAPI extends GTModelAdmin {
	public function __construct($config = array()) {
		parent::__construct($config);
	}

	public function getCountry($country) {
		$db = $this->_db;
		$query = $db->getQuery(true);

		// Main Table
		$query->select($db->quoteName(array(
			'a.id', 'a.code', 'a.name', 'a.name_en', 'a.latitude', 'a.longitude', 
			'a.capital_city_id', 'a.largest_city_id', 'a.population', 'a.area', 
			'a.language', 'a.languages', 'continent_code'
		)));
		$query->from($db->quoteName('#__gtsafetravel_ref_country', 'a'));

		$query->where('('.
			$db->quoteName('a.old_id').' = '.intval($country).' OR '.
			$db->quoteName('a.code').' = '.$db->quote($country).' OR '.
			$db->quoteName('a.id').' = '.intval($country)
		.')');

		// Join Indicator Status
		$query->join('LEFT', $db->quoteName('#__gtsafetravel_ref_country_indicator', 'b').' ON '.
			$db->quoteName('a.id').' = '.$db->quoteName('b.id')
		);
		// Join Indicator Ref
		$query->select($db->quoteName('c.id', 'indicator_id'));
		$query->select($db->quoteName('c.name', 'indicator'));
		$query->select($db->quoteName('c.bg_color', 'indicator_bg_color'));
		$query->select($db->quoteName('c.text_color', 'indicator_text_color'));
		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_indicator', 'c').' ON '.
			'IFNULL('.$db->quoteName('b.indicator_id').', 5) = '.$db->quoteName('c.id')
		);

		//echo nl2br(str_replace('#__','eburo_',$query));
		$db->setQuery($query);
		return $db->loadObject();
	}

	public function getCountries($all = false, $countryOffset = '', $limit = 250, $offset = 0) {
		$db		= $this->_db;
		$query	= $db->getQuery(true);

		// Main Table
		$query->select($db->quoteName(array(
			'a.id', 'a.code', 'a.name', 'a.name_en', 'a.latitude', 'a.longitude', 
			'a.capital_adm_id', 'a.largest_adm_id', 'a.population', 'a.area', 
			'a.language', 'a.languages', 'continent_code', 'location', 'location2',
			'a.capital_city', 'a.largest_city'
		)));
		$query->from($db->quoteName('#__gtsafetravel_ref_country', 'a'));

		if(!$all) {
			$query->where($db->quoteName('a.published').' = 1');
		}
		$query->order($db->quoteName('a.code'));

		// Join Indicator Status
		$query->join('LEFT', $db->quoteName('#__gtsafetravel_ref_country_indicator', 'b').' ON '.
			$db->quoteName('a.id').' = '.$db->quoteName('b.id')
		);
		// Join Indicator Ref
		$query->select($db->quoteName('c.id', 'indicator_id'));
		$query->select($db->quoteName('c.name', 'indicator'));
		$query->select($db->quoteName('c.bg_color', 'indicator_bg_color'));
		$query->select($db->quoteName('c.text_color', 'indicator_text_color'));
		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_indicator', 'c').' ON '.
			'IFNULL('.$db->quoteName('b.indicator_id').', 5) = '.$db->quoteName('c.id')
		);

		if($countryOffset) {
			$query->where($db->quoteName('a.code').' >= '.$db->quote($countryOffset));
		}

		$query->where('('.$db->quoteName('a.capital_city_id').' IS NOT NULL OR '.$db->quoteName('a.largest_city_id').' IS NOT NULL)');

		/*
		$countries = array('GR','AZ','RU','UA','RS');
		$countries = array_map(array($db,'quote'), $countries);
		$countries = implode(',', $countries);
		$query->where($db->quoteName('a.code').' IN ('.$countries.')');
		*/

		$query->setLimit($limit, $offset);

		//echo nl2br(str_replace('#__','eburo_',$query)); die;

		$db->setQuery($query);
		return $db->loadObjectList();
	}

	public function searchAdm($country_code, $name = null, $parent = null, $level = 2) {
		$db = $this->_db;
		$query = $db->getQuery(true);

		// Main Table
		$query->select('a.*');
		$query->from($db->quoteName('#__gtsafetravel_ref_country_adm', 'a'));
		$query->where($db->quoteName('a.country_code').' = '.$db->quote($country_code));

		if($level > 1) {
			$query->where($db->quoteName('a.adm'.($level-1)).' = '.$db->quote($parent));
		}
		$query->where($db->quoteName('a.name_en').' = '.$db->quote($name));
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);
		$result = $db->loadObject();
		return $this->sanitizeItem($result, 'ref_country_adm', true);
	}

	public function getAdm($city_id) {
		$db = $this->_db;
		$query = $db->getQuery(true);

		// Main Table
		$query->select($db->quoteName(array(
			'a.id', 'a.country_id', 'a.adm1_id', 'a.name', 'a.name_en', 
			'a.level', 'a.fcode', 'a.latitude', 'a.longitude', 'a.population'
		)));
		$query->from($db->quoteName('#__gtsafetravel_ref_country_adm', 'a'));
		$query->where($db->quoteName('a.id').' = '.intval($city_id));

		//echo nl2br(str_replace('#__','eburo_',$query));
		$db->setQuery($query);
		return $db->loadObject();
	}

	public function getNearbyAdm($loc, $level = 2, $acc = 100) {
		$cities	= $this->getNearbyAdms($loc, $level, $acc, 1);
		$city = reset($cities);
		return $city;
	}

	public function getNearbyAdms($loc, $level = 2, $acc = 100, $limit = 10) {
		$db		= $this->_db;
		$query	= $db->getQuery(true);
		$loc 	= GTHelperGeo::splitLocation($loc);

		$distance = '(3959 * acos(
			cos(radians('.$db->quoteName('a.latitude').')) * cos(radians('.$loc->latitude.')) * 
			cos(radians('.$loc->longitude.') - radians('.$db->quoteName('a.longitude').')) + 
			sin(radians(a.latitude)) * sin(radians('.$loc->latitude.'))
		))';

		$query->select($db->quoteName(array(
			'a.id', 'a.country_id', 'a.adm1_id', 'a.adm1_code', 'a.name', 'a.name_en', 
			'a.level', 'a.fcode', 'a.latitude', 'a.longitude', 'a.population'
		)));
		$query->select($distance.' distance');
		$query->from($db->quoteName('#__gtsafetravel_ref_country_adm', 'a'));
		$query->where($db->quoteName('a.level').' = '.$db->quote($level));

		if($acc) {
			$acc = $acc/111.32;

			$lat_min = $loc->latitude - $acc;
			$lat_max = $loc->latitude + $acc;
			$lon_min = $loc->longitude - $acc;
			$lon_max = $loc->longitude + $acc;

			$query->where($db->quoteName('a.latitude').' BETWEEN '.$lat_min.' AND '.$lat_max);
			$query->where($db->quoteName('a.longitude').' BETWEEN '.$lon_min.' AND '.$lon_max);
		}
		
		$query->order($distance);
		$query->setLimit($limit);

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);
		return $db->loadObjectList();
	}
}
