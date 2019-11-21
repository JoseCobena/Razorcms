<?php if (!defined("RAZOR_BASE_PATH")) die("No direct script access to this content");

/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */
 
class RazorZip extends ZipArchive {

	public function add_dir($dir_path, $zip_path = null) 
	{
		// add empty dir
		if (!empty($zip_path)) $this->addEmptyDir($zip_path);

		// go through all files and add
		$dir = opendir($dir_path);
		while ($file = readdir($dir))
		{
			if ($file == '.' || $file == '..' || $file == '.git' || $file == '.svn') continue;
			
			// add files or call itself to add folder
			if (is_file("{$dir_path}/{$file}")) $this->addFile("{$dir_path}/{$file}", "{$zip_path}/{$file}");
			else $this->add_dir("{$dir_path}/{$file}", "{$zip_path}/{$file}");
		}
	}
}

/* EOF */