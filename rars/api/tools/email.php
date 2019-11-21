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
 
class ToolsEmail extends RazorAPI
{
	function __construct()
	{
		// REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
		parent::__construct();

		session_start();
		session_regenerate_id();
	}

	public function post($data)
	{
		// Check details
		if (!isset($_SERVER["REMOTE_ADDR"], $_SERVER["HTTP_USER_AGENT"], $_SERVER["HTTP_REFERER"], $_SESSION["signature"])) $this->response(null, null, 400);
		if (empty($_SERVER["REMOTE_ADDR"]) || empty($_SERVER["HTTP_USER_AGENT"]) || empty($_SERVER["HTTP_REFERER"]) || empty($_SESSION["signature"])) $this->response(null, null, 400);

		// check referer matches the site
		if (strpos($_SERVER["HTTP_REFERER"], RAZOR_BASE_URL) !== 0) $this->response(null, null, 400);

		// check data
		if (!isset($data["signature"], $data["email"], $data["message"], $data["extension"]["type"], $data["extension"]["handle"], $data["extension"]["extension"])) $this->response(null, null, 400);
		if (empty($data["signature"]) || empty($data["email"]) || empty($data["message"]) || empty($data["extension"]["type"]) || empty($data["extension"]["handle"]) || empty($data["extension"]["extension"])) $this->response(null, null, 400);
		if (!isset($data["human"]) || !empty($data["human"])) $this->response("robot", "json", 406);

		// get signature and compare to session
		if ($_SESSION["signature"] !== $data["signature"]) $this->response(null, null, 400);
		unset($_SESSION["signature"]);
		session_destroy();

		// create manifest path for extension that requested email
		$ext_type = preg_replace('/[^A-Za-z0-9-]/', '', $data["extension"]["type"]);
		$ext_handle = preg_replace('/[^A-Za-z0-9-]/', '', $data["extension"]["handle"]);
		$ext_extension = preg_replace('/[^A-Za-z0-9-]/', '', $data["extension"]["extension"]);
		$manifest_path = RAZOR_BASE_PATH."extension/{$ext_type}/{$ext_handle}/{$ext_extension}/{$ext_extension}.manifest.json";
		
		if (!is_file($manifest_path)) $this->response(null, null, 400);

		$manifest = RazorFileTools::read_file_contents($manifest_path, "json");

		// fetch extension settings and look for email
		$db = new RazorDB();
		$db->connect("extension");
		$options = array(
			"amount" => 1,
			"filter" => array("json_settings")
		);
		$search = array(
			array("column" => "type", "value" => $manifest->type),
			array("column" => "handle", "value" => $manifest->handle),
			array("column" => "extension", "value" => $manifest->extension)
		);
		$extension_settings = $db->get_rows($search, $options);
		$extension_settings = $extension_settings["result"][0]["json_settings"];
		$db->disconnect();  

		if (empty($extension_settings)) $this->response(null, null, 400);
		$extension_settings = json_decode($extension_settings);

		// get site data
		$db->connect("setting");
		$res = $db->get_rows(array("column" => "id", "value" => null, "not" => true));
		$db->disconnect(); 

		$settings = array();

		foreach ($res["result"] as $result)
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
				
		// clean email data
		$to = $extension_settings->email;
		$from = preg_replace('/[^A-Za-z0-9-_+@.]/', '', $data["email"]);
		$subject = "{$settings["name"]} Contact Form";
		$message = htmlspecialchars($data["message"], ENT_QUOTES);

		// send to email response
		$this->email($from, $to, $subject, $message);

		// return the basic user details
		$this->response("success", "json");
	}
}

/* EOF */