<?php if (!defined("RAZOR_BASE_PATH")) die("No direct script access to this content");

/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

class RazorSite
{
	private $link = null;
	private $all_menus = null;
	private $site = null;
	private $page = null;
	private $menu = null;
	private $content = null;
	private $login = false;
	private $logged_in = false;
	private $db = null;

	function __construct()
	{
		// generate path from get
		$this->link = (isset($_GET["path"]) ? $_GET["path"] : null);

		$this->db = new RazorPDO();
	}

	public function load()
	{
		// check for admin flag
		if ($this->link == "login")
		{
			$this->link = null;
			$this->login = true;
		}

		// check for logged in
		if (isset($_COOKIE["token"]))
		{
			include(RAZOR_BASE_PATH."library/php/razor/razor_api.php");
			$api = new RazorAPI();
			$this->logged_in = $api->check_access(86400);
		}

		// load data
		$this->get_all_menus();
		$this->get_site_data();
		$this->get_page_data();
		$this->get_menu_data();
		$this->get_content_data();
	}

	public function render()
	{
		// is 404 ?
		if (empty($this->page) || (!isset($_COOKIE["token"]) && !(int) $this->page["active"]))
		{
			header("HTTP/1.0 404 Not Found");
			include_once(RAZOR_BASE_PATH."theme/view/404.php");
			return;
		}

		// is 401 ?
		if ($this->logged_in < $this->page["access_level"])
		{
			header("HTTP/1.0 401 Unauthorized");
			include_once(RAZOR_BASE_PATH."theme/view/401.php");
			return;
		}

		// if default not chosen, load manifest
		if (!empty($this->page["theme"]) && is_file(RAZOR_BASE_PATH."extension/theme/{$this->page["theme"]}"))
		{
			$manifest = RazorFileTools::read_file_contents(RAZOR_BASE_PATH."extension/theme/{$this->page["theme"]}", "json");
			$view_path = RAZOR_BASE_PATH."extension/theme/{$manifest->handle}/{$manifest->extension}/view/{$manifest->layout}.php";

			if (is_file($view_path)) include_once($view_path);
		}
		else include_once(RAZOR_BASE_PATH."theme/view/default.php");
	}

