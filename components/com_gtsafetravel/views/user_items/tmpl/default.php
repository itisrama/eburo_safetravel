<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
?>

<div id="com_gtsafetravel" class="item-page<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php if ($this->params->get('show_page_heading', 1)): ?>
	<div class="page-header">
		<h1><?php echo $this->page_title; ?></h1>
	</div>
	<?php endif; ?>

	<?php echo $this->modal->render(); ?>
	<form action="<?php echo GTHelper::getURL(); ?>" method="post" name="adminForm" id="adminForm">
		<?php echo $this->filter_form ? $this->loadTemplate('modal') : null; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $this->ordering; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->direction; ?>" />
		<?php echo JHtml::_('form.token'); ?>

		<?php if(!$this->user->guest):?>
			<?php echo $this->loadTemplate('button'); ?>
		<?php endif;?>

		<div id="table-filter">
			<?php echo $this->loadTemplate('form'); ?>
		</div>
		<br/>
		<table id="adminlist" class="adminlist table table-striped table-bordered" width="100%"></table>
	</form>
</div>
