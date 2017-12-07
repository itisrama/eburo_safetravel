<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
if(version_compare(JVERSION, '3.0', 'lt')){
	JHtml::_('behavior.tooltip');
}
JHtml::_('behavior.formvalidation');
?>
<div class="row-fluid">
	<div class="col-md-6 col-md-offset-3">
		<div class="guest-wrap clearfix">
			<div class="guest-inner reset <?php echo $this->pageclass_sfx?>">
				<div class="text-center">
					<img src="<?php echo JURI::base(true).'/images/login/logo.png'?>">
				</div>
				<hr/>
				
				<form id="user-registration" action="<?php echo JRoute::_('index.php?option=com_users&task=reset.request'); ?>" method="post" class="form-validate form-horizontal">

					<?php foreach ($this->form->getFieldsets() as $fieldset): ?>
					<p><?php echo JText::_($fieldset->label); ?></p>

					<fieldset>
						<?php foreach ($this->form->getFieldset($fieldset->name) as $name => $field): ?>
							<div class="form-group">
								<div class="col-sm-3 control-label">
								<?php echo $field->label; ?>
							</div>
								<div class="col-sm-9">
								<?php echo $field->input; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</fieldset>
					<?php endforeach; ?>

					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary validate"><?php echo JText::_('JSUBMIT'); ?></button>
							<?php echo JHtml::_('form.token'); ?>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
