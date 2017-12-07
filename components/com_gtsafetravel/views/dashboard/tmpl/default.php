<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');

$dateTypeOpts = array(
	'day' => ucwords(JText::_('COM_GTSAFETRAVEL_DATE_DAY')),
	'month' => ucwords(JText::_('COM_GTSAFETRAVEL_DATE_MONTH')),
	'year' => ucwords(JText::_('COM_GTSAFETRAVEL_DATE_YEAR'))
);
$agent = GTHelper::agent();
$isMobile = $agent->isMobile() && !$agent->isTablet();
$mapHeight = $isMobile ? '250px' : '550px';
$chartHeight = $isMobile ? '200px' : '400px';
?>

<div id="com_gtsafetravel" class="item-page<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php if ($this->params->get('show_page_heading', 1)): ?>
	<div class="page-header" style="position:relative">
		<h1><?php echo $this->page_title; ?></h1>
	</div>
	<?php endif; ?>

	<h2><?php echo JText::_('COM_GTSAFETRAVEL_PT_DASHBOARD_EMERGENCY')?></h2>
	<form id="dashboardEmergency" class="form-inline" action="<?php echo GT_COMPONENT.'&task=dashboard.emergency'?>">
		<div class="form-group">
			<select name="emergency_date_type" class="date_type">
				<?php echo JHtml::_('select.options', $dateTypeOpts, '', '', $this->state->get('emergency_date_type'));?>
			</select>
		</div>
		<div class="form-group date day">
			<?php echo GTHelperDate::getDatePicker('emergency_start_date', JHtml::date($this->state->get('emergency_start_date'), 'd-m-Y'), '', 'dd-mm-yyyy', 'days', $this->eERDate, $this->lERDate); ?>
		</div>
		<div class="form-group date day">
			<?php echo GTHelperDate::getDatePicker('emergency_end_date', JHtml::date($this->state->get('emergency_end_date'), 'd-m-Y'), '', 'dd-mm-yyyy', 'days', $this->eERDate, $this->lERDate); ?>
		</div>
		<div class="form-group date month">
			<?php echo GTHelperDate::getDatePicker('emergency_month', $this->state->get('emergency_month'), '', 'mm-yyyy', 'months', $this->eERDate, $this->lERDate); ?>
		</div>
		<div class="form-group date year">
			<?php echo GTHelperDate::getDatePicker('emergency_year', $this->state->get('emergency_year'), '', 'yyyy', 'years', $this->eERDate, $this->lERDate); ?>
		</div>
		<div class="form-group">
			<button type="submit" class="btn btn-primary"><?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_SHOW')?> <i class="fa fa-chevron-down"></i></button>
			<button id="dashboardEmergencyRefresh" class="btn btn-info"><?php echo JText::_('COM_GTSAFETRAVEL_SHOW_ALL_MAP')?> <i class="fa fa-globe"></i></button>
		</div>
	</form><br/>
	<div id="dashboardEmergencyMap" style="width:100%; height:<?php echo $mapHeight; ?>"></div>

	<h2><?php echo JText::_('COM_GTSAFETRAVEL_PT_DASHBOARD_TRAVEL')?></h2>
	<form id="dashboardTravel" class="form-inline" action="<?php echo GT_COMPONENT.'&task=dashboard.travel'?>">
		<div class="form-group">
			<select name="travel_date_type" class="date_type">
				<?php echo JHtml::_('select.options', $dateTypeOpts, '', '', $this->state->get('travel_date_type'));?>
			</select>
		</div>
		<div class="form-group date day">
			<?php echo GTHelperDate::getDatePicker('travel_start_date', JHtml::date($this->state->get('travel_start_date'), 'd-m-Y'), '', 'dd-mm-yyyy', 'days', $this->eTravelDate, $this->lTravelDate); ?>
		</div>
		<div class="form-group date day">
			<?php echo GTHelperDate::getDatePicker('travel_end_date', JHtml::date($this->state->get('travel_end_date'), 'd-m-Y'), '', 'dd-mm-yyyy', 'days', $this->eTravelDate, $this->lTravelDate); ?>
		</div>
		<div class="form-group date month">
			<?php echo GTHelperDate::getDatePicker('travel_month', $this->state->get('travel_month'), '', 'mm-yyyy', 'months', $this->eTravelDate, $this->lTravelDate); ?>
		</div>
		<div class="form-group date year">
			<?php echo GTHelperDate::getDatePicker('travel_year', $this->state->get('travel_year'), '', 'yyyy', 'years', $this->eTravelDate, $this->lTravelDate); ?>
		</div>
		<div class="form-group">
			<button type="submit" class="btn btn-primary"><?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_SHOW')?> <i class="fa fa-chevron-down"></i></button>
		</div>
	</form><br/>

	<div class="row">
		<div class="col-md-8">
			<div id="dashboardTravelChart"><div style="height:<?php echo $chartHeight; ?>;"></div></div>
		</div>
		<div class="col-md-4">
			<div id="dashboardTravelTable" style="height:<?php echo $chartHeight; ?>;">
				<table class="table table-condensed table-striped" width="100%"></table>
			</div>
		</div>
	</div>
	
</div>
