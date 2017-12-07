<!-- Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterLabel" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="filterLabel"><?php echo JText::_('COM_GTSAFETRAVEL_PT_FILTER')?></h4>
			</div>
			<div class="modal-body">
				<div class="form-horizontal">
					<?php echo GTHelperFieldset::renderEdit($this->filter_form->getFieldset('item'));?>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_CLOSE')?></button>
				<button type="submit" class="btn btn-primary"><?php echo JText::_('COM_GTSAFETRAVEL_TOOLBAR_SELECT_FILTER')?></button>
			</div>
		</div>
	</div>
</div>

