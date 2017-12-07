<?php
/** 
 *------------------------------------------------------------------------------
 * @package       T3 Framework for Joomla!
 *------------------------------------------------------------------------------
 * @copyright     Copyright (C) 2004-2013 JoomlArt.com. All Rights Reserved.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * @authors       JoomlArt, JoomlaBamboo, (contribute to this project at github 
 *                & Google group to become co-author)
 * @Google group: https://groups.google.com/forum/#!forum/t3fw
 * @Link:         http://t3-framework.org 
 *------------------------------------------------------------------------------
 */


defined('_JEXEC') or die;
$app = JFactory::getApplication();
$menu = $app->getMenu();

$home_class		= @$menu->getActive()->id == $menu->getDefault()->id ? 'home' : NULL;
$menu_class		= @$menu->getActive()->alias;
$guest_class	= JFactory::getUser()->guest ? 'guest' : NULL;
$page_class		= implode(" ", array_filter(array($home_class, $menu_class, $guest_class)));


if(JFactory::getUser()->guest) {
	JFactory::getDocument()->addScript(T3_TEMPLATE_URL . '/js/guest.js');
}
?>

<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>"
		class='<jdoc:include type="pageclass" /> <?php echo $page_class?>'>

<head>
	<jdoc:include type="head" />
	<?php $this->loadBlock('head') ?>
</head>

<body>

	<div class="t3-wrapper">

		<?php $this->loadBlock('header') ?>
		<?php $this->loadBlock('mainnav') ?>

		<div class="t3-page-wrapper">
			<?php $this->loadBlock('navhelper') ?>
			<?php $this->loadBlock('spotlight-1') ?>
			<?php $this->loadBlock('mainbody') ?>
			<?php $this->loadBlock('spotlight-2') ?>
			<?php $this->loadBlock('footnav') ?>
		</div>

		<?php $this->loadBlock('footer') ?>
		
	</div>

</body>

</html>