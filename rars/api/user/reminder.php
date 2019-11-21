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
 
class UserReminder extends RazorAPI
{
	private $resource = null;

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	// request password reset
	public function post($data)
	{
		// no email
		if (empty($data["email"])) $this->response("User not found", "json", 404);

		// try find user
		$user = $this->razor_db->get_first('user', '*', array('email_address' => $data['email']));

		// check for match
		if (empty($user)) $this->response("User not found", "json", 404);

		// check attempts
		$user = $user["result"][0];
		if ($user["reminder_time"] > time() - 600) $this->response("Only one password request allowed per hour", "json", 401);

		/* Match found, attempts good, carry on */

		// now we will store token and send it via email
		$user_agent = $_SERVER["HTTP_USER_AGENT"];
		$ip_address = $_SERVER["REMOTE_ADDR"];
		$pass_hash = $user["password"];
		$reminder_time = time();
		$reminder_token = sha1($reminder_time.$user_agent.$ip_address.$pass_hash);

		// set new reminder
		$row = array(
			"reminder_token" => $reminder_token,
			"reminder_time" => $reminder_time
		);
		$this->razor_db->edit_data('user', $row, array('id' => $user['id']));

		// get setting
		$forgot_password_email = $this->razor_db->get_first('setting', array('value'), array('name' => 'forget_password_email'));
		$forgot_password_email = $forgot_password_email['value'];

		// email user pasword reset email
		$server_email = str_replace("www.", "", $_SERVER["SERVER_NAME"]);
		$reminder_link = RAZOR_BASE_URL."login#/password-reset/{$reminder_token}_{$user["id"]}";

		// email text replacement
		$search = array(
			"**server_name**",
			"**user_email**",
			"**forgot_password_link**"
		);

		$replace = array(
			$_SERVER["SERVER_NAME"],
			$user["email_address"],
			$reminder_link
		);

		$message = str_replace($search, $replace, $forgot_password_email);

		$this->email("no-reply@{$server_email}", $user["email_address"], "{$_SERVER["SERVER_NAME"]} Account Password Reset", $message);

		$this->response("success", "json");
	}
}