	public function content($loc, $col)
	{
		// create extension dependancy list
		$ext_dep_list = array();

		// admin angluar loading for editor, return
		if (isset($_GET["edit"]) && ($this->logged_in > 6 || ($this->logged_in > 5 && !(int) $this->page["active"])))
		{
			echo <<<OUTPUT
<div class="content-column" ng-if="changed" ng-class="{'edit': toggle}">
	<div class="content-block" ng-class="{'active': editingThis('{$loc}{$col}' + block.content_id)}" ng-repeat="block in locations.{$loc}[{$col}]">
		<div class="input-group block-controls" ng-if="!block.extension">
			<span class="input-group-btn">
				<button class="btn btn-default" ng-click="locations.{$loc}[{$col}].splice(\$index - 1, 0, locations.{$loc}[{$col}].splice(\$index, 1)[0])" ng-show="toggle"><i class="fa fa-arrow-up"></i></button>
				<button class="btn btn-default" ng-click="locations.{$loc}[{$col}].splice(\$index + 1, 0, locations.{$loc}[{$col}].splice(\$index, 1)[0])" ng-show="toggle"><i class="fa fa-arrow-down"></i></button>
			</span>
			<input type="text" class="form-control" placeholder="Add Content Name" ng-show="toggle" ng-model="content[block.content_id].name"/>
			<span class="input-group-btn">
				<button class="btn btn-warning" ng-show="toggle" ng-click="removeContent('{$loc}', '{$col}', \$index)"><i class="fa fa-times"></i></button>
			</span>
		</div>

		<div id="{$loc}{$col}{{block.content_id}}" ng-if="!block.extension" class="content-edit" ng-click="startBlockEdit('{$loc}{$col}',  block.content_id)" ng-bind-html="content[block.content_id].content | html"></div>

		<div class="content-settings" ng-if="block.extension">
			<div class="extension-controls">
				<span class="btn-group pull-left">
					<button class="btn btn-default" ng-click="locations.{$loc}[{$col}].splice(\$index - 1, 0, locations.{$loc}[{$col}].splice(\$index, 1)[0])" ng-show="toggle"><i class="fa fa-arrow-up"></i></button>
					<button class="btn btn-default" ng-click="locations.{$loc}[{$col}].splice(\$index + 1, 0, locations.{$loc}[{$col}].splice(\$index, 1)[0])" ng-show="toggle"><i class="fa fa-arrow-down"></i></button>
				</span>
				<h3 class="extension-title pull-left"><i class="fa fa-puzzle-piece"></i> Extension</h3>
				<button class="btn btn-warning pull-right" ng-show="toggle" ng-click="removeContent('{$loc}', '{$col}', \$index)"><i class="fa fa-times"></i></button>
			</div>
			<form class="form-horizontal" role="form" name="form" novalidate>
				<div class="form-group">
					<label class="col-sm-3 control-label">Type</label>
					<div class="col-sm-7">
						<input type="text" class="form-control" value="{{block.extension.split('/')[0]}}" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">Handle</label>
					<div class="col-sm-7">
						<input type="text" class="form-control" value="{{block.extension.split('/')[1]}}" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">Extension</label>
					<div class="col-sm-7">
						<input type="text" class="form-control" value="{{block.extension.split('/')[2]}}" disabled>
					</div>
				</div>
				<div class="form-group" ng-repeat="setting in block.extension_content_settings" ng-form="subForm">
					<label class="col-sm-3 control-label">{{setting.label}}</label>
					<div class="col-sm-7">
						<input type="text" class="form-control" placeholder="{{setting.placeholder}}" name="input" ng-model="block.settings[setting.name]" ng-pattern="setting.regex.substring(0, 1) == '/' ? setting.regex.substring(1, setting.regex.length -1) : setting.regex" >
					</div>
					<div class="col-sm-2 error-block" ng-show="subForm.input.\$dirty && subForm.input.\$invalid">
						<span class="alert alert-danger alert-form" ng-show="subForm.input.\$error.pattern">Invalid</span>
					</div>
				</div>
			</form>
		</div>
	</div>

	<button class="btn btn-default" ng-show="toggle" ng-click="addNewBlock('{$loc}', '{$col}')"><i class="fa fa-plus"></i></button>
	<button class="btn btn-default" ng-show="toggle" ng-click="findBlock('{$loc}', '{$col}')"><i class="fa fa-search"></i></button>
	<button class="btn btn-default" ng-show="toggle" ng-click="findExtension('{$loc}', '{$col}')"><i class="fa fa-puzzle-piece"></i></button>
</div>
OUTPUT;
			return;
		}

		// if not editor and not empty, output content for public
		foreach ($this->content as $c_data)
		{
			if ($c_data["location"] == $loc && $c_data["column"] == $col)
			{
				if (!empty($c_data["content_id"]))
				{
					// load content
					echo '<div ng-if="!changed" content-id="'.$c_data["content_id"].'">';

					// content
					$content = $this->db->get_first('content', '*', array('id' => $c_data['content_id']));

					echo str_replace("\\n", "", $content["content"]);

					echo '</div>';
				}
				elseif (!empty($c_data["extension"]))
				{
					// load extension
					$manifest = RazorFileTools::read_file_contents(RAZOR_BASE_PATH."extension/{$c_data['extension']}", "json");
					$view_path = RAZOR_BASE_PATH."extension/{$manifest->type}/{$manifest->handle}/{$manifest->extension}/view/{$manifest->view}.php";

					echo '<div ng-if="!changed">';
					include($view_path);
					echo '</div>';
				}
			}
		}
	}

