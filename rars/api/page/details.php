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
 
class PageDetails extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($page_id)
	{
		// get all page data 
		$page = $this->razor_db->get_first('page', '*', array('id' => (int) $page_id));

		// return the basic user details
		$this->response(array("page" => $page), "json");
	}

	// update content
	public function post($data)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 8) $this->response(null, null, 401);
		if (empty($data)) $this->response(null, null, 400);

		// ensure we only have changes we want
		$changes = array(
			'active' => $data['active'],
			'name' => $data['name'],
			'title' => $data['title'],
			'link' => $data['link'],
			'theme' => $data['theme'],
			'keywords' => $data['keywords'],
			'access_level' => (int) $data['access_level'], 
			'description' => $data['description']
		);

		$this->razor_db->edit_data('page', $changes, array('id' => $data['id']));

		// return the basic user details
		$this->response($data, "json");
	}
}

/* EOF */