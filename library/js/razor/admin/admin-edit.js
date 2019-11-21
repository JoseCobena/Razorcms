/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

define(["angular", "cookie-monster", "jquery", "summernote", "ui-bootstrap"], function(angular, monster, $)
{
	angular.module("razor.admin.edit", ['ui.bootstrap'])

	.controller("edit", function($scope, rars, $modal, $sce, $timeout, $rootScope, $http)
	{
		$scope.user = null;
		$scope.editing = {"handle": null, "id": null};
		$scope.toggle = true;
		$scope.changed = true;
		$scope.clickAndSort = {};

		$scope.site = null;
		$scope.content = null;
		$scope.locations = null;
		$scope.page = null;
		$scope.menu = null;

		$scope.init = function()
		{
			$scope.loginCheck();
			$scope.loadPage();
		};

		$scope.loginCheck = function()
		{
			rars.get("user/basic", "current", monster.get("token")).success(function(data)
			{
				if (!!data.user)
				{
					$scope.user = data.user;
					$scope.loggedIn = true;
					$scope.showLogin = false;
				}
				else
				{
					// clear token and user
					monster.remove("token");
					$scope.user = null;
					$scope.loggedIn = false;
					$scope.showLogin = true;
				}
			});
		};

		$scope.logout = function()
		{
			monster.remove("token");
			$scope.user = null;
			$scope.loggedIn = false;
			window.location.href = RAZOR_BASE_URL;
		};

		$scope.loadPage = function()
		{
			//get system data
			rars.get("system/data", "all", monster.get("token")).success(function(data)
			{
				$scope.system = data.system;
			});

			// get site data
			rars.get("setting/editor", "all", monster.get("token")).success(function(data)
			{
				$scope.site = data.settings;
				if (!$scope.site.icon_position) $scope.site["icon_position"] = "tl"; // default to top left
			});

			// grab content for page
			rars.get("content/editor", RAZOR_PAGE_ID).success(function(data)
			{
				$scope.content = (!data.content || data.content.length < 1 ? {} : data.content);
				$scope.locations = (!data.locations || data.locations.length < 1 ? {} : data.locations);
			});

			// grab page data
			rars.get("page/details", RAZOR_PAGE_ID).success(function(data)
			{
				$scope.page = data.page;

				if (!$scope.page.theme) return;

				// load in theme data
				$http.get(RAZOR_BASE_URL + "extension/theme/" + $scope.page.theme).then(function(response)
				{
					$scope.page.themeData = response.data;
				});
			});

			// all available menus
			rars.get("menu/editor", "all").success(function(data)
			{
				$scope.menus = data.menus;
			});
		};

		$scope.bindHtml = function(html)
		{
			// required due to deprecation of html-bind-unsafe
			return $sce.trustAsHtml(html);
		};

		$scope.startEdit = function()
		{
			$scope.toggle = true;
			$scope.changed = true;
		};

		$scope.stopEdit = function()
		{
			// clear edit stuff
			$scope.editing = {"handle": null, "id": null};
			$scope.toggle = false;
		};

		$scope.saveEdit = function()
		{
			// if already editing, end
			if (!!$scope.editing.id)
			{
				$scope.content[$scope.editing.id].content = $("#" + $scope.editing.handle).code();
				$("#" + $scope.editing.handle).destroy();
			}

			// clear edit stuff
			$scope.editing = {"handle": null, "id": null};
			$scope.savedEditContent = false;
			$scope.savedEditMenu = false;

			// save all content for page
			rars.post("content/editor", {"locations": $scope.locations, "content": $scope.content, "page_id": RAZOR_PAGE_ID}, monster.get("token")).success(function(data)
			{
				// stop edit
				$scope.savedEditContent = true;
				$scope.saveSuccess();
			}).error(function(){
				// stop edit
				$scope.savedEditContent = true;
				$scope.saveSuccess();
			});

			// save all content for page
			rars.post("menu/editor", $scope.menus, monster.get("token")).success(function(data)
			{
				$scope.savedEditMenu = true;
				$scope.saveSuccess();
			}).error(function(){
				// stop edit
				$scope.savedEditMenu = true;
				$scope.saveSuccess();
			});

			$scope.toggle = false;
		};

		$scope.saveSuccess = function()
		{
			if (!$scope.savedEditContent || !$scope.savedEditMenu) return;

			$rootScope.$broadcast("global-notification", {"type": "success", "text": "Changes saved successfully, reloading page in 3 seconds."});

			// dont want to, but the two can't exist together... we need to refresh now, this enables us to have live extensions when logged in :(
			$timeout(function() { window.location = "?" }, 3000);
		};

		$scope.startBlockEdit = function(locCol, content_id)
		{
			if (!$scope.toggle) return;

			// if already editing, end
			if (!!$scope.editing.id)
			{
				$scope.content[$scope.editing.id].content = $("#" + $scope.editing.handle).code();
				$("#" + $scope.editing.handle).destroy();
			}

			// clear edit stuff
			$scope.editing = {
				"handle": locCol + content_id,
				"id": content_id,
			};

			// start summernote and ensure callback for file uploading
			$("#" + locCol + content_id).summernote({
		        onImageUpload: function(files, editor, welEditable)
		        {
					rars.post("file/image", {"files": files}, monster.get("token")).success(function(data)
					{
						for (var i = 0; i < data.files.length; i++)
						{
							editor.insertImage(welEditable, data.files[i].url);
						};
					}).error(function(data)
					{
						$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not upload image, please try again."});
					});
		        },
		        onImageUploadError: function()
		        {
					$rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not upload image, please try again."});
		        }
		    });
		};

		$scope.editingThis = function(handle)
		{
			return (handle === $scope.editing.handle ? true : false);
		};

		$scope.addNewBlock = function(loc, col, block)
		{
			// generate new ID
			var id = (!!block ? block.id : "new-" + new Date().getTime());
			var name = (!!block ? block.name : null);
			var content = (!!block ? block.content : null);
			var extension = (!!block && !!block.type && !!block.handle && !!block.extension ? block.type + "/" + block.handle + "/" + block.extension + "/" + block.extension + ".manifest.json" : null);

			// first add content, then location
			if (extension === null)
			{
				if (!$scope.content) $scope.content = {};
				$scope.content[id] = {"content_id": id, "content": content, "name": name};

				if (!$scope.locations) $scope.locations = {};
				if (!$scope.locations[loc]) $scope.locations[loc] = {};
				if (!$scope.locations[loc][col]) $scope.locations[loc][col] = [];

				$scope.locations[loc][col].push({"id": "new", "content_id": id});
			}
			else
			{
				if (!$scope.locations) $scope.locations = {};
				if (!$scope.locations[loc]) $scope.locations[loc] = {};
				if (!$scope.locations[loc][col]) $scope.locations[loc][col] = [];

				var newBlock = {"id": "new", "extension": extension, "settings": null, "extension_content_settings": null};
				if (!!block.content_settings)
				{
					// save manifest
					newBlock.extension_content_settings = block.content_settings;

					// build default values from manifest
					var settings = {};
					angular.forEach(block.content_settings, function(set, key)
					{
						// set values
						settings[set.name] = set.value;
					});
					newBlock.settings = settings;
				}

				$scope.locations[loc][col].push(newBlock);
			}
		};

		$scope.findBlock = function(loc, col)
		{
			$modal.open(
			{
				templateUrl: RAZOR_BASE_URL + "theme/partial/modal/content-selection.html",
				controller: "contentListModal"
			}).result.then(function(selected)
			{
				$scope.addNewBlock(loc, col, selected);
			});
		};

		$scope.removeContent = function(loc, col, index)
		{
			// remove from locations
			var block = $scope.locations[loc][col].splice(index, 1)[0];

			// remove from content if content item
			if (typeof block.content_id == "string" && block.content_id.substring(0,3) == "new") delete $scope.content[block.content_id];
		};

		$scope.findExtension = function(loc, col)
		{
			$modal.open(
			{
				templateUrl: RAZOR_BASE_URL + "theme/partial/modal/extension-selection.html",
				controller: "extensionListModal"
			}).result.then(function(selected)
			{
				$scope.addNewBlock(loc, col, selected);
			});
		};

		$scope.findMenuItem = function(loc, parentMenuIndex)
		{
			$modal.open(
			{
				templateUrl: RAZOR_BASE_URL + "theme/partial/modal/menu-item-selection.html",
				controller: "menuItemListModal"
			}).result.then(function(selected)
			{
				if (!!selected.label)
				{
					// url link
					if (typeof parentMenuIndex == "undefined") $scope.menus[loc].menu_items.push({"link_label": selected.label, "link_url": selected.link || '#', "link_target": selected.target});
					else
					{
						if (!$scope.menus[loc].menu_items[parentMenuIndex].sub_menu) $scope.menus[loc].menu_items[parentMenuIndex].sub_menu = [];
						$scope.menus[loc].menu_items[parentMenuIndex].sub_menu.push({"link_label": selected.label, "link_url": selected.link || '#', "link_target": selected.target});
					}
				}
				else
				{
					if (typeof parentMenuIndex == "undefined") $scope.menus[loc].menu_items.push({"page_id": selected.id, "page_name": selected.name, "page_link": selected.link, "page_active": selected.active});
					else
					{
						if (!$scope.menus[loc].menu_items[parentMenuIndex].sub_menu) $scope.menus[loc].menu_items[parentMenuIndex].sub_menu = [];
						$scope.menus[loc].menu_items[parentMenuIndex].sub_menu.push({"page_id": selected.id, "page_name": selected.name, "page_link": selected.link, "page_active": selected.active});
					}
				}
			});

			return false;
		};

		$scope.linkIsActive = function(page_id)
		{
			return page_id == RAZOR_PAGE_ID;
		};

		$scope.getMenuLink = function(link)
		{
			return RAZOR_BASE_URL + link;
		};

		$scope.cancelEdit = function()
		{
			$scope.loadPage();
			$scope.changed = null;
			$scope.stopEdit();
		};

		$scope.clickAndSortClick = function(location, index, items)
		{
			// only allow sort when editing
			if ($scope.toggle)
			{
				if (!$scope.clickAndSort[location]) $scope.clickAndSort[location] = {};
				$scope.clickAndSort[location].moveFrom = (!$scope.clickAndSort[location].selected ? index : ($scope.clickAndSort[location].picked != index ? $scope.clickAndSort[location].moveFrom : null));
				$scope.clickAndSort[location].moveTo = ($scope.clickAndSort[location].selected && $scope.clickAndSort[location].picked != null && $scope.clickAndSort[location].picked != index ? index : null);
				$scope.clickAndSort[location].selected = !$scope.clickAndSort[location].selected;
				$scope.clickAndSort[location].picked = index;
				if ($scope.clickAndSort[location].moveTo != null) items.splice($scope.clickAndSort[location].moveTo, 0, items.splice($scope.clickAndSort[location].moveFrom, 1)[0]);
			}
		};
	})

	.controller("contentListModal", function($scope, $modalInstance, rars, $sce)
	{
		$scope.oneAtATime = true;

		rars.get("content/list", "all").success(function(data)
		{
			$scope.content = data.content;
		});

		$scope.cancel = function()
		{
			$modalInstance.dismiss('cancel');
		};

		$scope.close = function(c)
		{
			$modalInstance.close(c);
		};

		$scope.addContent = function(c)
		{
			$scope.close(c);
		};

		$scope.loadHTML = function(html)
		{
			return $sce.trustAsHtml(html);
		};
	})

	.controller("contentListAccordion", function($scope)
	{
		$scope.oneAtATime = true;
	})

	.controller("extensionListModal", function($scope, $modalInstance, rars)
	{
		$scope.oneAtATime = true;

		rars.get("ext/list", "system", monster.get("token")).success(function(data)
		{
			$scope.extensions = data.extensions;
		});

		$scope.cancel = function()
		{
			$modalInstance.dismiss('cancel');
		};

		$scope.close = function(e)
		{
			$modalInstance.close(e);
		};

		$scope.addExtension = function(e)
		{
			$scope.close(e);
		};
	})

	.controller("extensionListAccordion", function($scope)
	{
		$scope.oneAtATime = true;
	})

	.controller("menuItemListModal", function($scope, $modalInstance)
	{
		$scope.cancel = function()
		{
			$modalInstance.dismiss('cancel');
		};

		$scope.close = function(item)
		{
			$modalInstance.close(item);
		};
	})

	.controller("menuItemListAccordion", function($scope, rars)
	{
		$scope.oneAtATime = true;

		// grab content list
		rars.get("page/list", "all").success(function(data)
		{
			$scope.pages = data.pages;
		});

		$scope.addMenuItem = function(item) {
			$scope.$parent.close(item);
		};

		$scope.loadPreview = function(link)
		{
			return RAZOR_BASE_URL + link + "?preview";
		};
	})

	.filter("html", function ($sce)
	{
        return function (html) {
            return $sce.trustAsHtml(html);
        }
	});
});
