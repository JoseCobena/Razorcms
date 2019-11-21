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
 
class ContentData extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	// delete content
	public function delete($id)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 8) $this->response(null, null, 401);
		if (!is_numeric($id)) $this->response(null, null, 400);

		// delete page 
		$this->razor_db->delete_data('content', array('id' => (int) $id));
		$this->razor_db->delete_data('page_content', array('content_id' => (int) $id));

		// return the basic user details
		$this->response("success", "json");
	}
}

/* EOF */