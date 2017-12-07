<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

$isTmplComp = $this->input->get('tmpl') == 'component';
?>
<div>
	<button type="button" class="btn btn-success" onclick="submitbutton('user_item.apply')">
		<i class="fa fa-save"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_APPLY')?>
	</button>
	<button type="button" class="btn btn-default" onclick="submitbutton('user_item.save')">
		<i class="fa fa-check"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_SAVE_AND_PREVIEW')?>
	</button>
	<?php if(!$isTmplComp):?>
		<div class="pull-right">
			<button type="button" class="btn btn-orange" onclick="submitform('user_item.cancel')">
				<i class="fa fa-times-circle"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_CANCEL')?>
			</button>
			<?php if(!$this->isNew):?>
				<?php if($this->isTrashed  && $this->canDelete):?>
					<button type="button" class="btn btn-red" onclick="submitbuttonDelete('user_items.deleteList')">
						<i class="fa fa-trash-o"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_TRASH_PERMANENTLY')?>
					</button>
				<?php elseif($this->canEditState):?>
					<button type="button" class="btn btn-red" onclick="submitbuttonDelete('user_items.trash')">
						<i class="fa fa-trash-o"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_TRASH')?>
					</button>
				<?php endif;?>
			<?php endif;?>
		</div>
	<?php endif;?>
</div>