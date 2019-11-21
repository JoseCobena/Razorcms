/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */
 
define(["angular", "cookie-monster"], function(angular, monster)
{
	angular.module("razor.base.cookieMessage", [])

	.controller("cookieMessage", function($scope)
	{
		$scope.hideMessage = monster.get("cookieMessage");

		$scope.agree = function()
		{
			$scope.hideMessage = true;
			monster.set("cookieMessage", true, 90); // store for at least 90 days so it doesn't keep hassling people
		};
	});
});