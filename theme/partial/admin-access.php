<!-- admin html -->
<body class="razor-access">

	<?php
		// generate signature 
		if ($this->site["allow_registration"])
		{
			// check server details
			$signature = null;
			if (isset($_SERVER["REMOTE_ADDR"], $_SERVER["HTTP_USER_AGENT"]) && !empty($_SERVER["REMOTE_ADDR"]) && !empty($_SERVER["HTTP_USER_AGENT"]))
			{
				// signature generation
				$signature = sha1($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"].rand(0, 100000));
				$_SESSION["signature"] = $signature;
			}
		}
	?>
 
	<?php if ($this->site["allow_registration"]): ?>
		<script type="text/javascript">
			var RAZOR_FORM_SIGNATURE = '<?php echo $signature ?>';
		</script>
	<?php endif ?>

	<!--[if lt IE 9]>
		<div class="ie8 ie8-admin">
			<p class="message">
				<i class="fa fa-exclamation-triangle"></i> You are using an outdated version of Internet Explorer that is not supported, 
				please update your browser or consider using an alternative, modern browser, such as 
				<a href="http://www.google.com/chrome">Google Chome</a>.
			</p>
		</div>
	<![endif]-->
	<div id="razor-access" class="ng-cloak" ng-controller="access" ng-init="init('<?php echo $this->site['name'] ?>', '<?php echo $this->site["allow_registration"] ?>')">

		<global-notification></global-notification>

		<?php if ($this->logged_in > 0): ?>
			<div class="razor-access-panel" ng-class="site.icon_position" ng-show="user.id">
				<i class="razor-logo razor-logo-50 razor-logo-black-circle dashboard-icon mobile-hide-inline-block {{site.icon_position}}" ng-class="{'flash': !!firstAccess}" ng-hide="changed" ng-click="persist = !persist"></i>
				<i class="razor-logo razor-logo-25 razor-logo-black-circle dashboard-icon mobile-show-inline-block {{site.icon_position}}" ng-class="{'flash': !!firstAccess}" ng-hide="changed" ng-click="persist = !persist"></i>

				<div class="inner-panel {{site.icon_position}}" ng-class="{'persist': persist}">
					<div class="account-details text-right">
						<?php if ($this->logged_in > 5): ?>
							<i class="fa fa-cog fa-4x pull-left admin-panel-icon" ng-click="persist = false; openDash()"></i>
						<?php endif ?>
						<span class="name">
							{{user.name}} 
							<a href="#" ng-click="editProfile()"><i class="fa fa-user" data-toggle="tooltip" data-placement="bottom" title="User Profile"></i></a>
							<a href="#" ng-click="logout()"><i class="fa fa-sign-out" data-toggle="tooltip" data-placement="bottom" title="Sign Out"></i></a>
						</span>
						<span class="last-login-date">Last login: {{user.last_logged_in * 1000 | date:'EEE, MMM d, y'}}</span>
					</div>				
					<?php if ($this->logged_in > 5): ?>
						<div class="editor-controls">
							<a href="?edit" class="btn btn-sm btn-primary" ng-if="user.access_level > 6 || !page.active">
								<i class="fa fa-pencil"></i><span class="mobile-hide-inline"> Edit Page</span>
							</a>
							<button class="btn btn-sm btn-default" ng-click="addNewPage()" ng-hide="toggle || changed">
								<i class="fa fa-file-text-o"></i><span class="mobile-hide-inline"> Add New Page</span>
							</button>
							<button class="btn btn-sm btn-default" ng-click="copyPage()" ng-hide="toggle || changed">
								<i class="fa fa-files-o"></i><span class="mobile-hide-inline"> Copy This Page</span>
							</button>
						</div>
					<?php endif ?>
				</div>
			</div>
		<?php endif ?>

		<?php if ($this->logged_in > 5): ?>
			<div class="razor-admin-dashboard" ng-if="dash && user.id">
				<div class="dash-nav">
					<div class="container">
						<div class="row">
							<div class="col-sm-12">	
								<div class="dash-controls">
									<ul class="dashbar-nav pull-left">
										<li class="close-dash">
											<a href="#" ng-click="closeDash()">
												<i class="fa fa-times close-dash"></i><span class="mobile-hide-inline"> Close</span>
											</a>
										</li>
										<li class="page-details" ng-class="{'active':activePage == 'page'}">
											<a href="#page">
												<i class="fa fa-file-text-o"></i><span class="mobile-hide-inline"> Page</span>
											</a>
										</li>
									</ul>
									<ul class="dashbar-nav pull-right">
										<li ng-class="{'active':activePage == 'pages'}">
											<a href="#pages">
												<i class="fa fa-files-o"></i><span class="mobile-hide-inline"> Pages</span>
											</a>
										</li>
										<li ng-class="{'active':activePage == 'content'}">
											<a href="#content">
												<i class="fa fa-th-large"></i><span class="mobile-hide-inline"> Content</span>
											</a>
										</li>
										<li ng-class="{'active':activePage == 'extensions'}" ng-if="user.access_level > 8">
											<a href="#extensions">
												<i class="fa fa-puzzle-piece"></i><span class="mobile-hide-inline"> Extensions</span>
											</a>
										</li>
										<li ng-class="{'active':activePage == 'profile'}" ng-if="user.access_level == 10">
											<a href="#profile">
												<i class="fa fa-user"></i><span class="mobile-hide-inline"> Users</span>
											</a>
										</li>
										<li ng-class="{'active':activePage == 'settings'}" ng-if="user.access_level > 8">
											<a href="#settings">
												<i class="fa fa-cog"></i><span class="mobile-hide-inline"> Settings</span>
												<i class="fa fa-exclamation-triangle" ng-show="upgrade" style="color: #f00;"></i>
											</a>
										</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="dash-workspace">
					<div class="container">
						<div class="row">
							<div class="col-lg-12">
								<div class="workspace" ng-show="user.id" ng-view></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif ?>
	</div>
<!-- /body in template - do not close -->