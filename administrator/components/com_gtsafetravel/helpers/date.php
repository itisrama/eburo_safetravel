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

class GTHelperDate {

	static function format($date_str, $format) {
		$avoids = array('', '0000-00-00', '0000-00-00 00:00:00');
		if(in_array(trim($date_str), $avoids)) {
			return null;
		}

		$date = JHtml::date($date_str, $format);
		return $date;
	}

	static function validate($date) {
		$d = DateTime::createFromFormat('Y-m-d', $date);
		return $d && $d->format('Y-m-d') === $date;
	}

	static function getFirstWeekday($month, $year) {
		$unix = mktime(0, 0, 0, $month, 1, $year);
		$num = date("N", $unix);
		if (in_array($num, array(6, 7))) {
			$unix = $unix + ((8-$num) * 24 * 60 * 60);
		}
		return $unix;
	}
	
	static function getLastWeekday($month, $year) {
		$unix = mktime(0, 0, 0, $month, 1, $year);
		$unix = mktime(0, 0, 0, $month, date('t', $unix), $year);
		$num = date("N", $unix);
		if (in_array($num, array(6, 7))) {
			$unix = $unix - (($num-5) * 24 * 60 * 60);
		}
		return $unix;
	}
	static function getYearPeriod($start, $end) {
		$year1 = date('Y', $start);
		$year2 = date('Y', $end);
		
		$period = array();
		$y = $year1;
		while(!($y == $year2+1)) {
			$cur_date = mktime(0,0,0,1,1,$y);
			$timestamp = new stdClass();
			$jom_date = JFactory::getDate($cur_date);
			$timestamp->unix = $cur_date;
			$timestamp->sdate = JText::_('COM_GTSAFETRAVEL_LABEL_YEAR') . ' ' . $jom_date->format('Y', true);
			$timestamp->ldate = JText::_('COM_GTSAFETRAVEL_LABEL_YEAR') . ' ' . $jom_date->format('Y', true);
			$period[] = $timestamp;
			$y++;
		}
		return $period;
		
	}
	static function getMonthPeriod($start, $end) {
		$end2 = strtotime("+1 month", $end);
		list($month1,$year1) = explode('-',date('m-Y',$start));
		list($month2,$year2) = explode('-',date('m-Y',$end2));
		
		$period = array();
		list($m,$y) = array($month1,$year1);
		while(!($m == $month2 && $y == $year2)) {
			$cur_date = mktime(0,0,0,$m,1,$y);
			$timestamp = new stdClass();
			$jom_date = JFactory::getDate($cur_date);
			$timestamp->unix = $cur_date;
			$timestamp->sdate = $jom_date->format('m/Y', true);
			$timestamp->ldate = $jom_date->format('M Y', true);
			$period[] = $timestamp;
			
			$m++;
			if($m>12) {
				$m=1;
				$y++;
			}
		}
		return $period;
	}
	
	static function getWeekPeriod($start, $end) {		
		// Set time elements
		$end2 = strtotime("+1 week", $end);
		list($week1,$month1,$year1) = explode('-',date('W-m-Y',$start));
		list($week2,$month2,$year2) = explode('-',date('W-m-Y',$end2));
		$week_rom = array('I','II','III','IV','V');
		
		// Set initial value
		$period = array();
		list($w,$m,$y) = array($week1,$month1,$year1);
		$week_num = 0;
		// Iterate while week, month, year not in the end date
		while(!($w == $week2 && $m == $month2 && $y == $year2)) {
			// Set first week and last week of the month
			$first_week = self::getFirstWeekday($m, $y);
			$last_week = self::getLastWeekday($m, $y);
			// Set week position
			$cur_week_num = $w - intval(date('W', $first_week));
			// Check if week position invalid 
			if($cur_week_num < 0) {
				// Check if it is in January 
				if($m == 1) {
					$week_num = $w;
				} else {
					$week_num = $week_num+1;
				}
			} else {
				$week_num = $cur_week_num;
			}
			if($week_num == 0) {
				$cur_date = $first_week;
			} else {
				if($w == 1 && $m == 12) {
					$cur_date = strtotime($y+1 .'W'.sprintf('%02d',$w));
				} else {
					$cur_date = strtotime($y.'W'.sprintf('%02d',$w));
				}
			}

			$timestamp = new stdClass();
			$jom_date = JFactory::getDate($cur_date);
			$timestamp->unix = $cur_date;
			$timestamp->sdate = $jom_date->format('M Y', true). ' ( '. $week_rom[$week_num] . ' )';
			$timestamp->ldate = $jom_date->format('F Y', true). ' ( '. $week_rom[$week_num] . ' )';
			$period[] = $timestamp;
			if($w == date('W', $last_week)) {
				$m++;
				if($m > 12) {
					$m = 1;
					$y++;
				}
				$w = intval(date('W', GTHelperDate::getFirstWeekday($m, $y)));
			} else {
				$w = intval(date('W', strtotime("+1 week", $cur_date)));
			}
		}
		return $period;
	}
	
