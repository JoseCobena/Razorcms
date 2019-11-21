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
	angular.module("razor.admin.extensions", ["ui.bootstrap"])

	.controller("extensions", function($scope, rars, $rootScope, $modal)
	{
		$scope.content = null;

		$scope.loadExtensions = function()
		{
			// grab page data
			rars.get("ext/list", "all", monster.get("token")).success(function(data)
			{
				$scope.extensions = data.extensions;
			});

			// grab category list
			rars.get("list/repository", "category", monster.get("token")).success(function(data)
			{
				$scope.cats = data.list;
			}).error(function(){
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to load category list."});
			});

			// grab extension list
			rars.get("list/repository", "extension", monster.get("token")).success(function(data)
			{
				$scope.exts = data.list;
			}).error(function(){
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to load extension list."});
			});
		};

		$scope.searchExtensions = function()
		{
			$modal.open(
			{
				templateUrl: RAZOR_BASE_URL + "theme/partial/modal/search-extensions.html",
				controller: "searchExtensionsModal"
			}).result.then(function() {}, function()
			{
				$scope.loadExtensions();
			});
		}

		$scope.removeExtension = function(e)
		{
			// generate id
			var ext = e.type + "__" + e.handle + "__" + e.extension;

			// grab category list
			rars.delete("ext/data", ext, monster.get("token")).success(function(data)
			{
				$scope.loadExtensions();
				$rootScope.$broadcast("global-notification", {"type": "success", "text": "Extension removed from system."});
			}).error(function(){
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to remove extension."});
			});
		}
	})

	.controller("extensionsListAccordion", function($scope, rars, $rootScope)
	{
		$scope.oneAtATime = true;

		$scope.saveSettings = function(e)
		{
			// grab page data
			rars.post("ext/data", e, monster.get("token")).success(function(data)
			{
				$rootScope.$broadcast("global-notification", {"type": "success", "text": "Settings updated."});
			}).error(function(data)
			{
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not save setings, please try again later."});
			});
		};

		// get extension details
		$scope.getExtensionDetails = function(ext)
		{
			$scope.extensionDetails = null;

			if (ext.type == "Theme") ext = ext.layouts[0];

			//grab content list
			rars.post("ext/repository", ext, monster.get("token")).success(function(data)
			{
				$scope.extensionDetails = data.details;
			}).error(function(){
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to load extension details."});
			});
		};
	})

	.controller("searchExtensionsModal", function($scope, $modalInstance)
	{
		$scope.cancel = function()
		{
			$modalInstance.close();
		};
	})

	.controller("searchExtensionsAccordion", function($scope, rars, $rootScope)
	{
		$scope.extensionDetails = null;
		$scope.oneAtATime = true;

		// grab content list
		rars.get("list/repository", "extension", monster.get("token")).success(function(data)
		{
			$scope.repo = data.list;
		}).error(function(){
			$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to load extension list."});
		});

		// grab category list
		rars.get("list/repository", "category", monster.get("token")).success(function(data)
		{
			$scope.cats = data.list;
		}).error(function(){
			$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to load category list."});
		});

		// get extension details
		$scope.getExtensionDetails = function(ext)
		{
			$scope.extensionDetails = null;

			//grab content list
			rars.post("ext/repository", ext, monster.get("token")).success(function(data)
			{
				$scope.extensionDetails = data.details;
			}).error(function(){
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to load extension details."});
			});
		};

		// install extension
		$scope.installExtension = function(ext)
		{
			//grab content list
			rars.post("ext/install", ext, monster.get("token")).success(function(data)
			{
				$scope.isopen = false;
				$rootScope.$broadcast("global-notification", {"type": "success", "text": "Extension installed."});
			}).error(function(){
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Failed to install extension, please install manually."});
			});
		};
	});
});
