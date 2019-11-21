<?php
namespace extension;

if (!defined("RAZOR_BASE_PATH")) die("No direct script access to this content");

/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2015
 */

/**
 * Implement Razor Extension Theme to force structure of theme extension
 * You are required to create setup and view methods for configuring and displaying theme
 */
interface RazorExtensionTheme
{
	private function create_extension();
	private function create_theme();

	public function setup();
	public function view();
}

/**
* Implement Razor Extension Instance to force structure of instance extension (extension added to a content area)
* You are required to create setup, controller and view methods for configuring controlling, data tasks and output to content area
*/
interface RazorExtensionInstance
{
	private function create_extension();
	private function create_instance();

	public function setup();
	public function controller();
	public function view();
}

/**
 * RazorExtension
 * Extend this class and use correct interface above to create a new theme or instance extension
 * Once extended, you may access site specific data through $this->site (like db)
 * Setup is run first to configure your extension.
 * Themes will now fire the view command to output.
 * Instances will fire controller to allow data tasks to be performed via private methods, followed by view.
 */
class RazorExtension
{
	// system level properties from RazorSite class
	protected $site = null
	protected $ext_base_path = null;

	// Extension Properties
	protected $type = null;
	protected $handle = null;
	protected $extension = null;
	protected $name = null;
	protected $author = null;
	protected $version = null;
	protected $created = null;
	protected $description = null;
	protected $settings = null;

	/**
	 * @param Object &$site The razor_site object passed by reference so we may access system resources available to razor_site
	 * @param array $content [optional] The instance content e.g. when a content area loads extension, the content area info
	 */
	function __construct(&$site, $content = null)
	{
		if (empty(__NAMESPACE__)) throw new exception('Extension must provide namespace to match extension path structure');
		$this->ext_base_path = RAZOR_BASE_PATH.'/'.str_replace('\\', '/', __NAMESPACE__).'/';

		// read manifest
		if (!is_file("{$this->ext_base_path}manifest.json")) throw new Exception('Could not locate extension manifest');
		$manifest = RazorFileTools::read_file_contents("{$this->ext_base_path}manifest.json", "json");

		// create ref to razor_sites properties and methods
		if (!empty($site)) $this->site &= $site;

		// grab implemented interface
		$interfaces = class_implements($this);
		if (empty($interfaces)) throw new exception('Extension must implement correct interface');

		// setup extension and instance
		$this->create_extension($manifest);
		if (in_array('RazorExtensionTheme', $interfaces)) $this->create_theme($manifest);
		if (in_array('RazorExtensionInstance', $interfaces)) $this->create_instance($content);

		// run child construct setup
		$this->setup();
	}

	private function create_extension($manifest)
	{
		// create extension
		$this->version = isset($manifest->version) ? $maifest->version : null;
		$this->created = isset($manifest->created) ? $maifest->created : null;
		$this->name = isset($manifest->name) ? $maifest->name : null;
		$this->description = isset($manifest->description) ? $maifest->description : null;
		$this->author = isset($manifest->author) ? $maifest->author : null;
		$this->type = isset($manifest->type) ? $maifest->type : null;
		$this->handle = isset($manifest->handle) ? $maifest->handle : null;
		$this->extension = isset($manifest->extension) ? $maifest->extension : null;

		// setup settings for this extension (global)
		$extension = $this->site->razor_db->get_first(
			'extension',
			array("json_settings"),
			array("type" => $this->manifest->type, "handle" => $this->manifest->handle, "extension" => $this->manifest->extension)
		);

		if (!empty($extension)) $this->settings = json_decode($extension['json_settings']);
	}

	private function create_theme($manifest)
	{
		if (!isset($manifest->view) || empty($manifest->view)) return;

		$this->theme = new StdClass();
		$this->theme->view = isset($manifest->view) ? $manifest->view : null;
		$this
	}

	private function create_instance($content)
	{
		if (empty($content)) return;

		$this->instance = new StdClass();

// once we know this, we can map it below to instance
// $this->instance = '';

		// setup settings for this instance (local)
		$s = json_decode($content["json_settings"]);
		foreach ($this->manifest->content_settings as $m_set) $this->instance->settings->{$m_set->name} = (isset($s->{$m_set->name}) && !empty($s->{$m_set->name}) ? $s->{$m_set->name} : $m_set->value);
	}
}
// /* EOF */
