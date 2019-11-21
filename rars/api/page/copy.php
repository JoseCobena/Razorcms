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

class PageCopy extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	// copy page
	public function post($data)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 6) $this->response(null, null, 401);
		if (empty($data)) $this->response(null, null, 400);

		$page = $this->razor_db->get_first('page', '*', array('link' => (isset($data["link"]) ? $data["link"] : "")));

		if (!empty($page)) $this->response(array("error" => "duplicate link found", "code" => 101), 'json', 409);

		// copy the page
		$row = array(
			"name" => $data["name"],
			"title" => $data["title"],
			"link" => $data["link"],
			"keywords" => $data["keywords"],
			"description" => $data["description"],
			"access_level" => (int) $data["access_level"],
			"theme" => $data["theme"],
			"json_settings" => $data["json_settings"],
			"active" => false
		);
		$new_page = $this->razor_db->add_data('page', $row, '*');

		if (empty($new_page)) $this->response(null, null, 400);

        $new_page = $new_page[0];

		// next lets get all the page content for page we are copying
		$page_content = $this->razor_db->get_all('page_content', '*', array('page_id' => $data['id']));

		// now copy if any found
		if (count($page_content) > 0)
		{
			$new_rows = array();
			foreach ($page_content as $row)
			{
				$new_row = array();
				foreach ($row as $key => $col)
				{
					if ($key == "id") continue;
					else if ($key == "page_id") $new_row[$key] = $new_page["id"];
					else $new_row[$key] = $col;
				}
				$new_rows[] = $new_row;
			}

			$this->razor_db->add_data('page_content', $new_rows);
		}

		// return the basic page details
		$this->response($new_page, "json");
	}
}

/* EOF */
