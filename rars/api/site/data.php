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
 
class SiteData extends RazorAPI
{
    function __construct()
    {
        // REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
        parent::__construct();
    }

    // add or update content
    public function post($data)
    {
        // login check - if fail, return no data to stop error flagging to user
        if ((int) $this->check_access() < 10) $this->response(null, null, 401);
        if (empty($data)) $this->response(null, null, 400);

        $db = new RazorDB();
        $db->connect("site");
        $search = array("column" => "id", "value" => 1);
        $row = array();

        if (isset($data["name"])) $row["name"] = $data["name"];
        if (isset($data["google_analytics_code"])) $row["google_analytics_code"] = $data["google_analytics_code"];

        $db->edit_rows($search, $row);
        $db->disconnect(); 

        $this->response("success", "json");
    }
}

/* EOF */