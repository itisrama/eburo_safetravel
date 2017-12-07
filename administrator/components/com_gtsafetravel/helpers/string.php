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

class GTHelperStr {

	public static function matchParentheses($str, $type = '( )') {
		list($left,$right)	= explode(' ', $type);
		$delimiter_wrap		= '~';
		$delimiter_left		= $left;
		$delimiter_right	= $right;
		
		$delimiter_left		= preg_quote( $delimiter_left,  $delimiter_wrap );
		$delimiter_right	= preg_quote( $delimiter_right, $delimiter_wrap );
		$pattern			= $delimiter_wrap . $delimiter_left
							. '((?:[^' . $delimiter_left . $delimiter_right . ']++|(?R))*)'
							. $delimiter_right . $delimiter_wrap;

		preg_match_all($pattern, $str, $matches);

		return $matches;
	}

	public static function matchQuotes($str) {
		preg_match_all('~(["\'])([^"\']+)\1~', $str, $matches);
		
		$matches[1] = array_pop($matches);
		return $matches;
	}

	public static function sanitize($text, $stripTags = true, $nbsp = false) {
		if($stripTags) {
			$excps = '<strong><b><i><br><button><li><div><span><input><a><img>';
			$text = strip_tags($text, $excps);
			$text = str_replace(array('<li>','</li>'), array('',','), $text);
			$text = preg_replace("/<br\W*?\/>/", "\n", $text);
			$text = preg_replace("/[\n\r\t]/"," ",$text);
		}

		if(!$nbsp) {
			$text = str_replace('&nbsp;', ' ', $text);
		}
		
		$text = preg_replace('/\s+/u', ' ', $text);
		$text = str_replace(array(', ',' ,',',,',','),array(',',',',',',', '), $text);
		$text = trim($text);
		$text = trim($text, ',');
		$text = str_replace(' :', ': ', $text);

		return $text;
	}

	public static function trim($text, $max = 300) {
		$text = self::sanitize($text);
		if (strlen($text) > $max) {
			$offset = ($max - 3) - strlen($text);
			$text = substr($text, 0, strrpos($text, ' ', $offset)) . '...';
		}
		return $text;
	}
}
