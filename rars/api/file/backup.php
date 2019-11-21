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

class FileBackup extends RazorAPI
{
	private $tmp_path = null;
	private $backup_path = null;
	private $backup_url = null;

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();

		// set paths
		$this->tmp_path = RAZOR_BASE_PATH."storage/tmp";
		$this->backup_path = RAZOR_BASE_PATH."storage/tmp/backup";
		$this->backup_url = RAZOR_BASE_URL."storage/tmp/backup";

		// includes
		include_once(RAZOR_BASE_PATH."library/php/razor/razor_zip.php");

		// check if folders exist
		if (!is_dir($this->tmp_path)) mkdir($this->tmp_path);
		if (!is_dir($this->backup_path)) mkdir($this->backup_path);
	}

	// get a package from razorcms server
	public function get($type)
	{
		if ((int) $this->check_access() < 10 || empty($type)) $this->response(null, null, 401);
		
		if ($type != "full") $this->response(null, null, 400);

		// compress the whole system to a single zip backup file
		$zip = new RazorZip;
		$time = time();
		$zip->open("{$this->backup_path}/upgrade_backup_{$time}.zip", ZipArchive::CREATE);
		$zip->add_dir(RAZOR_BASE_PATH);
		$zip->close();

		$this->response(array("backup" => "{$this->backup_url}/upgrade_backup_{$time}.zip"), "json");
	}
}

/* EOF */