	public function menu($loc)
	{
		// first, check if menu present, if not create it
		if ($this->add_new_menu($loc)) $this->get_menu_data();;

		// admin angluar loading for editor, return
		if (isset($_GET["edit"]) && $this->logged_in > 6)
		{
			echo <<<OUTPUT
<li ng-if="changed" ng-repeat="mi in menus.{$loc}.menu_items" ng-class="{'click-and-sort': toggle, 'active': linkIsActive(mi.page_id), 'dropdown': mi.sub_menu || toggle, 'selected': \$parent.clickAndSort['{$loc}'].selected, 'place-holder': \$parent.clickAndSort['{$loc}'].picked != \$index && \$parent.clickAndSort['{$loc}'].selected}">
	<a ng-href="{{(!toggle ? mi.link_url || getMenuLink(mi.page_link) : '#')}}" ng-click="clickAndSortClick('{$loc}', \$index, menus.{$loc}.menu_items); \$event.preventDefault()" target="{{mi.link_target}}">
		<button class="btn btn-xs btn-default" ng-if="toggle" ng-click="menus.{$loc}.menu_items.splice(\$index, 1); \$event.preventDefault()"><i class="fa fa-times"></i></button>
		<i class="fa fa-eye-slash" ng-hide="mi.page_active || mi.link_label"></i>
		{{mi.page_name || mi.link_label}}
		<i class="fa fa-caret-down" ng-if="mi.sub_menu"></i>
	</a>
	<ul class="dropdown-menu">
		<li ng-repeat="mis in mi.sub_menu" ng-class="{'click-and-sort-sub': toggle, 'active': linkIsActive(mis.page_id), 'selected': \$parent.clickAndSort['{$loc}Sub'].selected, 'place-holder': \$parent.clickAndSort['{$loc}Sub'].picked != \$index && \$parent.clickAndSort['{$loc}Sub'].selected}">
			<a ng-href="{{(!toggle ? mis.link_url || getMenuLink(mis.page_link) : '#')}}" ng-click="clickAndSortClick('{$loc}Sub', \$index, mi.sub_menu); \$event.preventDefault()" target="{{mis.link_target}}">
				<button class="btn btn-xs btn-default" ng-if="toggle" ng-click="mi.sub_menu.splice(\$index, 1); \$event.preventDefault()"><i class="fa fa-times"></i></button>
				<i class="fa fa-eye-slash" ng-hide="mis.page_active || mis.link_label"></i>
				{{mis.page_name || mis.link_label}}
			</a>
		</li>

		<li ng-if="toggle" class="text-center"><a style="cursor: pointer;" class="add-new-menu" ng-click="findMenuItem('{$loc}', \$index)"><i class="fa fa-th-list"></i></a></li>
	</ul>
</li>

<li ng-show="toggle" class="add-new-menu"><a style="cursor: pointer;" ng-click="findMenuItem('{$loc}')"><i class="fa fa-th-list"></i></a></li>
OUTPUT;
		}

		// empty, return
		if (!isset($this->menu[$loc])) return;

		// else carry on with nromal php loading
		foreach ($this->menu[$loc] as $m_item)
		{
			// link item or page item that has access
			if (!empty($m_item["link_label"]) || (!empty($m_item["page_id"]) && $m_item["page_id.access_level"] <= $this->logged_in && ($m_item["page_id.active"] || $this->logged_in > 5)))
			{
				// sort any submenu items
				if (!isset($m_item["sub_menu"]))
				{
					echo '<li '.($this->logged_in < 7 ? '' : 'ng-if="!changed"').' '.($m_item["page_id"] == $this->page["id"] ? ' class="active"' : '').'>';
					echo '<a href="'.(isset($m_item["page_id.link"]) ? RAZOR_BASE_URL.$m_item["page_id.link"] : $m_item["link_url"]).'" target="'.$m_item["link_target"].'" '.($m_item["link_url"] == "#" ? 'onclick="return false;"' : '').'>';
					if (isset($m_item["page_id.active"]) && !$m_item["page_id.active"]) echo '<i class="fa fa-eye-slash"></i> ';
					echo (isset($m_item["page_id.name"]) ? $m_item["page_id.name"] : $m_item["link_label"]);
					echo '</a>';
				}
				else
				{
					echo '<li '.($this->logged_in < 7 ? '' : 'ng-if="!changed"').' class="dropdown'.($m_item["page_id"] == $this->page["id"] ? ' active' : '').'">';
					echo '<a class="dropdown-toggle" href="'.(isset($m_item["page_id.link"]) ? RAZOR_BASE_URL.$m_item["page_id.link"] : $m_item["link_url"]).'" target="'.$m_item["link_target"].'" '.($m_item["link_url"] == "#" ? 'onclick="return false;"' : '').'>';
					if (isset($m_item["page_id.active"]) && !$m_item["page_id.active"]) echo '<i class="fa fa-eye-slash"></i> ';
					echo (isset($m_item["page_id.name"]) ? $m_item["page_id.name"] : $m_item["link_label"]);
					echo ' <i class="fa fa-caret-down"></i></a>';
					echo '<ul class="dropdown-menu">';
					foreach ($m_item["sub_menu"] as $sm_item)
					{
						if (!empty($sm_item["link_label"]) || (!empty($sm_item["page_id"]) && $sm_item["page_id.access_level"] <= $this->logged_in && ($sm_item["page_id.active"] || $this->logged_in > 5)))
						{
							echo '<li '.($this->logged_in < 7 ? '' : 'ng-if="!changed"').' '.($sm_item["page_id"] == $this->page["id"] ? ' class="active"' : '').'>';
							echo '<a href="'.(isset($sm_item["page_id.link"]) ? RAZOR_BASE_URL.$sm_item["page_id.link"] : $sm_item["link_url"]).'" target="'.$sm_item["link_target"].'" '.($sm_item["link_url"] == "#" ? 'onclick="return false;"' : '').'>';
							if (isset($sm_item["page_id.active"]) && !$sm_item["page_id.active"]) echo '<i class="fa fa-eye-slash"></i> ';
							echo (isset($sm_item["page_id.name"]) ? $sm_item["page_id.name"] : $sm_item["link_label"]);
							echo '</a>';
						}
					}
					echo "</ul>";
				}

				echo '</li>';
			}
		}
	}

