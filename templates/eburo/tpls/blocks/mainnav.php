<?php
	$lastvisit = JFactory::getUser()->lastvisitDate;
	$lastvisit = $lastvisit != '0000-00-00 00:00:00' ? $lastvisit : null;
	$lastvisit = JHtml::date($lastvisit, 'j F Y');
?>
<aside id="t3-mainnav" class="t3-mainnav t3-navbar-collapse t3-nav-sidebar navbar-default">
	<nav class="t3-navbar" role="navigation">
		<div class="head"><div class="wrapper" style="line-height:25px">
			<?php if ($this->countModules('head-navbar')) : ?>
				<jdoc:include type="modules" name="<?php $this->_p('head-navbar') ?>" style="raw" />
			<?php else:?>
				Last Login: <?php echo $lastvisit?>
			<?php endif;?>
		</div></div>
		<jdoc:include type="<?php echo $this->getParam('navigation_type', 'megamenu') ?>" name="<?php echo $this->getParam('mm_type', 'mainmenu') ?>" />
		<div class="foot">
			<div class="wrapper">
				<jdoc:include type="modules" name="<?php $this->_p('foot-navbar') ?>" style="raw" />
			</div>
		</div>
	</nav>
</aside>