	static function getDayPeriod($start, $end) {
		$diff = floor(($end - $start)/(24*60*60));
		$period = array();
		for($i=0;$i<=$diff;$i++) {
			$cur_date = strtotime("+$i day", $start);
			if(in_array(date('w', $cur_date), array(0,6))) continue;
			$timestamp = new stdClass();
			$jom_date = JFactory::getDate($cur_date);
			$timestamp->unix = $cur_date;
			$timestamp->sdate = $jom_date->format('d/m/Y', true);
			$timestamp->ldate = $jom_date->format('d M Y', true);
			$period[] = $timestamp;
		}
		return $period;
	}

	static function diff($then, $now = null) {
		$date		= JFactory::getDate();
		$now		= $now ? $now : $date->toSql();
		$now		= date_create($now);
		$then		= date_create($then);
		$interval	= date_diff($now, $then);

		$diff = array_slice(array_filter(array(
			$interval->format('%y') ? sprintf(JText::_('COM_GTSAFETRAVEL_YEAR'), $interval->format('%y')) : null,
			$interval->format('%m') ? sprintf(JText::_('COM_GTSAFETRAVEL_MONTH'), $interval->format('%m')) : null,
			$interval->format('%d') ? sprintf(JText::_('COM_GTSAFETRAVEL_DAY'), $interval->format('%d')) : null,
			$interval->format('%h') ? sprintf(JText::_('COM_GTSAFETRAVEL_HOUR'), $interval->format('%h')) : null,
			$interval->format('%i') ? sprintf(JText::_('COM_GTSAFETRAVEL_MINUTE'), $interval->format('%i')) : null,
			$interval->format('%s') ? sprintf(JText::_('COM_GTSAFETRAVEL_SECOND'), $interval->format('%s')) : null,
		)), 0, 2);

		$diff = implode(' ', $diff);
		$diff = sprintf(JText::_('COM_GTSAFETRAVEL_AGO'), $diff);

		return $diff;
	}

	static function userToGMT($date = null) {
		$date	= $date ? $date : GTHelperDate::format('now', 'Y-m-d H:i:s');
		$gmt	= strtotime(JFactory::getDate()->toSql());
		$user	= strtotime(GTHelperDate::format('now', 'Y-m-d H:i:s'));
		$offset	= ($user - $gmt) * -1;

		$date_user	= JFactory::getDate($date)->toUnix();
		$date_gmt	= $offset + $date_user;

		return JFactory::getDate($date_gmt)->toSql();
	}

	static function convertFormat($format) {
		return strtr($format,
			array('d' => 'j', 'dd' => 'd', 'DD' => 'l', 'D' => 'D', 'mm' => 'm', 'm' => 'n', 'MM' => 'F', 'M' => 'M', 'yyyy' => 'Y', 'yy' => 'y')
		);
	}

	public static function getDatePicker($name, $value, $attributes, $format = '%Y-%m-%d', $minView = 'days', $start = false, $end = 'now') {
		// Load JSs
		if(is_numeric(strpos($format, '%'))) {
			$format = str_replace(array('%', 'd', 'm', 'Y'), array('', 'dd', 'mm', 'yyyy'), $format);
		}
		$deflang	= explode('-', JComponentHelper::getParams('com_languages')->get('site'));
		$deflang	= reset($deflang);

		$start = $start ? JHtml::date($start, self::convertFormat($format)) : false;
		$end = $end ? JHtml::date($end, self::convertFormat($format)) : false;

		$params					= array();
		$params['format']		= $format;
		$params['language']		= $deflang;
		$params['startDate']	= $start;
		$params['endDate']		= $end;
		$params['minViewMode']	= $minView;
		$params 				= json_encode(array_filter($params));

		$document	= JFactory::getDocument();
		$document->addScript(GT_ADMIN_JS . '/datepicker/bootstrap-datepicker.js');
		$document->addScript(GT_ADMIN_JS . '/datepicker/locales/bootstrap-datepicker.' . $deflang . '.js');
		$document->addStylesheet(GT_ADMIN_CSS . '/bootstrap-datepicker3.min.css');
		$document->addScriptDeclaration("
			(function ($){
				$(document).ready(function (){
					$('#". $name ."_container').datepicker(".$params.")
				});
			})(jQuery);
		");



		$input = '<div class="input-group input-small date" id="'.$name.'_container">';
		$input .= '<span class="input-group-addon btn btn-danger" onclick="jQuery(this).next().val(null)"><i class="fa fa-times"></i></span>';
		$input .= '<input type="text" class="form-control" readonly="" style="background-color:white" value="'.$value.'" id="'.$name.'" name="'.$name.'">';
		$input .= '<span class="input-group-addon btn"><i class="fa fa-calendar"></i></span></div>';

		return $input;
	}
}
