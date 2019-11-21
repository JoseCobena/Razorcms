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

class SettingData extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	// add or update content
	public function post($data)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 9) $this->response(null, null, 401);
		if (empty($data)) $this->response(null, null, 400);

		if (isset($data["name"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["name"]), array('name' => 'name'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "name", "value" => (string) $data["name"], "type" => "string"));
		}

		if (isset($data["home_page"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["home_page"]), array('name' => 'home_page'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "home_page", "value" => (string) $data["home_page"], "type" => "int"));
		}

		if (isset($data["icon_position"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["icon_position"]), array('name' => 'icon_position'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "icon_position", "value" => (string) $data["icon_position"], "type" => "string"));
		}

		if (isset($data["google_analytics_code"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["google_analytics_code"]), array('name' => 'google_analytics_code'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "google_analytics_code", "value" => (string) $data["google_analytics_code"], "type" => "string"));
		}

		if (isset($data["forgot_password_email"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["forgot_password_email"]), array('name' => 'forgot_password_email'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "forgot_password_email", "value" => (string) $data["forgot_password_email"], "type" => "string"));
		}

		if (isset($data["allow_registration"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["allow_registration"]), array('name' => 'allow_registration'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "allow_registration", "value" => (string) $data["allow_registration"], "type" => "bool"));
		}

		if (isset($data["manual_activation"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["manual_activation"]), array('name' => 'manual_activation'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "manual_activation", "value" => (string) $data["manual_activation"], "type" => "bool"));
		}

		if (isset($data["registration_email"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["registration_email"]), array('name' => 'registration_email'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "registration_email", "value" => (string) $data["registration_email"], "type" => "string"));
		}

		if (isset($data["activation_email"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["activation_email"]), array('name' => 'activation_email'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "activation_email", "value" => (string) $data["activation_email"], "type" => "string"));
		}

		if (isset($data["activate_user_email"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["activate_user_email"]), array('name' => 'activate_user_email'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "activate_user_email", "value" => (string) $data["activate_user_email"], "type" => "string"));
		}

		if (isset($data["cookie_message"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["cookie_message"]), array('name' => 'cookie_message'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "cookie_message", "value" => (string) $data["cookie_message"], "type" => "string"));
		}

		if (isset($data["cookie_message_button"]))
		{
			$res = $this->razor_db->edit_data('setting', array("value" => $data["cookie_message_button"]), array('name' => 'cookie_message_button'));
			if (empty($res)) $this->razor_db->add_data('setting', array("name" => "cookie_message_button", "value" => (string) $data["cookie_message_button"], "type" => "string"));
		}

		if (isset($data["dev_mode"]))
		{
			// error handler dev mode must be changed at file level due to when its instantiated before anything else
			$err_hand = file_get_contents(RAZOR_BASE_PATH.'library/php/razor/razor_error_handler.php');

			if ($data['dev_mode'] == true) $err_hand = str_replace('private $mode = "production";', 'private $mode = "development";', $err_hand);
			else $err_hand = str_replace('private $mode = "development";', 'private $mode = "production";', $err_hand);

			file_put_contents(RAZOR_BASE_PATH.'library/php/razor/razor_error_handler.php', $err_hand);
		}

		$this->response("success", "json");
	}
}

/* EOF */
