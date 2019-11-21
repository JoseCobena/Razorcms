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

class SystemUpgrade extends RazorAPI
{
	private $tmp_path = null;
	private $package_path = null;
	private $backup_path = null;

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();

		// set paths
		$this->tmp_path = RAZOR_BASE_PATH."storage/tmp";
		$this->package_path = RAZOR_BASE_PATH.'storage/tmp/package';
		$this->backup_path = RAZOR_BASE_PATH."storage/tmp/backup";

		// includes
		include_once(RAZOR_BASE_PATH."library/php/razor/razor_zip.php");
	}

	// perform system upgrade of all files
	public function get($type)
	{
		// this was moved to allow system to complete after migration to sqlite (and login lost during process)
		if ($type == "complete")
		{
			RazorFileTools::delete_directory($this->tmp_path);
			$this->response("success", "json");
		}

		if ((int) $this->check_access() < 10 || empty($type)) $this->response(null, null, 401);
		if ($type != "now") $this->response(null, null, 400);	
		if (!is_file("{$this->package_path}/system_upgrade.zip")) throw new exception("Upgrade file not found.");

		// lets grab the current system data before we upgrade, after it is too late.
		$system = $this->razor_db->get_first('system');
	
		// upgrade the system from hte upgrade zip
		$zip = new RazorZip;
		$zip->open("{$this->package_path}/system_upgrade.zip");

		/* UPGRADE */
		$zip->extractTo(RAZOR_BASE_PATH);
		$zip->close();

		// now run any post install script, let this fail if not present as we always provide it, to stop people adding it (as we overwrite it)
		include("{$this->package_path}/system_upgrade_post_install.php");

		// once install script is run, remove it for safety
		RazorFileTools::delete_file("{$this->package_path}/system_upgrade_post_install.php");

		$this->response("success", "json");
	}

	// perform revert of upgrade
	public function post($data)
	{
		if ((int) $this->check_access() < 10) $this->response(null, null, 401);
		if (!isset($data["backup"])) $this->response(null, null, 400);

		$parts = explode("/", $data["backup"]);
		$file = end($parts);

		if (!is_file("{$this->backup_path}/{$file}")) throw new exception("Upgrade file not found.");
	
		// open backup
		$zip = new RazorZip;
		$zip->open("{$this->backup_path}/{$file}");

		/* UPGRADE */
		$zip->extractTo(RAZOR_BASE_PATH);
		$zip->close();

		// remove tmp files
		RazorFileTools::delete_directory($this->tmp_path);
		
		$this->response("success", "json");
	}
}

/* EOF */