<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');

$fields		= $this->form->getFieldset('item');
$item		= $this->item->view;
$refInfos	= $item->ref_infos;
$infos 		= $item->infos;
$isInfo 	= count($infos);
?>
<div id="com_gtsafetravel" class="item-page<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php if ($this->params->get('show_page_heading', 1)) : ?>
	<div class="page-header">
		<img src="<?php echo $item->status ?>" height="30px" style="vertical-align:bottom"/>
		<h1 style="display:inline-block"><?php echo $this->page_title; ?></h1>
	</div>
	<?php endif; ?>
	<form action="<?php echo GTHelper::getURL(); ?>" method="post" id="adminForm">
		<?php if($item->flag || $item->landmark):?>
			<?php if($item->flag):?>
				<img src="<?php echo $item->flag ?>" height="150px" style="border:1px solid black" />
			<?php endif;?>
			<?php if($item->landmark):?>
				<img src="<?php echo $item->landmark ?>" height="150px"/>
			<?php endif;?>
			<br/><br/>
		<?php endif;?>
		
		<?php echo GTHelperFieldset::renderView($fields);?>
		<br/>
		<!-- START MAINTAB -->
		<?php echo $isInfo ? JHtml::_('bootstrap.startTabSet', 'countryTab', array('active' => 'info_1')) : null; ?>
		<?php foreach($refInfos as $refInfo):?>
			<!-- START TAB -->
			<?php 
				$tabName = sprintf(
					'<div class="hasTooltip" title="%s" style="font-size:1.6em; width:50px; text-align:center;"><strong><i class="fa fa-%s"></i></strong></div>', 
					$refInfo->name, $refInfo->icon
				);
				$info = @$infos[$refInfo->id];
				if(!$info) {
					continue;
				}
			?>
			<?php echo JHtml::_('bootstrap.addTab', 'countryTab', 'info_'.$refInfo->id, $tabName); ?>
			<div style="width:80%; max-width:900px;">
				<h2><?php echo $refInfo->name; ?></h2><hr/>
				<?php echo $info;?>
			</div>
			<br/>
			<br/>
			<!-- END TAB -->
			<?php echo $isInfo ? JHtml::_('bootstrap.endTab') : null; ?>
		<?php endforeach;?>

		<!-- END MAINTAB -->
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		<hr/>
		<?php echo $this->loadTemplate('button'); ?>

		<input type="hidden" name="id" value="<?php echo @$this->item->id ?>" />
		<input type="hidden" name="cid[]" value="<?php echo @$this->item->id ?>" />
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</form>

	
</div>
