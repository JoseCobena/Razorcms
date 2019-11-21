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
 
class ToolsVersion extends RazorAPI
{
	private $check_url = "http://www.razorcms.co.uk/rars/live/version/";

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($id)
	{
		if ($id != "current") $this->response(null, null, 400); 

		$host = (isset($_SERVER["SERVER_NAME"]) ? urlencode($_SERVER["SERVER_NAME"]) : (isset($_SERVER["HTTP_HOST"]) ? urlencode($_SERVER["HTTP_HOST"]) : "current"));

		$version_file = RazorFileTools::get_remote_content($this->check_url.$host);

		if (!empty($version_file))
		{
			$version = json_decode($version_file);
			$this->response($version, "json");
		}
		else
		{
			// send back unnavailable
			$this->response(null, null, 404);
		}

		// send back unnavailable
		$this->response(null, null, 404);
	}
}

/* EOF */