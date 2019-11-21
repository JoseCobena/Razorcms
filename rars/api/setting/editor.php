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

class SettingEditor extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($id)
	{
		if ((int) $this->check_access() < 6) $this->response(null, null, 401);

		$res = $this->razor_db->get_all('setting');

		$settings = array();
		foreach ($res as $result)
		{
			switch ($result["type"])
			{
				case "bool":
					$settings[$result["name"]] = (bool) $result["value"];
				break;
				case "int":
					$settings[$result["name"]] = (int) $result["value"];
				break;
				default:
					$settings[$result["name"]] = (string) $result["value"];
				break;
			}
		}

        // for super admin, allow dev mode to be changed
        if ((int) $this->check_access() > 9)
        {
            $err_hand = file_get_contents(RAZOR_BASE_PATH.'library/php/razor/razor_error_handler.php');
            if (strpos($err_hand, 'private $mode = "development";') > 0) $settings['dev_mode'] = true;
            elseif (strpos($err_hand, 'private $mode = "production";') > 0) $settings['dev_mode'] = false;
        }

		// return the basic user details
		$this->response(array("settings" => $settings), "json");
	}
}

/* EOF */
