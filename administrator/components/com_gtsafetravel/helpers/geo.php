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

class GTHelperGeo {
	public static function splitLocation($loc) {
		list($latitude, $longitude) = explode(',', $loc.',');

		$location				= new stdClass();
		$location->latitude		= floatval($latitude);
		$location->longitude	= floatval($longitude);

		return $location;
	}

	public static function convDMStoDEC($deg,$min,$sec) {
		// Converts DMS ( Degrees / minutes / seconds ) 
		// to decimal format longitude / latitude
		return $deg+((($min*60)+($sec))/3600);
	}    

	public static function convDECtoDMS($dec, $type = 1, $round = 2) {
		$vars = explode(".",$dec);
		$deg = abs($vars[0]);
		$tempma = "0.".$vars[1];

		$tempma = $tempma * 3600;
		$min = floor($tempma / 60);
		$sec = $tempma - ($min*60);
		$sec = round($sec, $round);

		$dirLat = $dec < 0 ? 'N' : 'S';
		$dirLon = $dec < 0 ? 'W' : 'E';
		$dir 	= $type == 1 ? $dirLat : $dirLon;

		$dms = sprintf('%s° %s′ %s″ %s', $deg, $min, $sec, $dir);
		return $dms;
	}   

	protected static function formatGeocode($gmapData, $admLevel = 3, $country = '', $url = '') {
		$status 	= $gmapData->status;
		$gmapData	= (array) @$gmapData->results;
		$gmapData 	= reset($gmapData);
		$addComps	= array_reverse((array) @$gmapData->address_components);

		$place					= new stdClass();
		$place->id 				= null;
		$place->name 			= null;
		$place->adms 			= array();
		$place->code			= @$gmapData->place_id;
		$place->code 			= $place->code ? ($admLevel.'|'.$place->code) : null;
		$place->country			= null;
		$place->country_code	= $country;
		$place->postal_code		= null;
		$place->extra_loc		= false;
		$place->address			= @$gmapData->formatted_address;
		$place->latitude		= @$gmapData->geometry->location->lat;
		$place->longitude		= @$gmapData->geometry->location->lng;
		$place->url 			= $url;
		$place->status 			= $status;

		$loc = new stdClass();
		
		$adms	= array();
		$loc 	= null;
		foreach ($addComps as $addComp) {
			$sName 	= $addComp->short_name;
			$lName 	= $addComp->long_name;
			$types	= $addComp->types;
			$type 	= reset($types);

			switch ($type) {
				case 'administrative_area_level_1':
				case 'administrative_area_level_2':
				case 'administrative_area_level_3':
					$level = str_replace('administrative_area_level_', '', $type);
					$adms[$level] = $lName;
					break;
				case 'locality':
				case 'colloquial_area':
					$loc = $lName;
					$place->$type = $lName;
					break;
				default:
					if($type) {
						$place->$type = $lName;
					}
					break;
			}
		}
		ksort($adms);
		$admLvs	= array_keys($adms);
		$minLv	= reset($admLvs);
		$maxLv	= end($admLvs);
		if($minLv > 1) {
			$newAdms = array();
			foreach ($adms as $admLv => $adm) {
				$newAdms[$admLv-1] = $adm;
			}
			$adms = $newAdms;
			$maxLv--;
		}

		$adms	= array_filter($adms, function($v,$k) { return $k < 3; }, ARRAY_FILTER_USE_BOTH);
		$maxLv	= $maxLv ? $maxLv : $admLevel-1;
		if($loc && $maxLv < 3) {
			$maxLv++;
			$adms[$maxLv] = $loc;
			$place->extra_loc = true;
		}
		if(!$adms) {
			$maxLv++;
			$adms[$maxLv] = $place->country;
			$place->extra_loc = true;
		}

		$place->adms = $adms;
		$place->name = @$adms[$admLevel];
		$place->name = $place->name ? $place->name : end($adms);

		//echo "<pre>"; print_r($place); echo "</pre>";
		return $place;
	}

	public static function getGmapKey() {
		//return 'AIzaSyDghYZAmm7a1A7NPO_XvNAlepj7laOUXIk';
		return 'AIzaSyAx-MASmJcNi4VGp-kBRg-3VYueYrtd64M';
		//return 'AIzaSyBuLAVN10X9ZM_JBK8OWJfO04mEMTgb3aY';
	}

	public static function geocodeLocation($loc, $lang = 'en', $level = 3, $country = '') {
		$gmapKey 	= self::getGmapKey();
		$gmapUrl	= sprintf('https://maps.googleapis.com/maps/api/geocode/json?key=%s&latlng=%s&language=%s', $gmapKey, $loc, $lang);
		$gmapUrl 	.= $country ? '&components=country:'.$country : null;
		$gmapData 	= GTHelper::curlJSON($gmapUrl);

		if($gmapData->status == 'ZERO_RESULTS') {
			$gmapUrl 	= str_replace('&language='.$lang, '', $gmapUrl);
			$gmapData	= GTHelper::curlJSON($gmapUrl);
		}

		//echo "<pre>"; print_r($gmapUrl); echo "</pre>";
		return self::formatGeocode($gmapData, $level, $country, $gmapUrl);
	}

