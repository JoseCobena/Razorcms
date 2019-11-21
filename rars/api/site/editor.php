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
 
class SiteEditor extends RazorAPI
{
    function __construct()
    {
        // REQUIRED IN EXTENDED CLASS TO LOAD DEFAULTS
        parent::__construct();
    }

    public function get($page_id)
    {
        $db = new RazorDB();

        // get menu data too
        $db->connect("site");

        $search = array("column" => "id", "value" => 1);
        $site = $db->get_rows($search);
        $site = $site["result"][0];
        
        $db->disconnect();  

        // return the basic user details
        $this->response(array("site" => $site), "json");
    }
}

/* EOF */