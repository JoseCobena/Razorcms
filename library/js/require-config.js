/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

require.config({
	baseUrl: RAZOR_BASE_URL + 'library/js',
	waitSeconds: 60,

	paths: {
		"angular": "angular/angular-1.3.13.min",
		"angular-route": "angular/angular-route.min",
		"angular-resource": "angular/angular-resource.min",
		"angular-sanitize": "angular/angular-sanitize.min",
		"angular-cookies": "angular/angular-cookies.min",

		"ui-bootstrap": "ui-bootstrap/ui-bootstrap-custom-tpls-0.12.0.min",

		"cookie-monster": "cookie-monster/cookie-monster.min",

		"jquery": "jquery/jquery-1.11.2.min",
		"jquery-bootstrap": "jquery-bootstrap/bootstrap.min",
		"summernote": "summernote/summernote-custom.min",
		"codemirror": "codemirror/codemirror.min"
	},

	shim: {
		"angular": { exports: "angular" },
		"angular-route": { deps: ["angular"] },
		"angular-resource": { deps: ["angular"] },
		"angular-sanitize": { deps: ["angular"] },
		"angular-cookies": { deps: ["angular"] },

		"ui-bootstrap": { deps: ["angular"] },

		"jquery": { exports: "$" },
		"jquery-bootstrap": { deps: ["jquery"] },
		"summernote": { deps: ["jquery"]},
	}
});
