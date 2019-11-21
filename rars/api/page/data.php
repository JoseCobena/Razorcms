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

class PageData extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	// add page
	public function post($data)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 6) $this->response(null, null, 401);
		if (empty($data)) $this->response(null, null, 400);

		// check link unique
		$result = $this->razor_db->get_first('page', '*', array('link' => (isset($data["link"]) ? $data["link"] : "")));
		if (!empty($result)) $this->response(array("error" => "duplicate link found", "code" => 101), 'json', 409);

		$row = array(
			"name" => $data["name"],
			"title" => $data["title"],
			"link" => $data["link"],
			"keywords" => $data["keywords"],
			"description" => $data["description"],
			"access_level" => (int) $data["access_level"],
			"active" => false
		);
		$result = $this->razor_db->add_data('page', $row, '*');
        $result = $result[0];

		// return the basic user details
		$this->response($result, "json");
	}

	// add or update content
	public function delete($id)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 8) $this->response(null, null, 401);
		if (!is_numeric($id)) $this->response(null, null, 400);

		// delete page
		$this->razor_db->delete_data('page', array('id' => (int) $id));

		// remove any page_content items
		$this->razor_db->delete_data('page_content', array('page_id' => (int) $id));

		// remove any menu_items
		$this->razor_db->delete_data('menu_item', array('page_id' => (int) $id));

		// return the basic user details
		$this->response("success", "json");
	}
}

/* EOF */
