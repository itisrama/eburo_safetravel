<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');

$fields = $this->form->getFieldset('item');
?>
<div id="com_gtsafetravel" class="item-page<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php if ($this->params->get('show_page_heading', 1)) : ?>
	<div class="page-header">
		<h1><?php echo $this->page_title; ?></h1>
	</div>
	<?php endif; ?>
	<form action="<?php echo GTHelper::getURL(); ?>" method="post" id="adminForm">
		<fieldset>
			<?php echo GTHelperFieldset::renderView($fields);?>
		</fieldset>

		<hr/>
		<?php echo $this->loadTemplate('button'); ?>

		<input type="hidden" name="id" value="<?php echo @$this->item->id ?>" />
		<input type="hidden" name="cid[]" value="<?php echo @$this->item->id ?>" />
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</form>

	
</div>
