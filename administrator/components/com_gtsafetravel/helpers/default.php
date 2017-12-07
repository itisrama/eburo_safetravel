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

class FakeObject {
	public function __call($method, $args) {
		if (isset($this->$method)) {
			$func = $this->$method;
			return call_user_func_array($func, $args);
		}
	}
}

class GTHelper {

	public static function getInfo() {
		$xml = JPATH_COMPONENT_ADMINISTRATOR . DS . 'manifest.xml';
		$xml = JApplicationHelper::parseXMLInstallFile($xml);

		$info = new stdClass();
		$info->name			= $xml['name'];
		$info->type			= $xml['type'];
		$info->creationDate	= $xml['creationdate'];
		$info->creationYear	= array_pop(explode(' ', $xml['creationdate']));
		$info->author		= $xml['author'];
		$info->copyright	= $xml['copyright'];
		$info->authorEmail	= $xml['authorEmail'];
		$info->authorUrl	= $xml['authorUrl'];
		$info->version		= $xml['version'];
		$info->description	= $xml['description'];

		return $info;
	}
	
	public static function pluralize($word) {
		$plural = array(
			array('/(x|ch|ss|sh)$/i', "$1es"),
			array('/([^aeiouy]|qu)y$/i', "$1ies"),
			array('/([^aeiouy]|qu)ies$/i', "$1y"),
			array('/(bu)s$/i', "$1ses"),
			array('/s$/i', "s"),
			array('/$/', "s"));

		// Check for matches using regular expressions
		foreach ($plural as $pattern)
		{
			if (preg_match($pattern[0], $word))
			{
				$word = preg_replace($pattern[0], $pattern[1], $word);
				break;
			}
		}
		return $word;
	}

