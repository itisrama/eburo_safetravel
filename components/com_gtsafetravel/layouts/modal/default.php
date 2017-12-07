<!-- Modal -->
<div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="filterLabel" data-backdrop="static">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="filterLabel">&nbsp;</h4>
			</div>
			<div class="modal-body">
				<div class="loading-msg"><i class="fa fa-spinner fa-spin"></i> <?php echo JText::_('COM_GTSAFETRAVEL_LOADING')?></div>
				<iframe src="about:blank" width="100%" scrolling="no" style="border:none; opacity:0; height:0"></iframe>
			</div>
		</div>
	</div>
</div>