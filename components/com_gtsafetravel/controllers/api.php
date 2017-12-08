<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelControllerAPI extends GTControllerAdmin {
	public function __construct($config = array()) {
		parent::__construct($config);

		$this->input->set('tmpl', 'raw');
		GTHelperAPI::setTime(microtime(true));

		if(!method_exists($this, $this->input->get('task'))) {
			GTHelperAPI::prepareJSON(null, JText::_('COM_GTSAFETRAVEL_SERVICE_NOT_FOUND'), 404);
		}
	}

	public function testssh() {
		GTHelperSSH::connect();
		GTHelperSSH::unlink('test.txt');
		GTHelperSSH::disconnect();
	}

	public function checkSession($is_called = false) {
		// Get Params
		$access_token = GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		list($session_id, $client_key) = explode('|', $access_token.'|');

		$isLogin = GTHelperAPI::validateSession($session_id, $client_key);

		if(!$isLogin) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_SESSION_EXPIRED'), 401);
		}
		if(!$is_called) {
			GTHelperAPI::prepareJSON(true, JText::_('COM_GTSAFETRAVEL_CLIENT_SESSION_VALID'));
		}
	}

	public function login($client_id = 0, $succesMsg2 = '', $isRegister = false) {
		// Get Params
		$username	= GTHelper::getInput('username', '', 'raw');
		$password	= GTHelper::getInput('password', '', 'raw');

		// Process Data
		if($client_id) {
			$client = GTHelperDB::getItem('sys_client', 'id = '.$client_id, 'id, key, published, password, old_password');
		} else {
			$client = GTHelperDB::getItem('sys_client', 'username = "'.$username.'" OR email = "'.$username.'"', 'id, key, published, password, old_password');
		}
		

		$failedMsg = $client_id ? JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2') : JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED');
		$succesMsg = $succesMsg2 ? $succesMsg2 : JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_SUCCESS');
		$unverfMsg = $client_id ? JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_UNVERIFIED2') : JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_UNVERIFIED');
		
		$messages = array(
			$failedMsg,
			JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_BLOCKED'),
			$succesMsg,
			JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_UNVERIFIED')
		);
		$isOldLoginValid	= $client->old_password == md5($password);
		$isLoginValid		= $isOldLoginValid || JUserHelper::verifyPassword($password, $client->password);
		$isLoginValid		= $client_id ? true : $isLoginValid;

		$client_id		= $client->id;
		$client_status	= $isLoginValid ? $client->published : false;
		$client_status	= is_numeric($client_status) ? $client_status + 1 : 0;
		$message		= $messages[$client_status];
		$isActive		= $client_status == 2;

		if($isLoginValid) {
			$newPassword			= JUserHelper::hashPassword($password);
			$client->key			= @$client->key ? $client->key : md5(uniqid('', true));
			$client->password		= $newPassword;
			$client->old_password	= $isOldLoginValid ? null : $client->old_password;
			GTHelperDB::insertItem('sys_client', $client);
		}

		if($isActive) {
			GTHelperAPI::setSession($client->key);
			$client = GTHelperAPI::getClient($client_id);
			GTHelperAPI::setLoginStatus($client_id);

			if($isRegister) {
				GTHelper::loadCurl(GTHelperAPI::chatUrl().'/auth/signup', array(
					'access_token'	=> $client->access_token,
					'name'			=> $client->name,
					'username'		=> $client->username,
					'email'			=> $client->email,
					'phone'			=> $client->phone,
					'photo'			=> $client->photo
				), true);
			} else {
				GTHelper::loadCurl(GTHelperAPI::chatUrl().'/auth/update/session', array(
					'access_token' => $client->access_token
				), true);
			}
		} else {
			$client = false;
		}

		GTHelperAPI::prepareJSON($client, $message, $client_status == 3 ? 401 : '');
	}

	public function loginExternal() {
		// Get Params
		$hash			= GTHelper::getInput('hash', '', 'raw');
		$hash_type		= GTHelper::getInput('hash_type', '', 'int');

		// Process Data
		$client_id = GTHelperDB::getItem('sys_client_credential', array(
			'hash' => $hash,
			'credential_id' => $hash_type
		), 'client_id');

		if($client_id) {
			$this->login($client_id);
		} else {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}
	}

	public function clientAvailable($username = '', $email = '', $json = true, $no_email = false) {
		// Get Params
		$username	=  GTHelper::getInput('username', $username, 'raw');
		$email		=  GTHelper::getInput('email', $email, 'raw');

		// Process Data
		$client = GTHelperDB::getItem('sys_client', array(
			'email' => $email,
		), 'id, key');

		$client2 = GTHelperDB::getItem('sys_client', array(
			'username' => $username,
		), 'id, key');

		$client_id		= @$client->id;
		$client_id2		= @$client2->id;
		$client_key		= @$client->key;
		$client_key2	= @$client2->key;

		// Make sure client is not registered
		$emailAvailable = $no_email ? true : !$client_id;
		$unameAvailable = $no_email ? ($client_id == $client_id2 ? true : !$client_id2) : !$client_id2;
		
		$clientAvailable = $unameAvailable && $emailAvailable;

		if($clientAvailable) {
			if($username) {
				$message = JText::_('COM_GTSAFETRAVEL_CLIENT_USERNAME_AVAILABLE');
			} else {
				$message = JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_AVAILABLE');
			}
		} else {
			if(!$unameAvailable) {
				$message = JText::_('COM_GTSAFETRAVEL_CLIENT_USERNAME_EXISTS');
			} elseif(!$emailAvailable) {
				$message = JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_EXISTS');
			} else {
				$message = JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_USERNAME_EXISTS');
			}
		}

		if($json) {
			GTHelperAPI::prepareJSON($clientAvailable, $message);
		} else {
			return array($clientAvailable, $message, $client, $client2);
		}
	}

	public function register() {
		// Get Params
		$username	= GTHelper::getInput('username', '', 'raw');
		$password	= GTHelper::getInput('password', '', 'raw');
		$name		= GTHelper::getInput('name', '', 'raw');
		$email		= GTHelper::getInput('email', '', 'raw');
		$phone		= GTHelper::getInput('phone', '', 'raw');
		$photo		= GTHelper::getInput('photo', '', 'raw');

		// Process Data
		$client	= new stdClass();

		// Set User Data
		$client->key			= md5(uniqid('', true));
		$client->username		= $username;
		$client->password		= JUserHelper::hashPassword($password);
		$client->name			= $name;
		$client->email			= $email;
		$client->phone			= $phone;
		$client->confirm_code	= md5(uniqid('', true));
		$client->published		= 1;

		if(!$client->username && !$client->username) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_NOT_COMPLETE'));
		}

		list($clientAvailable, $message) = $this->clientAvailable($client->username, $client->email, false);

		if(!$clientAvailable) {
			GTHelperAPI::prepareJSON(false, $message);
		}

		// Upload Profile Photo
		$photo = GTHelperAPI::uploadPhotos($photo, GT_SAFETRAVEL_PROFILE.DS.$client->key);
		if($photo) {
			$photo = reset($photo);
			$photo = explode(DS, $photo);
			$photo = end($photo);
			$client->photo = $photo;
		}

		// Send Email Confirmation
		$conf_link		= 'index.php?option=com_gtsafetravel&view=api&layout=emailconf';
		$conf_link_id	= GTHelper::getMenuId($conf_link);
		$conf_link		= JRoute::_($conf_link.'&Itemid='.$conf_link_id.'&confirm_code='.$client->confirm_code, true, -1);
		$email_title	= JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_CONF_TITLE');
		$email_body		= JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_CONF_BODY');
		$email_body		= strtr($email_body, array(
			'{NAME}' => $client->name,
			'{LINK}' => $conf_link
		));
		GTHelperMail::send($email_title, $email_body, $client->email);

		// Save User
		$client_id = GTHelperDB::insertItem('sys_client', $client, 'id', 'key');

		// Return User Data
		$this->login($client_id, JText::_('COM_GTSAFETRAVEL_CLIENT_REGISTRATION_SUCCESS'), true);
	}

	public function registerExternal() {
		// Get Params
		$username		= GTHelper::getInput('username', '', 'raw');
		$name			= GTHelper::getInput('name', '', 'raw');
		$email			= GTHelper::getInput('email', '', 'raw');
		$isValid		= GTHelper::getInput('valid_email', '', 'int');
		$phone			= GTHelper::getInput('phone', '', 'raw');
		$hash			= GTHelper::getInput('hash', '', 'raw');
		$hash_type		= GTHelper::getInput('hash_type', '', 'int');
		$photo			= GTHelper::getInput('photo', '', 'raw');

		// Process Data
		$client		= new stdClass();
		$clientCred	= new stdClass();
		
		// Set User Data
		$client->username	= $username;
		$client->name		= $name;
		$client->email		= $email;
		$client->phone		= $phone;
		$client->published	= 1;

		// Set User Credential
		$clientCred->hash			= $hash;
		$clientCred->credential_id	= $hash_type;

		if(!($client->username && $client->email && $clientCred->credential_id)) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_NOT_COMPLETE'));
		}

		$clientAvailable = $this->clientAvailable($client->username, $client->email, false, $isValid);
		list($available, $message, $prevClient) = $clientAvailable;

		if(!$available) {
			GTHelperAPI::prepareJSON(false, $message);
		}

		// Set User ID & Key
		$client->id		= $prevClient->id;
		$client->key	= $prevClient->key ? $prevClient->key : md5(uniqid('', true));

		// Upload Profile Photo
		$photo = GTHelper::getInput('photo', '', 'raw');
		$photo = GTHelperAPI::uploadPhotos($photo, GT_SAFETRAVEL_PROFILE.DS.$client->key);
		if($photo) {
			$photo = reset($photo);
			$photo = explode(DS, $photo);
			$photo = end($photo);
			$client->photo = $photo;
		}

		$client	= JArrayHelper::fromObject($client);
		$client	= array_filter($client);
		
		// Save User
		$client_id = GTHelperDB::insertItem('sys_client', $client, 'id', 'key');

		// Save User Credential
		GTHelperDB::delete('sys_client_credential', 'hash = "'.$hash.'" AND credential_id = "'.$hash_type.'"');
		$clientCred->client_id	= $client_id;
		GTHelperDB::insertItem('sys_client_credential', $clientCred, 'client_id');

		// Return User Data
		$this->login($client_id, JText::_('COM_GTSAFETRAVEL_CLIENT_REGISTRATION_SUCCESS2'), true);
	}

	public function updateProfil() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$username			= GTHelper::getInput('username', '', 'raw');
		$name				= GTHelper::getInput('name', '', 'raw');
		$email				= GTHelper::getInput('email', '', 'raw');
		$phone				= GTHelper::getInput('phone', '', 'raw');
		$phone_indonesia	= GTHelper::getInput('phone_indonesia', '', 'raw');
		$photo				= GTHelper::getInput('photo', '', 'raw');
		$birth_place		= GTHelper::getInput('birth_place', '', 'raw');
		$birth_date			= GTHelper::getInput('birth_date', '');
		$address			= GTHelper::getInput('address', '', 'raw');
		$address_indonesia	= GTHelper::getInput('address_indonesia', '', 'raw');
		$identity_number	= GTHelper::getInput('identity_number', '', 'raw');
		$passport_number	= GTHelper::getInput('passport_number', '', 'raw');
		$passport_expired	= GTHelper::getInput('passport_expired', '', 'raw');
		$passport_file 		= GTHelper::getInput('passport_file', '', 'raw');
		$passport_type 		= GTHelper::getInput('passport_type');
		$photo				= GTHelper::getInput('photo', '', 'raw');
		$gender				= GTHelper::getInput('gender', '');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client		= new stdClass();
		$client_key	= $user_id;
		$client_id 	= GTHelperAPI::clientKeyToID($client_key);
		
		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_USER_INVALID'));
		}

		$clientAvailable = $this->clientAvailable($username, $email, false);
		list(, $message, $prevClient, $prevClient2) = $clientAvailable;

		$emailAvailable = $prevClient->id ? $prevClient->id == $client_id : true;
		$unameAvailable = $prevClient2->id ? $prevClient2->id == $client_id : true;
		if(!$emailAvailable || !$unameAvailable) {
			GTHelperAPI::prepareJSON(false, $message);
		}

		$client->id					= $client_id;
		$client->key				= $client_key;
		$client->username			= $username;
		$client->name				= $name;
		$client->email				= $email;
		$client->phone				= $phone;
		$client->phone_indonesia	= $phone_indonesia;
		$client->address			= $address;
		$client->address_indonesia	= $address_indonesia;
		$client->birth_place		= $birth_place;
		$client->birth_date			= GTHelperDate::format($birth_date, 'Y-m-d');
		$client->gender				= $gender;
		$client->identity_number	= $identity_number;
		$client->passport_number	= $passport_number;
		$client->passport_expired	= GTHelperDate::format($passport_expired, 'Y-m-d');
		$client->passport_type		= $passport_type;

		// Upload Passport & Profile Photo
		$photo			= GTHelperAPI::uploadPhotos($photo, GT_SAFETRAVEL_PROFILE.DS.$client->key);
		$passport_file	= GTHelperAPI::uploadPhotos($passport_file, GT_SAFETRAVEL_PASSPORT.DS.$client->key);

		if($photo) {
			$photo = reset($photo);
			$photo = explode(DS, $photo);
			$photo = end($photo);
			$client->photo = $photo;
		}

		if($passport_file) {
			$passport_file = reset($passport_file);
			$passport_file = explode(DS, $passport_file);
			$passport_file = end($passport_file);
			$client->passport_file = $passport_file;
		}

		$client_id	= GTHelperDB::insertItem('sys_client', $client, 'id', 'key');
		
		GTHelper::loadCurl(GTHelperAPI::chatUrl().'/auth/signup', array(
			'access_token'	=> $access_token,
			'name'			=> $client->name,
			'username'		=> $client->username,
			'email'			=> $client->email,
			'phone'			=> $client->phone,
			'photo'			=> $client->photo
		), true);

		$client		= GTHelperAPI::getClient($client_id);
		GTHelperAPI::prepareJSON($client, JText::_('COM_GTSAFETRAVEL_CLIENT_PROFILE_SAVED'));
	}

	public function updatePassword() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$password_old	= GTHelper::getInput('password_old', '', 'raw');
		$password		= GTHelper::getInput('password', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client		= new stdClass();
		$client_key	= $user_id;
		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		
		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_USER_INVALID'));
		}

		$client->id			= $client_id;
		$client->key		= $client_key;
		$client->password	= JUserHelper::hashPassword($password);

		$client_id	= GTHelperDB::insertItem('sys_client', $client, 'id', 'key');
		$client		= GTHelperAPI::getClient($client_id);
		GTHelperAPI::prepareJSON($client, JText::_('COM_GTSAFETRAVEL_CLIENT_PASSWORD_SAVED'));
	}

	public function getMostVisitedCountries() {
		// Process Data
		$countries = GTHelperDB::getItems('ref_adm_country a', 'a.published = 1 AND a.recognized = 1', array(
				'a.id country_id', 
				'a.code country_code',
				'a.name country_name',
				'COUNT(b.id) country_visited_count',
				'a.recognized country_recognized'
			), '', array(array('sys_client_country b', 'a.id = b.country_id', 'LEFT')), array('COUNT(b.id) desc', 'a.name'), 'a.id'
		);

		foreach ($countries as &$country) {
			$country_code = strtolower($country->country_code);
			$country->country_name				= $country->country_recognized ? $country->country_name : JText::sprintf('COM_GTSAFETRAVEL_UNRECOGNIZED_STATE', $country->country_name);
			$country->country_flag				= GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.$country_code.'.png');
		}

		GTHelperAPI::prepareJSON($countries);
	}

	public function getCountries() {
		// Process Data
		$countries = GTHelperDB::getItems('ref_adm_country a', 'a.published = 1 AND a.recognized = 1', array(
				'a.id country_id', 
				'a.code country_code', 
				'a.name country_name', 
				'a.recognized country_recognized', 
				'b.id country_indicator', 
				'b.description country_indicator_name', 
			), '', array(array('ref_indicator b', 'IFNULL(a.indicator_id, 5) = b.id')), 'a.name'
		);

		foreach ($countries as &$country) {
			$country_code = strtolower($country->country_code);

			$country->country_name				= $country->country_recognized ? $country->country_name : JText::sprintf('COM_GTSAFETRAVEL_UNRECOGNIZED_STATE', $country->country_name);
			$country->country_indicator			= intval($country->country_indicator);
			$country->country_recognized		= $country->country_recognized == 1;
			$country->country_flag				= GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.$country_code.'.png');
			$country->country_indicator_image	= GTHelper::verifyFile(GT_MEDIA_URI.'/indicator/'.$country->country_indicator.'.png');
		}

		GTHelperAPI::prepareJSON($countries);
	}

	public function getCountry() {
		// Get Params
		$country_id		= GTHelper::getInput('country_id', '', 'int');
		$country_code	= GTHelper::getInput('country_code');

		// Process Data
		$country	= GTHelperDB::getItem('ref_adm_country', 
			$country_id ? 'id = "'.$country_id.'"' : 'code = "'.$country_code.'"'
		);

		$country_id = intval($country->id);
		$capital	= GTHelperDB::getItem('ref_adm_city', 'id = '.intval($country->capital_city_id), 'name, geoname_id');
		$vice		= GTHelperDB::getItem('ref_adm_city', 'id = '.intval($country->vice_city_id), 'name, geoname_id');
		
		$indicator = $country->indicator_id;
		$indicator = GTHelperDB::getItem('ref_indicator', 'id = '.($indicator ? $indicator : 5));

		$city_geoname_id	= $capital->geoname_id ? $capital->geoname_id : $vice->geoname_id;
		$fcs				= GTHelperGeo::getWeather($city_geoname_id);
		$fcs				= $fcs->forecasts;
		
		$forecasts = array();
		foreach ($fcs as $fc) {
			$date = JHtml::date($fc->weather_datetime, 'Ymd');
			$forecasts[$date] = $fc;
		}

		$forecasts	= array_values($forecasts);
		$forecasts  = array_slice($forecasts, 0, 5);
		$weather	= reset($forecasts);

		$news = GTHelperDB::getItems('web_news', array('FIND_IN_SET('.$country_id.', country_ids)', 'published = 1', 'type = "news"'), 
			'id news_id, title news_title, date news_date'
		);
		
		$embassies = GTHelperDB::getItems('ref_embassy a', array('FIND_IN_SET('.$country_id.', country_ids)', 'a.published = 1'), 
			'a.id embassy_id, a.name embassy_nama, b.name embassy_nama_kota, a.telp embassy_telp, a.fax embassy_fax, a.email embassy_email, a.hotline embassy_hotline, a.photo embassy_image_path, a.latitude embassy_latitude, a.longitude embassy_longitude', 
			'100', 'ref_adm_city b, a.city_id = b.id'
		);
		$infos = GTHelperDB::getItems('ref_info a', 'a.list = 0', 'a.alias, b.info', '100',
			'web_country_info b, a.id = b.info_id AND b.published = 1 AND b.country_id = '.$country_id.', left', 'a.id'
		);
		$country_code = strtolower($country->code);

		// Set Output
		$item								= new stdClass();
		$item->country_id					= $country_id;
		$item->country_code					= $country->code;
		$item->country_name					= $country->recognized ? $country->name : JText::sprintf('COM_GTSAFETRAVEL_UNRECOGNIZED_STATE', $country->name);
		$item->country_recognized			= $country->recognized == 1;
		$item->country_alias				= $country->name_en;
		$item->country_indicator			= $indicator->id;
		$item->country_indicator_image		= GTHelper::verifyFile(GT_MEDIA_URI.'/indicator/'.$indicator->id.'.png');
		$item->country_indicator_name		= $indicator->description;
		$item->country_indicator_color_bg	= $indicator->bg_color;
		$item->country_indicator_color_tx	= $indicator->text_color;
		$item->country_city					= $capital->name ? $capital->name : $vice->name;
		$item->country_latitude				= floatval($country->latitude);
		$item->country_longitude			= floatval($country->longitude);
		$item->country_flag					= GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.$country_code.'.png');
		$item->country_landmark				= GTHelper::verifyFile(GT_MEDIA_URI.'/landmark/'.$country_code.'.jpg');
		$item->country_weather				= $weather;
		$item->country_forecasts			= $forecasts;
		$item->country_himbauan_lists		= $news;
		$item->country_info_perwakilan		= $embassies;

		//echo "<pre>"; print_r($infos); echo "</pre>"; die;
		foreach ($infos as $alias => $info) {
			$info_name = 'country_info_'.$alias;
			$item->$info_name = $info;
		}

		$country = array($item);
		
		GTHelperAPI::prepareJSON($country, null, null, $country_id);
	}

	public function getCountryDetails() {
		$country_code = $this->input->get('country');

		$country = GTHelperDB::getItem('ref_adm_country', 'code = "'.$country_code.'"');

		$country_id = intval($country->id);
		$capital	=  GTHelperDB::getItem('ref_adm_city', 'id = '.intval($country->capital_city_id), 'name, geoname_id');
		$vice		=  GTHelperDB::getItem('ref_adm_city', 'id = '.intval($country->vice_city_id), 'name, geoname_id');

		$city_geoname_id = $capital->geoname_id ? $capital->geoname_id : $vice->geoname_id;

		$embassies = GTHelperDB::getItems(
		    'ref_embassy a',
		    array('FIND_IN_SET('.$country_id.', country_ids)', 'a.published = 1'),
		    'a.id, a.name, b.name city, a.telp, a.fax, a.email, a.hotline, a.photo, a.latitude, a.longitude',
		    '100',
		    'ref_adm_city b, a.city_id = b.id'
		);

		$country_code = strtolower($country->code);

		// Set Output
		$item = new stdClass();
		$item->id           = intval($country_id);
		$item->code         = $country_code;
		$item->name         = $country->name;
		$item->alias        = $country->name_local? $country->name_local : $country->name_en;
		$item->capital_city = $capital->name ? $capital->name : $vice->name;
		$item->area         = floatval($country->area);
		$item->latitude     = floatval($country->latitude);
		$item->longitude    = floatval($country->longitude);
		$item->population   = $country->population;
		$item->currency     = $country->currency;
		$item->flag         = GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.$country_code.'.png');
		$item->landmark     = GTHelper::verifyFile(GT_MEDIA_URI.'/landmark/'.$country_code.'.jpg');
		$item->embassies    = $embassies;
		$item->badges       = GTHelperAPI::getCountryBadges($country_id);

		$country = array($item);

		GTHelperAPI::prepareJSON($country);
	}

	public function getInfographicCountries() {
		// Process Data
		$countries = GTHelperDB::getItems('ref_adm_country', 'published = 1', 'code, name', '');

		foreach ($countries as $country_code => &$country_name) {
			$infographic_img = GTHelper::verifyFile(GT_SAFETRAVEL_INFOGRAPHIC_COUNTRY_URI.'thumb/'.strtolower($country_code).'.jpg');
			if($infographic_img == '-') {
				unset($countries[$country_code]);
				continue;
			}

			$infographic = new stdClass();
			$infographic->infographic_id	= $country_code;
			$infographic->infographic_name	= $country_name;
			$infographic->infographic_image	= $infographic_img;

			$country_name = $infographic;
		}

		$infographics = array_values($countries);

		GTHelperAPI::prepareJSON($infographics);
	}

	public function getInfographicCountry() {
		// Get Params
		$country_code = GTHelper::getInput('country_code');

		// Process Data
		$country = GTHelperDB::getItem('ref_adm_country', array(
			'published' => 1,
			'code' => strtoupper($country_code)
		), 'code, name, id');

		$infographic_img = GTHelper::verifyFile(GT_SAFETRAVEL_INFOGRAPHIC_COUNTRY_URI.strtolower($country->code).'.jpg');
		if(!$infographic_img) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_INFOGRAPHIC_INVALID'));
		}

		$infographic = new stdClass();
		$infographic->infographic_id	= $country->code;
		$infographic->infographic_name	= $country->name;
		$infographic->infographic_image	= $infographic_img;

		$infographics = array($infographic);
		

		GTHelperAPI::prepareJSON($infographics, null, null, $country->id);
	}

	public function findCities() {
		// Get Params
		$country_id		= GTHelper::getInput('country_id', '', 'int');
		$province_id	= GTHelper::getInput('province_id', '', 'int');
		$keyword		= GTHelper::getInput('keyword', '', 'raw');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');

		// Process Data
		$offset	= ($page - 1) * $limit;
		$wheres = array('a.published = 1',  'name LIKE "%'.$keyword.'%"', ($province_id ? 'a.province_id = '.$province_id : 'a.country_id = '.$country_id));
		$cities = GTHelperDB::getItems('ref_adm_city a', $wheres, 
			'a.id city_id, a.name city_name, a.address city_address', $page > 0 ? $limit.','.$offset : ''
		);

		foreach ($cities as &$city) {
			$address = str_replace($city->city_name.', ', '', $city->city_address);
			$address = explode(', ', $address);
			$address = count($address) > 1 ? reset($address) : '';
			$address = preg_replace("/[^a-zA-Z ]+/", "", $address);
			$address = trim($address);

			$city->city_name .= $address ? ', '.$address : '';
		}

		$count = GTHelperDB::count('ref_adm_city a', $wheres);
		$pages = ceil($count/$limit);

		GTHelperAPI::prepareJSON($cities, null, null, null, $pages, $count);
	}

	public function getClassifications() {
		// Get Params
		$type = GTHelper::getInput('type', 'temporary');

		// Process Data
		$items = GTHelperDB::getItems('ref_classification a', array('a.published = 1', $type == 'temporary' ? 'a.temporary = 1' : 'a.permanent = 1'), array(
				'a.id classification_id', 
				'a.name classification_name',
				'a.description classification_description'
			), '100', '', 'a.id'
		);

		GTHelperAPI::prepareJSON($items);
	}

	public function getTravel($id = '', $called = false) {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');

		// Process Data
		if(!$called) {
			$this->checkSession(true);
		}
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$travel = GTHelperDB::getItem('mob_travel', array('id = '.$id, 'client_id = '.$client_id), 
			'id, client_id, type, description, classification_id'
		);

		$classification = GTHelperDB::getItem('ref_classification', array('id = '.intval($travel->classification_id)), 'name');

		$client = GTHelperAPI::getClient($travel->client_id);

		$travel->id					= intval($travel->id);
		$travel->classification		= $classification;
		$travel->passport_number	= $client->passport_number;
		$travel->passport_expired	= $client->passport_expired;
		$travel->passport_file		= $client->passport_file;
		

		if($called) {
			return $travel;
		} else {
			if($travel->id) {
				GTHelperAPI::prepareJSON($travel);
			} else {
				GTHelperAPI::prepareJSON(false);
			}
		}
	}

	public function getTravelDetail($id = '') {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');

		// Process Data
		$travel = $this->getTravel($id, true);

		if(!$travel->id) {
			GTHelperAPI::prepareJSON(false);
		}

		$travel->destinations	= $this->getTravelDestinations($id, true);
		$travel->members		= $this->getTravelMembers($id, true);
		
		GTHelperAPI::prepareJSON($travel);
	}

	public function getTravels() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');

		// Process Data
		//$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');
		
		$offset		= ($page-1) * $limit;
		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$travels	= GTHelperDB::getItems('mob_travel a', array('client_id = '.$client_id, 'a.published = 1', 'b.published = 1'), 
			array(
				'a.id', 'a.client_id', 'a.classification_id', 'e.name classification', 'a.type', 'a.description', 'GROUP_CONCAT(CONCAT_WS("|", b.start_date, b.end_date, c.name, d.name) ORDER BY b.start_date) locations'
			), $limit.','.$offset, 
			array('mob_travel_destination b, a.id = b.travel_id', 'ref_adm_city c, b.city_id = c.id', 'ref_adm_country d, c.country_id = d.id', 'ref_classification e, a.classification_id = e.id'), 
			'a.id DESC', 'a.id'
		);

		$count = GTHelperDB::count('mob_travel a', array('client_id = '.$client_id, 'a.published = 1', 'b.published = 1'),
			array('mob_travel_destination b, a.id = b.travel_id', 'ref_adm_city c, b.city_id = c.id', 'ref_adm_country d, c.country_id = d.id', 'ref_classification e, a.classification_id = e.id')
		);
		$pages = ceil($count/$limit);

		foreach ($travels as &$travel) {
			$locations = explode(',', $travel->locations);
			foreach ($locations as &$location) {
				list($sdate, $edate, $city, $country) = explode('|', $location);
				$$location				= new stdClass();
				$location->start_date	= $sdate;
				$location->end_date		= $edate;
				$location->city			= $city;
				$location->country		= $country;
			}
			$travel->locations = $locations;
		}

		if($travels) {
			GTHelperAPI::prepareJSON($travels, null, null, null, $pages);
		} else {
			GTHelperAPI::prepareJSON(false, null, null, null, $pages);
		}
	}

	public function setTravel() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$id					= GTHelper::getInput('id', '', 'int');
		$classification_id	= GTHelper::getInput('classification_id', '', 'int');
		$start_date			= GTHelper::getInput('start_date');
		$end_date			= GTHelper::getInput('end_date');
		$description		= GTHelper::getInput('description', '', 'raw');
		$passport_number	= GTHelper::getInput('passport_number');
		$passport_expired	= GTHelper::getInput('passport_expired');
		$passport_file 		= GTHelper::getInput('passport_file', '', 'raw');
		$passport_type		= GTHelper::getInput('passport_type', 'regular');
		$phone_indonesia 	= GTHelper::getInput('phone_indonesia', '', 'raw');
		$address_indonesia 	= GTHelper::getInput('address_indonesia', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$travel			= $this->getTravel($id, true);
		$pass_expired	= GTHelperDate::format($passport_expired, 'Y-m-d');
		$published		= @$travel->published ? $travel->published : 1;

		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}

		$travel->id					= $id;
		$travel->client_id			= $client_id;
		$travel->classification_id	= $classification_id;
		$travel->description 		= $description;
		$travel->type 				= 'temporary';
		$travel->published			= $published;

		// Save Travel Data
		$travel_id = GTHelperDB::insertItem('mob_travel', $travel, '');

		// Update Client Data
		$client		= new stdClass();
		$client->id	= $client_id;

		if($phone_indonesia) {
			$client->phone_indonesia = $phone_indonesia;
		}
		if($address_indonesia) {
			$client->address_indonesia = $address_indonesia;
		}
		if($passport_number) {
			$client->passport_number = $passport_number;
		}
		if($passport_expired) {
			$client->passport_expired = GTHelperDate::format($passport_expired, 'Y-m-d');
		}
		if($passport_type) {
			$client->passport_type = $passport_type;
		}

		// Upload Passport
		$passport_file	= GTHelperAPI::uploadPhotos($passport_file, GT_SAFETRAVEL_PASSPORT.DS.$user_id);
		if($passport_file) {
			$passport_file = reset($passport_file);
			$passport_file = explode(DS, $passport_file);
			$passport_file = end($passport_file);
			$client->passport_file = $passport_file;
		}

		GTHelperDB::insertItem('sys_client', $client);

		$this->getTravelDetail($travel_id);
	}

	public function setTravelDestination() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$travel_id			= GTHelper::getInput('travel_id', '', 'int');
		$id					= GTHelper::getInput('id', '', 'int');
		$start_date			= GTHelper::getInput('start_date');
		$end_date			= GTHelper::getInput('end_date');
		$country_id			= GTHelper::getInput('country_id', '', 'int');
		$city_id			= GTHelper::getInput('city_id', '', 'int');
		$address			= GTHelper::getInput('address', '', 'raw');
		$phone				= GTHelper::getInput('phone', '', 'raw');
		$description		= GTHelper::getInput('description', '', 'raw');
		$visa_number		= GTHelper::getInput('visa_number', '', 'raw');
		$visa_issued		= GTHelper::getInput('visa_issued');
		$visa_expired		= GTHelper::getInput('visa_expired');
		$visa_photo			= GTHelper::getInput('visa_file', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');
		
		$client_id		= GTHelperAPI::clientKeyToID($user_id);

		$travel			= $this->getTravel($travel_id, true);
		$item			= $this->getTravelDestination($id, true);
		$published		= @$item->published ? $item->published : 1;

		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}

		$item->id			= $id;
		$item->travel_id	= $travel->id;
		$item->start_date	= GTHelperDate::format($start_date, 'Y-m-d');
		$item->end_date		= GTHelperDate::format($end_date, 'Y-m-d');
		$item->country_id	= $country_id;
		$item->city_id		= $city_id;
		$item->address		= $address;
		$item->phone		= $phone;
		$item->description	= $description;
		$item->published	= $published;
		
		// Upload Visa
		if($visa_number) {
			$item->visa_number = $visa_number;
		}

		if($visa_expired) {
			$item->visa_expired	= GTHelperDate::format($visa_expired, 'Y-m-d');
		}

		// Upload Visa
		$visa_id = null;
		if($visa_number && $visa_expired) {
			$visa_number	= preg_replace("/[^A-Z0-9]+/", "", strtoupper($visa_number));
			$visa			= GTHelperDB::getItem('sys_client_visa', array('country_id = '.$country_id, 'number = "'.$visa_number.'"', 'client_id = '.$client_id));
			
			$visa->number			= $visa_number;
			$visa->client_id		= $client_id;
			$visa->country_id		= $country_id;
			$visa->issued_date		= GTHelperDate::format($visa_issued, 'Y-m-d');
			$visa->expired_date		= GTHelperDate::format($visa_expired, 'Y-m-d');
			$visa->published 		= 1;

			$visa_photo	= $visa_photo ? GTHelperAPI::uploadPhotos($visa_photo, GT_SAFETRAVEL_VISA.DS.$user_id.DS.$visa_number) : null;
			if($visa_photo) {
				$visa_photo		= reset($visa_photo);
				$visa_photo		= explode(DS, $visa_photo);
				$visa_photo		= end($visa_photo);
				$visa->photo	= $visa_photo;
			}

			$visa_id = GTHelperDB::insertItem('sys_client_visa', $visa);
		}

		$item->visa_id = $visa_id;

		// Save Travel Destination Data
		$id = GTHelperDB::insertItem('mob_travel_destination', $item, '');

		$this->getTravelDestination($id);
	}

	public function getTravelDestination($id = '', $called = false) {
		// Get Params
		$travel_id		= GTHelper::getInput('travel_id', '', 'int');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');
		$access_token	= GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		if(!$called) {
			$this->checkSession(true);
		}
		list(,$user_id) = explode('|', $access_token.'|');

		$travel		= $this->getTravel($travel_id, true);
		$item		= GTHelperDB::getItem('mob_travel_destination', array('id = '.$id, 'travel_id = '.$travel->id), 
			'id, travel_id, start_date, end_date, address, phone, description, visa_number, visa_expired, visa_file, visa_id, city_id'
		);

		$city = GTHelperDB::getItem('ref_adm_city', array('id = '.intval($item->city_id)), 
			'id, country_id, name'
		);

		$country = GTHelperDB::getItem('ref_adm_country', array('id = '.intval($city->country_id)), 
			'id, name, code'
		);

		$item->visa_file	= GTHelperAPI::loadFiles(GT_SAFETRAVEL_VISA.DS.$travel_id.DS.$city->country_id, true);
		$item->start_date	= GTHelperDate::format($item->start_date, 'd-m-Y');
		$item->end_date		= GTHelperDate::format($item->end_date, 'd-m-Y');
		$item->visa_issued	= '';
		$item->visa_expired	= GTHelperDate::format($item->visa_expired, 'd-m-Y');

		if($item->visa_id) {
			$visa = GTHelperDB::getItem('sys_client_visa', array('id = '.intval($item->visa_id)));

			$item->visa_number	= $visa->number;
			$item->visa_issued	= GTHelperDate::format($visa->issued_date, 'd-m-Y');
			$item->visa_expired	= GTHelperDate::format($visa->expired_date, 'd-m-Y');
			$item->visa_file	= GTHelper::verifyFile(GT_SAFETRAVEL_VISA_URI.$user_id.'/'.$visa->number.'/'.$visa->photo);
		}

		unset($item->visa_id);

		$item->city_id		= $city->id;
		$item->city_name	= $city->name;
		$item->country_id	= $country->id;
		$item->country_name	= $country->name;
		$item->country_flag	= GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.strtolower($country->code).'.png');
		if($called) {
			return $item;
		} else {
			if($item->id) {
				GTHelperAPI::prepareJSON($item);
			} else {
				GTHelperAPI::prepareJSON(false);
			}
		}
	}

	public function getTravelDestinations($travel_id = '', $called = false) {
		// Get Params
		$travel_id		= $travel_id ? $travel_id : GTHelper::getInput('travel_id', '', 'raw');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');
		$access_token	= GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');
		
		$offset		= ($page-1) * $limit;
		$travel		= $this->getTravel($travel_id, true);
		$items		= GTHelperDB::getItems('mob_travel_destination a', array('a.travel_id = '.$travel->id, 'a.published = 1'), 
			'a.id, a.travel_id, a.start_date, a.end_date, a.address, a.phone, a.description, a.visa_number, a.visa_expired, a.visa_file, a.visa_id,
			b.country_id, c.name country_name, c.code country_flag, a.city_id, b.name city_name',
			$limit.','.$offset, array('ref_adm_city b, a.city_id = b.id', 'ref_adm_country c, b.country_id = c.id')
		);

		$count = GTHelperDB::count('mob_travel_destination a', array('a.travel_id = '.$travel->id, 'a.published = 1'));
		$pages = ceil($count/$limit);

		foreach ($items as $item) {
			$item->visa_file 	= GTHelperAPI::loadFiles(GT_SAFETRAVEL_VISA.DS.$travel_id.DS.$item->country_id, true);
			$item->start_date	= GTHelperDate::format($item->start_date, 'd-m-Y');
			$item->end_date		= GTHelperDate::format($item->end_date, 'd-m-Y');
			$item->visa_issued	= '';
			$item->visa_expired	= GTHelperDate::format($item->visa_expired, 'd-m-Y');

			$item->country_flag	= GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.strtolower($item->country_flag).'.png');

			if($item->visa_id) {
				$visa = GTHelperDB::getItem('sys_client_visa', array('id = '.intval($item->visa_id)));

				$item->visa_number	= $visa->number;
				$item->visa_issued	= GTHelperDate::format($visa->issued_date, 'd-m-Y');
				$item->visa_expired	= GTHelperDate::format($visa->expired_date, 'd-m-Y');
				$item->visa_file	= GTHelper::verifyFile(GT_SAFETRAVEL_VISA_URI.$user_id.'/'.$visa->number.'/'.$visa->photo);
			}

			unset($item->visa_id);
		}

		if($called) {
			return $items;
		} else {
			if($items) {
				GTHelperAPI::prepareJSON($items, null, null, null, $pages);
			} else {
				GTHelperAPI::prepareJSON(false, null, null, null, $pages);
			}
		}
		
	}

	public function getTravelMember($id = '', $called = false) {
		// Get Params
		$travel_id		= GTHelper::getInput('travel_id', '', 'raw');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');
		$access_token	= GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		if(!$called) {
			$this->checkSession(true);
		}
		list(,$user_id) = explode('|', $access_token.'|');

		$travel		= $this->getTravel($travel_id, true);
		$item		= GTHelperDB::getItem('mob_travel_member', array('id = '.$id, 'travel_id = '.$travel->id), 
			'id, travel_id, classification_id, name, gender, birth_date, passport_number, passport_expired'
		);

		$item->passport_expired	= GTHelperDate::format($item->passport_expired, 'd-m-Y');

		if($called) {
			return $travel;
		} else {
			if($item->id) {
				GTHelperAPI::prepareJSON($item);
			} else {
				GTHelperAPI::prepareJSON(false);
			}
		}
	}

	public function getTravelMembers($travel_id = '', $called = false) {
		// Get Params
		$travel_id		= $travel_id ? $travel_id : GTHelper::getInput('travel_id', '', 'raw');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');
		$access_token	= GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');
		
		$offset		= ($page-1) * $limit;
		$travel		= $this->getTravel($travel_id, true);
		$items		= GTHelperDB::getItems('mob_travel_member', array('travel_id = '.$travel->id, 'published = 1'), 
			'id, travel_id, classification_id, name, gender, birth_date, passport_number, passport_expired',
			$limit.','.$offset
		);

		$count = GTHelperDB::count('mob_travel_member a', array('a.travel_id = '.$travel->id, 'a.published = 1'));
		$pages = ceil($count/$limit);

		foreach ($items as &$item) {
			$item->passport_expired	= GTHelperDate::format($item->passport_expired, 'd-m-Y');
		}

		if($called) {
			return $items;
		} else {
			if($items) {
				GTHelperAPI::prepareJSON($items, null, null, null, $pages);
			} else {
				GTHelperAPI::prepareJSON(false, null, null, null, $pages);
			}
		}
	}

	public function setTravelMember() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$id					= GTHelper::getInput('id', '', 'int');
		$travel_id			= GTHelper::getInput('travel_id', '', 'int');
		$classification_id	= GTHelper::getInput('classification_id', '', 'int');
		$name				= GTHelper::getInput('name', '', 'raw');
		$gender				= GTHelper::getInput('gender', '', 'int');
		$birth_date			= GTHelper::getInput('birth_date');
		$passport_number	= GTHelper::getInput('passport_number', '', 'raw');
		$passport_expired	= GTHelper::getInput('passport_expired');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$travel			= $this->getTravel($travel_id, true);
		$item			= $this->getTravelMember($id, true);
		$published		= @$item->published ? $item->published : 1;

		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}

		$item->id					= $id;
		$item->travel_id			= $travel->id;
		$item->classification_id	= $classification_id;
		$item->name					= $name;
		$item->gender				= $gender;
		$item->birth_date			= GTHelperDate::format($birth_date, 'Y-m-d');
		$item->passport_number		= $passport_number;
		$item->passport_expired		= GTHelperDate::format($passport_expired, 'Y-m-d');
		$item->published			= $published;

		// Save Travel Member Data
		$id = GTHelperDB::insertItem('mob_travel_member', $item, '');

		$this->getTravelMember($id);
	}

	public function getCheckin($id = '', $called = false) {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');

		// Process Data
		if(!$called) {
			$this->checkSession(true);
		}
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$checkin = GTHelperDB::getItem('mob_gallery', array('id = '.$id, 'type = "checkin"', 'client_id = '.$client_id), 
			'id, key, client_id, country_id, address, description, latitude, longitude, created'
		);

		// Fetch Media
		$media_folder = GT_SAFETRAVEL_CHECKIN.DS.$checkin->key;
		$checkin->media = GTHelperAPI::loadFiles($media_folder, false);
		$checkin->media_num = 1;

		if($called) {
			return $checkin;
		} else {
			if($checkin->id) {
				GTHelperAPI::prepareJSON($checkin);
			} else {
				GTHelperAPI::prepareJSON(false);
			}
		}
	}

	public function getCheckins() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$offset		= ($page-1) * $limit;
		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$checkins	= GTHelperDB::getItems('mob_gallery', array('client_id = '.$client_id, 'published = 1', 'type = "checkin"'), 
			'id, key, client_id, country_id, address, description, latitude, longitude, created',
			$limit.','.$offset, '', 'id desc'
		);

		$count = GTHelperDB::count('mob_gallery a', array('a.client_id = '.$client_id, 'a.published = 1'));
		$pages = ceil($count/$limit);

		foreach ($checkins as &$checkin) {
			$created_unix = strtotime($checkin->created);
			$checkin->created_year	= JHtml::date($checkin->created, 'Y');
			$checkin->created_date	= JHtml::date($checkin->created, 'j M');
			$checkin->created_time	= JHtml::date($checkin->created, 'H:i');
			$checkin->created_diff	= GTHelperDate::diff($checkin->created);
			$checkin->created		= $created_unix;

			$checkin->media = '-';
			$checkin->media_num = 1;

			// Fetch Media
			$media_folder = GT_SAFETRAVEL_CHECKIN.DS.$checkin->key;
			$checkin->media = GTHelperAPI::loadFiles($media_folder, true);
		}

		if($checkins) {
			GTHelperAPI::prepareJSON($checkins, null, null, null, $pages);
		} else {
			GTHelperAPI::prepareJSON(false, null, null, null, $pages);
		}
	}

	public function setCheckin() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$id					= GTHelper::getInput('id', 0, 'int');
		$address			= GTHelper::getInput('address', '', 'raw');
		$description		= GTHelper::getInput('description', '', 'raw');
		$latitude			= GTHelper::getInput('latitude', '', 'float');
		$longitude			= GTHelper::getInput('longitude', '', 'float');
		$media				= GTHelper::getInput('media', array(), 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client			= new stdClass();
		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$checkin		= $this->getCheckin($id, true);
		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}
		
		$media_num	= intval(@$checkin->media_num);
		$published	= @$checkin->published ? $checkin->published : 1;
		$key		= @$checkin->key ? $checkin->key : md5(uniqid('', true));

		$latitude	= floatval($latitude);
		$longitude	= floatval($longitude);
		$distance	= '
			(3959 * ACOS(
				COS(RADIANS(a.latitude)) * COS(RADIANS('.$latitude.')) * 
				COS(RADIANS('.$longitude.') - RADIANS(a.longitude)) + 
				SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$latitude.'))
			))
		';

		$city = GTHelperDB::getItems('ref_adm_city a', array('a.published = 1', 'a.latitude IS NOT NULL', 'a.longitude IS NOT NULL'),
			'', '1', '', array(array($distance, 'ASC'))
		);
		$city = reset($city);
		
		$checkin->id			= $id;
		$checkin->key			= $key;
		$checkin->client_id		= $client_id;
		$checkin->country_id	= $city->country_id;
		$checkin->city_id		= $city->id;
		$checkin->address		= $address;
		$checkin->description	= $description;
		$checkin->latitude		= $latitude;
		$checkin->longitude		= $longitude;
		$checkin->published		= $published;
		$checkin->type			= 'checkin';

		// Upload Media
		$media_folder = GT_SAFETRAVEL_CHECKIN.DS.$key;
		$checkin->photos = GTHelperAPI::uploadPhotos($media, $media_folder);
		$checkin->photos = implode(PHP_EOL, $checkin->photos);

		// Save Data
		$checkin->media_num	= $media_num;
		$checkin_id = GTHelperDB::insertItem('mob_gallery', $checkin, '');


		$this->getCheckin($checkin_id);
	}

	public function getEmergency($id = '', $called = false) {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');

		// Process Data
		if(!$called) {
			$this->checkSession(true);
		}
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$emergency	= GTHelperDB::getItem('mob_emergency', array('id = '.$id, 'client_id = '.$client_id), 
			'id, key, client_id, country_id, address, description, latitude, longitude, media_num, created'
		);

		// Fetch Media
		$media_folder = GT_SAFETRAVEL_EMERGENCY.DS.$emergency->key;
		$emergency->media = GTHelperAPI::loadFiles($media_folder, false);
		$emergency->files = GTHelperAPI::loadFiles($media_folder.DS.'file', false);

		if($called) {
			return $emergency;
		} else {
			if($emergency->id) {
				GTHelperAPI::prepareJSON($emergency);
			} else {
				GTHelperAPI::prepareJSON(false);
			}
		}
	}

	public function getEmergencies() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');
		
		$offset		= ($page-1) * $limit;
		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$emergencies	= GTHelperDB::getItems('mob_emergency', array('client_id = '.$client_id, 'published = 1'), 
			'id, key, client_id, country_id, address, description, latitude, longitude, media_num, created',
			$limit.','.$offset, '', 'id desc'
		);

		$count = GTHelperDB::count('mob_emergency a', array('a.client_id = '.$client_id, 'a.published = 1'));
		$pages = ceil($count/$limit);

		foreach ($emergencies as &$emergency) {
			// Fetch Media
			$media_folder = GT_SAFETRAVEL_EMERGENCY.DS.$emergency->key;
			$emergency->media = GTHelperAPI::loadFiles($media_folder, true);
			$emergency->files = GTHelperAPI::loadFiles($media_folder.DS.'file', true);
		}
		if($emergencies) {
			GTHelperAPI::prepareJSON($emergencies, null, null, null, $pages);
		} else {
			GTHelperAPI::prepareJSON(false, null, null, null, $pages);
		}
	}

	public function setEmergency() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$id					= GTHelper::getInput('id', 0, 'int');
		$address			= GTHelper::getInput('address', '', 'raw');
		$description		= GTHelper::getInput('description', '', 'raw');
		$latitude			= GTHelper::getInput('latitude', '', 'float');
		$longitude			= GTHelper::getInput('longitude', '', 'float');
		$type				= GTHelper::getInput('type', '4', 'int');
		$media				= GTHelper::getInput('media', array(), 'raw');
		$files				= $this->input->files->get('files');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client			= new stdClass();
		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$emergency		= $this->getEmergency($id, true);
		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}
		
		$media_num	= intval(@$emergency->media_num);
		$published	= @$emergency->published ? $emergency->published : 1;
		$key		= @$emergency->key ? $emergency->key : md5(uniqid('', true));

		$latitude	= floatval($latitude);
		$longitude	= floatval($longitude);
		$distance	= '
			(3959 * ACOS(
				COS(RADIANS(a.latitude)) * COS(RADIANS('.$latitude.')) * 
				COS(RADIANS('.$longitude.') - RADIANS(a.longitude)) + 
				SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$latitude.'))
			))
		';

		$city = GTHelperDB::getItems('ref_adm_city a', array('a.published = 1', 'a.latitude IS NOT NULL', 'a.longitude IS NOT NULL'),
			'', '1', '', array(array($distance, 'ASC'))
		);
		$city = reset($city);

		$emergency->id			= $id;
		$emergency->key			= $key;
		$emergency->client_id	= $client_id;
		$emergency->country_id	= $city->country_id;
		$emergency->city_id		= $city->id;
		$emergency->address		= $address;
		$emergency->description	= $description;
		$emergency->latitude	= $latitude;
		$emergency->longitude	= $longitude;
		$emergency->type		= $type;
		$emergency->published	= $published;

		// Upload Media
		$media_folder = GT_SAFETRAVEL_EMERGENCY.DS.$key;
		GTHelperAPI::uploadPhotos($media, $media_folder);

		// Save Files
		if($files) {
			$file_folder = $media_folder.DS.'file';
			foreach ($files as $fileK => $file) {
				$file 		= JArrayHelper::toObject($file);
				$fileSrc	= $file->tmp_name;
				$fileDest 	= JFile::makeSafe($file->name);
				$fileDest 	= md5($fileDest).'.'.strtolower(JFile::getExt($file->name));
				$fileDest	= $file_folder.DS.$fileDest;

				if(!$file->error) {
					JFile::upload($fileSrc, $fileDest);
				}
			}
		}

		// Convert Time
		$utc_offset = intval($city->utc_offset);
		$utc_offset = round($utc_offset/60) * 60;
		$gmt_offset = 'GMT'.($utc_offset >= 0 ? '+' : ''); 
		$gmt_offset .= floor($utc_offset / 60);
		$gmt_offset .= $utc_offset % 60 <> 0 ? ':'.abs($utc_offset % 60) : '';

		$date = GTHelperDate::format('now', 'Y-m-d', false, $utc_offset);
		$datetime = GTHelperDate::format('now', 'd/m/Y - H:i', false, $utc_offset).' ('.$gmt_offset.')';

		// Save Data
		$emergency->media_num	= $media_num;
		$emergency_id = GTHelperDB::insertItem('mob_emergency', $emergency, '');

		// Send Email
		if(!$id > 0) {
			$client = GTHelperAPI::getClient($client_id);
			$destination = GTHelperDB::getItems('mob_travel_destination a', array('b.client_id = '.$client_id, '"'.$date.'" BETWEEN a.start_date AND a.end_date'), 'a.city_id, a.address, a.phone', '1', 'mob_travel b, a.travel_id = b.id');
			$destination = reset($destination);
			$distance	= '
				(3959 * ACOS(
					COS(RADIANS(a.latitude)) * COS(RADIANS('.$city->latitude.')) * 
					COS(RADIANS('.$city->longitude.') - RADIANS(a.longitude)) + 
					SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$city->latitude.'))
				))
			';

			$embassy = GTHelperDB::getItems('ref_embassy a', array('a.published = 1', 'a.latitude IS NOT NULL', 'a.longitude IS NOT NULL', 'b.country_id = '.$city->country_id), 'a.name, a.address, a.email', '1', 'ref_embassy_country b, a.id = b.embassy_id', array(array($distance, 'ASC'))
			);
			$embassy = reset($embassy);
			$emailTitle = JText::_('COM_GTSAFETRAVEL_CLIENT_EMERGENCY_TITLE');
			$clientPhones = array($client->phone, @$destination->phone);
			$clientPhones = array_filter($clientPhones);
			$clientPhones = implode(' / ', $clientPhones);
			$emailBody = strtr(JText::_('COM_GTSAFETRAVEL_CLIENT_EMERGENCY_BODY'), array(
				'{EMBASSY_NAME}' => $embassy->name,
				'{CLIENT_NAME}' => $client->name,
				'{PASSPORT_NUM}' => $client->passport_number,
				'{IDENTITY_NUM}' => $client->identity_number,
				'{PASSPORT_PHOTO}' => $client->passport_file,
				'{ADDRESS}' => @$destination->address,
				'{PHONE}' => $clientPhones,
				'{PHONE_INDONESIA}' => $client->phone_indonesia,
				'{DATETIME}' => $datetime,
				'{CITY}' => $city->name,
				'{COUNTRY}' => GTHelperDB::getItem('ref_adm_country', 'id = '.$city->country_id, 'name'),
				'{LOCATION}' => sprintf('https://www.google.com/maps/?q=%s,%s', $latitude, $longitude),
				'{LOCATION_ADDRESS}' => $address,
				'{DESCRIPTION}' => $description,
				'{LINK}' => GTHelper::getURL(array('Itemid' => 322, 'view' => 'ref_item', 'layout' => 'view', 'id' => $emergency_id, 'tmpl' => 'component'))
			));
			
			GTHelperMail::send($emailTitle, $emailBody, array('pwni.bhi@kemlu.go.id', 'hernawanabid@yahoo.co.id', 'itisrama@gmail.com'));
			if(GTHelperAPI::isLive()) {
				$embassyEmail = $embassy->email;
				$embassyEmail = str_replace(" ", "", $embassyEmail);
				$embassyEmail = str_replace("\n", "\n\r", $embassyEmail);
				$embassyEmail = str_replace(array(',', ';', "\n\r"), PHP_EOL, $embassyEmail);
				$embassyEmail = explode(PHP_EOL, $embassyEmail);
				GTHelperMail::send($emailTitle, $emailBody, $embassyEmail);
			}

		}
		
		$this->getEmergency($emergency_id);
	}

	public function getEmbassies() {
		// Get Params
		$country_id	= GTHelper::getInput('country_id', '', 'int');
		$page		= GTHelper::getInput('page', '1', 'int');
		$limit		= GTHelper::getInput('limit', '100', 'int');
		$offset		= ($page - 1) * $limit;

		// Process Data
		$embassies = GTHelperDB::getItems('ref_embassy a', array('a.published = 1', $country_id ? 'FIND_IN_SET('.$country_id.', a.country_ids)' : ''), 
			'a.id embassy_id, a.name embassy_nama, b.name embassy_nama_kota, a.telp embassy_telp, a.fax embassy_fax, a.email embassy_email, a.hotline embassy_hotline, a.photo embassy_image_path, a.latitude embassy_latitude, a.longitude embassy_longitude', 
			$limit.','.$offset, 'ref_adm_city b, a.city_id = b.id', 'a.country_id', 'a.id'
		);

		$count = GTHelperDB::count('ref_embassy a', $country_id ? array('FIND_IN_SET('.$country_id.', a.country_ids)') : '');
		$pages = ceil($count/$limit);

		$result = new stdClass();
		$result->embassies = $embassies;

		GTHelperAPI::prepareJSON($result, null, null, null, $pages);
	}

	public function getEmbassy($json = true) {
		// Get Params
		$embassy_id = GTHelper::getInput('embassy_id', '0', 'int');

		// Process Data
		$embassy = GTHelperDB::getItem('ref_embassy', 'id = '.$embassy_id, 
			'id, country_id, name, city_id, address, telp, fax, email, hotline, photo, latitude, longitude, transport, area, website'
		);

		$city = GTHelperDB::getItem('ref_adm_city', 'id = '.$embassy->city_id, 'id, name, country_id');

		if(!$embassy->id) {
			GTHelperAPI::prepareJSON(false);
		}

		$country = GTHelperDB::getItem('ref_adm_country a', 'a.id = '.intval($city->country_id), 
			'id, name, code'
		);

		$image_path = array($embassy->country_id, $embassy->id);
			$image_path = implode(DS, $image_path);

		$item = new stdClass();
		$item->embassy_id				= $embassy->id;
		$item->embassy_country_id		= $country->id;
		$item->embassy_country_name		= $country->name;
		$item->embassy_country_code		= $country->code;
		$item->embassy_city_id			= intval($city->id);
		$item->embassy_city_name		= $city->name;
		$item->embassy_name				= $embassy->name;
		$item->embassy_telp				= $embassy->telp;
		$item->embassy_fax				= $embassy->fax;
		$item->embassy_email			= $embassy->email;
		$item->embassy_hotline			= $embassy->hotline;
		$item->embassy_rating			= floatval(GTHelperDB::aggregate('mob_rating_embassy', 'value', 'avg', 'embassy_id = '.$embassy->id));
		$item->embassy_address			= $embassy->address;
		$item->embassy_latitude			= (string) $embassy->latitude;
		$item->embassy_longitude		= (string) $embassy->longitude;
		$item->embassy_image_path		= $embassy->id ? GTHelperAPI::loadFiles(GT_SAFETRAVEL_EMBASSY.DS.$image_path, true) : '-';
		$item->embassy_transport_to		= $embassy->transport;
		$item->embassy_transport_from	= $embassy->transport;
		$item->embassy_website			= $embassy->website;
		$item->embassy_area				= $embassy->area;
		$item->embassy_total_rate		= intval(GTHelperDB::count('mob_rating_embassy', 'embassy_id = '.$embassy->id));
		$item->embassy_total_comment	= intval(GTHelperDB::count('mob_comment_embassy', 'embassy_id = '.$embassy->id));

		if($json) {
			GTHelperAPI::prepareJSON($item, null, null, $embassy_id);
		} else {
			return $item;
		}
	}

	public function getSlider() {
		$this->getSliders(true);
	}

	public function getSliders($single = false) {
		// Get Params
		$country_id		= GTHelper::getInput('country_id', '', 'int');
		$country_code	= GTHelper::getInput('country_code');

		// Process Data
		$country = GTHelperDB::getItem('ref_adm_country', 
			$country_id ? 'id = "'.$country_id.'"' : 'code = "'.$country_code.'"', 'id, code, latitude, longitude'
		);

		$model = $this->getModel();
		$sliders = $model->getSliders($country->latitude, $country->longitude, 20, 0);

		foreach ($sliders as $k => $slider) {
			if($slider->country_landmark == '-') {
				unset($sliders[$k]);
			}
		}
		$sliders = array_slice($sliders, 0, $limit);

		GTHelperAPI::prepareJSON($sliders);
	}

	public function getNewsList() {
		// Get Params
		$country_id		= GTHelper::getInput('country_id', '', 'int');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');
		$keyword 		= GTHelper::getInput('keyword', '', '');
		$offset			= ($page - 1) * $limit;
		$orderby		= GTHelper::getInput('orderby', 'date');

		// Process Data
		$wheres = array('a.published = 1', 'LENGTH(a.content) > 20', 'a.type = "news"');
		if($country_id) {
			$wheres[] = 'FIND_IN_SET("'.$country_id.'",'.'a.country_ids)';
		}

		if($keyword) {
			$wheres[] = '(a.title LIKE "%'.$keyword.'%" OR a.content LIKE "%'.$keyword.'%")';
		}
		
		$news = GTHelperDB::getItems('web_news a', $wheres, 
			array('a.id news_id', 'GROUP_CONCAT(DISTINCT CONCAT(b.id,":",b.name)) news_countries', 'a.title news_title', 
			'a.content news_content', 'a.date news_date', 'a.total_read news_total_read'), 
			$limit.','.$offset, array(array('ref_adm_country b', 'FIND_IN_SET(b.id, a.country_ids)')), ($orderby == 'date' ? 'a.created' : 'a.total_read').' desc, a.id desc', 'a.id'
		);

		$count = GTHelperDB::count('web_news a', $wheres);
		$pages = ceil($count/$limit);

		foreach ($news as &$n) {
			$countries = explode(',', $n->news_countries);
			$country_ids = array();
			$country_names = array();
			foreach ($countries as $country) {
				list($country_id, $country_name) = explode(':', $country);
				$country_ids[] = $country_id;
				$country_names[] = $country_name;
			}
			$country_id = array_rand($country_ids, 1);
			unset($n->news_countries);

			$content 				= trim(strip_tags($n->news_content));
			$n->news_content		= $content ? (strlen($content) > 260 ? mb_substr($content, 0, strpos($content, ' ', 260)) : $content) : '';
			$n->news_rating 		= floatval(GTHelperDB::aggregate('mob_rating_news', 'value', 'avg', 'news_id = '.$n->news_id));
			$n->news_date 			= JHtml::date($n->news_date, 'j F Y');
			$n->news_total_read		= intval($n->news_total_read);
			$n->news_total_rate		= intval(GTHelperDB::count('mob_rating_news', 'news_id = '.$n->news_id));
			$n->news_total_comment	= intval(GTHelperDB::count('mob_comment_news', 'news_id = '.$n->news_id));
			$n->news_image_path		= GTHelperAPI::loadFiles(GT_SAFETRAVEL_NEWS.DS.$n->news_id, true);
			$n->news_country_id 	= $country_ids[$country_id];
			$n->news_country_ids 	= $country_ids;
			$n->news_country_name 	= $country_names[$country_id];
			$n->news_country_names 	= $country_names;
		}

		GTHelperAPI::prepareJSON($news, null, null, null, $pages);
	}

	public function setNewsRating() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$news_id		= GTHelper::getInput('news_id', '', 'int');
		$value			= GTHelper::getInput('value', '', 'double');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$rating		= GTHelperDB::getItem('mob_rating_news', 'news_id = '.$news_id.' AND client_id = '.$client_id);

		$rating->news_id	= $news_id;
		$rating->client_id	= $client_id;
		$rating->value		= $value;
		$rating->published 	= 1;

		GTHelperDB::insertItem('mob_rating_news', $rating);

		GTHelperAPI::prepareJSON(true, JText::_('COM_GTSAFETRAVEL_CLIENT_RATING_SUCCESS'), null, $news_id);
	}

	public function getNews() {
		// Get Params
		$news_id = GTHelper::getInput('news_id', '', 'int');

		// Process Data
		$news = GTHelperDB::getItem('web_news', 'id = '.$news_id, 
			'id, country_ids, title, date, content, total_read'
		);

		$countries = GTHelperDB::getItems('ref_adm_country a', 'a.id IN ('.$news->country_ids.')', 
			'id, name, code'
		);
		$country_ids = array();
		$country_names = array();
		foreach ($countries as $country) {
			$country_ids[] = $country->id;
			$country_names[] = $country->name;
		}

		$country = $countries[array_rand($countries, 1)];

		$item = new stdClass();
		$item->news_id				= $news->id;
		$item->news_title			= $news->title;
		$item->news_rating 			= floatval(GTHelperDB::aggregate('mob_rating_news', 'value', 'avg', 'news_id = '.$news->id));
		$item->news_country_id		= $country->id;
		$item->news_country_ids		= $country_ids;
		$item->news_country_name	= $country->name;
		$item->news_country_names	= $country_names;
		$item->news_date			= $news->id ? JHtml::date($news->date, 'j F Y') : '';
		$item->news_content			= $news->content;
		$item->news_total_read		= intval($news->total_read);
		$item->news_total_rate		= intval(GTHelperDB::count('mob_rating_news', 'news_id = '.$news->id));
		$item->news_total_comment	= intval(GTHelperDB::count('mob_comment_news', 'news_id = '.$news->id));
		$item->news_image_path 		= $news->id ? GTHelperAPI::loadFiles(GT_SAFETRAVEL_NEWS.DS.$news->id, true) : '-';
		
		$news->total_read = intval($news->total_read)+1;
		GTHelperDB::insertItem('web_news', $news);

		GTHelperAPI::prepareJSON($item, null, null, $news_id);
	}

	public function getNewsComments() {
		// Get Params
		$news_id	= GTHelper::getInput('news_id', '', 'int');
		$page		= GTHelper::getInput('page', '1', 'int');
		$limit		= GTHelper::getInput('limit', '100', 'int');
		
		// Process Data
		$offset		= ($page - 1) * $limit;
		$comments	= GTHelperDB::getItems('mob_comment_news a', 'a.published = 1 AND a.news_id = '.$news_id, 
			'a.id comment_id, b.name comment_user, b.key comment_user_photo, a.text comment_text, a.created comment_date', 
			$limit.','.$offset, 'sys_client b, a.client_id = b.id', 'a.created desc'
		);

		$count = GTHelperDB::count('mob_comment_news a', 'a.news_id = '.$news_id);
		$pages = ceil($count/$limit);

		foreach ($comments as &$comment) {
			$commentDate = $comment->comment_date;
			$comment->comment_user_photo	= GTHelperAPI::loadFiles(GT_SAFETRAVEL_PROFILE.DS.$comment->comment_user_photo, true);
			$comment->comment_date			= JHtml::date($commentDate, 'j F Y');
			$comment->comment_time			= JHtml::date($commentDate, 'H:i');
			$comment->comment_date_diff		= GTHelperDate::diff($commentDate);
		}

		GTHelperAPI::prepareJSON($comments, null, null, null, $pages);
	}

	public function setNewsComment() {
		// Get Params
		$access_token    = GTHelper::getInput('access_token', '', 'raw');
		$news_id		= GTHelper::getInput('news_id', '', 'int');
		$text			= GTHelper::getInput('text', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$comment	= new stdClass();

		$comment->id		= 0;
		$comment->news_id	= $news_id;
		$comment->client_id	= $client_id;
		$comment->text		= nl2br($text);
		$comment->published	= 1;

		// To be deleted
		$comment->approved		= JFactory::getDate()->toSQL();
		$comment->approved_by	= 1;

		GTHelperDB::insertItem('mob_comment_news', $comment);

		GTHelperAPI::prepareJSON(true, JText::_('COM_GTSAFETRAVEL_CLIENT_COMMENT_SUCCESS'), null, $news_id);
	}

	public function getPlaces() {
		// Get Params
		$country_id		= GTHelper::getInput('country_id', '', 'int');
		$province_id	= GTHelper::getInput('province_id', '', 'int');
		$city_id		= GTHelper::getInput('city_id', '', 'int');
		$category_id	= GTHelper::getInput('category_id', '', 'int');
		$latitude		= GTHelper::getInput('latitude', '0', 'double');
		$longitude		= GTHelper::getInput('longitude', '0', 'double');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '25', 'int');
		$keyword 		= GTHelper::getInput('keyword', '', '');
		$offset			= ($page - 1) * $limit;

		$location = '';
		$country = GTHelperDB::getItem('ref_adm_country', 'id = '.$country_id, 'capital_city_id, vice_city_id, north, south, east, west');
		if($city_id) {
			$location = GTHelperDB::getItem('ref_adm_city', 'id = '.$city_id, 'latitude, longitude, north, south, east, west');
		} elseif($province_id) {
			$location = GTHelperDB::getItem('ref_adm_province', 'id = '.$province_id, 'latitude, longitude, north, south, east, west');
		} elseif($country_id) {
			$city_id = $country->capital_city_id ? $country->capital_city_id : $country->vice_city_id;
			$location = GTHelperDB::getItem('ref_adm_city', 'id = '.$city_id, 'latitude, longitude, north, south, east, west');
		}

		// Process Data
		if(is_double($latitude) && is_double($longitude)) {
			$latitude	= $location->latitude;
			$longitude	= $location->longitude;
		}

		$distance = '
			(3959 * ACOS(
				COS(RADIANS(a.latitude)) * COS(RADIANS('.$latitude.')) * 
				COS(RADIANS('.$longitude.') - RADIANS(a.longitude)) + 
				SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$latitude.'))
			))
		';

		$wheres = array(
			'a.published = 1',
			'a.category_id = '.$category_id,
			'(a.country_id = '.$country_id.' OR a.country_id IS NULL)'
		);


		/*if($keyword) {
			$wheres[] = '(a.name LIKE "%'.$keyword.'%" OR a.address LIKE "%'.$keyword.'%")';
		}*/

		$places = GTHelperDB::getItems('mob_place a', $wheres, 
			'a.id place_id, a.category_id place_category_id, a.country_id place_country_id, b.name place_country_name, a.photo_ref place_image_path,
			a.name place_name, a.address place_address, a.latitude place_latitude, a.longitude place_longitude, a.rating place_rating', 
			$limit.','.$offset, array(
				'ref_adm_country b, a.country_id = b.id, LEFT',
			), array(array($distance))
		);

		$count = GTHelperDB::count('mob_place a', $wheres);
		$pages = ceil($count/$limit);

		foreach ($places as &$place) {
			$rating	= floatval(GTHelperDB::aggregate('mob_rating_place', 'value', 'avg', 'place_id = '.$place->place_id));
			$rating	= array_filter(array($rating, $place->place_rating));
			$rating	= $rating ? array_sum($rating)/count($rating) : 0;
			
			$image_path = '-';
			if($place->place_image_path) {
				$image_path = GTHelperGeo::photoUrl($place->place_image_path);
				$photoID = GTHelperDB::getItem('mob_place_photo', 'url = "'.$image_path.'"', 'id');

				$photo				= new stdClass();
				$photo->id 			= $photoID;
				$photo->place_id	= $place->place_id;
				$photo->url			= $image_path;
				$photo->is_external	= 1;
				GTHelperDB::insertItem('mob_place_photo', $photo);

				$newPlace = new stdClass();
				$newPlace->id = $place->place_id;
				$newPlace->photo_ref = '';
				GTHelperDB::insertItem('mob_place', $newPlace);
			} else {
				$photos = GTHelperDB::getItems('mob_place_photo', 'place_id = '.$place->place_id, 'url');
				$photoK = $photos ? array_rand($photos, 1) : 0;
				$image_path = @$photos[$photoK];
			}

			$place->place_id			= intval($place->place_id);
			$place->place_category_id	= intval($place->place_category_id);
			$place->place_image_path	= $image_path;
			$place->place_rating		= $rating;
			$place->place_province_id	= '';
			$place->place_province_name	= '';
			$place->place_city_id		= '';
			$place->place_city_name		= '';
		}

		GTHelperAPI::prepareJSON($places, null, null, null, $pages);
	}

	public function getProvinces() {
		// Get Params
		$country_id	= GTHelper::getInput('country_id', '', 'int');
		$page		= GTHelper::getInput('page', '1', 'int');
		$limit		= GTHelper::getInput('limit', '100', 'int');

		// Process Data
		$offset	= ($page - 1) * $limit;
		$provinces = GTHelperDB::getItems('ref_adm_province a', 'a.published = 1 AND a.country_id = '.$country_id, 
			'a.id province_id, a.country_id province_country_id, a.name province_name', $page > 0 ? $limit.','.$offset : ''
		);

		$count = GTHelperDB::count('ref_adm_province a', 'a.country_id = '.$country_id);
		$pages = ceil($count/$limit);

		GTHelperAPI::prepareJSON($provinces, null, null, null, $pages);
	}

	public function getCities() {
		// Get Params
		$country_id		= GTHelper::getInput('country_id', '', 'int');
		$province_id	= GTHelper::getInput('province_id', '', 'int');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');

		// Process Data
		$offset	= ($page - 1) * $limit;
		$cities = GTHelperDB::getItems('ref_adm_city a', 'a.published = 1 AND '.($province_id ? 'a.province_id = '.$province_id : 'a.country_id = '.$country_id), 
			'a.id city_id, a.country_id city_country_id, a.province_id city_province_id, a.name city_name', $page > 0 ? $limit.','.$offset : ''
		);

		$count = GTHelperDB::count('ref_adm_city a', $province_id ? 'a.province_id = '.$province_id : 'a.country_id = '.$country_id);
		$pages = ceil($count/$limit);

		GTHelperAPI::prepareJSON($cities, null, null, null, $pages, $count);
	}

	public function setPlaceRating() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$place_id		= GTHelper::getInput('place_id', '', 'int');
		$value			= GTHelper::getInput('value', '', 'double');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$rating		= GTHelperDB::getItem('mob_rating_place', 'place_id = '.$place_id.' AND client_id = '.$client_id);

		$rating->place_id	= $place_id;
		$rating->client_id	= $client_id;
		$rating->value		= $value;
		$rating->published 	= 1;

		GTHelperDB::insertItem('mob_rating_place', $rating);

		GTHelperAPI::prepareJSON(true, JText::_('COM_GTSAFETRAVEL_CLIENT_RATING_SUCCESS'), null, $place_id);
	}

	public function getPlace($place_id = null) {
		// Get Params
		$place_id = $place_id ? $place_id : GTHelper::getInput('place_id', '', 'int');

		// Process Data
		$place = GTHelperDB::getItem('mob_place', 'id = '.$place_id, 
			'id, country_id, category_id, name, address, latitude, longitude, rating'
		);

		$country = GTHelperDB::getItem('ref_adm_country a', 'a.id = '.intval($place->country_id), 
			'id, name, code'
		);

		$rating	= floatval(GTHelperDB::aggregate('mob_rating_place', 'value', 'avg', 'place_id = '.intval($place->id)));
		$rating	= array_filter(array($rating, $place->rating));
		$rating	= $rating ? array_sum($rating)/count($rating) : 0;

		$photos = GTHelperDB::getItems('mob_place_photo', 'place_id = '.$place->id, 'url');
		$photoK = $photos ? array_rand($photos, 1) : 0;
		$image_path = @$photos[$photoK];

		$item = new stdClass();
		$item->place_id				= $place->id;
		$item->place_category_id	= $place->category_id;
		$item->place_country_id		= $country->id;
		$item->place_country_name	= $country->name;
		$item->place_country_code	= $country->code;
		$item->place_province_id	= "";
		$item->place_province_name	= "";
		$item->place_city_id		= "";
		$item->place_city_name		= "";
		$item->place_name			= $place->name;
		$item->place_rating			= $rating;
		$item->place_address 		= $place->address;
		$item->place_description 	= '';
		$item->place_latitude 		= (string) $place->latitude;
		$item->place_longitude 		= (string) $place->longitude;
		$item->place_total_rate		= intval(GTHelperDB::count('mob_rating_place', 'place_id = '.$place->id));
		$item->place_total_comment	= intval(GTHelperDB::count('mob_comment_place', 'place_id = '.$place->id));

		$item->place_image_path = $image_path;

		GTHelperAPI::prepareJSON($item, null, null, $place_id);
	}

	public function getPlaceComments() {
		// Get Params
		$place_id		= GTHelper::getInput('place_id', '', 'int');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');
		$offset			= ($page - 1) * $limit;

		// Process Data
		$comments = GTHelperDB::getItems('mob_comment_place a', 'a.place_id = '.$place_id.' AND a.published = 1', 
			'a.id comment_id, b.name comment_user, b.key comment_user_photo, a.text comment_text, a.created comment_date', 
			$limit.','.$offset, 'sys_client b, a.client_id = b.id', 'a.created desc'
		);

		$count = GTHelperDB::count('mob_comment_place a', 'a.place_id = '.$place_id.' AND a.published = 1');
		$pages = ceil($count/$limit);

		foreach ($comments as &$comment) {
			$commentDate = $comment->comment_date;
			
			$comment->comment_user_photo	= GTHelperAPI::loadFiles(GT_SAFETRAVEL_PROFILE.DS.$comment->comment_user_photo, true);
			$comment->comment_date			= JHtml::date($commentDate, 'j F Y');
			$comment->comment_time			= JHtml::date($commentDate, 'H:i');
			$comment->comment_date_diff		= GTHelperDate::diff($commentDate);
		}

		GTHelperAPI::prepareJSON($comments, null, null, null, $pages);
	}

	public function setPlaceComment() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$place_id		= GTHelper::getInput('place_id', '', 'int');
		$text			= GTHelper::getInput('text', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$comment	= new stdClass();

		$comment->id		= 0;
		$comment->place_id	= $place_id;
		$comment->client_id	= $client_id;
		$comment->text		= nl2br($text);
		$comment->published	= 1;

		// To be deleted
		$comment->approved		= JFactory::getDate()->toSQL();
		$comment->approved_by	= 1;

		GTHelperDB::insertItem('mob_comment_place', $comment);

		GTHelperAPI::prepareJSON(true, JText::_('COM_GTSAFETRAVEL_CLIENT_COMMENT_SUCCESS'), null, $place_id);
	}

	public function getInsurances() {
		// Get Params

		// Process Data
		$insurances = GTHelperDB::getItems('web_insurance a', 'a.published = 1', 
			'a.id insurance_id, a.name insurance_name, a.description insurance_description', ''
		);

		GTHelperAPI::prepareJSON($insurances);
	}

	public function getInsurance() {
		// Get Params
		$insurance_id = GTHelper::getInput('insurance_id', '', 'int');
		
		// Process Data
		$insurance = GTHelperDB::getItem('web_insurance', 'id = '.$insurance_id, 'id, name, description, created, modified');

		$insuranceItems = GTHelperDB::getItems('web_insurance_item a', 
			array('a.published = 1', 'a.insurance_id = '.$insurance_id), 
			'a.id item_id, a.name item_title, a.description item_content', ''
		);

		$item = new stdClass();
		$item->insurance_id = $insurance->id;
		$item->insurance_name = $insurance->name;
		$item->insurance_description = $insurance->description;
		$item->insurance_last_update = $insurance->modified ? $insurance->modified : $insurance->created;
		$item->insurance_last_update = JHtml::date($item->insurance_last_update, 'j F Y H:i:s');
		$item->insurance_item = $insuranceItems;

		GTHelperAPI::prepareJSON($item, null, null, $insurance_id);
	}

	public function getChecklist() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$checklist = GTHelperDB::getItems('ref_checklist a', 'a.published = 1', 
			'a.id checklist_id, a.name checklist_name, a.description checklist_description', ''
		);

		foreach ($checklist as $cl) {
			$cl->checklist_id 	= intval($cl->checklist_id);
			$cl->checklist_user = GTHelperAPI::clientKeyToID($user_id);
		}

		GTHelperAPI::prepareJSON($checklist);
	}

	public function getDisclaimers() {
		// Get Params

		// Process Data
		$disclaimers = GTHelperDB::getItems('web_disclaimer a', 'a.published = 1', 
			'a.id disclaimer_id, a.name disclaimer_name, a.ordering disclaimer_sort, a.description disclaimer_description, a.value disclaimer_value, a.created_by disclaimer_create_user_id, a.created disclaimer_create_date, a.published disclaimer_status', '', '', 'a.ordering' 
		);

		foreach ($disclaimers as $cl) {
			$cl->disclaimer_create_date		= JHtml::date($cl->disclaimer_create_date, 'Y-m-d H:i:s');
			$cl->disclaimer_create_user_id	= intval($cl->disclaimer_create_user_id);
			$cl->disclaimer_status			= intval($cl->disclaimer_status);
			$cl->disclaimer_log_id			= null;
			$cl->disclaimer_code			= '';
		}

		GTHelperAPI::prepareJSON($disclaimers);
	}

	public function getTips() {
		// Get Params

		// Process Data
		$tips = GTHelperDB::getItems('web_tip a', 'a.published = 1', 
			'a.id tips_id, a.name tips_name, a.ordering tips_sort, a.description tips_description, a.value tips_value, a.created_by tips_create_user_id, a.created tips_create_date, a.published tips_status', '', '', 'a.ordering' 
		);

		foreach ($tips as $cl) {
			$cl->tips_create_date		= JHtml::date($cl->tips_create_date, 'Y-m-d H:i:s');
			$cl->tips_create_user_id	= intval($cl->tips_create_user_id);
			$cl->tips_status			= intval($cl->tips_status);
			$cl->tips_log_id			= null;
			$cl->tips_code				= '';
		}

		GTHelperAPI::prepareJSON($tips);
	}

	public function getConsulars() {
		// Get Params
		$page		= GTHelper::getInput('page', '1', 'int');
		$limit		= GTHelper::getInput('limit', '100', 'int');
		$embassy_id	= GTHelper::getInput('embassy_id', '', 'int');

		// Process Data
		$offset		= ($page - 1) * $limit;
		$embassy	= $this->getEmbassy(false);
		$count		= GTHelperDB::count('web_consular_item', 'published = 1 AND embassy_id = '.$embassy_id);
		$consulars	= GTHelperDB::getItems('web_consular_item a', 'a.published = 1 AND a.embassy_id = '.$embassy_id, 
			'a.id consular_id, a.name consular_name, a.description consular_description', $page > 0 ? $limit.','.$offset : '', '', 'a.id' 
		);

		$result					= new stdClass();
		$result->embassy		= $embassy;
		$result->consulars		= $consulars;
		$result->page_current	= $page;
		$result->page_max		= ceil($count/$limit);
		$result->total_result	= $count;

		GTHelperAPI::prepareJSON($result, null, null, null, $result->page_max);
	}

	public function getConsularDiplomatics() {
		// Get Params

		// Process Data
		$item	= GTHelperDB::getItem('web_consular', 'id = 1', 'id, name, description, created');
		$items	= GTHelperDB::getItems('web_consular_item a', 'a.published = 1 AND a.consular_id = '.intval($item->id), 
			'a.id consular_item_id, a.name consular_item_name, a.description consular_item_description', '', '', 'a.id' 
		);

		$consular = new stdClass();
		$consular->consular_id			= $item->id;
		$consular->consular_description	= sprintf('<strong>%s</strong><br><p>%s</p>', $item->name, $item->description);
		$consular->consular_items		= $items;
		$consular->consular_create_date	= JHtml::date($item->created, 'Y-m-d H:i:s');

		GTHelperAPI::prepareJSON($consular);
	}

	public function getPoint() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$user_id		= GTHelper::getInput('user_id', '', 'raw');

		// Process Data
		$this->checkSession(true);
		if(!$user_id) {
			list(,$user_id) = explode('|', $access_token.'|');
		}
		$client_id = GTHelperAPI::clientKeyToID($user_id);

		$db = JFactory::getDbo();
		$query1 = GTHelperDB::buildQuery('mob_api_count a', array('a.client_id = '.$client_id, 'b.point > 0'), 
			array(
				'IF(b.repeat = 1, SUM(b.point * a.count), b.point) point', 'a.client_id'
			), '', 'ref_api b, a.api_id = b.id', '', 'a.client_id, a.item_id, a.api_id'
		);

		$query2 = GTHelperDB::buildQuery(array($query1, 'a'), '', 
			'SUM(a.point) point', '', '', '', 'a.client_id'
		);

		$country_count = GTHelperDB::count('sys_client_country a', array('a.client_id = '.$client_id));

		//echo nl2br(str_replace('#__','eburo_',$query2)).'<br/><br/>'; die;
		$point = $db->setQuery($query2)->loadResult();
		$point = intval($point);

		$levels = GTHelperDB::getItems('ref_client_level', '', 'name, level, point', '', '', 'point asc');


		foreach ($levels as &$level) {
			$level->icon = GTHelper::verifyFile(GT_MEDIA_URI.'/level/'.$level->level.'.png');
			if($level->point <= $point) {
				$level->unlocked = 1;
				$curlevel = clone $level;
			} else {
				$level->unlocked = 0;
			}
		}

		$nextlevel = end($levels);
		foreach (array_reverse($levels) as $lv) {
			if($lv->point > $point) {
				$nextlevel = clone $lv;
			}
		}

		$p						= new stdClass();
		$p->current_point		= $point;
		$p->current_level		= $curlevel->name;
		$p->current_level_num	= $curlevel->level;
		$p->current_level_point	= $curlevel->point;
		$p->current_level_icon 	= GTHelper::verifyFile(GT_MEDIA_URI.'/level/'.$curlevel->level.'.png');
		$p->next_level			= $nextlevel->name;
		$p->next_level_num		= $nextlevel->level;
		$p->next_level_point	= $nextlevel->point;
		$p->next_level_lack		= $point > $nextlevel->point ? 0 : $nextlevel->point - $point;
		$p->next_level_percent	= $point > $nextlevel->point ? 100 : round($point / $nextlevel->point, 2) * 100;
		$p->next_level_icon 	= GTHelper::verifyFile(GT_MEDIA_URI.'/level/'.$nextlevel->level.'.png');
		$p->levels 				= $levels;
		$p->country_count 		= $country_count;

		GTHelperAPI::prepareJSON($p);
	}

	public function getPoints() {
		// Get Params
		$page	= GTHelper::getInput('page', '1', 'int');
		$limit	= GTHelper::getInput('limit', '25', 'int');


		// Process data
		$offset	= ($page - 1) * $limit;
		$db		= JFactory::getDbo();
		$query1	= GTHelperDB::buildQuery('mob_api_count a', array('a.client_id > 0', 'b.point > 0', 'c.published = 1'), 
			array(
				'IF(b.repeat = 1, SUM(b.point * a.count), b.point) point', 'a.client_id'
			), '', array('ref_api b, a.api_id = b.id', 'sys_client c, a.client_id = c.id'), '', 'a.client_id, a.item_id, a.api_id'
		);

		$query2 = GTHelperDB::buildQuery(array($query1, 'a'), '', 
			array('b.key user_id', 'b.name', 'SUM(a.point) point'), $limit.','.$offset, 'sys_client b, a.client_id = b.id', 'SUM(a.point) DESC', 'a.client_id'
		);

		$count = GTHelperDB::count('sys_client a', 'a.published = 1');
		$pages = ceil($count/$limit);

		$points = $db->setQuery($query2)->loadObjectList();
		$levels = GTHelperDB::getItems('ref_client_level', '', 'name, level, point', '', '', 'point desc');
		
		foreach ($points as &$point) {
			foreach ($levels as $level) {
				if($level->point < $point->point) {
					break;
				}
			}
			$point->level = $level->name;
			$point->level_num = $level->level;
			$point->level_icon = GTHelper::verifyFile(GT_MEDIA_URI.'/level/'.$level->level.'.png');
			$point->photo = GTHelperAPI::loadFiles(GT_SAFETRAVEL_PROFILE.DS.$point->user_id, true);
		}

		GTHelperAPI::prepareJSON($points, null, null, null, $pages);
	}

	public function getLocation() {
		// Get Params
		$latitude		= GTHelper::getInput('latitude', '0', 'double');
		$longitude		= GTHelper::getInput('longitude', '0', 'double');
		$access_token	= GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$client_id	= intval($client_id);
		$latitude	= floatval($latitude);
		$longitude	= floatval($longitude);
		$distance	= '
			(3959 * ACOS(
				COS(RADIANS(a.latitude)) * COS(RADIANS('.$latitude.')) * 
				COS(RADIANS('.$longitude.') - RADIANS(a.longitude)) + 
				SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$latitude.'))
			))
		';

		$city = GTHelperDB::getItems('ref_adm_city a', array('a.published = 1', 'a.latitude IS NOT NULL', 'a.longitude IS NOT NULL'),
			'', '1', '', array(array($distance, 'ASC'))
		);
		$city = reset($city);

		if(@$city->id) {
			$country = GTHelperDB::getItem('ref_adm_country', 'id = '.$city->country_id, 'id, code, name');
		} else {			
			$country = GTHelperDB::getItems('ref_adm_country a', 
				array($latitude.' BETWEEN a.south AND a.north', $longitude.' BETWEEN a.west AND a.east'),
				'a.id, a.code, a.name', '1', '', array(array($distance, 'ASC'))
			);
			$country = reset($country);
		}

		$city_id		= @$city->id;
		$country_id		= @$country->id;
		$destination	= GTHelperDB::getItems('mob_travel_destination a', array('b.client_id = '.$client_id, '"'.date('Y-m-d').'" BETWEEN a.start_date AND a.end_date', 'b.type = "temporary"'), 'a.id, a.country_id, b.type, a.travel_id', '1', 'mob_travel b, a.travel_id = b.id', array('b.type DESC', 'a.id DESC'));
		$destination	= reset($destination);

		$country_code = strtolower(@$country->code);
		$country_code = $country_code ? $country_code : '-';
		$country_flag = GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.$country_code.'.png');
		$country_flag2 = explode('/', $country_flag);
		$country_flag2 = end($country_flag2);

		if($client_id) {
			$prevLog	= GTHelperDB::getItems('sys_client_location a', 'a.client_id = '.$client_id, 
			'id, latitude, longitude, ordering', '1', '', 'a.datetime DESC');
			$prevLog	= reset($prevLog);

			$loc1		= $latitude.','.$longitude;
			$loc2		= floatval(@$prevLog->latitude).','.floatval(@$prevLog->longitude);
			$distance	= GTHelperGeo::countDistance($loc1, $loc2);

			if(($distance > 5 && @$prevLog->id > 0) || !@$prevLog->id > 0) {
				$ordering	= intval($prevLog->ordering) + 1;
				$ordering	= $ordering > 25 ? 1 : $ordering; 
				$logID		= GTHelperDB::getItem('sys_client_location a', 
					array('a.client_id = '.$client_id, 'a.ordering = '.$ordering), 'id');
				$logID		= intval($logID);

				$log 				= new stdClass();
				$log->id			= $logID;
				$log->client_id 	= $client_id;
				$log->country_id	= $country_id;
				$log->city_id		= $city_id;
				$log->latitude 		= $latitude;
				$log->longitude 	= $longitude;
				$log->ordering 		= $ordering;
				$log->datetime 		= JFactory::getDate()->toSQL();

				GTHelperDB::insertItem('sys_client_location', $log);

				if($country_id) {
					$clientCountry 				= new stdClass();
					$clientCountry->id			= GTHelperDB::getItem('sys_client_country', 
						array('country_id = '.$country_id, 'client_id = '.$client_id), 'id');
					$clientCountry->client_id	= $client_id;
					$clientCountry->country_id	= $country_id;

					GTHelperDB::insertItem('sys_client_country', $clientCountry);
				}

				if($city_id) {
					$clientCity 			= new stdClass();
					$clientCity->id			= GTHelperDB::getItem('sys_client_city', 
						array('city_id = '.$city_id, 'client_id = '.$client_id), 'id');
					$clientCity->client_id	= $client_id;
					$clientCity->city_id	= $city_id;

					GTHelperDB::insertItem('sys_client_city', $clientCity);
				}

				/*
				if($place_id) {
					$place = new stdClass();
					$place->id			= GTHelperDB::getItem('sys_client_place', 
						array('place_id = '.$place_id, 'client_id = '.$client_id), 'id');
					$place->client_id	= $client_id;
					$place->place_id	= $place_id;

					GTHelperDB::insertItem('sys_client_place', $place);
				}
				*/
			}
			$user_location = array(@$city->name, @$country->name);
			$user_location = array_filter($user_location);
			$user_location = implode(', ', $user_location);
			
			$return = GTHelper::loadCurl(GTHelperAPI::chatUrl().'/auth/update/location', array(
				'access_token' => $access_token,
				'latitude' => $latitude,
				'longitude' => $longitude,
				'location' => $user_location,
				'flag' => $country_flag2
			), true);
		}

		$location				= new stdClass();
		$location->country_id	= $country_id;
		$location->country_code	= @$country->code;
		$location->country_name	= @$country->name;
		$location->country_flag = $country_flag;
		$location->city_id		= $city_id;
		$location->city_name	= @$city->name;
		$location->latitude 	= (string) $latitude;
		$location->longitude 	= (string) $longitude;

		if(!@$destination->id) {
			$location->travel_id		= 0;
			$location->travel_status	= 'missing';
			$location->travel_note		= JText::_('COM_GTSAFETRAVEL_API_TRAVEL_DATA_MISSING');
		} elseif($destination->country_id != $country_id) {
			$location->travel_id		= $destinations->travel_id;
			$location->travel_status	= 'mismatch';
			$location->travel_note		= JText::_('COM_GTSAFETRAVEL_API_TRAVEL_DATA_MISMATCH');
		} else {
			$location->travel_id		= $destinations->travel_id;
			$location->travel_status	= 'valid';
			$location->travel_note		= JText::_('COM_GTSAFETRAVEL_API_TRAVEL_DATA_VALID');
		}

		GTHelperAPI::prepareJSON($location);
	}

	public function getBadges() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$user_id		= GTHelper::getInput('user_id', '', 'raw');

		// Process Data
		$this->checkSession(true);
		if(!$user_id) {
			list(,$user_id) = explode('|', $access_token.'|');
		} 

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$client_id	= intval($client_id);

		$client_countries = GTHelperDB::getItems('ref_adm_country a', array('a.published = 1', 'b.client_id = '.$client_id), 'a.id, a.name, a.code badge', '', 'sys_client_country b, a.id = b.country_id');
		$badges = GTHelperAPI::getClientBadges($client_id);

		foreach ($client_countries as $k => &$country) {
			$country_badge = GTHelper::verifyFile(GT_MEDIA_URI.'/badge/country/'.strtolower($country->badge).'.png');
			$country_badge = $country_badge != '-' ? $country_badge : GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.strtolower($country->badge).'.png');

			$country->badge = $country_badge;
		}

		$result				= new stdClass();
		$result->badges		= $badges;
		$result->countries	= $client_countries;

		GTHelperAPI::prepareJSON($result);
	}

	public function setGallery() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$id					= GTHelper::getInput('id', 0, 'int');
		$address			= GTHelper::getInput('address', '', 'raw');
		$description		= GTHelper::getInput('description', '', 'raw');
		$latitude			= GTHelper::getInput('latitude', '', 'float');
		$longitude			= GTHelper::getInput('longitude', '', 'float');
		$photo				= GTHelper::getInput('photo', '', 'raw');
		$is_public			= GTHelper::getInput('is_public', 1, 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client			= new stdClass();
		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$gallery		= $this->getGallery($id, true);

		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}
		
		$published	= @$gallery->published ? $gallery->published : 1;
		$key		= @$gallery->key ? $gallery->key : md5(uniqid('', true));
		$latitude	= floatval($latitude);
		$longitude	= floatval($longitude);
		$distance	= '
			(3959 * ACOS(
				COS(RADIANS(a.latitude)) * COS(RADIANS('.$latitude.')) * 
				COS(RADIANS('.$longitude.') - RADIANS(a.longitude)) + 
				SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$latitude.'))
			))
		';

		$city = GTHelperDB::getItems('ref_adm_city a', array('a.published = 1', 'a.latitude IS NOT NULL', 'a.longitude IS NOT NULL'),
			'', '1', '', array(array($distance, 'ASC'))
		);
		$city = reset($city);

		$gallery->id			= $id;
		$gallery->key			= $key;
		$gallery->client_id		= $client_id;
		$gallery->country_id	= $city->country_id;
		$gallery->city_id		= $city->id;
		$gallery->description	= $description;
		$gallery->address		= $address;
		$gallery->type			= 'gallery';
		$gallery->latitude		= $latitude;
		$gallery->longitude		= $longitude;
		$gallery->published		= $published;
		$gallery->is_public 	= $is_public;
		
		// Upload Photo
		$photo_folder = GT_SAFETRAVEL_GALLERY.DS.$key;
		$photos = GTHelperAPI::uploadPhotos($photo, $photo_folder);
		$photos = (array) $photos;
		$photos = reset($photos);
		if($photos) {
			$gallery->photos = $photos;
		}
		
		// Save Data
		$gallery_id = GTHelperDB::insertItem('mob_gallery', $gallery, '');

		$this->getGallery($gallery_id);
	}


	public function getGallery($id = '', $called = false) {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');

		// Process Data
		$gallery = GTHelperDB::getItem('mob_gallery', array('id = '.intval($id)), 
			'id, key, client_id, country_id, city_id, address, type, description, latitude, longitude, created, is_public', 'type'
		);

		$client = GTHelperDB::getItem('sys_client', array('id = '.intval($gallery->client_id)), 
			'name, key'
		);

		list(,$user_id)	= explode('|', $access_token.'|');
		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$is_liked 		= (bool) GTHelperDB::count('mob_gallery_like', array('gallery_id = '.intval($id), 'client_id = '.$client_id, 'is_like = 1'));
		$like_count		= GTHelperDB::aggregate('mob_gallery_like', 'is_like', 'SUM', array('gallery_id = '.intval($id), 'is_like = 1'));
		$comment_count	= GTHelperDB::count('mob_comment_gallery', array('gallery_id = '.intval($id)));
		unset($gallery->client_id);

		$is_public = $gallery->is_public == 1; unset($gallery->is_public);

		// Fetch Media
		$photo_folder			= ($gallery->type == 'gallery' ? GT_SAFETRAVEL_GALLERY : GT_SAFETRAVEL_CHECKIN).DS.$gallery->key;
		$gallery->created_year	= JHtml::date($gallery->created, 'Y');
		$gallery->created_date	= JHtml::date($gallery->created, 'j M');
		$gallery->created_time	= JHtml::date($gallery->created, 'H:i');
		$gallery->created_diff	= GTHelperDate::diff($gallery->created);
		$gallery->photo			= GTHelperAPI::loadFiles($photo_folder, true);
		$gallery->user_id		= $client->key;
		$gallery->user_name		= $client->name;
		$gallery->user_photo	= GTHelperAPI::loadFiles(GT_SAFETRAVEL_PROFILE.DS.$client->key, true);
		$gallery->comment_count	= GTHelperNumber::abbreviate(intval($comment_count));
		$gallery->like_count	= GTHelperNumber::abbreviate(intval($like_count));
		$gallery->is_liked		= $is_liked;
		$gallery->is_public 	= $is_public;
		
		if($called) {
			return $gallery;
		} else {
			if($gallery->id) {
				GTHelperAPI::prepareJSON($gallery);
			} else {
				GTHelperAPI::prepareJSON(false);
			}
		}
	}

	public function getGalleries() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');
		$country_id 	= GTHelper::getInput('country_id', '', 'int');
		$city_id 		= GTHelper::getInput('city_id', '', 'int');
		$user_id 		= GTHelper::getInput('user_id', '', 'raw');
		$order_by 		= GTHelper::getInput('order_by', 'date');

		// Process Data
		list(,$cur_user_id)	= explode('|', $access_token.'|');

		$wheres = array('a.published = 1', 'a.photos IS NOT NULL');
		if($user_id != $cur_user_id) {
			$wheres[] = 'a.is_public = 1';
		}
		if($country_id) {
			$wheres[] = 'a.country_id = '.$country_id;
		}
		if($city_id) {
			$wheres[] = 'a.city_id = '.$city_id;
		}
		if($user_id) {
			$client_id	= GTHelperAPI::clientKeyToID($user_id);
			$wheres[]	= 'a.client_id = '.$client_id;
		}

		$offset		= ($page-1) * $limit;
		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$galleries	= GTHelperDB::getItems('mob_gallery a', $wheres, 
			array('a.id', 'a.key', 'b.key user_id', 'b.name user_name', 'b.key user_photo', 'a.country_id', 'a.city_id', 'a.address', 'a.description', 'a.type', 'a.latitude', 'a.longitude', 'a.is_public', 'a.created'),
			$limit.','.$offset, array('sys_client b, a.client_id = b.id'), ($order_by == 'date' ? 'a.created' : 'a.total_like').' desc', 'id'
		);

		$count = GTHelperDB::count('mob_gallery a', $wheres);
		$pages = ceil($count/$limit);

		foreach ($galleries as &$gallery) {
			$created_unix 			= strtotime($gallery->created);
			$gallery->created_year	= JHtml::date($gallery->created, 'Y');
			$gallery->created_date	= JHtml::date($gallery->created, 'j M');
			$gallery->created_time	= JHtml::date($gallery->created, 'H:i');
			$gallery->created_diff	= GTHelperDate::diff($gallery->created);
			$gallery->created		= $created_unix;
			$gallery->user_photo 	= GTHelperAPI::loadFiles(GT_SAFETRAVEL_PROFILE.DS.$gallery->user_photo, true);

			// Fetch Media
			$photo_folder	= ($gallery->type == 'gallery' ? GT_SAFETRAVEL_GALLERY : GT_SAFETRAVEL_CHECKIN).DS.$gallery->key;
			$gallery->photo	= GTHelperAPI::loadFiles($photo_folder, true);
		}

		if($galleries) {
			GTHelperAPI::prepareJSON($galleries, null, null, null, $pages, $count);
		} else {
			GTHelperAPI::prepareJSON(false, null, null, null, $pages, $count);
		}
	}

	public function getGalleryComments() {
		// Get Params
		$gallery_id		= GTHelper::getInput('gallery_id', '', 'int');
		$page			= GTHelper::getInput('page', '1', 'int');
		$limit			= GTHelper::getInput('limit', '100', 'int');
		
		// Process Data
		$offset		= ($page - 1) * $limit;
		$comments	= GTHelperDB::getItems('mob_comment_gallery a', 'a.published = 1 AND a.gallery_id = '.$gallery_id, 
			array('a.id comment_id', 'b.key comment_user_id', 'b.name comment_user', 'b.key comment_user_photo', 'a.text comment_text', 'a.created comment_date'), 
			$limit.','.$offset, array('sys_client b, a.client_id = b.id'), 'a.created desc'
		);

		$count = GTHelperDB::count('mob_comment_gallery a', 'a.published = 1 AND a.gallery_id = '.$gallery_id);
		$pages = ceil($count/$limit);

		foreach ($comments as &$comment) {
			$commentDate = $comment->comment_date;
			$comment->comment_user_photo	= GTHelperAPI::loadFiles(GT_SAFETRAVEL_PROFILE.DS.$comment->comment_user_photo, true);
			$comment->comment_date			= JHtml::date($commentDate, 'j F Y');
			$comment->comment_time			= JHtml::date($commentDate, 'H:i');
			$comment->comment_date_diff		= GTHelperDate::diff($commentDate);
		}

		GTHelperAPI::prepareJSON($comments, null, null, null, $pages);
	}

	public function setGalleryComment() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$gallery_id		= GTHelper::getInput('gallery_id', '', 'int');
		$text			= GTHelper::getInput('text', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$comment	= new stdClass();

		$comment->id			= 0;
		$comment->gallery_id	= $gallery_id;
		$comment->client_id		= $client_id;
		$comment->text			= nl2br($text);
		$comment->published		= 1;

		GTHelperDB::insertItem('mob_comment_gallery', $comment);

		GTHelperAPI::prepareJSON(true, JText::_('COM_GTSAFETRAVEL_CLIENT_COMMENT_SUCCESS'), null, $gallery_id);
	}

	public function setGalleryLike() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$gallery_id		= GTHelper::getInput('gallery_id', '', 'int');
		$is_like		= GTHelper::getInput('like', '1', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$like		= new stdClass();

		$like->gallery_id	= $gallery_id;
		$like->client_id	= $client_id;
		$like->is_like		= $is_like;

		GTHelperDB::insertItem('mob_gallery_like', $like);

		if($gallery_id) {
			$count_like	= GTHelperDB::count('mob_gallery_like', 'is_like = 1 AND gallery_id = '.$gallery_id);
			$gallery	= new stdClass();

			$gallery->id			= $gallery_id;
			$gallery->total_like	= $count_like;

			GTHelperDB::insertItem('mob_gallery', $gallery);
		}

		$msg = $is_like ? JText::_('COM_GTSAFETRAVEL_CLIENT_LIKE_SUCCESS') : JText::_('COM_GTSAFETRAVEL_CLIENT_UNLIKE_SUCCESS');

		GTHelperAPI::prepareJSON(true, $msg, null, $gallery_id);
	}

	public function setGalleryPrivacy() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$gallery_id		= GTHelper::getInput('gallery_id', '', 'int');
		$is_public		= GTHelper::getInput('is_public', '1', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		if(!$gallery_id) {
			GTHelperAPI::prepareJSON(false);
		}

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$gallery	= new stdClass();
		$is_public 	= $is_public == 1;

		$gallery->id		= $gallery_id;
		$gallery->is_public	= intval($is_public == 1);

		GTHelperDB::insertItem('mob_gallery', $gallery);

		$msg = $is_public ? JText::_('COM_GTSAFETRAVEL_CLIENT_PVC_PUBLIC_SUCCESS') : JText::_('COM_GTSAFETRAVEL_CLIENT_PVC_PRIVATE_SUCCESS');

		GTHelperAPI::prepareJSON(true, $msg, null, $gallery_id);
	}

	public function deleteGallery() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$gallery_id		= GTHelper::getInput('id', '', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);

		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}

		GTHelperDB::publish('mob_gallery', array('id = '.$gallery_id, 'client_id = '.$client_id), -2);

		GTHelperAPI::prepareJSON(true, JText::_('COM_GTSAFETRAVEL_API_GALLERY_DELETED'), null, $gallery_id);
	}

	public function checkVisa() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$country_id		= GTHelper::getInput('country_id', '', 'int');
		$start_date		= GTHelper::getInput('start_date', '');
		$end_date		= GTHelper::getInput('end_date', '');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$client 	= GTHelperDB::getItem('sys_client', 'id = '.intval($client_id), 'id, passport_number, passport_type');
		$country 	= GTHelperDB::getItem('ref_adm_country', 'id = '.$country_id, 'id, name, code');

		if(!$client->id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}

		if(!$client->passport_number || !in_array($client->passport_type, array('regular', 'diplomatic'))) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_API_PASSPORT_DATA_MISSING'));
		}

		$start_date	= intval(strtotime($start_date));
		$end_date	= intval(strtotime($end_date));
		$duration 	= round(($end_date - $start_date)/(24*60*60));

		$visa = GTHelperDB::getItem('ref_visa_free', array(
			'country_id = '.$country_id,
			$client->passport_type.'_stay >= '.$duration
		));

		$item				= new stdClass();
		$item->country		= $country->name;
		$item->country_flag	= GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.strtolower($country->code).'.png');
		
		if($client->passport_type == 'regular') {
			$item->is_required	= !in_array($visa->regular_type, array('free', 'voa', 'evisavoa','freevoa'));
			$item->type			= $visa->id ? JText::_('COM_GTSAFETRAVEL_VISA_'.strtoupper($visa->regular_type)) : JText::_('COM_GTSAFETRAVEL_VISA_REQUIRED');
			$item->duration 	= intval($visa->regular_stay);
			$item->note			= $visa->id ? implode(' - ', array_filter(array(JText::sprintf('COM_GTSAFETRAVEL_VALID_FOR_N_DAYS', $visa->regular_stay), $visa->regular_note))) : JText::sprintf('COM_GTSAFETRAVEL_VISA_REQUIRED_DESC', $country->name);
		} else {
			$item->is_required	= !($visa->id > 0);
			$item->type 		= $visa->id ? JText::_('COM_GTSAFETRAVEL_VISA_FREE') : JText::_('COM_GTSAFETRAVEL_VISA_REQUIRED');
			$item->duration 	= intval($visa->diplomatic_stay);
			$item->note			= $visa->id ? implode(' - ', array_filter(array(JText::sprintf('COM_GTSAFETRAVEL_VALID_FOR_N_DAYS', $visa->diplomatic_stay), $visa->diplomatic_note))) : JText::sprintf('COM_GTSAFETRAVEL_VISA_REQUIRED_DESC', $country->name);
		}

		GTHelperAPI::prepareJSON($item);
	}

	public function forgetPassword() {
		// Get Params
		$email	= GTHelper::getInput('email', '', 'raw');

		// Process Data
		$client = GTHelperDB::getItem('sys_client', array(
			'email = "'.$email.'"'
		), 'id, name, email');

		if(!$client->id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_INVALID'));
		}

		// Save Reset Code
		$client->is_reset	= 0;
		$client->reset_code	= md5(uniqid('', true));
		GTHelperDB::insertItem('sys_client', $client);

		// Send Email Confirmation
		$conf_link		= 'index.php?option=com_gtsafetravel&view=reset_password';
		$conf_link_id	= GTHelper::getMenuId($conf_link);
		$conf_link		= JRoute::_($conf_link.'&Itemid='.$conf_link_id.'&code='.$client->reset_code, true, -1);
		$email_title	= JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_RESET_TITLE');
		$email_body		= JText::_('COM_GTSAFETRAVEL_CLIENT_EMAIL_RESET_BODY');
		$email_body		= strtr($email_body, array(
			'{NAME}' => $client->name,
			'{LINK}' => $conf_link
		));
		GTHelperMail::send($email_title, $email_body, $client->email);

		GTHelperAPI::prepareJSON(true);
	}

	public function getExpiredVisa() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$days_before	= GTHelper::getInput('days_before', '60', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$start_date = JHtml::date('now', 'Y-m-d');
		$end_date = JHtml::date('+'.$days_before.' day', 'Y-m-d');

		$visas	= GTHelperDB::buildQuery('mob_travel_destination a', 'c.client_id = '.$client_id.' AND a.visa_expired > 0', 
			array('a.country_id', 'MAX(a.visa_expired) visa_expired', 'c.client_id'), 
			'', array('mob_travel c, a.travel_id = c.id'), 'a.visa_expired asc', 'a.country_id'
		);

		$expiredVisas	= GTHelperDB::getItems(array($visas, 'a'), 'a.visa_expired BETWEEN "'.$start_date.'" AND "'.$end_date.'" AND a.client_id = '.$client_id, 
			array('a.country_id', 'b.name country_name', 'a.visa_expired'), 
			'', array('ref_adm_country b, a.country_id = b.id'), 'a.visa_expired asc'
		);

		GTHelperAPI::prepareJSON($expiredVisas);
	}

	public function setPointShare() {
		// Get Params
		$access_token = GTHelper::getInput('access_token', '', 'raw');
		
		GTHelperAPI::prepareJSON(true);
	}

	public function setTravelPermanent() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$id					= GTHelper::getInput('id', 0, 'int');
		$identity_number	= GTHelper::getInput('identity_number', '', 'raw');
		$birth_place		= GTHelper::getInput('birth_place', '', 'raw');
		$birth_date			= GTHelper::getInput('birth_date', '');
		$gender				= GTHelper::getInput('gender', '');
		$passport_number	= GTHelper::getInput('passport_number');
		$passport_expired	= GTHelper::getInput('passport_expired');
		$passport_file 		= GTHelper::getInput('passport_file', '', 'raw');
		$passport_type		= GTHelper::getInput('passport_type', 'regular');

		$start_date			= GTHelper::getInput('start_date');
		$end_date			= GTHelper::getInput('end_date');

		$classification_id	= GTHelper::getInput('classification_id', 0, 'int');
		$address			= GTHelper::getInput('address', '', 'raw');
		$country_id			= GTHelper::getInput('country_id', 0, 'int');
		$city_id			= GTHelper::getInput('city_id', 0, 'int');
		$postcode			= GTHelper::getInput('postcode', '');
		$phone				= GTHelper::getInput('phone', '', 'int');
		$visa_photo			= GTHelper::getInput('visa_photo', '', 'raw');
		$visa_number		= GTHelper::getInput('visa_number', '');
		$visa_issued		= GTHelper::getInput('visa_issued');
		$visa_expired		= GTHelper::getInput('visa_expired');
		$permit_photo		= GTHelper::getInput('permit_photo', '', 'raw');

		$contact_name				= GTHelper::getInput('contact_name', '');
		$contact_relationship		= GTHelper::getInput('contact_relationship', '');
		$contact_email				= GTHelper::getInput('contact_email', '');
		$contact_phone				= GTHelper::getInput('contact_phone', '');
		
		$contact_dom_name			= GTHelper::getInput('contact_dom_name', '');
		$contact_dom_relationship	= GTHelper::getInput('contact_dom_relationship', '');
		$contact_dom_email			= GTHelper::getInput('contact_dom_email', '');
		$contact_dom_phone			= GTHelper::getInput('contact_dom_phone', '');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$travel			= $this->getTravel($id, true);
		$permanent 		= GTHelperDB::getItem('mob_travel_permanent', 'travel_id = '.intval($id), 'id, travel_id');
		$published		= @$travel->published ? $travel->published : 1;
		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_LOGIN_FAILED2'));
		}

		$travel->id					= $id;
		$travel->client_id			= $client_id;
		$travel->classification_id	= $classification_id;
		$travel->type 				= 'permanent';
		$travel->published			= $published;

		// Save Travel Data
		$travel_id = GTHelperDB::insertItem('mob_travel', $travel, '');

		// Update Client Data
		$client		= new stdClass();
		$client->id	= $client_id;

		if($identity_number) {
			$client->identity_number = preg_replace("/[^A-Z0-9]+/", "", strtoupper($identity_number));
		}
		if($gender) {
			$client->gender = $gender;
		}
		if($birth_place) {
			$client->birth_place = $birth_place;
		}
		if($birth_date) {
			$client->birth_date = GTHelperDate::format($birth_date, 'Y-m-d');
		}
		if($passport_number) {
			$client->passport_number = $passport_number;
		}
		if($passport_expired) {
			$client->passport_expired = GTHelperDate::format($passport_expired, 'Y-m-d');
		}
		if($passport_type) {
			$client->passport_type = $passport_type;
		}

		GTHelperDB::insertItem('sys_client', $client);

		// Upload Passport
		$passport_file	= GTHelperAPI::uploadPhotos($passport_file, GT_SAFETRAVEL_PASSPORT.DS.$user_id);
		if($passport_file) {
			$passport_file = reset($passport_file);
			$passport_file = explode(DS, $passport_file);
			$passport_file = end($passport_file);
			$client->passport_file = $passport_file;
		}

		// Upload Visa
		$visa_id = null;
		if($visa_number && $visa_issued && $visa_expired) {
			$visanum	= preg_replace("/[^A-Z0-9]+/", "", strtoupper($visa_number));
			$visa		= GTHelperDB::getItem('sys_client_visa', array('country_id = '.$country_id, 'number = "'.$visa_number.'"', 'client_id = '.$client_id));
			
			$visa->number			= $visanum;
			$visa->client_id		= $client_id;
			$visa->country_id		= $country_id;
			$visa->issued_date		= GTHelperDate::format($visa_issued, 'Y-m-d');
			$visa->expired_date		= GTHelperDate::format($visa_expired, 'Y-m-d');
			$visa->published 		= 1;

			$visa_photo	= $visa_photo ? GTHelperAPI::uploadPhotos($visa_photo, GT_SAFETRAVEL_VISA.DS.$user_id.DS.$visa_number) : null;
			if($visa_photo) {
				$visa_photo	= reset($visa_photo);
				$visa_photo	= explode(DS, $visa_photo);
				$visa_photo	= end($visa_photo);

				$visa->photo = $visa_photo;
			}
			$visa_id = GTHelperDB::insertItem('sys_client_visa', $visa);
		}

		// Set Destination
		$destination = GTHelperDB::getItem('mob_travel_destination', array('travel_id = '.$travel_id));
		$destination->travel_id		= $travel_id;
		$destination->start_date	= GTHelperDate::format($start_date, 'Y-m-d');
		$destination->end_date		= GTHelperDate::format($end_date, 'Y-m-d');
		$destination->country_id	= $country_id;
		$destination->city_id		= $city_id;
		$destination->visa_id		= $visa_id;
		$destination->address		= $address;
		$destination->phone			= $phone;
		$destination->published		= 1;

		GTHelperDB::insertItem('mob_travel_destination', $destination);
		$diff = GTHelperDate::diff($destination->start_date, $destination->end_date, false);

		$permanent->travel_id		= $travel_id;
		$permanent->address			= $address;
		$permanent->country_id		= $country_id;
		$permanent->city_id			= $city_id;
		$permanent->postcode		= $postcode;
		$permanent->phone			= $phone;
		$permanent->duration_year	= $diff->y;
		$permanent->duration_month	= $diff->m;

		// Upload Permit
		$permit_photo	= GTHelperAPI::uploadPhotos($permit_photo, GT_SAFETRAVEL_PERMIT.DS.$user_id.DS.$travel_id);
		if($permit_photo) {
			$permit_photo	= reset($permit_photo);
			$permit_photo	= explode(DS, $permit_photo);
			$permit_photo	= end($permit_photo);

			$permanent->permit_photo = $permit_photo;
		}

		$permanent->contact_name				= $contact_name;
		$permanent->contact_relationship		= $contact_relationship;
		$permanent->contact_email				= $contact_email;
		$permanent->contact_phone				= $contact_phone;

		$permanent->contact_dom_name			= $contact_dom_name;
		$permanent->contact_dom_relationship	= $contact_dom_relationship;
		$permanent->contact_dom_email			= $contact_dom_email;
		$permanent->contact_dom_phone			= $contact_dom_phone;
		$permanent->published					= $published;

		// Save travel permanent data
		GTHelperDB::insertItem('mob_travel_permanent', $permanent);
		$this->getTravelPermanent($travel_id);
	}

	public function getTravelPermanent($id = '') {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$id				= $id ? $id : GTHelper::getInput('id', '', 'int');

		// Process Data
		$this->checkSession(true);
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		$travel		= GTHelperDB::getItem('mob_travel', array('id = '.$id, 'client_id = '.$client_id), 
			'id, client_id, classification_id'
		);

		if(!$travel->id) {
			GTHelperAPI::prepareJSON(false);
		}

		$travel_id = intval($id);
		$permanent = GTHelperDB::getItem('mob_travel_permanent', 'travel_id = '.$travel_id, 'address, country_id, city_id, postcode, phone, duration_year, duration_month, permit_photo, contact_name, contact_relationship, contact_email, contact_phone, contact_dom_name, contact_dom_relationship, contact_dom_email, contact_dom_phone');
		$destination = GTHelperDB::getItem('mob_travel_destination', array('travel_id = '.$travel_id), 
			'id, visa_id, city_id, country_id, start_date, end_date, address, phone'
		);
		
		if($destination->id) {
			$diff = GTHelperDate::diff($destination->start_date, $destination->end_date, false);

			$permanent->address			= $destination->address;
			$permanent->country_id		= $destination->country_id;
			$permanent->city_id			= $destination->city_id;
			$permanent->phone			= $destination->phone;
			$permanent->duration_year	= $diff->y;
			$permanent->duration_month	= $diff->m;
		}

		$classification	= GTHelperDB::getItem('ref_classification', array('id = '.intval($travel->classification_id)), 'name');
		$country		= GTHelperDB::getItem('ref_adm_country', array('id = '.intval($permanent->country_id)), 'name, code');
		$city			= GTHelperDB::getItem('ref_adm_city', array('id = '.intval($permanent->city_id)), 'name');
		$visa			= GTHelperDB::getItem('sys_client_visa', array('id = '.intval($destination->visa_id)));

		$client = GTHelperDB::getItem('sys_client', 'id = '.intval($client_id));

		$result								= new stdClass();
		$result->id							= intval($travel->id);
		$result->name						= $client->name;
		$result->identity_number			= $client->identity_number;
		$result->birth_place				= $client->birth_place;
		$result->birth_date					= $client->birth_date;
		$result->gender						= $client->gender;
		$result->passport_number			= $client->passport_number;
		$result->passport_expired			= $client->passport_expired;
		$result->passport_file				= GTHelper::verifyFile(GT_SAFETRAVEL_PASSPORT_URI.$client->key.'/'.$client->passport_file);
		$result->classification_id			= $travel->classification_id;
		$result->classification_name		= $classification;
		$result->address					= $permanent->address;
		$result->country_id					= $permanent->country_id;
		$result->country_name				= $country->name;
		$result->country_flag				= GTHelper::verifyFile(GT_SAFETRAVEL_FLAG_URI.strtolower($country->code).'.png');
		$result->city_id					= $permanent->city_id;
		$result->city						= $city;
		$result->postcode					= $permanent->postcode;
		$result->phone						= $permanent->phone;
		$result->start_date					= $destination->start_date;
		$result->end_date					= $destination->end_date;
		$result->duration_year				= $permanent->duration_year;
		$result->duration_month				= $permanent->duration_month;
		$result->visa_number				= $visa->number;
		$result->visa_issued				= GTHelperDate::format($visa->issued_date, 'd-m-Y');
		$result->visa_expired				= GTHelperDate::format($visa->expired_date, 'd-m-Y');
		$result->visa_photo					= GTHelper::verifyFile(GT_SAFETRAVEL_VISA_URI.$client->key.'/'.$visa->number.'/'.$visa->photo);
		$result->permit_photo				= GTHelper::verifyFile(GT_SAFETRAVEL_PERMIT_URI.$client->key.'/'.$id.'/'.$permanent->permit_photo);
		$result->contact_name				= $permanent->contact_name;
		$result->contact_relationship		= $permanent->contact_relationship;
		$result->contact_email				= $permanent->contact_email;
		$result->contact_phone				= $permanent->contact_phone;
		$result->contact_dom_name			= $permanent->contact_dom_name;
		$result->contact_dom_relationship	= $permanent->contact_dom_relationship;
		$result->contact_dom_email			= $permanent->contact_dom_email;
		$result->contact_dom_phone			= $permanent->contact_dom_phone;

		GTHelperAPI::prepareJSON($result);
	}

	public function setTravelWork() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$travel_id			= GTHelper::getInput('travel_id', 0, 'int');
		$activity_id		= GTHelper::getInput('activity_id', 0, 'int');
		$occupation_id		= GTHelper::getInput('occupation_id', 0, 'int');
		$company_name		= GTHelper::getInput('company_name', '', 'raw');
		$company_address	= GTHelper::getInput('company_address', '', 'raw');
		$ktkln				= GTHelper::getInput('ktkln', '', 'raw');
		$pptkis				= GTHelper::getInput('pptkis', '', 'raw');
		$agent_name			= GTHelper::getInput('agent_name', '', 'raw');
		
		// Process Data
		$this->checkSession(true);

		$work = GTHelperDB::getItem('mob_travel_work', 'travel_id = '.intval($travel_id), 'id, travel_id, published');

		$work->travel_id		= $travel_id;
		$work->activity_id		= $activity_id;
		$work->occupation_id	= $occupation_id;
		$work->company_name		= $company_name;
		$work->company_address	= $company_address;
		$work->ktkln			= $ktkln;
		$work->pptkis			= $pptkis;
		$work->agent_name		= $agent_name;
		$work->published		= $work->published ? $work->published : 1;

		// Save travel permanent data
		GTHelperDB::insertItem('mob_travel_work', $work);

		$this->getTravelWork($travel_id);
	}

	public function getTravelWork($travel_id = '') {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$travel_id 		= $travel_id ? $travel_id : GTHelper::getInput('travel_id', '', 'int');

		// Process Data
		$this->checkSession(true);

		$work = GTHelperDB::getItem('mob_travel_work', 'travel_id = '.intval($travel_id));

		if(!$work->id) {
			GTHelperAPI::prepareJSON(false);
		}

		$activity	= GTHelperDB::getItem('ref_activity', array('id = '.intval($work->activity_id)), 'name');
		$occupation	= GTHelperDB::getItem('ref_occupation', array('id = '.intval($work->occupation_id)), 'name');

		$result						= new stdClass();
		$result->travel_id			= $work->travel_id;
		$result->activity_id		= $work->activity_id;
		$result->activity_name		= $activity;
		$result->occupation_id		= $work->occupation_id;
		$result->occupation_name	= $occupation;
		$result->company_name		= $work->company_name;
		$result->company_address	= $work->company_address;
		$result->ktkln				= $work->ktkln;
		$result->pptkis				= $work->pptkis;
		$result->agent_name			= $work->agent_name;

		GTHelperAPI::prepareJSON($result);
	}

	public function setTravelStudy() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$travel_id			= GTHelper::getInput('travel_id', 0, 'int');
		$activity_id		= GTHelper::getInput('activity_id', 0, 'int');
		$degree_id			= GTHelper::getInput('degree_id', 0, 'int');
		$school_name		= GTHelper::getInput('school_name', '', 'raw');
		$school_programme	= GTHelper::getInput('school_programme', '', 'raw');
		$school_duration	= GTHelper::getInput('school_duration', 0, 'int');
		
		// Process Data
		$this->checkSession(true);

		$study = GTHelperDB::getItem('mob_travel_study', 'travel_id = '.intval($travel_id), 'id, travel_id, published');

		$study->travel_id			= $travel_id;
		$study->activity_id			= $activity_id;
		$study->degree_id			= $degree_id;
		$study->school_name			= $school_name;
		$study->school_programme	= $school_programme;
		$study->school_duration		= $school_duration;
		$study->published			= $study->published ? $study->published : 1;

		// Save travel permanent data
		GTHelperDB::insertItem('mob_travel_study', $study);

		$this->getTravelStudy($travel_id);
	}

	public function getTravelStudy($travel_id = '') {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$travel_id 		= $travel_id ? $travel_id : GTHelper::getInput('travel_id', '', 'int');

		// Process Data
		$this->checkSession(true);

		$study = GTHelperDB::getItem('mob_travel_study', 'travel_id = '.intval($travel_id));
		if(!$study->id) {
			GTHelperAPI::prepareJSON(false);
		}

		$activity	= GTHelperDB::getItem('ref_activity', array('id = '.intval($study->activity_id)), 'name');
		$degree		= GTHelperDB::getItem('ref_degree', array('id = '.intval($study->degree_id)), 'name');

		$result						= new stdClass();
		$result->travel_id			= $study->travel_id;
		$result->activity_id		= $study->activity_id;
		$result->activity_name		= $activity;
		$result->degree_id			= $study->degree_id;
		$result->degree_name		= $degree;
		$result->school_name		= $study->school_name;
		$result->school_programme	= $study->school_programme;
		$result->school_duration	= $study->school_duration;

		GTHelperAPI::prepareJSON($result);
	}

	public function setTravelOther() {
		// Get Params
		$access_token		= GTHelper::getInput('access_token', '', 'raw');
		$travel_id			= GTHelper::getInput('travel_id', 0, 'int');
		$activity_id		= GTHelper::getInput('activity_id', 0, 'int');
		$description		= GTHelper::getInput('description', '', 'raw');
		
		// Process Data
		$this->checkSession(true);

		$other = GTHelperDB::getItem('mob_travel_other', 'travel_id = '.intval($travel_id), 'id, travel_id, published');

		$other->travel_id	= $travel_id;
		$other->activity_id	= $activity_id;
		$other->description	= $description;
		$other->published	= $other->published ? $other->published : 1;

		// Save travel permanent data
		GTHelperDB::insertItem('mob_travel_other', $other);

		$this->getTravelOther($travel_id);
	}

	public function getTravelOther($travel_id = '') {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$travel_id 		= $travel_id ? $travel_id : GTHelper::getInput('travel_id', '', 'int');

		// Process Data
		$this->checkSession(true);

		$other = GTHelperDB::getItem('mob_travel_other', 'travel_id = '.intval($travel_id), 'id, travel_id, activity_id, description');
		if(!$other->id) {
			GTHelperAPI::prepareJSON(false);
		}

		$activity 	= GTHelperDB::getItem('ref_activity', array('id = '.intval($other->activity_id)), 'name');

		$result					= new stdClass();
		$result->travel_id		= $other->travel_id;
		$result->activity_id	= $other->activity_id;
		$result->activity_name	= $activity;
		$result->description	= $other->description;

		GTHelperAPI::prepareJSON($result);
	}

	public function getActivities() {
		// Get Params
		$classification_id = GTHelper::getInput('classification_id', '', 'int');

		// Process Data
		$offset	= ($page - 1) * $limit;
		$activities = GTHelperDB::getItems('ref_activity a', 'a.published = 1 AND a.classification_id = '.$classification_id, 
			'a.id activity_id, a.code activity_code, a.name activity_name', ''
		);

		GTHelperAPI::prepareJSON($activities);
	}

	public function getOccupations() {
		// Get Params
		$activity_id = GTHelper::getInput('activity_id', '', 'int');

		// Process Data
		$offset	= ($page - 1) * $limit;
		$occupations = GTHelperDB::getItems('ref_occupation a', 'a.published = 1 AND a.activity_id = '.$activity_id, 
			'a.id occupation_id, a.code occupation_code, a.name occupation_name', ''
		);

		GTHelperAPI::prepareJSON($occupations);
	}

	public function getDegrees() {
		// Process Data
		$degrees = GTHelperDB::getItems('ref_degree a', 'a.published = 1', 
			'a.id degree_id, a.name degree_name, a.short_name degree_short_name', ''
		);

		GTHelperAPI::prepareJSON($degrees);
	}

	public function setPlace() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$category_id 	= GTHelper::getInput('category_id', '1', 'int');
		$place_id 		= GTHelper::getInput('place_id', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$google			= GTHelperGeo::detail($place_id, 'en');

		$categories = GTHelperDB::getItem('ref_place_category', 'id = '.$category_id, 'types');
		$categories = explode(',', $categories);

		if(!$google->gid) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_API_PLACE_ID_INVALID'));
		}

		if(!in_array($google->type, $categories)) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_API_PLACE_TYPE_INVALID'));
		}

		$distance	= '
			(3959 * ACOS(
				COS(RADIANS(a.latitude)) * COS(RADIANS('.$google->latitude.')) * 
				COS(RADIANS('.$google->longitude.') - RADIANS(a.longitude)) + 
				SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$google->latitude.'))
			))
		';

		$wheres = array(
			'a.published = 1', 
			'a.latitude IS NOT NULL', 
			'a.longitude IS NOT NULL',
			$google->latitude.' BETWEEN a.south AND a.north',
			$google->longitude.' BETWEEN a.west AND a.east'
		);

		$city = GTHelperDB::getItems('ref_adm_city a', $wheres,
			'', '1', '', array(array($distance, 'ASC'))
		);
		$city = reset($city);

		if(!@$city->id) {
			array_pop($wheres);
			array_pop($wheres);

			$city = GTHelperDB::getItems('ref_adm_city a', $wheres,
				'', '1', '', array(array($distance, 'ASC'))
			);
			$city = reset($city);
		}
		
		$place				= GTHelperDB::getItem('mob_place', 'gid = "'.$google->gid.'"');
		$place->category_id	= $category_id;
		$place->client_id	= $place->id ? $place->client_id : $client_id;
		$place->gid			= $google->gid;
		$place->name		= $google->name;
		$place->rating 		= $google->rating;
		$place->address		= $google->address;
		$place->latitude	= $google->latitude;
		$place->longitude	= $google->longitude;
		$place->city_id		= $city->id;
		$place->country_id 	= $city->country_id;
		$place->type 		= $google->type;
		$place->types 		= implode(',', $google->types);
		$place->url 		= $google->url;
		$place->phone 		= $google->phone;
		$place->website 	= $google->website;
		$place->price_level	= $google->price_level;
		$place->published	= 1;
		
		$place_id	= GTHelperDB::insertItem('mob_place', $place);

		$hourIDs = GTHelperDB::getItems('mob_place_hour a', 'a.place_id = '.$place_id, 'id');
		foreach ($google->hours as $k => &$hour) {
			$hourID = @$hoursIDs[$k];
			if($hourID) {
				unset($hourIDs[$k]);
			}

			list($day, $open, $close) = $hour;
			$hour			= new stdClass();
			$hour->id		= $hourID;
			$hour->place_id	= $place_id;
			$hour->day		= $day;
			$hour->open		= $open;
			$hour->close	= $close;

			GTHelperDB::insertItem('mob_place_hour', $hour);
		}

		foreach ($hourIDs as $hourID) {
			$hour			= new stdClass();
			$hour->id		= $hourID;
			$hour->place_id	= $place_id;
			$hour->day		= '';
			$hour->open		= '';
			$hour->close	= '';

			GTHelperDB::insertItem('mob_place_hour', $hour);
		}
		
		foreach ($google->photos as &$photo) {
			list($ref, $url, $title) = $photo;
			$ref	= GTHelperGeo::photoUrl($ref);
			$refID	= GTHelperDB::getItem('mob_place_photo', 'url = "'.$ref.'"', 'id');
			$desc	= array($title, $url);
			$desc	= array_filter($desc);
			$desc	= implode(PHP_EOL, $desc);

			$photo				= new stdClass();
			$photo->id 			= $refID;
			$photo->place_id	= @$place_id;
			$photo->url			= $ref;
			$photo->description = $desc;
			$photo->is_external	= 1;
			$photo->published 	= 1;
			GTHelperDB::insertItem('mob_place_photo', $photo);
		}

		$this->getPlace($place_id);
	}

	public function getContinents() {
		// Process Data
		$continents = GTHelperDB::getItems('ref_adm_continent a', '', 
			'id continent_id, code continent_code, name continent_name', ''
		);

		GTHelperAPI::prepareJSON($continents);
	}

	public function getCountriesByContinent() {
		// Get Params
		$continent_ids = GTHelper::getInput('continent_ids', '1');

		// Process Data
		$db = JFactory::getDbo();
		$continent_ids = explode(',', $continent_ids);
		$continent_ids = array_map(array($db, 'quote'), $continent_ids);
		$continent_ids = implode(',', $continent_ids);

		$countries = GTHelperDB::getItems('ref_adm_country a', 'a.published = 1 AND a.continent_id IN ('.$continent_ids.')', 
			'a.id country_id, a.code country_code, a.name country_name, b.name country_capital_name, b.latitude country_capital_latitude, b.longitude country_capital_longitude', '',
			array(array('ref_adm_city b', 'IFNULL(a.capital_city_id, a.vice_city_id) = b.id'))
		);

		foreach ($countries as &$country) {
			$country->country_flag = GTHelper::verifyFile(GT_MEDIA_URI.'/flag/'.strtolower($country->country_code).'.png');
		}

		GTHelperAPI::prepareJSON($countries);
	}

	public function searchUser() {
		// Get Params
		$query	= GTHelper::getInput('query');
		$page	= GTHelper::getInput('page', '1', 'int');
		$limit	= GTHelper::getInput('limit', '100', 'int');

		// Process Data
		$offset	= ($page - 1) * $limit;
		$users	= GTHelperDB::getItems('sys_client a', array('a.published = 1', 'a.name LIKE "%'.$query.'%"', 'a.email LIKE "%'.$query.'%"', 'a.username LIKE "%'.$query.'%"'), 
			'a.key user_id, a.name user_name, a.username user_username, a.email user_email, a.photo user_photo', $limit.','.$offset
		);

		foreach ($users as &$user) {
			$user->user_photo = GTHelper::verifyFile(GT_SAFETRAVEL_PROFILE_URI.'/'.$user->user_id.'/'.$user->user_photo);
		}

		GTHelperAPI::prepareJSON($users);
	}

	public function setChatPrivacy() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$is_active 		= GTHelper::getInput('is_active', '1', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client = GTHelperDB::getItem('sys_client', 'key = "'.$user_id.'"', 'id, username, chat_available');
		if(!$client->id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_USER_INVALID'));
		}

		// Set Status
		$client->chat_available = $is_active;
		GTHelperDB::insertItem('sys_client', $client);

		GTHelper::loadCurl(GTHelperAPI::chatUrl().'/auth/update/status', array(
			'access_token' => $access_token,
			'user_status' => $is_active
		), true);

		GTHelperAPI::prepareJSON(true, $is_active ? JText::_('COM_GTSAFETRAVEL_API_CHAT_ACTIVATED') : JText::_('COM_GTSAFETRAVEL_API_CHAT_DEACTIVATED'));
	}

	public function getPointHistory() {
		// Get Params
		$user_id	= GTHelper::getInput('user_id', '', 'raw');
		$page		= GTHelper::getInput('page', '1', 'int');
		$limit		= GTHelper::getInput('limit', '10', 'int');

		// Process Data
		$client_id	= GTHelperAPI::clientKeyToID($user_id);
		if(!$client_id) {
			GTHelperAPI::prepareJSON(false, JText::_('COM_GTSAFETRAVEL_CLIENT_USER_INVALID'));
		}

		$offset		= ($page - 1) * $limit;
		$city		= GTHelperDB::aggregate('sys_client_location', 'datetime', 'max', 'client_id = '.$client_id);
		$city		= GTHelperDB::getItem('sys_client_location', array('client_id = '.$client_id, 'datetime = "'.$city.'"'), 'city_id');
		$city		= GTHelperDB::getItem('ref_adm_city', 'id = '.intval($city));
		
		if($city->utc_offset) {
			$utc_offset	= intval($city->utc_offset);
			$utc_offset	= round($utc_offset/60) * 60;
		} else {
			$utc_offset = 0;
		}
		
		$points		= GTHelperDB::getItems('mob_api_point a', array('a.client_id = '.$client_id, 'b.point > 0'), 
			'b.description activity, b.point, a.created', $limit.','.$offset,
			'ref_api b, a.api_id = b.id', 'a.created DESC'
		);

		foreach ($points as &$point) {
			$point->created = GTHelperDate::format($point->created, 'd/m/Y H:i', false, $utc_offset);
		}

		GTHelperAPI::prepareJSON($points);
	}

	public function getInfoPoints() {
		// Process Data
		$points = GTHelperDB::getItems('ref_api a', 'a.point > 0', 
			'a.description point_name, a.point point_value, a.repeat point_repeatable', ''
		);

		foreach ($points as &$point) {
			$point->point_value			= intval($point->point_value);
			$point->point_repeatable	= $point->point_repeatable == 1;
		}

		GTHelperAPI::prepareJSON($points);
	}

	public function getChecklistAll() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$destinations 	= GTHelperDB::getItems('mob_travel_destination a', array('a.start_date > "'.GTHelperDate::format('now', 'Y-m-d').'"', 'c.client_id = '.$client_id), 'a.travel_id, a.id destination_id, b.name country, a.start_date, a.end_date', '', array('ref_adm_country b, a.country_id = b.id', 'mob_travel c, a.travel_id = c.id'));

		GTHelperAPI::prepareJSON($destinations);
	}

	public function getChecklistDetail() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$travel_id		= GTHelper::getInput('travel_id', '', 'int');
		$destination_id	= GTHelper::getInput('destination_id', '', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$travel_id		= GTHelperDB::getItem('mob_travel', array('id = '.$travel_id, 'client_id = '.$client_id), 'id');
		$destination	= GTHelperDB::getItem('mob_travel_destination', array('id = '.$destination_id, 'travel_id = '.$travel_id), 'id, checklist, visa_id, visa_number');
		$checklist		= explode(',', $destination->checklist);
		$refChecklist	= GTHelperDB::getItems('ref_checklist', 'published = 1', 'id checklist_id, name, description');

		// Add Passport to Checklist
		$client = GTHelperAPI::getClient($client_id);
		if($client->passport_number) {
			array_push($checklist, 1);
		} else {
			$passport_index = array_search(1, $checklist);
			unset($checklist[$passport_index]);
		}

		if($destination->visa_id || $destination->visa_number) {
			array_push($checklist, 2);
		} else {
			$visa_index = array_search(2, $refChecklist);
			unset($refChecklist[$visa_index]);
			$refChecklist = array_values($refChecklist);
		}

		foreach ($refChecklist as $cl) {
			$cl->checked	= in_array($cl->checklist_id, $checklist);
			$cl->locked		= in_array($cl->checklist_id, array(1,2));
		}
		
		if($destination->id) {
			GTHelperAPI::prepareJSON($refChecklist);
		} else {
			GTHelperAPI::prepareJSON(false);
		}
	}

	public function setChecklistItem() {
		// Get Params
		$access_token	= GTHelper::getInput('access_token', '', 'raw');
		$travel_id		= GTHelper::getInput('travel_id', '', 'int');
		$destination_id	= GTHelper::getInput('destination_id', '', 'int');
		$checklist_id	= GTHelper::getInput('checklist_id', '', 'int');
		$checked 		= GTHelper::getInput('checked', '', 'int');

		// Process Data
		$this->checkSession(true);
		list(,$user_id) = explode('|', $access_token.'|');

		$client_id		= GTHelperAPI::clientKeyToID($user_id);
		$travel_id		= GTHelperDB::getItem('mob_travel', array('id = '.$travel_id, 'client_id = '.$client_id), 'id');
		$destination	= GTHelperDB::getItem('mob_travel_destination', array('id = '.$destination_id, 'travel_id = '.$travel_id), 'id, checklist, visa_id, visa_number');
		$checklist		= explode(',', $destination->checklist);

		if($checked == 1) {
			array_push($checklist, $checklist_id);
			$checklist = array_unique($checklist);
		} else {
			$checklist_index = array_search($checklist_id, $checklist);
			unset($checklist[$checklist_index]);
			$checklist = array_values($checklist);
		}

		$destination->checklist = implode(',', $checklist);
		if($destination->id) {
			GTHelperDB::insertItem('mob_travel_destination', $destination);
			GTHelperAPI::prepareJSON(true, JText::_($checked == 1 ? 'COM_GTSAFETRAVEL_CLIENT_CHECKLIST_ADD_SUCCESS' : 'COM_GTSAFETRAVEL_CLIENT_CHECKLIST_DEL_SUCCESS'));
		} else {
			GTHelperAPI::prepareJSON(false);
		}
	}
}
