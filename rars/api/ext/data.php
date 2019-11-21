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

class ExtData extends RazorAPI
{
	private $ext_path = null;

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();

		// set paths
		$this->ext_path = RAZOR_BASE_PATH."extension";
		$this->rars_path = RAZOR_BASE_PATH."rars/api/extension";
	}

	public function post($ext)
	{
		if ((int) $this->check_access() < 9) $this->response(null, null, 401);
		if (empty($ext)) $this->response(null, null, 400);

		$settings = array();
		foreach ($ext["settings"] as $set) $settings[$set["name"]] = $set["value"];

		$columns = array('id');
		$where = array('extension' => strtolower($ext['extension']), 'type' => strtolower($ext['type']), 'handle' => strtolower($ext['handle']));
		$extension = $this->razor_db->get_first('extension', $columns, $where);

		if (!empty($extension)) $this->razor_db->edit_data('extension', array("json_settings" => json_encode($settings)), $where);
		else
		{
			// add new
			$data = array(
				"extension" => strtolower($ext["extension"]),
				"type" => strtolower($ext["type"]),
				"handle" => strtolower($ext["handle"]),
				"json_settings" => json_encode($settings),
				"user_id" => $this->user["id"],
				"access_level" => 0
			);
			$this->razor_db->add_data('extension', $data);
		}

		$this->response("success", "json");
	}

	public function delete($id)
	{
		if ((int) $this->check_access() < 9) $this->response(null, null, 401);
		if (empty($id)) $this->response(null, null, 400);

		$parts = explode("__", strtolower($id));
		if (count($parts) != 3) $this->response(null, null, 400);

		$category = preg_replace('/[^a-z0-9-_]/', '', $parts[0]);
		$handle = preg_replace('/[^a-z0-9-_]/', '', $parts[1]);
		$extension = preg_replace('/[^a-z0-9-_]/', '', $parts[2]);
		$remove_path = "{$this->ext_path}/{$category}/{$handle}/{$extension}";
		$rars_remove_path = "{$this->rars_path}/{$category}/{$handle}/{$extension}";

		if (!is_dir($remove_path)) $this->response(null, null, 400);

		if (RazorFileTools::delete_directory($remove_path))
		{
			if (is_dir($rars_remove_path)) RazorFileTools::delete_directory($rars_remove_path);
			$this->response("success", "json");
		}

		$this->response(null, null, 400);
	}
}

/* EOF */
