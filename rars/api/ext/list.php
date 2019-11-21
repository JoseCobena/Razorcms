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

class ExtList extends RazorAPI
{
	private $types = array("theme", "system", "all");

	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($type)
	{
		if ((int) $this->check_access() < 9) $this->response(null, null, 401);
		if (empty($type) || !in_array($type, $this->types)) $this->response(null, null, 400);

		// first scan the folders for manifests
		$manifests = RazorFileTools::find_file_contents(RAZOR_BASE_PATH."extension", "manifest.json", "json", "end");

		// split into types, so we can filter a little
		$extensions = array();

		$extension_settings = $this->razor_db->get_all('extension');

		foreach ($manifests as $mf)
		{
			// grab settings if any
			if (isset($mf->settings))
			{
				if (is_array($extension_settings))
				{
					foreach ($extension_settings as $es)
					{
						if ($es['extension'] == $mf->extension && $es['type'] == $mf->type && $es['handle'] == $mf->handle)
						{
							$db_settings = json_decode($es["json_settings"]);

							foreach ($mf->settings as $key => $setting)
							{
								if (isset($db_settings->{$setting->name})) $mf->settings[$key]->value = $db_settings->{$setting->name};
							}
						}
					}
				}
			}

			// sort list
			if ($mf->type == $type)
			{
				if ($mf->type == "theme")
				{
					// group manifest layouts for themes
					if (!isset($extensions[$mf->type.$mf->handle.$mf->extension]))
					{
						$extensions[$mf->type.$mf->handle.$mf->extension] = array(
							"layouts" => array(),
							"type" => $mf->type,
							"handle" => $mf->handle,
							"description" => $mf->description,
							"name" => $mf->name
						);
					}

					$extensions[$mf->type.$mf->handle.$mf->extension]["layouts"][] = $mf;
				}
				else $extensions[] = $mf;
			}
			else if ($type == "system" && $mf->type != "theme") $extensions[] = $mf;
			else if ($type == "all")
			{
				$mf->type = ucfirst($mf->type);

				if ($mf->type == "Theme")
				{
					// group manifest layouts for themes
					if (!isset($extensions[$mf->type.$mf->handle.$mf->extension]))
					{
						$extensions[$mf->type.$mf->handle.$mf->extension] = array(
							"layouts" => array(),
							"type" => $mf->type,
							"handle" => $mf->handle,
							"extension" => $mf->extension,
							"description" => $mf->description,
							"name" => $mf->name
						);
					}

					$extensions[$mf->type.$mf->handle.$mf->extension]["layouts"][] = $mf;
				}
				else $extensions[] = $mf;
			}
		}

		// ensure we have array return and not object
		$extensions = array_values($extensions);

		$this->response(array("extensions" => $extensions), "json");
	}
}

/* EOF */