	public static function geocodeAddress($components, $lang = 'en', $level = 3) {
		$components	= is_string($components) ? explode(',', $components) : $components;
		$components = is_object($components) ? JArrayHelper::fromObject($components) : $components;
		$components = is_array($components) ? $components : array($components);
		$components = array_unique($components);
		$country 	= array_pop($components);
		$address 	= reset($components);

		$components = array_slice($components, -2, 2);
		$components	= 'administrative_area:'.implode('|administrative_area:', $components);
		$countryPx 	= 'country:'.$country.'|'; 

		$gmapKey 	= self::getGmapKey();
		$gmapUrl 	= 'https://maps.googleapis.com/maps/api/geocode/json?key=%s&address=%s&components=%s&language=%s';
		$gmapUrl	= sprintf($gmapUrl, $gmapKey, $address, $countryPx.$components, $lang);
		$gmapData 	= GTHelper::curlJSON($gmapUrl);

		if($gmapData->status == 'ZERO_RESULTS') {
			$gmapUrl	= str_replace($country == 'PS' ? $countryPx : $components, '', $gmapUrl);
			$gmapUrl 	= str_replace('&language='.$lang, '', $gmapUrl);
			$gmapData	= GTHelper::curlJSON($gmapUrl);
		}

		return self::formatGeocode($gmapData, $level, $country, $gmapUrl);
	}

	public static function countDistance($location1, $location2, $round = 2) {
		$loc2	= $location2 ? $location2 : $location1;
		$loc1	= self::splitLocation($location1);
		$loc2	= self::splitLocation($loc2);

		return round(3959 * acos(
			cos(deg2rad($loc1->latitude)) * cos(deg2rad($loc2->latitude)) * 
			cos(deg2rad($loc2->longitude) - deg2rad($loc1->longitude)) + 
			sin(deg2rad($loc1->latitude)) * sin(deg2rad($loc2->latitude))
		), $round);
	}

	public static function getGeonameByID($geoname_id) {		
		$url 	= 'http://api.geonames.org/getJSON?username=itisrama&geonameId='.intval($geoname_id);
		$url 	= sprintf($url, $level, $north, $south, $east, $west);
		$data 	= GTHelper::curlJSON($url);
		$data 	= (array) @$data->geonames;
		$data 	= reset($data);
		return $data;
	}

	public static function getGeonameByLocation($location, $level = 1, $country = '', $name = '') {
		$levels = array(
			array('ADM1'),
			array('ADM2'),
			array('ADM2','ADM3','PPLC','PPLA2','PPLA3','PPLA4','PPL','PPLF','PPLG','PPLL','PPLX','PPLR','PPLS','STLMT')
		);

		$location	= self::splitLocation($location);
		$latitude	= round($location->latitude, $level);
		$longitude	= round($location->longitude, $level);
		
		$acc 	= pow(10, ($level-1) * -1);
		$acc	= $acc * 5;
		$north	= $latitude + $acc;
		$south	= $latitude - $acc;
		$east	= $longitude + $acc;
		$west	= $longitude - $acc;
		$fcs	= $levels[$level-1];
		$fcodes	= implode('&featureCode=', $fcs);

		$max 	= $level > 2 ? 10 : 5;
		$url 	= 'http://api.geonames.org/searchJSON?style=MEDIUM&featureCode=%s&north=%s&south=%s&east=%s&west=%s&maxRows=%s&username=itisrama&orderby=relevance';
		$url 	= sprintf($url, $fcodes, $north, $south, $east, $west, $max);
		$url 	.= $country ? '&country='.$country : null;
		$data 	= GTHelper::curlJSON($url);
		$data 	= (array) @$data->geonames;
		$results	= array();
		$fcs		= array_flip($fcs);
		$name		= GTHelper::translitLatin($name);

		foreach ($data as $k => $dt) {
			$match = 0;
			if($name) {
				$dtName = GTHelper::translitLatin($dt->name);
				$dtName2 = GTHelper::translitLatin(@$dt->adminName1);

				similar_text($name, $dtName, $match1);
				similar_text($dtName, $name, $match2);

				$match += $match1;
				$match += $match2;
				if($dtName2) {
					similar_text($name, $dtName2, $match1);
					similar_text($dtName2, $name, $match2);

					$match += $match1;
					$match += $match2;
				}
			}

			$key = sprintf('%02d', $match);
			$key .= sprintf('%02d', 14 - $fcs[$dt->fcode]);
			$key .= sprintf('%02d', $max - $k);
			$key .= sprintf('%09d', $dt->population);
			$results[$key] = $dt;
		}
		krsort($results);
		$results = reset($results);
		return $results;
	}
}
