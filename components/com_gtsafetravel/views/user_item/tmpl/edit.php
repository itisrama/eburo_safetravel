<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');

$fields = array_merge($this->form->getFieldset('item'),$this->form->getFieldset('password'));
?>
<div id="com_gtsafetravel" class="item-page<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php if ($this->params->get('show_page_heading', 1)) : ?>
	<div class="page-header">
		<h1><?php echo $this->page_title; ?></h1>
	</div>
	<?php endif; ?>
	<form action="<?php echo GTHelper::getURL(); ?>" method="post" name="adminForm" id="adminForm" class="form-horizontal form-validation">
		<?php echo GTHelperFieldset::renderEdit($fields);?>
		<hr/>
		<?php echo $this->loadTemplate('button'); ?>

		<input type="hidden" name="id" value="<?php echo isset($this->item->id) ? $this->item->id : 0 ?>" />
		<input type="hidden" name="cid[]" value="<?php echo isset($this->item->id) ? $this->item->id : 0 ?>" />
		<input type="hidden" name="return_view" value="1" />
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>