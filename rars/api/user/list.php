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
 
class UserList extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($id)
	{
		if ((int) $this->check_access() < 10) $this->response(null, null, 401);

		// set options
		$columns = array(
			"id", 
			"name", 
			"email_address", 
			"access_level", 
			"active", 
			"ip_address", 
			"last_logged_in"
		);
		$users = $this->razor_db->get_all('user', $columns);
		
		// return the basic user details
		$this->response(array("users" => $users), "json");
	}
}

/* EOF */