	public static function recursive_ksort(&$array) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::recursive_ksort($v);
			}
		}
		return ksort($array);
	}

	public static function getMenuId($url) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('id')->from('#__menu')->where($db->quoteName('link') .' = '.$db->quote($url));

		$db->setQuery($query);
		return intval(@$db->loadObject()->id);
	}
	
	
	public static function addSubmenu($vName) {
		$submenus = array(
			'samples'
		);

		foreach ($submenus as $submenu) {
			JHtmlSidebar::addEntry(
				JText::_('COM_GTSAFETRAVEL_PT_'.strtoupper($submenu)),
				'index.php?option=com_gtsafetravel&amp;view='.$submenu,
				$vName == $submenu
			);
		}
		
	}

	public static function cleanstr($str) {
		return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $str));
	}

	public static function fixJSON($str) {
		$str = preg_replace("/(?<!\"|'|\w)([a-zA-Z0-9_]+?)(?!\"|'|\w)\s?:/", "\"$1\":", $str);
		$str = str_replace("'", '"', $str);

		return $str;
	}

	public static function getReferences($pks, $table, $key = 'id', $name = 'name', $published = null, $index = 'id') {
		$pks = GTHelperArray::toArray($pks);

		if(!count($pks) > 0) return array();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		array_walk($pks, array($db, 'quote'));

		foreach ($pks as $k => $pk) {
			$pks[$k] = $db->quote($pk); 
		}

		$query->select($db->quoteName(array('a.'.$name, 'a.'.$key)));
		$query->from($db->quoteName('#__gtsafetravel_'.$table, 'a'));
		$query->where($db->quoteName('a.'.$key) . ' IN (' . implode(',', $pks) . ')');

		if(is_numeric($published)) {
			$query->where($db->quoteName('a.published') . ' = ' . $db->quote($published));
		}

		$db->setQuery($query);
		//echo nl2br(str_replace('#__','eburo_',$query));
		
		$items = $db->loadObjectList($index);

		foreach ($items as &$item) {
			$item = $item->$name;
		}

		return $items ? $items : array();
	}

	public static function getListCount() {
		$params	= func_get_args();
		$db		= JFactory::getDBO();

		$db->setQuery($params[0]);

		if($params[1]) {
			return (int) $db->loadResult();
		}

		$db->execute();
		return (int) $db->getNumRows();
	}

	public static function getURL($params = array()) {
		$urlVars	= array(
			'Itemid'	=> JRequest::getInt('Itemid'),
			'option'	=> JRequest::getCmd('option'),
			'view'		=> JRequest::getCmd('view'),
			'tmpl'		=> JRequest::getCmd('tmpl'),
		);
		if(is_string($params)) {
			$params = explode('&', $params);
			foreach ($params as $strParam) {
				list($urlKey, $urlVar) = explode('=', $strParam);
				$urlVars[$urlKey] = $urlVar;
			}
		} elseif(is_array($params)) {
			foreach ($params as $urlKey => $urlVar) {
				$urlVars[$urlKey] = $urlVar;
			}
		}

		$urlVars = http_build_query(array_filter($urlVars));
		return JRoute::_('index.php?'.$urlVars, false, -1);
	}

	public static function getUserId($val, $key = 'id') {
		// Initialise some variables
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName($key) . ' = ' . $db->quote($val));
		$db->setQuery($query, 0, 1);

		return $db->loadResult();
	}

	public static function httpQuery($query) {
		$query = http_build_query($query, "", "&");
		$query = str_replace(array('%5B', '%5D'), array('[', ']'), $query);
		return $query;
	}

	public static function verifyFile($url, $is_internal = true) {
		if($is_internal) {
			$path = str_replace(JURI::root(), JPATH_BASE.DS, $url);
			$path = str_replace('/', DS, $path);

			return file_exists($path) ? $url : '';
		} else {
			$headers = get_headers($url);
			return stripos($headers[0], "200 OK") ? $url : '';
		}
	}

	public static function curlJSON($url) {
		$url = str_replace(' ', '+', $url);
		$proxy = '';
		$proxyauth = '';
		//$proxy = 'inetgw-proxy:8080';
		//$proxyauth = 'user:password';

		$user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

		$options = array(
			CURLOPT_CUSTOMREQUEST	=>"GET",        //set request type post or get
			CURLOPT_POST			=>false,        //set to GET
			CURLOPT_USERAGENT		=> $user_agent, //set user agent
			CURLOPT_COOKIEFILE		=>"cookie.txt", //set cookie file
			CURLOPT_COOKIEJAR		=>"cookie.txt", //set cookie jar
			CURLOPT_RETURNTRANSFER	=> true,     // return web page
			CURLOPT_HEADER			=> false,    // don't return headers
			CURLOPT_FOLLOWLOCATION	=> true,     // follow redirects
			CURLOPT_ENCODING		=> "",       // handle all encodings
			CURLOPT_AUTOREFERER		=> true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT	=> 120,      // timeout on connect
			CURLOPT_TIMEOUT			=> 120,      // timeout on response
			CURLOPT_MAXREDIRS		=> 10,       // stop after 10 redirects
			CURLOPT_SSL_VERIFYPEER	=> false
		);

		if($proxy) {
			$options[CURLOPT_PROXY] = $proxy;
		}
		if($proxy && $proxyauth) {
			$options[CURLOPT_PROXYUSERPWD] = $proxyauth;
		}
		
		$ch			= curl_init($url);
		curl_setopt_array($ch, $options);
		$content	= curl_exec($ch);
		$error		= curl_errno($ch);
		$errmsg		= curl_error($ch);
		curl_close( $ch );
		return $error > 0 ? false : json_decode($content);
	}

	public static function isEmpty($var) {
		$var = is_float($var) && empty($var) ? 0 : $var;
		return isset($var) ? !($var === "0" || $var === 0 || $var) : true;
	}

	public static function encodeJSON($data) {
		$json = json_encode($data);
		$json = str_replace(':null', ':""', $json);
		return $json;
	}

	public static function translitLatin($str, $removeSpace = false) {
		if(preg_match('/[^\\p{Common}\\p{Latin}]/u', $str)) {
			$str = iconv('UTF-8', 'ISO-8859-1//IGNORE', $str);
		} else {
			$str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
			$str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
			$str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
			$str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
			$str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
			$str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
			$str = preg_replace('/(đ)/', 'd', $str);

			$str = preg_replace('/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/', 'A', $str);
			$str = preg_replace('/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/', 'E', $str);
			$str = preg_replace('/(Ì|Í|Ị|Ỉ|Ĩ)/', 'I', $str);
			$str = preg_replace('/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/', 'O', $str);
			$str = preg_replace('/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/', 'U', $str);
			$str = preg_replace('/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/', 'Y', $str);
			$str = preg_replace('/(Đ)/', 'D', $str);

			$result = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
			$result = $result ? $result : iconv('UTF-8', 'ISO-8859-1//IGNORE', $str);
			$str = $result;
		}

		$space = $removeSpace ? '' : ' ';
		$str = preg_replace('~\x{00a0}~siu', $space, $str);
		$str = preg_replace('/\s/', $space, $str);
		$str = str_replace('  ', ' ', $str);

		return $str;
	}

	public static function agent() {
		return new Mobile_Detect;
	}
}