	public function data_main()
	{
		// public or preview
		if (isset($_GET["preview"]) || (!$this->login && !isset($_COOKIE["token"])))
		{
			echo 'data-main="base-module"';
			return;
		}

		// logged in
		if (!isset($_GET["edit"]) || $this->logged_in < 6)
		{
			echo 'data-main="admin-access-module"';
			return;
		}

		// admin editable
		echo 'data-main="admin-edit-module"';
	}

	public function body()
	{
		// public or preview
		if (isset($_GET["preview"]) || (!$this->login && !isset($_COOKIE["token"])))
		{
			// start by opening body
			echo "<body>";

			// if public viewable only, allow google tracking code to be used
			if (!isset($_GET["preview"]) && !empty($this->site["google_analytics_code"]))
			{
				echo <<<OUTPUT
	<!-- google tracking script -->
		<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', '{$this->site["google_analytics_code"]}', '{$_SERVER["SERVER_NAME"]}');
			ga('send', 'pageview');
		</script>
	<!-- google tracking script -->
OUTPUT;
			}

			// add in IE8 and below header
			echo <<<OUTPUT
	<!--[if lt IE 9]>
		<div class="ie8">
			<p class="message">
				<i class="fa fa-exclamation-triangle"></i> You are using an outdated version of Internet Explorer that is not supported,
				please update your browser or consider using an alternative, modern browser, such as
				<a href="http://www.google.com/chrome">Google Chome</a>.
			</p>
		<div>
	<![endif]-->
OUTPUT;

			// if public viewable only, allow google tracking code to be used
			if (!empty($this->site["cookie_message"]) && !empty($this->site["cookie_message_button"]))
			{
				echo <<<OUTPUT
	<!-- cookie message -->
	<div id="razor-cookie" class="cookie-message" ng-controller="cookieMessage">
		<div class="alert alert-info alert-dismissable ng-cloak" ng-if="!hideMessage">
			<p class="text-center">
				{$this->site["cookie_message"]}
				<button class="btn btn-default" ng-click="agree()">{$this->site["cookie_message_button"]}</button>
			</p>
		</div>
	</div>
	<!-- cookie message -->
OUTPUT;
			}

			return;
		}

		// logged in
		if (!isset($_GET["edit"]) || $this->logged_in < 6)
		{
			include(RAZOR_BASE_PATH."theme/partial/admin-access.php");
			return true;
		}

		include(RAZOR_BASE_PATH."theme/partial/admin-edit.php");
		return true;
	}

