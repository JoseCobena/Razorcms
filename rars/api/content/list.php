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

class ContentList extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($id)
	{
		$data = $this->razor_db->query_all(
			'SELECT a.*'
			.", c.id AS 'page.id'"
			.", c.link AS 'page.link'"
			.", c.name AS 'page.name'"
			.' FROM content AS a'
			.' LEFT JOIN page_content AS b ON a.id = b.content_id'
			.' LEFT JOIN page AS c ON c.id = b.page_id'
		);

		$content = array();
		foreach ($data as $row)
		{
			// create if not exist
			if (!isset($content[$row['id']])) $content[$row['id']] = array();
			if (!isset($content[$row['id']]['used_on_pages'])) $content[$row['id']]['used_on_pages'] = array();

			// write data
			$content[$row['id']]['access_level'] = $row['access_level'];
			$content[$row['id']]['content'] = $row['content'];
			$content[$row['id']]['id'] = $row['id'];
			$content[$row['id']]['json_settings'] = $row['json_settings'];
			$content[$row['id']]['name'] = $row['name'];

			// write pages used on
			if (isset($row['page.id'], $row['page.link'], $row['page.name']))
			{
				$content[$row['id']]['used_on_pages'][$row['page.id']] = array(
					'id' => $row['page.id'],
					'link' => $row['page.link'],
					'name' => $row['page.name']
				);
			}
		}

		// return the basic user details
		$this->response(array("content" => $content), "json");
	}
}

/* EOF */
