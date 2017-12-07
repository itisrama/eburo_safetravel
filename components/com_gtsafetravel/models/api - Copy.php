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

	public function getWeather($city_id = null) {
		$date = JFactory::getDate();
		$city_id = $city_id ? $city_id : $this->input->get('city_id');

		$urlIcon = 'http://openweathermap.org/img/w/%s.png';
		$url = 'http://openweathermap.org/data/2.5/weather?id='.$city_id.'&units=metric&appid=b1b15e88fa797225412429c1c50c122a1';

		$db = $this->_db;
		$query = $db->getQuery(true);
		$current_hour = $date->format('H');
		$current_hour = round($current_hour/24*4)*6;
		$current_hour = sprintf('%02d', $current_hour);
		$current_date = $date->format('Y-m-d');
		$current_time = $current_date.' '.$current_hour.':00:00';

		$db = $this->_db;
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.id', 'weather_id'));
		$query->select($db->quoteName('a.datetime', 'weather_datetime'));
		$query->select($db->quoteName('a.icon', 'weather_icon'));
		$query->select($db->quoteName('a.description', 'weather_name'));
		$query->select($db->quoteName('a.temp', 'weather_temperature'));
		$query->select($db->quoteName('a.description', 'weather_season_name'));
		$query->select($db->quoteName('a.temp_min', 'weather_temp_min'));
		$query->select($db->quoteName('a.temp_max', 'weather_temp_max'));
		$query->select($db->quoteName('a.pressure', 'weather_pressure'));
		$query->select($db->quoteName('a.humidity', 'weather_humidity'));
		$query->select($db->quoteName('a.wind_speed', 'weather_wind_speed'));
		$query->select($db->quoteName('a.cloud', 'weather_cloud'));
		$query->select($db->quoteName('a.visibility', 'weather_visibility'));

		$query->from($db->quoteName('#__gtsafetravel_ref_weather', 'a'));

		$query->where($db->quoteName('a.city_id').' = '.$db->quote($city_id));

		$db->setQuery($query);

		$item = $db->loadObject();
		$weather_id = intval(@$item->weather_id);
		unset($item->weather_id);

		if(@$item->weather_datetime == $current_time) {
			$item->weather_icon			= GTHelper::verifyFile(sprintf($urlIcon, $item->weather_icon), false);
			$item->weather_temperature	= floatval($item->weather_temperature).' °C';
			$item->weather_temp_min		= floatval($item->weather_temp_min).' °C';
			$item->weather_temp_max		= floatval($item->weather_temp_max).' °C';
			$item->weather_pressure		.= ' hpa';
			$item->weather_humidity		.= '%';
			$item->weather_wind_speed	= floatval($item->weather_wind_speed).' m/s';
			$item->weather_cloud		.= '%';
			$item->weather_visibility	.= ' m';
			
			return $item;
		} else {
			$data = @file_get_contents($url, 0, null, null);
			$data = json_decode($data);

			if(!@$data->name) {
				return $item;
			}

			$weather = $data->weather;
			$weather = reset($weather);

			$new				= new stdClass();
			$new->id			= $weather_id;
			$new->city_id		= $city_id;
			$new->country_code	= $data->sys->country;
			$new->latitude		= $data->coord->lat;
			$new->longitude		= $data->coord->lon;
			$new->name			= $data->name;
			$new->datetime		= $current_time;
			$new->icon			= $weather->icon;
			$new->code			= $weather->id;
			$new->description	= ucwords($weather->description);
			$new->temp 			= $data->main->temp;
			$new->pressure		= $data->main->pressure;
			$new->temp_min		= $data->main->temp_min;
			$new->temp_max		= $data->main->temp_max;
			$new->humidity		= $data->main->humidity;
			$new->wind_speed	= $data->wind->speed;
			$new->wind_deg		= $data->wind->deg;
			$new->cloud			= $data->clouds->all;
			$new->visibility	= $data->visibility;			

			$this->saveExternal($new, 'ref_weather');
			
			return $this->getWeather($city_id);
		}
	}

	public function getCountries($all = false) {
		$latitude	= $this->input->get('latitude');
		$longitude	= $this->input->get('longitude');
		$limit		= $this->input->get('limit', 0);
		$offset		= $this->input->get('offset', 0);

		$db = $this->_db;
		$query = $db->getQuery(true);

		$isNearby = $latitude && $longitude;

		$query->select($db->quoteName('a.id', 'country_id'));
		$query->select($db->quoteName('a.code', 'country_code'));
		$query->select($db->quoteName('a.name', 'country_name'));
		$query->select($db->quoteName('a.name_en', 'country_alias'));
		$query->select($db->quoteName('c.id', 'country_indicator'));
		$query->select($db->quoteName('c.name', 'country_indicator_name'));
		$query->select($db->quoteName('a.language', 'country_language'));

		$query->from($db->quoteName('#__gtsafetravel_ref_country', 'a'));
		$query->join('LEFT', $db->quoteName('#__gtsafetravel_ref_country_indicator', 'b').' ON '.
			$db->quoteName('a.id').' = '.$db->quoteName('b.id')
		);

		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_indicator', 'c').' ON '.
			'IFNULL('.$db->quoteName('b.indicator_id').', 5) = '.$db->quoteName('c.id')
		);

		if(!$all) {
			$query->where($db->quoteName('a.published').' = 1');
		}

		if($isNearby) {
			$distance = '
				(3959 * acos(
					cos(radians('.$db->quoteName('a.latitude').')) * cos(radians('.floatval($latitude).')) * 
					cos(radians('.floatval($longitude).') - radians('.$db->quoteName('a.longitude').')) + 
					sin(radians(a.latitude)) * sin(radians('.floatval($latitude).'))
				))
			';

			$query->select($distance.' country_distance');
			$query->where($db->quoteName('a.latitude').' IS NOT NULL');
			$query->where($db->quoteName('a.longitude').' IS NOT NULL');

			$query->order($distance);
			$query->setLimit($limit + 10, $offset);
		} else {
			$startCountry = $this->input->get('start_country');
			if($startCountry) {
				$query->where($db->quoteName('a.code').' >= '.$db->quote($startCountry));
			}

			$query->order($db->quoteName('a.code'));

			if($limit) {
				$query->setLimit($limit, $offset);
			}
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);

		$items = $db->loadObjectList();

		foreach ($items as $k => &$item) {
			$item->country_id				= intval($item->country_id);
			$item->country_indicator		= intval($item->country_indicator);
			$item->country_flag				= GTHelper::verifyFile(GT_MEDIA_URI.'/flags/320/'.strtolower($item->country_code).'.png');
			$item->country_flag_large		= GTHelper::verifyFile(GT_MEDIA_URI.'/flags/320/'.strtolower($item->country_code).'.png');
			$item->country_landmark			= GTHelper::verifyFile(GT_MEDIA_URI.'/landmark/'.$item->country_code.'.jpg');
			$item->country_indicator_image	= GTHelper::verifyFile(GT_MEDIA_URI.'/indicator/'.$item->country_indicator.'.png');
			$item->country_language 		= $item->country_language ? $item->country_language : 'en';

			if($isNearby) {
				$item->country_distance		= floatval(round($item->country_distance, 2));

				if(!$item->country_landmark) {
					unset($items[$k]);
				}
			}
		}

		$items = $isNearby ? array_slice($items, 0, $limit) : $items;
		return $items;
	}

	public function getCountryNews($country_id = null) {
		$country_id	= $country_id ? $country_id : $this->input->get('country_id');

		$db = $this->_db;
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.id', 'news_id'));
		$query->select($db->quoteName('a.code', 'news_code'));
		$query->select($db->quoteName('a.title', 'news_title'));
		$query->select($db->quoteName('a.date', 'news_date'));

		$query->from($db->quoteName('#__gtsafetravel_web_news', 'a'));

		$query->where($db->quoteName('a.country_id').' = '.$db->quote($country_id));
		$query->where($db->quoteName('a.published').' = 1');
		$query->order($db->quoteName('a.id'));

		$db->setQuery($query);

		$items = $db->loadObjectList();

		foreach ($items as &$item) {
			$item->news_id = intval($item->news_id);
		}

		return $items;
	}

	public function getCountryEmbassies($country_id = null) {
		$country_id	= $country_id ? $country_id : $this->input->get('country_id');

		$db = $this->_db;
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.id', 'embassy_id'));
		$query->select($db->quoteName('a.name', 'embassy_nama'));
		$query->select($db->quoteName('a.city', 'embassy_nama_kota'));
		$query->select($db->quoteName('a.telp', 'embassy_telp'));
		$query->select($db->quoteName('a.fax', 'embassy_fax'));
		$query->select($db->quoteName('a.email', 'embassy_email'));
		$query->select($db->quoteName('a.hotline', 'embassy_hotline'));
		$query->select($db->quoteName('a.image_path', 'embassy_image_path'));
		$query->select($db->quoteName('a.latitude', 'embassy_latitude'));
		$query->select($db->quoteName('a.longitude', 'embassy_longitude'));

		$query->from($db->quoteName('#__gtsafetravel_ref_embassy', 'a'));
		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_embassy_country', 'b').' ON '.
			$db->quoteName('a.id').' = '.$db->quoteName('b.embassy_id')
		);

		$query->where($db->quoteName('a.country_id').' = '.$db->quote($country_id));
		$query->where($db->quoteName('a.published').' = 1');
		$query->order($db->quoteName('a.id'));

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);

		$items = $db->loadObjectList();

		foreach ($items as &$item) {
			$item->embassy_id				= intval($item->embassy_id);
		}

		return $items;
	}

	public function getCountryInfo($country_id = null) {
		$infos		= array('himbauan', 'umum', 'keluarmasuk', 'keamanan', 'hukum', 'matauang', 'perjalanan', 'telekomunikasi', 'asuransi');
		$country_id	= $country_id ? $country_id : $this->input->get('country_id');

		$db = $this->_db;
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.info_id', 'info', 'created')));
		$query->from($db->quoteName('#__gtsafetravel_web_country_info', 'a'));
		$query->where($db->quoteName('a.country_id').' = '.$db->quote($country_id));
		$query->group($db->quoteName('a.info_id'));

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);

		$items = $db->loadObjectList('info_id');

		$result = array();
		foreach ($infos as $k => $suffix) {
			$info_created = @$item->created;
			$info_created = $info_created ? JHtml::date($info_created, 'd F Y H:i:s') : '';

			$item = @$items[$k+1];
			$result['country_info_'.$suffix] = @$item->info;
			$result['country_info_'.$suffix.'_create_date'] = $info_created;
		}

		return JArrayHelper::toObject($result);
	}

	public function getCountry() {
		$country_id = $this->input->get('country_id', 'ID');

		$db = $this->_db;
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.id', 'country_id'));
		$query->select($db->quoteName('a.code', 'country_code'));
		$query->select($db->quoteName('a.name', 'country_name'));
		$query->select($db->quoteName('a.name_en', 'country_alias'));
		$query->select($db->quoteName('c.id', 'country_indicator'));
		$query->select($db->quoteName('c.name', 'country_indicator_name'));
		$query->select($db->quoteName('c.bg_color', 'country_indicator_color_bg'));
		$query->select($db->quoteName('c.text_color', 'country_indicator_color_tx'));
		$query->select($db->quoteName('d.name', 'country_largest_city'));
		$query->select($db->quoteName('d.id', 'country_largest_city_id'));
		$query->select($db->quoteName('a.latitude', 'country_latitude'));
		$query->select($db->quoteName('a.longitude', 'country_longitude'));

		$query->from($db->quoteName('#__gtsafetravel_ref_country', 'a'));
		$query->join('LEFT', $db->quoteName('#__gtsafetravel_ref_country_indicator', 'b').' ON '.
			$db->quoteName('a.id').' = '.$db->quoteName('b.id')
		);

		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_indicator', 'c').' ON '.
			'IFNULL('.$db->quoteName('b.indicator_id').', 5) = '.$db->quoteName('c.id')
		);

		$query->join('LEFT', $db->quoteName('#__gtsafetravel_ref_city', 'd').' ON '.
			$db->quoteName('a.largest_city_id').' = '.$db->quoteName('d.id')
		);

		$query->where('('.
			$db->quoteName('a.old_id').' = '.intval($country_id).' OR '.
			$db->quoteName('a.code').' = '.$db->quote($country_id).' OR '.
			$db->quoteName('a.id').' = '.intval($country_id)
		.')');
		$query->where('IF('.$db->quoteName('a.code').' = '.$db->quote('ID').', TRUE, '.$db->quoteName('a.published').' = 1)');
		$query->group($db->quoteName('a.id'));

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);

		$item = $db->loadObject();
		//echo "<pre>"; print_r($item); echo "</pre>"; die;

		if(!is_object($item)) {
			return null;
		}

		$item->country_id				= intval($item->country_id);
		$item->country_indicator		= intval($item->country_indicator);
		$item->country_flag				= GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.$item->country_code.'.png');
		$item->country_landmark			= GTHelper::verifyFile(GT_MEDIA_URI.'/landmark/'.$item->country_code.'.jpg');
		$item->country_indicator_image	= GTHelper::verifyFile(GT_MEDIA_URI.'/indicator/'.$item->country_indicator.'.png');

		return $item;
	}

	public function getCountryDetail() {
		$item = $this->getCountry();
		if(!$item) {
			return null;
		}

		$item->country_weather 			= $this->getWeather($item->country_largest_city_id);
		$item->country_himbauan_lists	= $this->getCountryNews();
		$item->country_info_perwakilan	= $this->getCountryEmbassies();
		
		$country_info = $this->getCountryInfo($item->country_id);
		foreach ($country_info as $cInfoKey => $cInfo) {
			$item->$cInfoKey = $cInfo;
		}

		return $item;
	}

	public function getCountrySliders() {
		$item = $this->getCountry();
		if(!$item) {
			return null;
		}

		$this->input->set('latitude', $item->country_latitude);
		$this->input->set('longitude', $item->country_longitude);
		$this->input->set('limit', 10);

		$sliders = $this->getCountries(false);

		return $sliders;
	}
}
