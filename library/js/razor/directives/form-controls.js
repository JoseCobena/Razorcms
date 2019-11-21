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
	angular.module("razor.directives.formControls", [])
 
	.directive('slideSwitch', function() {
		return {
			restrict: 'E',
			scope: {"rzrModel": "=", "rzrDisabled": "="},
			template: '<div class="slide-switch" title="{{(rzrDisabled ? \'Disabled\' : \'\')}}" ng-class="{\'slide-switch-on\': rzrModel, \'slide-switch-disabled\': rzrDisabled}" ng-click="rzrModel = (rzrDisabled ? rzrModel : !rzrModel)"><span class="slide-switch-slider"><span ng-hide="rzrModel">OFF</span><span ng-show="rzrModel">ON</span></span></div>'
		};
	})
 
	.directive('multiSelect', function() {
		return {
			restrict: 'E',
			scope: {"rzrSelected": "=", "rzrOptions": "=", "rzrValue": "=", "rzrLabel": "="},
			template: '<div class="multi-select">' +
				'<ul class="rzr-selected" ng-show="rzrSelected.length > 0">' +
					'<li ng-repeat="sel in rzrSelected" class="rzr-selected-item">' +
						'{{sel[rzrLabel]}}' +
						'<i class="ms-remove-item fa fa-times" ng-click="rzrSelected.splice($index, 1)"></i>' +
					'</li>' +
				'</ul>' +	
				'<i class="ms-input-filter fa fa-filter" ng-show="selectaOptions"></i><i class="ms-input-select fa fa-caret-down" ng-hide="selectaOptions"></i>' +
				'<input class="form-control" class="ms-filter" type="text" ng-model="search" ng-focus="selectaOptions = true" ng-blur="hideOptions()" placeholder="Click to select, filter on options">' +
				'<ul class="rzr-options" ng-show="selectaOptions">' +
					'<li ng-repeat="opt in rzrOptions | filter:search | filter:hideSelected" ng-click="rzrSelected.push(opt)" class="ms-option-item">{{opt[rzrLabel]}}</li>' +
					'<li class="ms-option-item-empty" ng-show="rzrOptions.length === rzrSelected.length"><i class="fa fa-ban"></i> empty</li>' +
				'</ul>' +
			'</div>',
			controller: function($scope, $timeout)
			{
				$scope.hideSelected = function(opt)
				{
					var result = true

					angular.forEach($scope.rzrSelected, function(val)
					{
						if (val[$scope.rzrValue] === opt[$scope.rzrValue]) result = false;
					});

					return result;
				};

				$scope.hideOptions = function()
				{
					$timeout(function() {
						$scope.selectaOptions = false;
					}, 250);
				};
			}
		};
	})

	.directive('rzrFileModel', ['$parse', function ($parse) {
		return {
			restrict: 'A',
			link: function($scope, $element, $attrs) {
				var model = $parse($attrs.rzrFileModel);
				
				$element.bind('change', function(){
					$scope.$apply(function(){
						model.assign($scope, $element[0].files);
					});
				});
			}
		};
	}]);
});