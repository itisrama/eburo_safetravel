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
$usersConfig = JComponentHelper::getParams('com_users');

$return = $this->params->get('login_redirect_url', $this->form->getValue('return'));
$return = base64_encode($return);

$app		= JFactory::getApplication();
$document	= JFactory::getDocument();
$tmpParams	= $app->getTemplate(true)->params;

$sitename		= $tmpParams->get('sitename');
$slogan			= $tmpParams->get('slogan', '');
$logotype		= $tmpParams->get('logotype', 'text');
$logoimage		= $logotype == 'image' ? $tmpParams->get('logoimage') : '';
$logoimageurl	= ($logotype == 'image' && $logoimage) ? JURI::base(false) . '/' . $logoimage : null;

if($logotype == 'image' && $logoimage) {
	$document->addStyleDeclaration(sprintf("
		.logo-image h1 {
			background-image: url(%s);
		}
	", $logoimageurl));
}
?>
<div class="row-fluid">
	<div class="col-md-6 col-md-offset-3">
		<div class="guest-wrap clearfix">
			<div class="row-fluid">
				<div class="display col-md-7">
					<div><img src="<?php echo JURI::base(true).'/images/site/login.jpg'?>"></div>
				</div>
				<div class="loginarea col-md-5">
					<div class="login <?php echo $this->pageclass_sfx?>">
						<div class="logo-<?php echo $logotype ?>">
							<a href="<?php echo JURI::base(true) ?>">
								<h1 class="hasTooltip" title="<?php echo $slogan?>"><?php echo $sitename ?></h1>
							</a>
						</div>
						<form action="<?php echo JRoute::_('index.php?option=com_users&task=user.login'); ?>" method="post">
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon btn-default"><i class="fa fa-user"></i></span>
									<input type="text" aria-required="true" required="" class="validate-username form-control" value="" id="username" name="username">
								</div>
							</div>
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon btn-default"><i class="fa fa-key"></i></span>
									<input type="password" aria-required="true" required="" maxlength="99" size="25" class="validate-password form-control" value="" id="password" name="password">
								</div>
							</div>
							
							<?php $tfa = JPluginHelper::getPlugin('twofactorauth'); ?>

							<?php if (!is_null($tfa) && $tfa != array()): ?>
								<div class="form-group">
									<?php echo $this->form->getField('secretkey')->input; ?>
								</div>
							<?php endif; ?>
							
							<?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
								<div class="checkbox small" style="margin-bottom:15px">
									<label>
										<input id="remember" type="checkbox" name="remember" value="yes"/> 
										<?php echo JText::_(version_compare(JVERSION, '3.0', 'ge') ? 'COM_USERS_LOGIN_REMEMBER_ME' : 'JGLOBAL_REMEMBER_ME') ?>
									</label>
									<a class="pull-right" href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>">
									<?php echo JText::_('COM_USERS_LOGIN_RESET'); ?></a>
								</div>
							<?php endif; ?>
							
							<div class="clearfix text-center">
								<?php if ($usersConfig->get('allowUserRegistration')): ?>
									<div class="btn-group">
										<a class="btn btn-info" href="<?php echo JRoute::_('index.php?option=com_users&view=registration'); ?>"><?php echo JText::_('JREGISTER'); ?></a>
										<button type="submit" class="btn btn-primary"><?php echo JText::_('JLOGIN'); ?></button>
									</div>
								<?php else:?>
									<button type="submit" class="btn btn-primary"><?php echo JText::_('JLOGIN'); ?></button>
								<?php endif;?>
								<input type="hidden" name="return" value="<?php echo $return; ?>" />
								<?php echo JHtml::_('form.token'); ?>
							</div>
							
							<?php /*
							<ul>
								<li></li>
								<li></li>
								
							</ul>*/?>
						</form>

					</div>

				</div>
			</div>
		</div>
	</div>
</div>