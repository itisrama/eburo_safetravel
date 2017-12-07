<?php
$db = JFactory::getDbo();
$query = $db->getQuery(true)
	->select('id, home, template, s.params')
	->from('#__template_styles as s')
	->where('s.client_id = 0')
	->where('s.home = 1')
	->where('e.enabled = 1')
	->join('LEFT', '#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

$db->setQuery($query);
$template	= $db->loadObject();
$tmpParams	= json_decode($template->params);

$config			= JFactory::getConfig();
$sitename		= @$tmpParams->sitename;
$slogan			= @$tmpParams->slogan;
$siteNameCfg	= $config->get('sitename');
$titleConj		= '-';
$titleNew		= $slogan ? $slogan : $sitename;
$document 		= JFactory::getDocument();

$document->setTitle(str_replace(
	$siteNameCfg.' -',
	$titleNew.' '.$titleConj,
	$document->getTitle()
));

$document->addStyleDeclaration('
	#element-box.login {
		text-align: center;
	}
	#element-box.login img {
		height: 60px;
	}
');

/* ADD FAVICON
=========================== */
$uriFavicon = JUri::root(true).'/templates/'.$template->template.'/favicon/';

$this->addHeadLink($uriFavicon.'apple-touch-icon-57x57.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '57x57'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-114x114.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '114x114'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-144x144.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '144x144'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-60x60.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '60x60'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-120x120.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '120x120'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-76x76.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '76x76'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-152x152.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '152x152'));
$this->addHeadLink($uriFavicon.'favicon-196x196.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '196x196'));
$this->addHeadLink($uriFavicon.'favicon-96x96.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '96x96'));
$this->addHeadLink($uriFavicon.'favicon-32x32.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '32x32'));
$this->addHeadLink($uriFavicon.'favicon-16x16.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '16x16'));
$this->addHeadLink($uriFavicon.'favicon-128.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '128x128'));
$document->setMetaData('application-name', '&nbsp;');
$document->setMetaData('msapplication-TileColor', '#FFFFFF');
$document->setMetaData('msapplication-TileImage', $uriFavicon.'mstile-144x144.png');
$document->setMetaData('msapplication-square70x70logo', $uriFavicon.'mstile-70x70.png');
$document->setMetaData('msapplication-square150x150logo', $uriFavicon.'mstile-150x150.png');
$document->setMetaData('msapplication-wide310x150logo', $uriFavicon.'mstile-310x150.png');
$document->setMetaData('msapplication-square310x310logo', $uriFavicon.'mstile-310x310.png');