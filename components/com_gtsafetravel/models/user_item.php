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

class GTSafeTravelModelUser_Item extends GTModelAdmin
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
		$data = parent::getItem($pk);

		if(!is_object($data)) return false;
		
		if($data->user_id) {
			$user = JFactory::getUser($data->user_id);
			$data->username = @$user->username;
			$data->email = @$user->email;
			$data->user = $user;
		}

		if(!$data->id > 0) {
			$profil		= @$this->user->profile->pihpssurvey;
			$filters	= array(
				'province_id', 'regency_ids'
			);

			foreach ($filters as $filter) {
				$profilVal = @$profil->$filter;
				$data->$filter = $profilVal;
			}
		}
		
		$this->item	= $data;

		return $data;
	}

	public function getForm($data = array(), $loadData = true, $control = 'jform') {
		$component_name = $this->input->get('option');
		$model_name = $this->getName();
		
		$data = $data ? JArrayHelper::toObject($data) : $this->getFormData();
		//echo "<pre>"; print_r($data); echo "</pre>";
		$this->data = $data;

		$type = $data->type ? $data->type : $this->menu->params->get('menu_name');
		$form = $model_name.'_'.$type;

		// Get the form.
		$form = $this->loadForm($component_name . '.' . $model_name, $form, array('control' => $control, 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	protected function getUsername($region_id) {
		$type		= $this->menu->params->get('menu_name');
		$profile	= @$this->user->profile->pihpssurvey;
		$region_id	= @$profile->region_id ? $profile->region_id : $region_id;

		// Get a db connection.
		$db = JFactory::getDBO();

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('COUNT(*) count');
		$query->select($db->quoteName('b.initial', 'region'));
		$query->from($db->quoteName('#__gtsafetravel_users', 'a'));
		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_regions', 'b').' ON '.
			$db->quoteName('a.region_id').' = '.$db->quoteName('b.id')
		);

		$query->where($db->quoteName('a.type').' = '.$db->quote($type));
		$query->where($db->quoteName('a.region_id').' = '.$db->quote($region_id));
		
		//$query->where($db->quoteName('a.published') . ' = 1');

		$db->setQuery($query);

		$count = $db->loadObject();

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		
		$username = $this->user->username;
		$username = explode('.', $username);
		$username = end($username);
		$username = substr($type, 0, 3).(intval($count->count)+1).'.'.$count->region;

		return $username;
	}

	public function save($data){
		$data = JArrayHelper::toObject($data);
		$profil		= @$this->user->profile->pihpssurvey;
		

		if(!$data->id > 0) {
			$data->username	= $this->getUsername($data->region_id);
			$data->type		= $this->menu->params->get('menu_name');
		}

		$data->region_id = @$profil->region_id ? $profil->region_id : $data->region_id;

		sort($data->markets_id);
		sort($data->seller_ids);

		$user = JUser::getInstance($data->user_id);
		$userdata = array(
			'id'			=> $data->user_id,
			'name'			=> $data->name,
			'username'		=> $data->username,
			'password'		=> $data->password,
			'password2'		=> $data->password2,
			'email'			=> $data->email,
			'groups'		=> array(2),
			'resetCount'	=> 0,
			'sendEmail'		=> 0,
			'block'			=> 0,
			'requireReset'	=> 0,
			'tags'			=> null
		);
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

		$data->user_id = JUserHelper::getUserId($data->username);

		if(!parent::save($data)) return false;
		return true;
	}

	public function delete(&$pks) {
		return true;
		foreach ($pks as $pk) {
			$data	= parent::getItem($pk);
			$user	= JUser::getInstance($data->user_id);
			$user->delete();
		}
		return parent::delete($pks);
	}
}