	private function get_site_data()
	{
		// get site data
		$setting = $this->db->get_all('setting');

		foreach ($setting as $result)
		{
			switch ($result["type"])
			{
				case "bool":
					$this->site[$result["name"]] = (bool) $result["value"];
				break;
				case "int":
					$this->site[$result["name"]] = (int) $result["value"];
				break;
				default:
					$this->site[$result["name"]] = (string) $result["value"];
				break;
			}
		}
	}

	private function get_page_data()
	{
		// get page data
        $where = (empty($this->link) ? array('id' => $this->site["home_page"]) : array('link' => $this->link));
		$page = $this->db->get_first('page', '*', $where);

		// ensure type correct
		if (!empty($page))
		{
			$this->page = $page;
			$this->page['id'] = (int) $this->page['id'];
			$this->page['active'] = (int) $this->page['active'];
			$this->page['access_level'] = (int) $this->page['access_level'];
		}
		else $this->page = null;
	}

	private function get_menu_data()
	{
		// if no page found, end here
		if (empty($this->page)) return;

		// collate all menus (to cut down on duplicate searches)
		$this->menu = array();

		$menus = $this->db->query_all('SELECT a.*'
			.", b.id AS 'page_id.id'"
			.", b.active AS 'page_id.active'"
			.", b.theme AS 'page_id.theme'"
			.", b.name AS 'page_id.name'"
			.", b.title AS 'page_id.title'"
			.", b.link AS 'page_id.link'"
			.", b.keywords AS 'page_id.keywords'"
			.", b.description AS 'page_id.description'"
			.", b.access_level AS 'page_id.access_level'"
			.", b.json_settings AS 'page_id.json_settings'"
			.", c.id AS 'menu_id.id'"
			.", c.name AS 'menu_id.name'"
			.", c.json_settings AS 'menu_id.json_settings'"
			.", c.access_level AS 'menu_id.access_level'"
			.' FROM menu_item AS a'
			.' LEFT JOIN page AS b ON a.page_id = b.id'
			.' LEFT JOIN menu AS c ON a.menu_id = c.id'
			.' ORDER BY position ASC'
		);

		// sort them into name
		foreach ($menus as $menu)
		{
			if (!isset($this->menu[$menu["menu_id.name"]])) $this->menu[$menu["menu_id.name"]] = array();

			if ($menu["level"] == 1) $this->menu[$menu["menu_id.name"]][] = $menu;

			if ($menu["level"] == 2)
			{
				$parent = count($this->menu[$menu["menu_id.name"]]) - 1;

				if (!isset($this->menu[$menu["menu_id.name"]][$parent]["sub_menu"])) $this->menu[$menu["menu_id.name"]][$parent]["sub_menu"] = array();

				$this->menu[$menu["menu_id.name"]][$parent]["sub_menu"][] = $menu;
			}
		}
	}

	private function add_new_menu($loc)
	{
		// check if menu exists in db, if yes return false to carry on
		if (in_array($loc, $this->all_menus)) return false;

		// create new menu
		$this->db->add_data('menu', array('name' => $loc));

		return true;
	}

	private function get_content_data()
	{
		// if no page found, end here
		if (empty($this->page)) return;

		// grab all content
		$this->content = $this->db->query_all('SELECT * FROM page_content WHERE page_id = :page_id ORDER BY position ASC', array('page_id' => $this->page['id']));
	}

	private function get_all_menus()
	{
		$menus = $this->db->get_all('menu');

		$this->all_menus = array();
		foreach ($menus as $menu) $this->all_menus[] = $menu["name"];
	}
}
/* EOF */
