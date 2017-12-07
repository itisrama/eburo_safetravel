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

jimport('phpexcel.Classes.PHPExcel');
jimport('phpexcel.Classes.PHPExcel.IOFactory');

class GTHelperExcel {
	static function toArray($file) {
		// load XLS file
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objPHPExcel = $objReader->setReadDataOnly(true)->load($file);
		$data = array();
		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {			
			$date = $worksheet->getCell('B2')->getCalculatedValue();
			$date = PHPExcel_Style_NumberFormat::toFormattedString($date, 'YYYY-MM-DD');
			$market_id = $worksheet->getCell('B3')->getCalculatedValue();
			$merchant_ids = array();
			foreach($worksheet->getRowIterator() as $row) {
				$row_index = $row->getRowIndex();
				if($row_index <= 8) {
					if($row_index == 8) {
						// get merchant ids
						foreach($row->getCellIterator() as $cell) {
							if($cell->getColumn() < 'C') continue;
							$merchant_ids[] = $cell->getCalculatedValue();
						}
					}
					continue;
				}
				// collect data
				$commodity_id = $worksheet->getCell('A'.$row_index)->getCalculatedValue();
				$i = 0;
				foreach($row->getCellIterator() as $cell) {
					if($cell->getColumn() < 'C') continue;
					$merchant_id = $merchant_ids[$i];
					$data[$market_id][$merchant_id][$date][$commodity_id] = $cell->getCalculatedValue();
					$i++;
				}
			}
		}
		
		return $data;
	}
	
	static function getLetterFromNumber($num) {
		$numeric = $num % 26;
		$letter = chr(65 + $numeric);
		$num2 = intval($num / 26);
		if ($num2 > 0) {
			return self::getLetterFromNumber($num2 - 1) . $letter;
		} else {
			return $letter;
		}
	}
}