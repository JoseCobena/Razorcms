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
	angular.module("razor.admin.content", [])

	.controller("content", function($scope, rars, $sce, $rootScope)
	{
		$scope.content = null;

		$scope.loadContent = function()
		{
			// grab page data
			rars.get("content/list", "all").success(function(data)
			{
				$scope.content = data.content;
			});
		};

		$scope.deleteContent = function(contentId)
		{
			rars.delete("content/data", contentId, monster.get("token")).success(function(data)
			{
				$rootScope.$broadcast("global-notification", {"type": "success", "text": "Content deleted successfully."});

				// clean up any locations or content in active data
				angular.forEach($scope.content, function(con, index)
				{
					if (con.id == contentId) $scope.content.splice(index, 1);
				});
			}).error(function()
			{
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Error deleting page."});
			});	   
		}; 

		$scope.loadHTML = function(html)
		{
			return $sce.trustAsHtml(html);
		};

		$scope.pageLink = function(link)
		{
			return RAZOR_BASE_URL + link;
		};
	})

	.controller("contentListAccordion", function($scope)
	{
		$scope.oneAtATime = true;
	});
});