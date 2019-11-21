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
	"angular-route",
	"ui-bootstrap",
	"jquery",
	"jquery-bootstrap",
	"razor/services/rars", 
	"razor/directives/form-controls", 
	"razor/directives/notification", 
	"razor/directives/validation",
	"razor/admin/admin-access", 
	"razor/admin/admin-settings", 
	"razor/admin/admin-page", 
	"razor/admin/admin-pages", 
	"razor/admin/admin-content", 
	"razor/admin/admin-profile",
	"razor/admin/admin-extensions"
], function(angular)
{
	angular.module("razor.access", [
		"ngRoute",
		"razor.services.rars", 
		"razor.directives.formControls", 
		"razor.directives.notification", 
		"razor.directives.validation", 
		"razor.admin.access",
		"razor.admin.settings", 
		"razor.admin.page", 
		"razor.admin.pages", 
		"razor.admin.content", 
		"razor.admin.profile", 
		"razor.admin.extensions"
	])

	.config(['$routeProvider', function($routeProvider) {
		$routeProvider
		.when('/page', {templateUrl: RAZOR_BASE_URL + 'theme/partial/admin-page.html', controller: "page"})
		.when('/pages', {templateUrl: RAZOR_BASE_URL + 'theme/partial/admin-pages.html', controller: "pages"})
		.when('/content', {templateUrl: RAZOR_BASE_URL + 'theme/partial/admin-content.html', controller: "content"})
		.when('/extensions', {templateUrl: RAZOR_BASE_URL + 'theme/partial/admin-extensions.html', controller: "extensions"})
		.when('/profile', {templateUrl: RAZOR_BASE_URL + 'theme/partial/admin-profile.html', controller: "profile"})
		.when('/settings', {templateUrl: RAZOR_BASE_URL + 'theme/partial/admin-settings.html', controller: "settings"})
		.when('/password-reset', {templateUrl: RAZOR_BASE_URL + 'theme/partial/admin-page.html', controller: "page"})
	}]);

	angular.bootstrap(document.getElementById("razor-access"), ["razor.access"]); // Necessary because the Angular files are being loading asynchronously
});