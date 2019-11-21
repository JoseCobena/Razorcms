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
	angular.module("razor.admin.profile", ["ui.bootstrap"])

	.controller("profile", function($scope, $modal)
	{
		$scope.createUser = function()
		{			
			$modal.open(
			{
				templateUrl: RAZOR_BASE_URL + "theme/partial/modal/create-user.html",
				controller: "createUserModal"
			});
		};
	})

	.controller("createUserModal", function($scope, $modalInstance, $rootScope, rars)
	{
		$scope.accessLevels = [
			{"name": "Admin", "value": 9},
			{"name": "Manager", "value": 8},
			{"name": "Editor", "value": 7},
			{"name": "Contributer", "value": 6},
			{"name": "User 5", "value": 5},
			{"name": "User 4", "value": 4},
			{"name": "User 3", "value": 3},
			{"name": "User 2", "value": 2},
			{"name": "User 1", "value": 1}
		];

		$scope.cancel = function()
		{
			$modalInstance.dismiss('cancel');
		};

		$scope.saveUser = function(newUser)
		{
			rars.post("user/data", newUser, monster.get("token")).success(function(data)
			{
				$rootScope.$broadcast("global-notification", {"type": "success", "text": "New user created."});
				$rootScope.$broadcast("reload-users");
				$modalInstance.close();
			}).error(function(data, header) 
			{ 
				if (header == 409) $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not create user, email address already registered."});
				else $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not create user, please try again later."});
			});
		};	
	})

	.controller("userListAccordion", function($scope, rars, $rootScope, $timeout)
	{
		$scope.oneAtATime = true;
		$scope.accessLevels = [
			{"name": "Admin", "value": 9},
			{"name": "Manager", "value": 8},
			{"name": "Editor", "value": 7},
			{"name": "Contributer", "value": 6},
			{"name": "User 5", "value": 5},
			{"name": "User 4", "value": 4},
			{"name": "User 3", "value": 3},
			{"name": "User 2", "value": 2},
			{"name": "User 1", "value": 1}
		];

		//grab content list
		rars.get("user/list", "all", monster.get("token")).success(function(data)
		{
			$scope.users = data.users;
		}); 

		$rootScope.$on("reload-users", function()
		{
			rars.get("user/list", "all", monster.get("token")).success(function(data)
			{
				$scope.users = data.users;
			}); 
		});

		$scope.saveUser = function(index)
		{
			$scope.processing = true;

			rars.post("user/data", $scope.users[index], monster.get("token")).success(function(data)
			{
				$scope.processing = false;
				if (!!data.reload)
				{
					$rootScope.$broadcast("global-notification", {"type": "success", "text": "User details saved, password change, logging out in 3 seconds."});

					$timeout(function()
					{
						window.location = RAZOR_BASE_URL;
					}, 3000);
				}
				else $rootScope.$broadcast("global-notification", {"type": "success", "text": "User details saved."});
			}).error(function() 
			{ 
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not save details, please try again later."});
				$scope.processing = false; 
			});
		};

		$scope.removeUser = function(index)
		{
			$scope.processing = true;

			rars.delete("user/data", $scope.users[index].id, monster.get("token")).success(function(data)
			{
				$scope.processing = false;
				if (!!data == "reload")
				{
					$rootScope.$broadcast("global-notification", {"type": "success", "text": "Your details have been removed, logging out in 3 seconds."});

					$timeout(function()
					{
						window.location = RAZOR_BASE_URL;
					}, 3000);
				}
				else $rootScope.$broadcast("global-notification", {"type": "success", "text": "User has been removed."});

				$scope.users.splice(index, 1);
			}).error(function() 
			{ 
				$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not remove user, please try again later."});
				$scope.processing = false; 
			});
		};
	});
});