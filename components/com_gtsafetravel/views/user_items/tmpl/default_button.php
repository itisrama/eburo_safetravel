<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
?>
<?php if($this->canCreate || $this->canEditState || $this->canDelete):?>
<div class="command form-inline">
	<?php if($this->canCreate):?>
		<button link="<?php echo GTHelper::getURL('task=user_item.add&tmpl=component') ?>" class="btn btn-success modalForm">
			<i class="fa fa-plus-circle"></i> <?php echo str_replace('%s', JText::_('COM_GTSAFETRAVEL_PT_'.strtoupper($this->menu_name)), JText::_('COM_GTSAFETRAVEL_PT_NEW'))?>
		</button>
	<?php endif;?>

	<?php if($this->filter_form):?>
	<button type="button" class="btn btn-info" data-toggle="modal" data-target="#filterModal">
		<i class="fa fa-filter"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_OPEN_FILTER')?>
	</button>
	<?php endif;?>
	
	<button type="button" class="btn btn-default" onclick="jQuery('#table-filter').slideToggle();">
		<i class="fa fa-filter"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_TOGGLE_FILTER')?>
	</button>
	
	<?php if($this->state->get('filter.published') == -2):?>
		<button type="button" class="btn btn-default" onclick="submitbuttonlist('user_items.publish')">
			<i class="fa fa-undo"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_RESTORE')?>
		</button>
	<?php endif;?>

	<div class="pull-right">
		<?php if($this->canDelete):?>
			<?php if($this->state->get('filter.published') == -2):?>
				<button type="button" class="btn btn-red" disabled onclick="submitbuttonlist('user_items.delete')">
					<i class="fa fa-trash-o"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_TRASH_PERMANENTLY')?>
				</button>
			<?php else:?>
				<button type="button" class="btn btn-red" onclick="submitbuttonlist('user_items.trash')">
					<i class="fa fa-trash-o"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_TRASH')?>
				</button>
			<?php endif;?>
		<?php endif;?>
	</div>
</div>
<?php endif;?>
