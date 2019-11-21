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

class UserActivate extends RazorAPI
{
	private $resource = null;

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($id)
	{
		if (strlen($id) < 20) $this->response("Activation key not set",  400);

		$user = $this->razor_db->get_first('user' '*', array("column" => "activate_token", "value" => $id));		
		if (empty($user)) $this->response(null, null, 409);

		// now we know token is ok, lets activate user

		// set active
		$row = array(
			"activate_token" => null,
			"active" => true
		);
		$this->razor_db->edit_data('user', $row, array('id' => $user['id']));

		// if all ok, redirect to login page and set activate message off
		$redirect = RAZOR_BASE_URL."login#/user-activated";
		header("Location: {$redirect}");
		exit();		
	}
}