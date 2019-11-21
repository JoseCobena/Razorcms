/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */
 
require([
	"angular",
	"ui-bootstrap",
	"jquery",
	"jquery-bootstrap",
	"razor/services/rars", 
	"razor/directives/form-controls", 
	"razor/directives/notification", 
	"razor/directives/validation",
	"razor/admin/admin-edit"
], function(angular)
{
	angular.module("razor.admin", [
		"razor.services.rars", 
		"razor.directives.formControls", 
		"razor.directives.notification", 
		"razor.directives.validation", 
		"razor.admin.edit"
	]);

	angular.bootstrap(document.getElementById("razor-admin"), ["razor.admin"]); // Necessary because the Angular files are being loading asynchronously
});