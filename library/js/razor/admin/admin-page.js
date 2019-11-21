/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

define(["angular", "cookie-monster", "ui-bootstrap"], function(angular, monster)
{
	angular.module("razor.admin.page", ["ui.bootstrap"])

	.controller("page", function($scope, rars, $rootScope, $http, $modal, $timeout)
	{
		$scope.accessLevels = [
			{"name": "Public Access", "value": 0},
			{"name": "User Level 1", "value": 1},
			{"name": "User Level 2", "value": 2},
			{"name": "User Level 3", "value": 3},
			{"name": "User Level 4", "value": 4},
			{"name": "User Level 5", "value": 5}
		];

		$scope.themeChanged = false;

		$scope.save = function()
		{
			$scope.processing = true;

			rars.post("page/details", $scope.page, monster.get("token")).success(function(data)
			{
				$scope.processing = false;

				if ($scope.themeChanged)
				{
					$rootScope.$broadcast("global-notification", {"type": "success", "text": "Page details saved, theme changed, reloading page in 3 seconds."});

					$timeout(function()
					{
						window.location = RAZOR_BASE_URL + $scope.page.link;
					}, 3000);
				}
				else $rootScope.$broadcast("global-notification", {"type": "success", "text": "Page details saved."});
			}).error(function()
			{
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not save details, please try again later."});
				$scope.processing = false;
			});
		};

		$scope.chooseTheme = function()
		{
			$modal.open(
			{
				templateUrl: RAZOR_BASE_URL + "theme/partial/modal/theme-selection.html",
				controller: "themeListModal"
			}).result.then(function(theme)
			{
				if (theme == "default")
				{
					$scope.page.theme = "";
					$scope.page.themeData = null;
				}
				else
				{
					$scope.page.theme = theme.handle + "/" + theme.extension + "/" + theme.manifest + ".manifest.json";
					$scope.page.themeData = theme;
				}

				$scope.themeChanged = true; // flag so we can reload
			});
		};
	})

	.controller("themeListModal", function($scope, $modalInstance)
	{
		$scope.cancel = function()
		{
			$modalInstance.dismiss('cancel');
		};

		$scope.close = function(theme)
		{
			$modalInstance.close(theme);
		};
	})

	.controller("themeListAccordion", function($scope, rars)
	{
		$scope.oneAtATime = true;

		//grab content list
		rars.get("ext/list", "theme", monster.get("token")).success(function(data)
		{
			$scope.themes = data.extensions;
		});

		// grab content list
		rars.get("list/repository", "extension", monster.get("token")).success(function(data)
		{
			$scope.themeDetails = data.list.extensions.filter(function(obj)
			{
				return obj.type === 'theme';
			});
		}).error(function(){
			$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to load extension list."});
		});

		$scope.selectTheme = function(theme)
		{
			$scope.$parent.close(theme);
		};

		$scope.screenshotPath = function(theme)
		{
			return RAZOR_BASE_URL + "/" + theme.handle + "/" + theme.theme + "/" + theme.layout + "/image/" + theme.screenshot;
		};
	});
});
