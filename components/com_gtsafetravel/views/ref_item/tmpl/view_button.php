<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

$isTmplComp = $this->input->get('tmpl') == 'component';
?>
<div>
	<?php if(!$isTmplComp):?>
		<button type="button" class="btn btn-orange" onclick="Joomla.submitbutton('ref_item.back')">
			<i class="fa fa-arrow-left"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_BACK')?>
		</button>
	<?php endif;?>
	
	<button type="button" class="btn btn-default" onclick="Joomla.submitbutton('ref_item.edit')">
		<i class="fa fa-edit"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_EDIT')?>
	</button>
	
	<?php if(!$isTmplComp):?>
		<div class="pull-right">
		<?php if($this->isTrashed  && $this->canDelete):?>
			<button type="button" class="btn btn-red" onclick="Joomla.submitbutton('ref_items.deleteList')">
				<i class="fa fa-trash"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_TRASH_PERMANENTLY')?>
			</button>
		<?php elseif($this->canEditState):?>
			<button type="button" class="btn btn-red" onclick="Joomla.submitbutton('ref_items.trash')">
				<i class="fa fa-trash-o"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_TRASH')?>
			</button>
		<?php endif;?>
		</div>
	<?php endif;?>
</div>