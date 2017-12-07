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
	public $startTime;

	public function __construct($config = array()) {
		parent::__construct($config);

		$this->input->set('tmpl', 'raw');

		$this->startTime = microtime(true);

		$task = $this->input->get('task');

		if(!method_exists($this, $task)) {
			$notif = new stdClass();
			$notif->code = 404;
			$notif->message = JText::_('COM_GTSAFETRAVEL_SERVICE_NOT_FOUND');
			$this->prepareJSON(null, $notif);
		}
	}

	protected function prepareJSON($data = null, $obj = null) {
		$isData = count($data) || !is_null($data);

		$json 				= new stdClass();
		$json->status		= $isData ? 1 : 0;
		$json->code			= $isData ? 200 : 204;
		$json->message		= $isData ? null : JText::_('COM_GTSAFETRAVEL_SERVICE_EMPTY');
		$json->count 		= count($data);
		$json->result		= $isData ? $data : null;
		$json->generated	= round(microtime(true) - $this->startTime, 2);
		$json->serverTime	= time();

		if(is_object($obj)) {
			foreach ($obj as $kObj => $vObj) {
				$json->$kObj = $vObj;
			}
		}

		header('Content-type: application/json; charset=utf-8');
		$json = GTHelper::encodeJSON($json);

		echo $json;

		$this->app->close();
	}

	public function test() {
		$countries = GTHelperDB::getItems(
			'ref_country a', 'a.code <= "AU"', 
			'a.code, a.name, a.name_en', 
			array(
				'ref_country_indicator b, a.geoname_id = b.id'
			),
			'a.code desc, b.indicator_id'
		);
		echo "<pre>"; print_r($countries); echo "</pre>";
	}

	public function importAdms() {
		$model		= $this->getModel();
		$countries = $model->getCountries(true);
		//echo "<pre>"; print_r($countries); echo "</pre>"; die;

		foreach ($countries as $country) {
			$location1 = null;
			$location2 = null;
			
			if($country->capital_city) {
				$this->input->set('address', $country->capital_city.','.$country->code); 
				$location = $this->getLocation(false);
				$country->capital_adm_id = $location->id;

			}
			if($country->largest_city) {
				$this->input->set('address', $country->largest_city.','.$country->code); 
				$location2 = $this->getLocation(false);
				$country->largest_adm_id = $location2->id;
			}

			$isCapital = !@$location->status;
			$isLargest = !@$location2->status;

			if(@$location->status || @$location2->status) {
				echo "<pre>"; print_r($country); echo "</pre>";
				echo "<pre>"; print_r(@$location); echo "</pre>";
				echo "<pre>"; print_r(@$location2); echo "</pre>";
				$this->app->close();
			} else {
				$model->saveExternal($country, 'ref_country');
			}
		}
	}
	
	public function countryGetCountryLists() {
		$model		= $this->getModel();
		$countries	= $model->getCountries();

		$this->prepareJSON($countries);
	}

	public function countryGetEmbassyList() {
		$model		= $this->getModel();
		$embassies	= $model->getCountryEmbassies();

		if($embassies) {
			$result				= new stdClass();
			$result->embassies	= $embassies;
			$embassies 			= $result;
		}

		$this->prepareJSON($embassies);
	}

	public function countryGetCountryDetail() {
		$model = $this->getModel();
		$country = $model->getCountryDetail();

		$this->prepareJSON($country);
	}

	public function countryGetSliders() {
		$model = $this->getModel();
		$sliders = $model->getCountrySliders();

		$this->prepareJSON($sliders);
	}

	public function getGeoname() {
		$location	= $this->input->get('location', '0,0', 'raw');
		$level 		= $this->input->get('level', 1, 'int');
		$geoname 	= GTHelperGeo::getGeonameByLocation($location, $level);

		$this->prepareJSON($geoname);
	}

	public function getLocation($json = true) {
		// Variables
		$user_id	= $this->input->get('user_id', 0, 'int');
		$location	= $this->input->get('location', '0,0', 'raw');
		$register 	= $this->input->get('register', 1, 'int');
		$share 		= $this->input->get('share', 1, 'int');
		$address 	= $this->input->get('address', '', 'raw');

		$model		= $this->getModel();
		$languages 	= array(
			'ar','bg','bn','ca','cs','da','de','el','en','es','eu',
			'eu','fa','fi','fil','fr','gl','gu','hi','hr','hu','it',
			'iw','ja','kn','ko','lt','lv','ml','mr','nl','no','pl',
			'pt','ro','ru','sk','sl','sr','sv','ta','te','th','tl',
			'tr','uk','vi'
		);

		// Get Data
		$model	= $this->getModel();
		if($address) {
			$city		= GTHelperGeo::geocodeAddress($address, 'en', 3);
			$locItem	= $city;
		} else {
			$city		= GTHelperGeo::geocodeLocation($location, 'en');
			$locItem	= GTHelperGeo::splitLocation($location);
		}

		if(!$city->code) {
			return $json ? $this->prepareJSON($city) : $city;
		}

		$locCurrent	= round($locItem->latitude, 6).','.round($locItem->longitude, 6);
		$locSearch	= floatval(round($locItem->latitude/0.05)*0.05).','.floatval(round($locItem->longitude/0.05)*0.05);
		$locSys		= $model->getItemExternal($locSearch, 'sys_location_log', 'location');
		//$locSys	= $model->sanitizeItem(null, 'sys_location_log', true);
		$locClient	= $model->getItemExternal($user_id, 'sys_location_client', 'client_id');
		$dist 		= GTHelperGeo::countDistance($locCurrent, $locSearch);
		
		$admName = @$city->adm ? $city->adm : $locSys->adm;
		if($locSys->id && $admName == $locSys->adm) {
			$locSys->country	= json_decode($locSys->country);
			$locSys->adm1		= json_decode($locSys->adm1);
			$locSys->adm2		= json_decode($locSys->adm2);
			$locSys->adm3		= json_decode($locSys->adm3);
			unset($locSys->created);
			unset($locSys->modified);
		} else {
			$country	= $model->getItemExternal($city->country_code, 'ref_country', 'code');
			$adms		= $city->adms;
			$adms 		= array_filter($adms);
			$admLvs 	= array_keys($adms);
			$maxAdmLv	= end($admLvs);
			$admGeo		= $model->searchAdm($city->country_code, $adms[$maxAdmLv], @$adms[$maxAdmLv-1], $maxAdmLv);
			$locAge		= $admGeo->modified ? $admGeo->modified : $admGeo->created;
			$locAge		= $locAge ? time() - strtotime($locAge) : 0;
			$locLimit	= 86400 * 150; // Valid for 150 days

			if($locAge > $locLimit || !$admGeo->id) {
				$admDts		= array();
				$parentIDs	= array();
				foreach ($adms as $admLv => &$adm) {
					$admTyp		= $admLv == $maxAdmLv && $city->extra_loc ? 'locality' : 'administrative';
					$address	= array_slice($adms, 0, $admLv);
					$address 	= array_reverse($address);
					$address[]	= $city->country_code;
					$admGeo		= GTHelperGeo::geocodeAddress($address, 'en', $admLv);
					$admDtPrev	= $model->getItemExternal($admGeo->code, 'ref_country_adm', 'code');
					$admLvID	= $admDtPrev->id;

					if(!$admGeo->code) {
						return $json ? $this->prepareJSON($admGeo) : $admGeo;
					}

					$admDt 					= new stdClass();
					$admDt->id 				= $admLvID;
					$admDt->code			= $admGeo->code;
					$admDt->country_id		= $country->id;
					$admDt->country_code	= $admGeo->country_code;
					$admDt->geoname_id		= $admDtPrev->geoname_id;
					$admDt->name			= $admDtPrev->name;
					$admDt->name_en			= $admGeo->name;
					$admDt->name_loc		= $admDtPrev->name;
					$admDt->address			= $admGeo->address;
					$admDt->level			= $admLv;
					$admDt->latitude		= $admGeo->latitude;
					$admDt->longitude		= $admGeo->longitude;
					$admDt->postal_code		= $admGeo->postal_code;
					$admDt->type 			= $admTyp;

					if(!$admDt->geoname_id && $admLv != $maxAdmLv) {
					//if(false) {
						$geoname = GTHelperGeo::getGeonameByLocation($admGeo->latitude.','.$admGeo->longitude, $admLv, $admDt->country_code, $admDt->name_en);
						$admDt->geoname				= @$geoname->name;
						$admDt->geoname_id			= @$geoname->geonameId;
						$admDt->geoname_level		= @$geoname->fcode;
						$admDt->geoname_population	= @$geoname->population ? $geoname->population : null;
					}
					
					if(!$admDtPrev->id) {
					//if(false) {
						$admGeo			= GTHelperGeo::geocodeAddress($address, 'id', $admLv);
						$admDt->name	= $admGeo->name ? $admGeo->name : $admDt->name;
						
						if(!$admGeo->code) {
							return $json ? $this->prepareJSON($admGeo) : $admGeo;
						}

						if(in_array($country->language, $languages)) {
							$admGeo				= GTHelperGeo::geocodeAddress($address, $country->language, $admLv);
							$admDt->name_loc	= $admGeo->name ? $admGeo->name : $admDt->name_loc;
							$admDt->name_loc	= $admDt->name_loc == $admDt->name_en ? null : $admDt->name_loc;

							if(!$admGeo->code) {
								return $json ? $this->prepareJSON($admGeo) : $admGeo;
							}
						}
					}

					foreach ($admDts as $parentLv => $parentDt) {
						$parentLvID			= $parentLv.'_id';
						$admDt->$parentLv	= $parentDt->name_en;
						$admDt->$parentLvID	= $parentDt->id;
					}

					$admDt = $model->saveExternal($admDt, 'ref_country_adm', 'code');
					$admLv = 'adm'.$admLv;
					
					$admDts[$admLv]	= $admDt;
				}
			} else {
				$admDts	= array();
				$adms	= array_reverse($adms);
				$adm 	= array_pop($adms);
				$admLv 	= $admGeo->level-1;
				$admDts[$admLv] = $admGeo;
				foreach ($adms as $adm) {
					$parentLv	= $admGeo->level-1;
					$parentLv	= 'adm'.$parentLv.'_id';
					$admGeo		= $model->getItemExternal($admGeo->$parentLv, 'ref_country_adm');
					$parentLv	= $admGeo->level-1;
					$admDts[$parentLv] = $admGeo;
				}
				ksort($admDts);
			}

			$loc					= array();
			$loc['id']				= 0;
			$loc['country_id']		= $country->id;
			$loc['country_code']	= $country->code;
			$loc['country']['code']		= $country->code;
			$loc['country']['name']		= $country->name;
			$loc['country']['name_en']	= $country->name_en;
			$loc['country']['official_name']	= $country->official_name;
			$loc['country']['official_name_en']	= $country->official_name_en;

			$loc['adm_id']	= 0;
			$loc['adm1_id']	= 0;
			$loc['adm2_id']	= 0;
			$loc['adm3_id']	= 0;
			
			$loc['adm1'] = null;
			$loc['adm2'] = null;
			$loc['adm3'] = null;

			$geonameID = 0;
			foreach ($admDts as $admDt) {
				$admLv						= 'adm'.$admDt->level;
				$loc[$admLv.'_id']			= $admDt->id;
				$loc[$admLv]['code']		= $admDt->code;
				$loc[$admLv]['name']		= $admDt->name;
				$loc[$admLv]['name_en']		= $admDt->name_en;
				$loc[$admLv]['name_loc']	= $admDt->name_loc;

				$geonameID = $admDt->geoname_id ? $admDt->geoname_id : $geonameID;
			}

			$adm	= end($admDts);
			$city	= $register ? $city : $adm;

			$loc['id']			= $adm->id;
			$loc['city']		= $city->name;
			$loc['adm']			= $adm->name;
			$loc['adm_id']		= $adm->id;
			$loc['address']		= $city->address;
			$loc['postal_code']	= $city->postal_code;
			$loc['latitude']	= $city->latitude;
			$loc['longitude']	= $city->longitude;
			$loc['location']	= $locSearch;
			$loc['geoname_id']	= $geonameID;

			// Convert to Object
			$loc = JArrayHelper::toObject($loc);

			$saveLoc			= clone $loc;
			$saveLoc->id		= $locSys->id;
			$saveLoc->country	= GTHelper::encodeJSON($saveLoc->country);
			$saveLoc->adm1		= $saveLoc->adm1 ? GTHelper::encodeJSON($saveLoc->adm1) : null;
			$saveLoc->adm2		= $saveLoc->adm2 ? GTHelper::encodeJSON($saveLoc->adm2) : null;
			$saveLoc->adm3		= $saveLoc->adm3 ? GTHelper::encodeJSON($saveLoc->adm3) : null;

			$model->saveExternal($saveLoc, 'sys_location_log'); $locSys = $loc;
		}

		$location			= $share ? $locItem : $locSys;
		$locationNew		= $location->latitude.','.$location->longitude;
		$locationOld		= $locClient->latitude.','.$locClient->longitude;
		$distance 			= $locClient->id ? GTHelperGeo::countDistance($locationNew, $locationOld) : 0;

		$saveLoc			= clone $locSys;
		$saveLoc->id 		= $locClient->id;
		$saveLoc->client_id	= $user_id;
		$saveLoc->latitude	= $location->latitude;
		$saveLoc->longitude	= $location->longitude;
		$saveLoc->distance 	= $distance;
		$saveLoc->country	= $saveLoc->country ? GTHelper::encodeJSON($saveLoc->country) : null;
		$saveLoc->adm1		= $saveLoc->adm1 ? GTHelper::encodeJSON($saveLoc->adm1) : null;
		$saveLoc->adm2		= $saveLoc->adm2 ? GTHelper::encodeJSON($saveLoc->adm2) : null;
		$saveLoc->adm3		= $saveLoc->adm3 ? GTHelper::encodeJSON($saveLoc->adm3) : null;
		$locSys->latitude	= floatval(round($location->latitude, 4));
		$locSys->longitude	= floatval(round($location->longitude, 4));

		if($distance > 0 || !$locClient->id) {
			//$model->saveExternal($saveLoc, 'sys_location_client');
		}

		$logCliLog1		= $model->getItemMax($user_id, 'sys_location_client_log', 'client_id', 'modified');
		$locationOld	= $logCliLog1->latitude.','.$logCliLog1->longitude;
		$distance		= $logCliLog1->id ? GTHelperGeo::countDistance($locationNew, $locationOld) : 0;
		if($distance > 5 || !$locClient->id) {
			$logCount	= $logCliLog1->client_key;
			$logCount	= explode(':', $logCount);
			$logCount	= intval(@$logCount[1]);
			$logCount	= $logCount == 100 ? 1 : $logCount+1;
			$clientKey	= $user_id.':'.$logCount;

			$logCliLog2				= $model->getItemExternal($clientKey, 'sys_location_client_log', 'client_key');
			$saveLoc->id			= $logCliLog2->id;
			$saveLoc->client_key	= $clientKey;
			$saveLoc->distance		= $distance;

			//$model->saveExternal($saveLoc, 'sys_location_client_log');
		}

		return $locSys;
	}


}
