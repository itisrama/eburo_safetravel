<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

class GTSafeTravelModelProfile extends GTModelAdmin
{

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	protected function populateState() {
		parent::populateState();

		$id = $this->input->getInt('id', 0);
		$this->setState($this->getName().'.id', intval($id));
	}
	
	public function getItem($pk = null) {
		$user = $this->user;
		
		$db = $this->_db;
		$query = $db->getQuery(true);

		$query->select('a.id');
		$query->from($db->quoteName('#__gtsafetravel_users', 'a'));
		$query->where($db->quoteName('a.user_id').' = '.intval($user->id));
		$db->setQuery($query);

		$pk = $db->loadResult();

		$data = parent::getItem($pk);
		$data->user = $user;
		$data->user->password = '';

		$data->user_id = @$user->id;
		$data->email = @$user->email;

		if(!$data->id > 0) {
			$profil		= @$this->user->profile->pihpssurvey;
			$filters	= array(
				
			);

			foreach ($filters as $filter) {
				$profilVal = @$profil->$filter;
				$data->$filter = $profilVal;
			}

			$data->tipe = $this->input->get('tipe');
			$data->peran = $this->input->get('peran');
		}
		
		$this->item	= $data;

		return $data;
	}

	public function save($data){
		$data = JArrayHelper::toObject($data);
		$user = JUser::getInstance($data->user->id);
		$userdata = JArrayHelper::fromObject($data->user);
		unset($data->user);
		// Bind the data.
		if (!$user->bind($userdata)) {
			$this->setError($user->getError());
			return false;
		}
		// Store the data.
		if (!$user->save()) {
			$this->setError($user->getError());
			return false;
		}

		if(!parent::save($data)) return false;

		return true;
	}

	public function delete(&$pks) {
		foreach ($pks as $pk) {
			$data	= parent::getItem($pk);
			$userID	= JUserHelper::getUserId($data->user_id);
			$user	= JUser::getInstance($userID);
			$user->delete();
		}
		return parent::delete($pks);
	}
}
