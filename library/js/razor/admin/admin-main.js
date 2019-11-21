/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

define(["angular", "cookie-monster", "nicedit", "ui-bootstrap"], function(angular, monster, nicedit)
{
    angular.module("razor.admin.main", ['ui.bootstrap'])

    .controller("main", function($scope, $location, rars, $modal, $sce, $timeout, $rootScope, $http)
    {
        $scope.location = $location;
        $scope.user = null;
        $scope.loginDetails = {"u": null, "p": null};
        $scope.passwordDetails = {"password": null, "repeat_password": null};
        $scope.editorInstance = null;
        $scope.editing = {"handle": null, "id": null, "code": null};
        $scope.toggle = null;
        $scope.changed = null;
        $scope.dash = null;
        $scope.clickAndSort = {};
        $scope.signature = null;

        $scope.site = null;
        $scope.content = null;
        $scope.locations = null;
        $scope.page = null;
        $scope.menu = null;

        $scope.init = function()
        {
            $scope.loginCheck();

            // nav active watcher
            $scope.$watch("location.path()", function(path)
            {
                $scope.activePage = path.split("/")[1];
            });

            $scope.loadPage();
        };

        $scope.login = function()
        {
            $scope.processing = true;

            rars.post("login", $scope.loginDetails).success(function(data)
            {
                if (!!data.user)
                {
                    // save cookie and redirect user based on access level
                    monster.set("token", data.token, null, "/");
                    $scope.user = data.user;
                    $scope.showLogin = false;
                    $scope.processing = false;
                    window.location.href = RAZOR_BASE_URL + "#page";
                }
                else
                {
                    // clear token and user
                    monster.remove("token");

                    $scope.showLogin = true;
                    $scope.processing = false;

                    if (data.login_error_code == 101) $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Login failed."});
                    if (data.login_error_code == 102) $rootScope.$broadcast("global-notification", {"type": "danger", "text": "You have been locked out, try again in " + (!!data.time_left ? Math.ceil(data.time_left / 60) : 0) + "min."});
                    if (data.login_error_code == 103) $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Account not activated, click link in activation email to activate."});
                    if (data.login_error_code == 104) $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Too many failed attempts, your IP has been banned."});
                }
            })
            .error(function(data)
            {
                $scope.showLogin = true;
                $scope.processing = false;
                $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Login failed."});
            });
        };

        $scope.forgotLogin = function()
        {
            $scope.processing = true;

            rars.post("user/reminder", {"email": $scope.loginDetails.u})
                .success(function(data)
                {
                    $rootScope.$broadcast("global-notification", {"type": "success", "text": "Password reset link emailed to you, you have one hour to use the link."});
                    $scope.processing = false;
                })
                .error(function(data, header)
                {
                    $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not send password request, user not found or too many requests in last ten minutes."});
                    $scope.processing = false;
                }
            );
        };

        $scope.passwordReset = function()
        {
            // only runs if page set to password-reset due to ng-init and ng-if
            // check for token, do base check on it
            var token = $location.path().split("/")[2];
            console.debug(token);
            console.debug(token.length);
            if (token.length < 20) return;

            // if there, send of for reset (which is only valid for an hour anyway)
            $scope.processing = true;

            rars.post("user/password", {"signature": $scope.signature, "passwords": $scope.passwordDetails, "token": token}).success(function(data)
            {
                // show success message, show login form
                $rootScope.$broadcast("global-notification", {"type": "success", "text": "Password reset complete, please log in."});
                $scope.processing = false;
                $scope.location.path('page');
            })
            .error(function(data)
            {
                // show failed message but give no reason why
                $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not reset password, try requesting a new reset. Returning home in 5 seconds"});
                $timeout(function() { window.location.href = RAZOR_BASE_URL }, 5000);
            });
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
            rars.get("site/editor", "all").success(function(data)
            {
                $scope.site = data.site;
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

        $scope.openDash = function()
        {
            $scope.dash = true;
            $scope.location.path('page');
        };

        $scope.closeDash = function()
        {
            $scope.loadPage();
            $scope.dash = false;
            $scope.location.path('page');
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
            // stop any edits
            $scope.stopBlockEdit();

            $scope.toggle = false;
        };

        $scope.saveEdit = function()
        {
            // stop any edits
            $scope.stopBlockEdit();

            $scope.savedEditContent = false;
            $scope.savedEditContent = false;

            // save all content for page
            rars.post("content/editor", {"locations": $scope.locations, "content": $scope.content, "page_id": RAZOR_PAGE_ID}, monster.get("token")).success(function(data)
            {
                // update page
                $scope.locations = data.locations;
                $scope.content = data.content;

                // stop edit
                $scope.savedEditContent = true;
                $scope.saveSuccess();
            });

            // save all content for page
            rars.post("menu/editor", $scope.menus, monster.get("token")).success(function(data)
            {
                $scope.savedEditMenu = true;
                $scope.saveSuccess();
            });

            $scope.toggle = false;
            $scope.changed = false;
        };

        $scope.saveSuccess = function()
        {
            if (!$scope.savedEditContent || !$scope.savedEditMenu) return;

            $rootScope.$broadcast("global-notification", {"type": "success", "text": "Changes saved successfully, reloading page in 3 seconds."});

            // dont want to, but the two can't exist together... we need to refresh now, this enables us to have live extensions when logged in :(
            $timeout(function() { window.location.reload() }, 3000);
        };

        $scope.startBlockEdit = function(locCol, content_id)
        {
            if (!$scope.toggle) return;

            // stop any edits
            $scope.stopBlockEdit();

            // load editor
            $scope.editing.handle = locCol + content_id;
            $scope.editing.id = content_id;

            $scope.editorInstance = new nicEditor({fullPanel : true, uploadURI : RAZOR_BASE_URL + "rars/file/image", authToken : monster.get("token")}).panelInstance($scope.editing.handle);
            // hide text-area
            angular.element(document.querySelector("#" + $scope.editing.handle)).addClass("hide");
        };

        $scope.stopBlockEdit = function()
        {
            if (!!$scope.editorInstance && !!$scope.editorInstance.instanceById($scope.editing.handle))
            {
                // copy data and end editor
                $scope.content[$scope.editing.id].content = $scope.editorInstance.instanceById($scope.editing.handle).getContent();

                // end editor
                $scope.editorInstance.removeInstance($scope.editing.handle);

                // show text-area
                angular.element(document.querySelector("#" + $scope.editing.handle)).removeClass("hide");
            }

            // clear edit stuff
            $scope.editing = {"handle": null, "id": null, "code": null};
            $scope.editorInstance = null;
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
                $scope.locations[loc][col].push({"id": "new", "extension": extension});
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
                if (typeof parentMenuIndex == "undefined") $scope.menus[loc].menu_items.push({"page_id": selected.id, "page_name": selected.name, "page_link": selected.link, "page_active": selected.active});
                else
                {
                    if (!$scope.menus[loc].menu_items[parentMenuIndex].sub_menu) $scope.menus[loc].menu_items[parentMenuIndex].sub_menu = [];
                    $scope.menus[loc].menu_items[parentMenuIndex].sub_menu.push({"page_id": selected.id, "page_name": selected.name, "page_link": selected.link, "page_active": selected.active});
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

        $scope.addNewPage = function(loc)
        {
            $modal.open(
            {
                templateUrl: RAZOR_BASE_URL + "theme/partial/modal/add-new-page.html",
                controller: "addNewPageModal"
            }).result.then(function(redirect)
            {
                if (!!redirect) window.location = RAZOR_BASE_URL + redirect;
            });
        };

        $scope.clickAndSortClick = function(location, index, items)
        {
            if (!$scope.clickAndSort[location]) $scope.clickAndSort[location] = {};
            $scope.clickAndSort[location].moveFrom = (!$scope.clickAndSort[location].selected ? index : ($scope.clickAndSort[location].picked != index ? $scope.clickAndSort[location].moveFrom : null));
            $scope.clickAndSort[location].moveTo = ($scope.clickAndSort[location].selected && $scope.clickAndSort[location].picked != null && $scope.clickAndSort[location].picked != index ? index : null);
            $scope.clickAndSort[location].selected = !$scope.clickAndSort[location].selected;
            $scope.clickAndSort[location].picked = index;
            if ($scope.clickAndSort[location].moveTo != null) items.splice($scope.clickAndSort[location].moveTo, 0, items.splice($scope.clickAndSort[location].moveFrom, 1)[0]);
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

    .controller("addNewPageModal", function($scope, $modalInstance, rars, $rootScope)
    {
        $scope.page = {};
        $scope.processing = null;
        $scope.completed = null;
        $scope.newPage = null;

        $scope.cancel = function()
        {
            $modalInstance.dismiss();
        };

        $scope.closeAndEdit = function()
        {
            $modalInstance.close($scope.newPage.link);
        };

        $scope.addAnother = function()
        {
            $scope.completed = null;
            $scope.processing = null;
            $scope.page = {};
        };

        $scope.saveNewPage = function()
        {
            $scope.processing = true;
            $scope.completed = false;

            rars.post("page/data", $scope.page, monster.get("token")).success(function(data)
            {
                $scope.newPage = data;
                $rootScope.$broadcast("global-notification", {"type": "success", "text": "New page saved successfully."});
                $scope.processing = false;
                $scope.completed = true;
            }).error(function()
            {
                if (!data.code) $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Could not save page, please try again later."});
                else if (data.code == 101) $rootScope.$broadcast("global-notification", {"type": "danger", "text": "Link is not unique, already being used by another page."});
                $scope.processing = false;
            });
        };

    });
});
