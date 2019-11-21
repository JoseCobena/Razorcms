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
 
class SystemData extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($id)
	{
		if ((int) $this->check_access() < 6) $this->response(null, null, 401);
		if (empty($id)) $this->response(null, null, 400);

		// get menu data too
		$system = $this->razor_db->get_first('system');

		$this->response(array("system" => $system), "json");
	}
}

/* EOF */