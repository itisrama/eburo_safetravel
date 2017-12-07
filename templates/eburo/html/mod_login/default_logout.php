<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_login
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
$name = $params->get('name') == 0 ? htmlspecialchars($user->get('name')) : htmlspecialchars($user->get('username'));
?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form" class="form-vertical">
	<span title="<?php echo $name?>" class="name">
		<span style="font-size:12px; text-align:center; margin-right:5px" class="label label-default"><i class="fa fa-user"></i></span>
		<a class="hasTooltip" title="<?php echo JText::_('T3_VIEW_PROFIL')?>" data-placement="bottom" href="<?php echo JRoute::_('index.php?option=com_users&view=profile')?>"><span class="name"><?php echo $name?></span></a>
	</span>
	<button type="submit" name="Submit" class="btn btn-red logout hasTooltip" data-placement="bottom" title="<?php echo JText::_('JLOGOUT'); ?>"><span class="fa fa-power-off"></span></button>
	<input type="hidden" name="option" value="com_users" />
	<input type="hidden" name="task" value="user.logout" />
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
