<?php if (!defined("RARS_BASE_PATH")) die("No direct script access to this content");

/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */
 
class UserRegister extends RazorAPI
{	
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();

		session_start();
		session_regenerate_id();
	}

	public function post($data)
	{
		// are we accepting registrations
		// get menu data too
		$allow = $this->razor_db->get_first('setting', array('value'), array('name' => 'allow_registration'));
		$manual = $this->razor_db->get_first('setting', array('value'), array('name' => 'manual_activation'));
		$registration_email = $this->razor_db->get_first('setting', array('value'), array('name' => 'registration_email'));
		$activation_email = $this->razor_db->get_first('setting', array('value'), array('name' => 'activation_email'));
		$activate_user_email = $this->razor_db->get_first('setting', array('value'), array('name' => 'activate_user_email'));

		if (!isset($allow["value"]) || !$allow["value"]) $this->response(null, null, 405);

		// verify form is coming from site and that human has sent it
		// Check details
		if (!isset($_SERVER["REMOTE_ADDR"], $_SERVER["HTTP_USER_AGENT"], $_SERVER["HTTP_REFERER"], $_SESSION["signature"])) $this->response(null, null, 400);
		if (empty($_SERVER["REMOTE_ADDR"]) || empty($_SERVER["HTTP_USER_AGENT"]) || empty($_SERVER["HTTP_REFERER"]) || empty($_SESSION["signature"])) $this->response(null, null, 400);

		// check referer matches the site
		if (strpos($_SERVER["HTTP_REFERER"], RAZOR_BASE_URL) !== 0) $this->response(null, null, 400);

		// check data
		if (!isset($data["signature"], $data["name"], $data["email_address"], $data["new_password"])) $this->response(null, null, 400);
		if (empty($data["signature"]) || empty($data["name"]) || empty($data["email_address"]) || empty($data["new_password"])) $this->response(null, null, 400);
		if (!isset($data["human"]) || !empty($data["human"])) $this->response("robot", "json", 406);

		// get signature and compare to session
		if ($_SESSION["signature"] !== $data["signature"]) $this->response(null, null, 400);
		unset($_SESSION["signature"]);
		session_destroy();
 
		// now we know registrations allowed, form came from website etc so lets check email unique and proceed with adding user

		// check email is unique
		$user = $this->razor_db->get_all('user', '*', array('email_address' => $data['email_address']));

		if (!empty($user)) $this->response(null, null, 409);
		
		// create new user
		$password = $this->create_hash($data["new_password"]);

		$row = array(
			"name" => $data["name"], 
			"email_address" => $data["email_address"],
			"access_level" => 1,
			"active" => false,
			"password" => $this->create_hash($data["new_password"])
		);

		$activate_link = "";
		if (!$manual["value"])
		{ 
			$activate_token = sha1($_SERVER["HTTP_USER_AGENT"].$_SERVER["REMOTE_ADDR"].$password);  
			$row["activate_token"] = $activate_token;
			$activate_link = RAZOR_BASE_URL."rars/user/activate/{$activate_token}";
		}

	 	$this->razor_db->add_data('user', $row);

		$server_email = str_replace("www.", "", $_SERVER["SERVER_NAME"]);

		// email text replacement
		$search = array(
			"**server_name**",
			"**user_email**",
			"**activation_link**"
		);

		$replace = array(
			$_SERVER["SERVER_NAME"],
			$data["email_address"],
			$activate_link
		);
	
		if ($manual["value"])
		{
			// send notifcation of registration and activation is manual to user
			$message1 = str_replace($search, $replace, $registration_email["value"]);
			$this->email("no-reply@{$server_email}", $data["email_address"], "{$_SERVER["SERVER_NAME"]} Account Registered", $message1);			
		 
			// send notifcation to super admin email that someone has registered and needs activation
			$super_email = $this->razor_db->get_first('user', '*', array('id' => 1));

			$message2 = str_replace($search, $replace, $activate_user_email["value"]);

			$this->email("no-reply@{$server_email}", $super_email, "{$_SERVER["SERVER_NAME"]} Account Registered", $message2);
		}
		else
		{	  
			$message3 = str_replace($search, $replace, $activation_email["value"]);
			$this->email("no-reply@{$server_email}", $data["email_address"], "{$_SERVER["SERVER_NAME"]} Account Activation", $message3);
		}

		$this->response(array("manual_activation" => $manual["value"]), "json");
	}
}

/* EOF */