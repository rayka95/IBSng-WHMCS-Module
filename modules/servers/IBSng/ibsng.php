<?php

	/*
	nothing to change here
	but be sure that you have 1_Month , 3_Month , 6_Month , 1_Year , 2_Year , 3_Year Group with correct charge and relative exp in your IBSng ,,,
	
	@Rayka95
	*/
  require_once 'config.php';
  require_once 'ibsng.class.php';
  function ibsng_ConfigOptions()
  {
    $configarray = array(
					'Count' => array('Type' => 'text', 'Size' => '20', 'Description' => 'Default: 1<br>'),
					'Credit' => array('Type' => 'text', 'Size' => '20', 'Description' => 'Default: Service Price'),
					'Owner' => array('Type' => 'text', 'Size' => '20', 'Description' => 'Default: system'),
					'Group' => array('Type' => 'dropdown', 'Options' => '[Billing Cycle],Standard,1_Month,2_Months,3_Months,4_Months,5_Months,6_Months,7_Months,8_Months,9_Months,10_Months,11_Months,1_Year,2_Years,3_Years'),
					'Username Prefix' => array('Type' => 'text', 'Size' => '20'),
					'Password Strength' => array('Type' => 'dropdown', 'Options' => 'Numeric,Lowercase Alphabetic,Uppercase Alphabetic,Num LC,Num UC,LC UC,Num LC UC'),
					'Password Length' => array('Type' => 'text', 'Size' => '20', 'Description' => 'Default: 8'),
					'* Charge' => array('Type' => 'text', 'Size' => '20', 'Description' => '<font color="red">Required</font><br>can be use like customfields::FIELD_NAME'),
					'Multi Login' => array('Type' => "text", "Size" => "20", "Description" => "Users"),
					'Session Timeout' => array('Type' => 'text', "Size" => "20", "Description" => "Seconds"),
					'Relative Expiration Date' => array('Type' => 'text', 'Size' => '16'),
					'Absolute Expiration Date' => array('Type' => 'text', 'Size' => '16'),
					'Date Unit' => array('Type' => 'dropdown', 'Options' => 'Gregorian,Minutes,Days,Months,Years,Jalali'),
					'Assign IP Address' => array('Type' => 'yesno')
					);
    return $configarray;
  }

  function ibsng_CreateAccount($params)
  {
    $serviceid = $params['serviceid'];
    $auth_name = $params['serverusername'];
    $auth_pass = $params['serverpassword'];
    $server_ip = $params['serverip'];
    $server_port = IBSNG_SERVER_PORT;
    $timeout = IBSNG_TIMEOUT;
	$count = $params['configoption1'] == "" ? "1" : $params['configoption1'];
	$result = mysql_query("SELECT amount,billingcycle FROM tblhosting WHERE id = '$serviceid';");
	while($row = mysql_fetch_array($result))
	{
		$__amount = $row['amount']  - ".00";
		$__billingcycle =  $row['billingcycle'];
	}
    $credit = $params['configoption2'] == "" ? $__amount : $params['configoption2'];
    $owner = $params['configoption3'] == "" ? "system" : $params['configoption3'];
    $group = $params['configoption4'] == "" ? "Standard" : $params['configoption4'];
    $prefix = $params['configoption5'];
	$passwd_strength = $params['configoption6'];
	$passwd_length = $params['configoption7'] == "" ? "8" : $params['configoption7'];
	$normal_charge = $params['configoption8'];
    $multi_login = $params['configoption9'];
	$session_timeout = $params['configoption10'];
	$rel_exp = intval($params['configoption11']);
    $abs_exp = intval($params['configoption12']);
    $date_unit = trim($params['configoption13']);
    $username = $prefix;
    $password = passwdgen($passwd_strength,$passwd_length);
    if($group == '[Billing Cycle]')
    {
	  switch($__billingcycle) {
		case 'Monthly':
			$group = '1_Month';
			break;
		case 'Quarterly':
			$group = '3_Months';
			break;
		case 'Semi-Annually':
			$group = '6_Months';
			break;
		case 'Annually':
			$group = '1_Year';
			break;
		case 'Biennially':
			$group = '2_Years';
			break;
		case 'Triennially':
			$group = '3_Years';
			break;
		default:
			$group = 'Standard';
			break;
	  }
    }
    if(strpos($normal_charge, '::') !== false)
    {
      $elemnts = split('::', $normal_charge);
      $normal_charge = $params[$elemnts[0]][$elemnts[1]];
    }
	if($params['configoption14'] == 'on')
	{
		$assign_ip = assignIP($normal_charge,$serviceid);
	}
    $ibsng = new IBSngAdapter($server_ip, $server_port, $timeout, $auth_name, $auth_pass);
    list($success, $rs) = $ibsng->createUser($count, $credit, $owner, $group, $username, $password, $normal_charge, $multi_login, $session_timeout, $rel_exp, $abs_exp, $date_unit, $assign_ip);
    if($success === true)
    {
      mysql_query('UPDATE tblhosting SET username = \'' . $rs['username'] . '\' WHERE id = ' . $serviceid . ' ; ');
	  mysql_query('UPDATE tblhosting SET password = \'' . encrypt($password) . '\' WHERE id = ' . $serviceid . ' ; ');
      $result = 'success';
    }
    else
    {
      $result = $rs;
    }

    return $result;
  }

  function ibsng_TerminateAccount($params)
  {
    $auth_name = $params['serverusername'];
    $auth_pass = $params['serverpassword'];
    $server_ip = $params['serverip'];
    $server_port = IBSNG_SERVER_PORT;
    $timeout = IBSNG_TIMEOUT;
    $username = $params['username'];
    $ibsng = new IBSngAdapter($server_ip, $server_port, $timeout, $auth_name, $auth_pass);
    $rs = $ibsng->delUserByUsername($username);
    if($rs === true)
    {
      $result = 'success';
    }
    else
    {
      $result = $rs;
    }

    return $result;
  }

  function ibsng_SuspendAccount($params)
  {
    $auth_name = $params['serverusername'];
    $auth_pass = $params['serverpassword'];
    $server_ip = $params['serverip'];
    $server_port = IBSNG_SERVER_PORT;
    $timeout = IBSNG_TIMEOUT;
    $username = $params['username'];
    $ibsng = new IBSngAdapter($server_ip, $server_port, $timeout, $auth_name, $auth_pass);
    $rs = $ibsng->lockUserByUserName($username, 'Locked by WHMCS');
    if($rs === true)
    {
      $result = 'success';
    }
    else
    {
      $result = $rs;
    }

    return $result;
  }

  function ibsng_UnsuspendAccount($params)
  {
    $auth_name = $params['serverusername'];
    $auth_pass = $params['serverpassword'];
    $server_ip = $params['serverip'];
    $server_port = IBSNG_SERVER_PORT;
    $timeout = IBSNG_TIMEOUT;
    $username = $params['username'];
    $ibsng = new IBSngAdapter($server_ip, $server_port, $timeout, $auth_name, $auth_pass);
    $rs = $ibsng->unlockUserByUserName($username);
    if($rs === true)
    {
      $result = 'success';
    }
    else
    {
      $result = $rs;
    }

    return $result;
  }
