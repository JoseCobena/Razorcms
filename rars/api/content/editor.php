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
 
class ContentEditor extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($page_id)
	{
		$query = 'SELECT a.*'
			.", b.id AS 'content.id'"
			.", b.name AS 'content.name'"
			.", b.content AS 'content.content'"
			.' FROM page_content AS a' 
			.' LEFT JOIN content AS b ON a.content_id = b.id' 
			.' WHERE a.page_id = :page_id'
			.' ORDER BY a.position ASC';
		$data = $this->razor_db->query_all($query, array('page_id' => $page_id)); 

		$content = array();
		$locations = array();
		foreach ($data as $row)
		{
			if (!empty($row["content.id"]))
			{
				$content[$row['content.id']] = array(
					"content_id" => $row["content.id"],
					"name" => $row["content.name"],
					"content" => $row["content.content"]
				);
			}

			$location_data = array(
				"id" => $row["id"], 
				"content_id" => $row["content_id"], 
				"extension" => $row["extension"],
				"settings" => json_decode($row["json_settings"])
			);

			if (!empty($row["extension"]))
			{
				$manifest = RazorFileTools::read_file_contents(RAZOR_BASE_PATH."extension/{$row['extension']}", "json");
				if (isset($manifest->content_settings) && !empty($manifest->content_settings))
				{
					// create object
					if (!is_object($location_data["settings"])) $location_data["settings"] = new stdClass();

					// copy settings
					$location_data["extension_content_settings"] = $manifest->content_settings;
					
					// if no settings present, add defaults from manifest
					foreach ($manifest->content_settings as $cs)
					{
						if (!isset($location_data["settings"]->{$cs->name})) $location_data["settings"]->{$cs->name} = $cs->value;
					}
				}
			}

			$locations[$row["location"]][$row["column"]][] = $location_data;
		}		

		// return the basic user details
		$this->response(array("content" => $content, "locations" => $locations), "json");
	}

	// add or update content
	public function post($data)
	{
		if ((int) $this->check_access() < 6) $this->response(null, null, 401);
		if (!isset($data["content"])) $this->response(null, null, 400);

		// update or add content
		$new_content_map = array();
		foreach ($data["content"] as $key => $content)
		{	
			// if content name empty, try to resolve this to something
			if (empty($content["name"]))
			{
				$name = strip_tags(str_replace("><", "> <", $content["content"]));
				$cap = (strlen($name) > 30 ? 30 : strlen($name));
				$content["name"] = substr($name, 0, $cap)."...";
			}

			if (!isset($content["content_id"]) || !isset($content["content"]) || empty($content["content"]))
			{
				unset($data["content"][$key]);
				continue;
			}

			if (stripos($content["content_id"], "new-") === false)
			{
				$query_data = array('content' => $content['content'], 'name' => $content['name']);
				$query_where = array('id' => $content['content_id']);
				$this->razor_db->edit_data('content', $query_data, $query_where);
			}
			else
			{
				// add new content and map the ID to the new id for locations table
				$row = array("content" => $content["content"], "name" => $content["name"]);
				$result_id = $this->razor_db->add_data('content', $row);
				$result_id = $result_id[0];
				$new_content_map[$content["content_id"]] = $result_id; 
			}
		}

		$current_page_content = $this->razor_db->get_all('page_content', '*', array('page_id' => (int) $data["page_id"]));

		// 2. iterate through updating or adding, make a note of all id's
		$new_page_content = array();
		$page_content_map = array();
		$edit_rows = array();
		
		foreach ($data["locations"] as $location => $columns)
		{
			foreach ($columns as $column => $blocks)
			{
				foreach ($blocks as $pos => $block)
				{
					if ($block["id"] != "new")
					{
						// update
						$search = array("column" => "id", "value" => $block["id"]);
						$edit_rows[] = array("id" => $block["id"], "location" => $location, "column" => (int) $column, "position" => $pos + 1, "json_settings" => json_encode($block["settings"]));

						$query_data = array('location' => $location, 'column' => (int) $column, 'position' => $pos + 1, 'json_settings' => json_encode($block['settings']));	
						$query_where = array('id' => $block['id']);	
						$this->razor_db->edit_data('page_content', $query_data, $query_where);					

						$page_content_map[] = $block["id"];
					}
					else
					{
						// add new, if new, add, if new but already present add, else add as ext
						$new_content_id = (isset($block["content_id"], $new_content_map[$block["content_id"]]) ? $new_content_map[$block["content_id"]] : (isset($block["content_id"]) && is_numeric($block["content_id"]) ? $block["content_id"] : null));

						if (!empty($new_content_id) || isset($block["extension"]))
						{
							$row = array(
								"page_id" => (int) $data["page_id"],
								"content_id" => $new_content_id,
								"location" => $location,
								"column" => (int) $column,
								"position" => $pos + 1
							);

							if (isset($block["extension"]))
							{
								$row["extension"] = $block["extension"];
								$row["json_settings"] = (isset($block["settings"]) ? json_encode($block["settings"]) : null);
							}

							$new_page_content[] = $row;
						}
					}
				}
			}
		}

		// do any additions
		if (!empty($new_page_content))
		{
			$new_ids = $this->razor_db->add_data('page_content', $new_page_content);		
			array_merge($page_content_map, $new_ids);
		}

		// 3. run through id's affected against snapshot, if any missing, remove them.
		foreach ($current_page_content as $row)
		{
			if (!in_array($row["id"], $page_content_map)) $this->razor_db->delete_data('page_content', array('id' => (int) $row['id']));
		}

		// return the basic user details
		$this->response("success", "json");
	}
}

/* EOF */