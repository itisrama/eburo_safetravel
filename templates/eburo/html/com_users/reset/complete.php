<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
?>
<div class="row-fluid">
	<div class="col-md-6 col-md-offset-3">
		<div class="guest-wrap clearfix">
			<div class="guest-inner reset-complete<?php echo $this->pageclass_sfx?>">
				<div class="text-center">
					<img src="<?php echo JURI::base(true).'/images/login/logo.png'?>">
				</div>
				<hr/>
				
				<form action="<?php echo JRoute::_('index.php?option=com_users&task=reset.complete'); ?>" method="post" class="form-validate form-horizontal">

					<?php foreach ($this->form->getFieldsets() as $fieldset): // Iterate through the form fieldsets and display each one.?>
						<?php $fields = $this->form->getFieldset($fieldset->name);?>
						<?php if (count($fields)):?>
							<fieldset>
							<?php if (isset($fieldset->label)):// If the fieldset has a label set, display it as the legend.
							?>
								<p><?php echo JText::_($fieldset->label);?></p>
							<?php endif;?>
							<?php foreach ($fields as $field) :// Iterate through the fields in the set and display them.?>
								<?php if ($field->hidden):// If the field is hidden, just display the input.?>
									<?php echo $field->input;?>
								<?php else:?>
									<div class="form-group">

										<div class="col-sm-3 control-label">
											<?php echo $field->label; ?>
											<?php if (!$field->required && $field->type != 'Spacer') : ?>
												<span class="optional"><?php echo JText::_('COM_USERS_OPTIONAL');?></span>
											<?php endif; ?>
										</div>
										<div class="col-sm-9">
											<?php echo $field->input;?>
										</div>

									</div>
								<?php endif;?>
							<?php endforeach;?>
							</fieldset>
						<?php endif;?>
					<?php endforeach;?>

					<hr/>
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

