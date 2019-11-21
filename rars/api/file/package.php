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

class FilePackage extends RazorAPI
{
	private $tmp_path = null;
	private $package_path = null;
	private $upgrade_url = "http://www.razorcms.co.uk/rars/live/upgrade/system";

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();

		// set paths
		$this->tmp_path = RAZOR_BASE_PATH."storage/tmp";
		$this->package_path = RAZOR_BASE_PATH.'storage/tmp/package';

		// check if folders exist
		if (!is_dir($this->tmp_path)) mkdir($this->tmp_path);
		if (!is_dir($this->package_path)) mkdir($this->package_path);
	}

	// get a package from razorcms server
	public function get($package)
	{
		if ((int) $this->check_access() < 10 || empty($package)) $this->response(null, null, 401);
		
		$method_name = "package_{$package}";

		if (method_exists($this, $method_name)) $this->$method_name();

		// if no method, end 404
		$this->response(null, null, 404);
	}

	/* PACKAGE METHODS */

	private function package_system_upgrade()
	{
		if ((int) $this->check_access() < 10) $this->response(null, null, 401);

		$file_contents = RazorFileTools::get_remote_content($this->upgrade_url);		
		
		if (empty($file_contents)) $this->response(null, null, 404);

		if (!RazorFileTools::write_file_contents("{$this->package_path}/system_upgrade.zip", $file_contents)) throw new Exception("Could not write upgrade file to storage/tmp/package.");

		$this->response("success", "json");
	}
}

/* EOF */