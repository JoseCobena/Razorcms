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
 
class MenuEditor extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();
	}

	public function get($page_id)
	{
		$menu_items = $this->razor_db->query_all('SELECT a.*'
			.", b.id AS 'menu.id'"
			.", b.name AS 'menu.name'"
			.", c.name AS 'page.name'"
			.", c.link AS 'page.link'"
			.", c.active AS 'page.active'"
			.' FROM menu_item AS a' 
			.' LEFT JOIN menu AS b ON a.menu_id = b.id'
			.' LEFT JOIN page AS c ON a.page_id = c.id'
			.' ORDER BY a.position ASC');

		$menus = array();
		foreach ($menu_items as $mi)
		{
			if (!isset($menus[$mi["menu.name"]]))
			{
				$menus[$mi["menu.name"]] = array(
					"id" => $mi["menu.id"], 
					"name" => $mi["menu.name"],
					"menu_items" => array()
				);
			}

			if ($mi["level"] == 1)
			{
				$menus[$mi["menu.name"]]["menu_items"][] = array(
					"id" => $mi["id"],
					"position" => $mi["position"],
					"page_id" => (isset($mi["page_id"]) ? $mi["page_id"] : null),
					"page_name" => (isset($mi["page.name"]) ? $mi["page.name"] : null),
					"page_link" => (isset($mi["page.link"]) ? $mi["page.link"] : null),
					"page_active" => (isset($mi["page.active"]) ? $mi["page.active"] : null),
					"level" => $mi["level"],
					"link_label" => $mi["link_label"],
					"link_url" => $mi["link_url"],
					"link_target" => $mi["link_target"]
				);				
			}

			if ($mi["level"] == 2)
			{
				$parent = count($menus[$mi["menu.name"]]["menu_items"]) - 1;
				
				if (!isset($menus[$mi["menu.name"]]["menu_items"][$parent]["sub_menu"]))
				{
					$menus[$mi["menu.name"]]["menu_items"][$parent]["sub_menu"] = array();
				}

				$menus[$mi["menu.name"]]["menu_items"][$parent]["sub_menu"][] = array(
					"id" => $mi["id"],
					"position" => $mi["position"],
					"page_id" => (isset($mi["page_id"]) ? $mi["page_id"] : null),
					"page_name" => (isset($mi["page.name"]) ? $mi["page.name"] : null),
					"page_link" => (isset($mi["page.link"]) ? $mi["page.link"] : null),
					"page_active" => (isset($mi["page.active"]) ? $mi["page.active"] : null),
					"level" => $mi["level"],
					"link_label" => $mi["link_label"],
					"link_url" => $mi["link_url"],
					"link_target" => $mi["link_target"]
				);   
			}
		}

		// if menu items missing, build a clean array to allow people to add new
		$menus_clean = $this->razor_db->get_all('menu');

		foreach ($menus_clean as $mc) 
		{
			if (isset($menus[$mc["name"]])) continue;

			$menus[$mc["name"]] = array(
				"id" => $mc["id"], 
				"name" => $mc["name"],
				"menu_items" => array()
			);
		}

		// return the basic user details
		$this->response(array("menus" => $menus), "json");
	}

	// add or update content
	public function post($data)
	{
		// login check - if fail, return no data to stop error flagging to user
		if ((int) $this->check_access() < 8) $this->response(null, null, 401);
	
		// 1. grab all menus in position order
		$all_menu_items = $this->razor_db->query_all('SELECT * FROM menu_item ORDER BY position ASC');

		// 2. make flat arrays
		$new_menus_flat = array();
		foreach ($data as $menu)
		{
			// set up menu item arrays
			if (!isset($new_menus_flat[$menu["id"]])) $new_menus_flat[$menu["id"]] = array();
			
			foreach ($menu["menu_items"] as $mi)
			{
				if (isset($mi["id"])) $new_menus_flat[$menu["id"]][] = $mi["id"];

				if (isset($mi["sub_menu"]) & !empty($mi["sub_menu"]))
				{
					foreach ($mi["sub_menu"] as $sub_menu_item)
					{
						if (isset($sub_menu_item["id"])) $new_menus_flat[$menu["id"]][] = $sub_menu_item["id"];
					}
				}
			}
		}

		$current_menus_flat = array();
		foreach ($all_menu_items as $ami)
		{
			// set up menu item arrays
			if (!isset($current_menus_flat[$ami["menu_id"]])) $current_menus_flat[$ami["menu_id"]] = array();
			$current_menus_flat[$ami["menu_id"]][] = $ami["id"];

			// at same time remove any items missing		  
			if (!in_array($ami["id"], $new_menus_flat[$ami["menu_id"]])) $this->razor_db->delete_data('menu_item', array('id' => (int) $ami["id"]));
		}

		// 3. update all of sent menu data, by looping through the new $data
		foreach ($data as $new_menu)
		{
			$edit_rows = array();
			$pos = 1;
			// each menu
			foreach ($new_menu["menu_items"] as $nmi)
			{
				if (isset($nmi["id"]) && in_array($nmi["id"], $current_menus_flat[$new_menu["id"]]))
				{
					// update menu item
					$this->razor_db->edit_data('menu_item', array('position' => $pos), array('id' => $nmi['id']));
				}
				else
				{
					// add new item
					$row = array(
						"menu_id" => (int) $new_menu["id"],
						"position" => $pos,
						"level" => 1,
						"page_id" => (isset($nmi["page_id"]) ? $nmi["page_id"] : null),
						"link_label" => (isset($nmi["link_label"]) ? $nmi["link_label"] : null),
						"link_url" => (isset($nmi["link_label"]) ? $nmi["link_url"] : null),
						"link_target" => (isset($nmi["link_label"]) ? $nmi["link_target"] : null)
					);

					$this->razor_db->add_data('menu_item', $row);
				}

				$pos++;

				// now check for sub menu
				if (isset($nmi["sub_menu"]) && !empty($nmi["sub_menu"]))
				{
					$edit_sub_rows = array();
		
					foreach ($nmi["sub_menu"] as $nsmi)
					{
						if (isset($nsmi["id"]) && in_array($nsmi["id"], $current_menus_flat[$new_menu["id"]]))
						{
							// update menu item
							$this->razor_db->edit_data('menu_item', array('position' => $pos), array('id' => $nsmi['id']));
						}
						else
						{
							// add new item
							$row = array(
								"menu_id" => (int) $new_menu["id"],
								"position" => $pos,
								"level" => 2,
								"page_id" => (isset($nsmi["page_id"]) ? $nsmi["page_id"] : null),
								"link_label" => (isset($nsmi["link_label"]) ? $nsmi["link_label"] : null),
								"link_url" => (isset($nsmi["link_label"]) ? $nsmi["link_url"] : null),
								"link_target" => (isset($nsmi["link_label"]) ? $nsmi["link_target"] : null)
							);

							$this->razor_db->add_data('menu_item', $row); 
						}
						
						$pos++;
					}
				}
			}
		}

		$this->response("success", "json");
	}
}

/* EOF */