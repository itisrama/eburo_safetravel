<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
?>

<div id="com_gtsafetravel" class="item-page<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php echo $this->loadTemplate('header'); ?>
	<?php echo $this->modal->render(); ?>
	<form action="<?php echo GTHelper::getURL(); ?>" method="post" name="adminForm" id="adminForm">
		<?php echo $this->filter_form ? $this->loadTemplate('modal') : null; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $this->ordering; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->direction; ?>" />
		<?php echo JHtml::_('form.token'); ?>
		<div id="table-filter">
			<?php echo $this->loadTemplate('form'); ?>
		</div>
		<table id="adminlist" class="adminlist table table-striped" width="100%"></table>
	</form>
</div>
