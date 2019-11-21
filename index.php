<?php

/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

// set session
session_start();
session_regenerate_id();

// sidewide constants
define("RAZOR_BASE_PATH", str_replace(array("index.php"), "", $_SERVER["SCRIPT_FILENAME"]));
$port = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443" ? "" : ":{$_SERVER["SERVER_PORT"]}");
define("RAZOR_BASE_URL", (isset($_SERVER['https']) && !empty($_SERVER['https']) ? "https://" : "http://").$_SERVER["SERVER_NAME"].$port.str_replace(array("index.php"), "", $_SERVER["SCRIPT_NAME"]));
define("RAZOR_USERS_IP", $_SERVER["REMOTE_ADDR"]);
define("RAZOR_USERS_UAGENT", $_SERVER["HTTP_USER_AGENT"]);

// build and cleanup
if (is_dir(RAZOR_BASE_PATH."build"))
{
	if (!is_dir(RAZOR_BASE_PATH."extension")) mkdir(RAZOR_BASE_PATH."extension");
	if (!is_dir(RAZOR_BASE_PATH."rars/api/extension")) mkdir(RAZOR_BASE_PATH."rars/api/extension");
	if (!is_dir(RAZOR_BASE_PATH."storage")) mkdir(RAZOR_BASE_PATH."storage");
	if (!is_dir(RAZOR_BASE_PATH."storage/database")) mkdir(RAZOR_BASE_PATH."storage/database");
	if (!is_dir(RAZOR_BASE_PATH."storage/files")) mkdir(RAZOR_BASE_PATH."storage/files");
	if (!is_dir(RAZOR_BASE_PATH."storage/log")) mkdir(RAZOR_BASE_PATH."storage/log");

	if (!is_file(RAZOR_BASE_PATH."storage/database/razorcms.sqlite")) copy(RAZOR_BASE_PATH."build/razorcms.sqlite", RAZOR_BASE_PATH."storage/database/razorcms.sqlite");

	unlink(RAZOR_BASE_PATH."build/razorcms.sqlite");
	rmdir(RAZOR_BASE_PATH."build");
}

// permission defines
// 6 to 10 - access to admin dash
define("SUPER_ADMIN", 10); // only one account with this and it cannot be removed
define("ADMIN", 9); // pretty much the same as super admin but can be removed
define("MANAGER", 8); // add, edt, remove content only
define("EDITOR", 7); // add, edit content only
define("CONTRIBUTER", 6); // add content only
// 1 to 5 - no access to admin dash, user levels only
define("USER_5", 5); // base level, can onlyalter profile and user areas of public site that are protected to level 1
define("USER_4", 4); // base level, can onlyalter profile and user areas of public site that are protected to level 1
define("USER_3", 3); // base level, can onlyalter profile and user areas of public site that are protected to level 1
define("USER_2", 2); // base level, can onlyalter profile and user areas of public site that are protected to level 1
define("USER_1", 1); // base level, can onlyalter profile and user areas of public site that are protected to level 1

// MAILGUN
define('MAILGUN_KEY', getenv('MAILGUN_KEY'));
define('MAILGUN_DOMAIN', getenv('MAILGUN_DOMAIN'));
define('MAILGUN_MAILER_ADDRESS', getenv('MAILGUN_MAILER_ADDRESS'));

// PDO
define('RAZOR_PDO', 'sqlite:'.RAZOR_BASE_PATH.'storage/database/razorcms.sqlite');

// includes
include_once(RAZOR_BASE_PATH.'library/php/razor/razor_file_tools.php');
include_once(RAZOR_BASE_PATH.'library/php/razor/razor_error_handler.php');
include_once(RAZOR_BASE_PATH.'library/php/razor/razor_site.php');
include_once(RAZOR_BASE_PATH."library/php/razor/razor_pdo.php");
require(RAZOR_BASE_PATH.'library/vendor/autoload.php');


// Load error handler
$error = new RazorErrorHandler();
set_error_handler(array($error, 'handle_error'));
set_exception_handler(array($error, 'handle_error'));


// continue with public load
$site = new RazorSite();
$site->load();
$site->render();

/* PHP END */
