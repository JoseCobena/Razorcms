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
 
class UserBasic extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	// fetch logged in user details if logged in
	public function get($id)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 1 || $id !== "current") $this->response(null, null, 204);

		// return the basic user details
		$this->response(array("user" => $this->user), "json");
	}
}

/* EOF */