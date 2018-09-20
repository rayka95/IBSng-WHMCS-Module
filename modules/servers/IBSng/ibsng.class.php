<?php
	/*Updated XMLRPC ADAPTER , but RENEWAL or first login reset ASAP ,,,

	@Rayka95
	*/

  require_once 'xmlrpc.inc.php';
  class IBSngAdapter
  {
    protected $server_ip = null;
    protected $server_port = null;
    protected $timeout = null;
    protected $auth_name = null;
    protected $auth_pass = null;
    function __construct($server_ip, $server_port, $timeout, $auth_name, $auth_pass)
    {
      $this->server_ip = $server_ip;
      $this->server_port = $server_port;
      $this->timeout = $timeout;
      $this->auth_name = $auth_name;
      $this->auth_pass = $auth_pass;
    }

    function rpc_request($server_method, $params_arr)
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $params_arr['auth_name'] = $auth_name;
      $params_arr['auth_pass'] = $auth_pass;
      $params_arr['auth_type'] = 'ADMIN';
      $params_arr['auth_remoteaddr'] = $_SERVER['REMOTE_ADDR'];
      $client = new xmlrpc_client('/', $server_ip, $server_port);
      $client->setDebug(FALSE);
      $xml_val = php_xmlrpc_encode($params_arr);
      $xml_msg = new xmlrpcmsg($server_method);
      $xml_msg->addParam($xml_val);
      $response = $client->send($xml_msg, $timeout);
      if($response == FALSE)
      {
        return array(FALSE, 'Error occured while connecting to server');
      }

      if($response->faultCode() != 0)
      {
        return array(FALSE, $response->faultString());
      }

      return array(TRUE, php_xmlrpc_decode($response->value()));
    }

    function searchUser($conds, $from, $to, $order_by, $desc)
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $params_arr = array('conds' => $conds, 'from' => $from, 'to' => $to, 'order_by' => $order_by, 'desc' => $desc);
      $server_method = 'user.searchUser';
      list($success, $resp) = $this->rpc_request($server_method, $params_arr);
      if(!$success)
      {
        return array(0, array());
      }

      return $resp;
    }

    function delUserById($user_id, $del_comment = '', $del_connection_logs = 0, $del_audit_logs = 0)
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $params_arr = array('user_id' => $user_id, 'delete_comment' => $del_comment, 'del_connection_logs' => $del_connection_logs, 'del_audit_logs' => $del_audit_logs);
      $server_method = 'user.delUser';
      list($success, $resp) = $this->rpc_request($server_method, $params_arr);
      return $success;
    }

    function delUserByUsername($username, $del_comment = '', $del_connection_logs = 0, $del_audit_logs = 0)
    {
      $user_id = $this->getUserId($username);
      if($user_id === false)
      {
        return false;
      }

      $success = $this->delUserById((string)$user_id, $del_comment, $del_connection_logs, $del_audit_logs);
      return $success;
    }

    function createUser($count, $credit, $owner, $group, $username, $password, $normal_charge = '', $multi_login, $session_timeout, $rel_exp, $abs_exp, $date_unit, $assign_ip, $credit_comment = '')
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $params_arr = array('count' => $count, 'credit' => $credit, 'owner_name' => $owner, 'group_name' => $group, 'credit_comment' => $credit_comment);
      $server_method = 'user.addNewUsers';
      list($success, $resp) = $this->rpc_request($server_method, $params_arr);
      if(!$success)
      {
        return $resp;
      }

      $user_id =(string)$resp[0];
      if((($username == null OR $username == '') OR !isset($username)))
      {
        $username = $user_id;
      } else {
		$username = $username . $user_id;
	  }

      $attrs = array('normal_username' => $username, 'normal_password' => $password, 'normal_generate_password' => 0, 'normal_generate_password_len' => '1', 'normal_save_usernames' => 0);

	  if($multi_login != null)
	  {
		$attrs['multi_login'] = $multi_login;
	  }
	  
	  if($session_timeout != null)
	  {
		$attrs['session_timeout'] = $session_timeout;
	  }

      if($rel_exp != null)
      {
        $attrs['rel_exp_date'] = $abs_exp;
        $attrs['rel_exp_date_unit'] = $date_unit;
      }
	  
      if($abs_exp != null)
      {
        $attrs['abs_exp_date'] = $abs_exp;
        $attrs['abs_exp_date_unit'] = $date_unit;
      }

	  if($assign_ip != null)
	  {
		$attrs['assign_ip'] = $assign_ip;
	  }

      if(!empty($normal_charge))
      {
        $attrs['normal_charge'] = $normal_charge;
      }

      list($success, $resp) = $this->editUserAttr($user_id, $attrs);
      if(!$success)
      {
        return array(false, $resp);
      }

      return array(true, array('user_id' => $user_id, 'username' => $username));
    }

    function editUserAttr($user_id, $attrs, $to_del_attrs = array())
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $params_arr = array('user_id' => $user_id, 'attrs' => $attrs, 'to_del_attrs' => $to_del_attrs);
      $server_method = 'user.updateUserAttrs';
      $rs = $this->rpc_request($server_method, $params_arr);
      return $rs;
    }

    function lockUserById($user_id, $lock_des = '')
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $attrs = array('lock' => $lock_des);
      $rs = $this->editUserAttr($user_id, $attrs);
      return $rs[0];
    }

    function unlockUserById($user_id)
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $attrs = array();
      $to_del_attrs = array('lock');
      $rs = $this->editUserAttr($user_id, $attrs, $to_del_attrs);
      return $rs[0];
    }

    function lockUserByUserName($username, $lock_des = '')
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $user_id = $this->getUserId($username);
      if($user_id === false)
      {
        return false;
      }

      $rs = $this->lockUserById((string)$user_id, $lock_des);
      return $rs;
    }

    function unlockUserByUserName($username)
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $user_id = $this->getUserId($username);
      if($user_id === false)
      {
        return false;
      }

      $rs = $this->unlockUserById((string)$user_id);
      return $rs;
    }

    function getUserId($normal_username)
    {
      $server_ip = $this->server_ip;
      $server_port = $this->server_port;
      $timeout = $this->timeout;
      $auth_name = $this->auth_name;
      $auth_pass = $this->auth_pass;
      $from = 0;
      $to = 20;
      $order_by = 'normal_username';
      $desc = FALSE;
      $conds = array('normal_username' => $normal_username, 'normal_username_op' => 'equals');
      $server_method = 'user.searchUser';
      list($count, $res) = $this->searchUser($conds, $from, $to, $order_by, $desc);
      if(($count == 0 OR 1 < $count))
      {
        return false;
      }

      $user_id = $res[0];
      return $user_id;
    }
  }
?>