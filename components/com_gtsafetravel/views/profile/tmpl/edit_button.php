<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

$isTmplComp = $this->input->get('tmpl') == 'component';
?>
<div>
	<button type="button" class="btn btn-success" onclick="submitbutton('profile.apply')">
		<i class="fa fa-save"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_APPLY')?>
	</button>
	<button type="button" class="btn btn-default" onclick="submitbutton('profile.save')">
		<i class="fa fa-check"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_SAVE_AND_PREVIEW')?>
	</button>
	<?php if(!$isTmplComp):?>
		<div class="pull-right">
			<button type="button" class="btn btn-orange" onclick="submitform('profile.cancel')">
				<i class="fa fa-times-circle"></i> <?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_CANCEL')?>
			</button>

		</div>
	<?php endif;?>
</div>