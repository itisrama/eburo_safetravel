<?php
/**
 * masteruser.php
* @Copyright Copyright (C) 2010- Spiral Scripts
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
******/


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin that checks to see if the password entered belongs to a master user,
 * allowing the administrators or nominated admin to log in as any user
 *
 */
class plgAuthenticationMasterUser extends JPlugin
{
	var $_plugin;
	var $_params;
	
    /**
     * Constructor
     *
	 * @param 	object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
     */
    function plgAuthenticationMasterUser(& $subject, $config) {
        parent::__construct($subject, $config);
		
		jimport('joomla.plugin.plugin');
		$this->_params = $this->params;
	
		
    }
	
   /**
     * This method handles any  authentication and reports back to the subject
     * The method checks to see if the password entered matches the master user password
     *
     * @access    public
	 * @access	public
	 * @param   array 	$credentials Array holding the user credentials
	 * @param 	array   $options     Array of extra options
     * @param   object  $response    Authentication response object
     * @return    boolean
     * @since 1.6
     */
    function onUserAuthenticate( $credentials, $options, &$response )
    {
		jimport('joomla.user.helper');
		
		$response->type = 'MasterUser';
		/*
		 */
		$response->status = JAuthentication::STATUS_FAILURE;
		$response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');
		$app = JFactory::getApplication();
		if($app->isAdmin()){ return false; }
				

		/*
		 * check IP address
		 */
		$restrict_ip = $this->_params->get('restrict_ip', '0');
		if ($restrict_ip == '1') {
			$my_ip = "," . trim($_SERVER['REMOTE_ADDR']) . ",";
			$ip_list = trim($this->_params->get('ip_addresses',''));
 			$ip_list = "," . str_replace(" ","",$ip_list) . ",";
			
			if (strpos($ip_list, $my_ip) === false) {
			    $response->status = JAuthentication::STATUS_FAILURE;
				$response->error_message = '';
				return (false);
			}
		}
		
		/*
		 * fail authentication if no username or password
		 */
		if (empty($credentials['password']) || empty($credentials['username'])) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');
			
			return (false);
		}
		
		
	
		/*
		 * Check  if the user exists
		 */	
		$db = JFactory::getDBO();
		//$query = 'SELECT id FROM #__users WHERE username=' . $db->quote($credentials['username']); 
		$query	= $db->getQuery(true);
		$query->select('id');
		$query->from('#__users');
		$query->where('username=' . $db->Quote($credentials['username']));
		
		$db->setQuery($query);
		$result = $db->loadObject();
		
		
		
		/*
		 * If username does not exist, fail authentication
		 */
		if (!$result) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message =  JText::_('JGLOBAL_AUTH_NO_USER');
			
			return (false);
		}
		/* Check it's not an Administrator or a Super Administrator
		 */
		
		
		$groups = JUserHelper::getUserGroups($result->id);
		
		
		$noLoginGroups = $this->_params->get('no_login_usergroups', array(7,8));
		if(empty($noLoginGroups))
		{
			$noLoginGroups = array(7,8);
		}
		JArrayHelper::toInteger($noLoginGroups);
		
		
		
		foreach($noLoginGroups as $noLoginGroup)
		{
			if(in_array($noLoginGroup, $groups))
			{
			  $response->status = JAuthentication::STATUS_FAILURE;
			  $response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');
			  
			  return false;
			}
		}
		
		
				
		  /*
		   * To authenticate the password should be equal
		   * to the Master User Password - first find eligible users.
		   */
        $masterGroups = $this->_params->get('master_usergroups', array());
		if(empty($masterGroups))
		{
			$masterGroups = array(7,8);
		}
		   
		$query = ''; 
		if ($this->_params->get('enable_all','0') == '1')
		{
		   $query = $db->getQuery(true);
		   $query->select('id, username, password, m.group_id AS group_id');
		   $query->from('#__users AS u');
		   $query->join('INNER','#__user_usergroup_map AS m ON u.id = m.user_id');	
		   $query->where('m.group_id IN('. implode(',',$masterGroups).')');
		}
		else
		{
			$users = trim($this->_params->get('master_ids',''));
			$users = str_replace(' ','',$users);
			$user_array = explode(',',$users);
			JArrayHelper::toInteger( $user_array );
			//$whereString = implode(') OR (id = ',$user_array);
			

			
		   $query = $db->getQuery(true);
		   $query->select('id, username, password, m.group_id AS group_id');
		   $query->from('#__users AS u');
		   $query->join('INNER','#__user_usergroup_map AS m ON u.id = m.user_id');		   
		  // $query->where('m.group_id IN('. implode(',',$adminGroups).') AND (u.id = '.$whereString.'))');
		   $query->where('m.group_id IN('. implode(',',$masterGroups).') AND u.id IN('.implode(',',$user_array).')');
		  
		}
		$db->setQuery($query);
		$adminRows = $db->loadObjectList();
		
		
		
		if( $adminRows)
		{
			
			
		  foreach($adminRows AS $adminRow)
		  {

			  $match = false;
			  
			  if(method_exists('JUserHelper','verifyPassword'))
			  {
				  $match = JUserHelper::verifyPassword($credentials['password'], $adminRow->password, $adminRow->id);
			  }
			  else
			  {
				  $match = $this->legacyTest($adminRow, $credentials);
			  }


			  
			 if($match){
		   
			  $user = JUser::getInstance($result->id); // Add user results
			  $response->email = $user->email;
			  $response->fullname = $user->name;
			  $response->status = JAuthentication::STATUS_SUCCESS;
			  $response->error_message = '';
			  break;
			 }
		  }
		}//end if adminRows		
		else
		{
			  $response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');
			  
		}
		
		
		
	}//end onUserAuthenticate
	
	private function legacyTest($row, $credentials)
	{
		      $parts	= explode( ':', $row->password );
			  $crypt	= $parts[0];
			  $salt	= @$parts[1];
			  $testcrypt = JUserHelper::getCryptedPassword($credentials['password'], $salt);
			  
			  if($crypt == $testcrypt){
				  return true;
			  }
			  else
			  {
				return false;  
			  }
		
	}
}
?>