<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_footer
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

$config		= JFactory::getConfig();
$app		= JFactory::getApplication();
$tmpParams	= $app->getTemplate(true)->params;

$sitename		= $tmpParams->get('sitename');
$siteNameCfg	= $config->get('sitename');
?>
<div class="module">
	<small><?php echo str_replace($siteNameCfg, $sitename, $lineone); ?></small>
</div>