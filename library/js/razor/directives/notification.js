/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */
 
define(["angular"], function(angular)
{
	angular.module("razor.directives.notification", [])
 
	.directive('globalNotification', function() {
		return {
			restrict: 'E',
			template: '<div class="notification"><span class="alert text-center" ng-class="{\'notification-message\': show, \'alert-success\': type == \'success\', \'alert-danger\': type == \'danger\'}" ng-show="show"><i class="fa fa-check-circle" ng-class="{\'fa-check-circle\': type, \'fa-exclamation-triangle\': type == \'danger\'}"></i> {{text}}</span></div>',
			controller: function($scope, $timeout, $rootScope)
			{
				$rootScope.$on("global-notification", function(ev, notification)
				{
					$scope.type = notification.type;
					$scope.text = notification.text;
					$scope.show = true;

					$timeout(function() 
					{
						$scope.show = false;
					}, 5000);
				});
			}
		};
	});
});