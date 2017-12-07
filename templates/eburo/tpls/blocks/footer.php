<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<!-- FOOTER -->
<footer id="t3-footer" class="wrap t3-footer">
	<section class="t3-copyright clearfix">
		<div class="copyright">
			<jdoc:include type="modules" name="<?php $this->_p('footer') ?>" />
		</div>
		<?php if ($this->getParam('t3-rmvlogo', 1)): ?>
			<div class="poweredby text-hide">
				<!--<a class="t3-logo t3-logo-color" href="http://gt.web.id" title="Powered by gtWeb"
				   target="_blank" <?php echo method_exists('T3', 'isHome') && T3::isHome() ? '' : 'rel="nofollow"' ?>><?php echo JText::_('T3_POWER_BY_HTML') ?></a>-->
			</div>
		<?php endif; ?>
	</section>
</footer>
<!-- //FOOTER -->