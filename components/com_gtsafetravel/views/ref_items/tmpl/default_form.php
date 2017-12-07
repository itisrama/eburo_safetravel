<?php 
	$publishedOptions = JHtml::_('jgrid.publishedOptions');
	$publishedOptions = array_slice($publishedOptions, 3)
?>
<div class="form-inline" style="margin-bottom:5px">
	<div class="form-group">
		<div class="input-group input-xlarge">
			<input class="form-control" name="filter_search" type="text" value="<?php echo $this->escape($this->state->get('filter.search')); ?>"  placeholder="<?php echo JText::_('COM_GTSAFETRAVEL_FIELD_FILTER_SEARCH') ?>" id="filter_search">
			<div class="input-group-btn">
				<a class="btn btn-default" onclick="document.getElementById('filter_search').value='';document.getElementById('adminForm').submit();">
					<i class="fa fa-times"></i>
				</a>
				<button class="btn btn-cyan" type="submit">
					<i class="fa fa-search"></i> <?php echo JText::_('COM_GTSAFETRAVEL_FIND')?>
				</button>
			</div>
		</div>
	</div>
	<div class="pull-right">
		<div class="form-group">
			<select name="filter_published" class="inputbox" onchange="document.getElementById('adminForm').submit()" style="width:auto">
				<option><?php echo JText::_('JOPTION_SELECT_PUBLISHED');?></option>
				<?php echo JHtml::_('select.options', $publishedOptions, 'value', 'text', $this->state->get('filter.published'), true);?>
			</select>
		</div>
	</div>
</div>
