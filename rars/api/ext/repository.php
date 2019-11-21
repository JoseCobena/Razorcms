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

class ExtRepository extends RazorAPI
{
	private $repo_url = "http://archive.razorcms.co.uk/";

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function post($data)
	{
		if ((int) $this->check_access() < 9) $this->response(null, null, 401);
		if (empty($data) || !isset($data["type"]) || !isset($data["handle"]) || !isset($data["extension"])) $this->response(null, null, 400);
		if (!isset($data["manifests"]) && !isset($data["manifest"])) $this->response(null, null, 400);

		// fetch cleaned data
		$manifest = preg_replace('/[^a-zA-Z0-9-_]/', '', (isset($data["manifests"][0]) ? $data["manifests"][0] : $data["manifest"])); // grab first only
		$category = preg_replace('/[^a-zA-Z0-9-_]/', '', strtolower($data["type"]));
		$handle = preg_replace('/[^a-zA-Z0-9-_]/', '', strtolower($data["handle"]));
		$name = preg_replace('/[^a-zA-Z0-9-_]/', '', strtolower($data["extension"]));

		// fetch details
		$man_url = $this->repo_url."extension/{$category}/{$handle}/{$name}/{$manifest}.manifest.json";

		$details_file = RazorFileTools::get_remote_content($man_url);
		if (!empty($details_file))
		{
			$details = json_decode($details_file);
			$this->response(array("details" => $details), "json");
		}

		// send back not found if no details
		$this->response(null, null, 404);
	}
}

/* EOF */
