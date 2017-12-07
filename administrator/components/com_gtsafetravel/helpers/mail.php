<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class GTHelperMail
{

	public static function send($title, $message, $recipient, $sender = null) {
		// SEND EMAIL
		// =======================================
		$app	= JFactory::getApplication();
		$mailer	= JFactory::getMailer();
		$config	= JFactory::getConfig();
	
		$sender	= $sender ? (array) $sender : array( 
			$config->get('mailfrom'),
			$config->get('fromname')
		);
		
		// Set Sender
		$mailer->setSender($sender);

		// Set Other Parameters
		$mailer->isHTML(true);
		$mailer->Encoding = 'base64';
		$mailer->addRecipient($recipient);
		$mailer->setSubject($title);
		$mailer->setBody($message);

		$recipient = is_array($recipient) ? implode(',', $recipient) : $recipient;
		// Send EMail
		if($mailer->send()) {
			$app->enqueueMessage(sprintf(JText::_('COM_GTSAFETRAVEL_EMAIL_SUCCESS'), $recipient));
		}
	}
}