/*
  function ibsng_ChangePassword($params) {
	
	return $result;
  }
*/
  function passwdgen($strength,$length) {
	switch($strength) {
		case 'Numeric':
			$characters = '0123456789';
			break;
		case 'Lowercase Alphabetic':
			$characters = 'abcdefghijklmnopqrstuvwxyz';
			break;
		case 'Uppercase Alphabetic':
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		case 'Num LC':
			$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
			break;
		case 'Num UC':
			$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		case 'LC UC':
			$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		case 'Num LC UC':
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
	}
	$string = '';    
	for($i = 0 ; $i < $length ; $i++) {
		$string .= $characters[mt_rand(0,strlen($characters))];
	}
	return $string;
  }

  function assignIP($server,$service_id) {
	$result = mysql_query("SELECT * FROM ip WHERE server = '$server' AND status = 'free' LIMIT 1");
	//if(empty($result))
		//"Error: There is no free IP address for this server in IP Pool.";
	//else
	$row = mysql_fetch_array($result);
	$ip = $row['ip'];
	mysql_query("UPDATE ip SET status = 'busy',service_id = '$service_id' WHERE ip = '$ip'");
	mysql_query("UPDATE tblhosting SET dedicatedip = '$ip' WHERE id = '$service_id'");
	return $ip;
  }

?>