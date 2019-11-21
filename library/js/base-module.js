/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

// load base angular and jquery for people to use in content and or bootstrap controls
require([
	"angular",
	"ui-bootstrap",
	"jquery",
	"jquery-bootstrap",
	"razor/base/cookie-message"
], function(angular)
{
	angular.module("razor.base", ["razor.base.cookieMessage"]);
	angular.bootstrap(document.getElementById("razor-cookie"), ["razor.base.cookieMessage"]); // Necessary because the Angular files are being loading asynchronously
});