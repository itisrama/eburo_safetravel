<?php

/**
 * @package		GT Component 
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTSafeTravelViewDashboard extends GTView {

	protected $items;
	protected $pagination;
	protected $state;

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	function display($tpl = null) {
		// Get model data.
		$this->eTravelDate	= $this->get('EarliestTravelDate');
		$this->lTravelDate	= $this->get('LatestTravelDate');
		$this->eERDate		= $this->get('EarliestEmergencyDate');
		$this->lERDate		= $this->get('LatestEmergencyDate');
		$this->state		= $this->get('State');

		// Add scripts
		//$this->document->addScript(GT_ADMIN_JS . '/inputmask/inputmask.extensions.js');
		$this->document->addScript(GT_ADMIN_JS . '/sprintf.js');
		$this->document->addScript(GT_ADMIN_JS . '/datatables.min.js');
		$this->document->addScript(GT_ADMIN_JS . '/jquery.number.min.js');
		$this->document->addScript(GT_ADMIN_JS . '/jquery.flot.min.js');
		$this->document->addScript(GT_ADMIN_JS . '/jquery.flot.tickrotor.js');
		$this->document->addScript('http://maps.googleapis.com/maps/api/js?sensor=false&libraries=drawing&key=AIzaSyBJOV22ANcvL_LI0p-_1GjegDg3tA7eVpE');
		$this->document->addScript(GT_ADMIN_JS . '/markerclusterer.js');
		$this->document->addScript(GT_JS . '/dashboard______.js');

		JText::script('COM_GTSAFETRAVEL_NUM');
		JText::script('COM_GTSAFETRAVEL_FIELD_CODE');
		JText::script('COM_GTSAFETRAVEL_N_VISIT');
		JText::script('COM_GTSAFETRAVEL_NUM_VISIT');
		JText::script('COM_GTSAFETRAVEL_FIELD_COUNTRY_GEONAME_ID');

		parent::display($tpl);
	}

}
