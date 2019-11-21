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
 
class RazorFileTools
{
	// fetch remote file using CURL
	public static function get_remote_content($url)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  FALSE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    
	    return $data;
	}

	/**
	 * Look recursively through folders and return a flat array of paths or contents for each match
	 * 
	 * @param string $path The folder to start searching
	 * @param string $search The filename to look for in each folder
	 * @param string $type What to return, "path" (filepath), or string, json to return contents in that style
	 * @param string $match What to match, "all" for whole filename match, end for end match
	 */
	public static function find_file_contents($path, $search, $type = "path", $match = "all")
	{
		$folders_files = self::read_dir_contents($path);

		if (empty($folders_files)) return array();

		$matches = array();

		foreach ($folders_files as $ff)
		{
			if (is_dir("{$path}/{$ff}")) $matches = array_merge($matches, self::find_file_contents("{$path}/{$ff}", $search, $type, $match));

			if ($match == "all" && $ff == $search)
			{
				if ($type != "path") $matches[] = self::read_file_contents("{$path}/{$ff}", $type);
				else $matches[] = "{$path}/{$ff}";
			}
			
			if ($match == "end" && strpos($ff, $search) !== false)
			{
				if ($type != "path") $matches[] = self::read_file_contents("{$path}/{$ff}", $type);
				else $matches[] = "{$path}/{$ff}";
			}
		}

		return $matches;
	}


	/**
	 * Look through json files for a matching keys value, only checks top level keys
	 * 
	 * @param string $path The file to search
	 * @param array $search The values to find [["key" => string, "search" => string],[]...]
	 */
	public static function select_from_json_php_file($path, $search_array)
	{
		if (isset($search_array["key"])) $search_array = array($search_array);
		$data = RazorFileTools::read_file_contents($path, "json.php");
	
		if (empty($data)) return;

		$match = array();
		foreach ($data as $row)
		{
			$match_count = 0;
			foreach ($search_array as $search)
			{
				if (!isset($row->$search["key"]) || $row->$search["key"] != $search["search"]) continue;
				$match_count++;
			}

			if ($match_count == count($search_array)) $match[] = $row;
		}
		return $match;
	}

	/**
	 * Look through json files for a matching keys value, only checks top level keys
	 * 
	 * @param string $path The folder to search
	 * @param string $key search specific key
	 * @param string $search The value to look for in the file
	 */
	public static function find_json_php_file($path, $key, $search)
	{
		$files = self::read_dir_contents($path, 'files');
		if (empty($files)) return;

		foreach ($files as $file)
		{
			// do pre search to speed things up
			if (stripos(self::read_file_contents("{$path}/{$file}", 'string'), $search) === false) continue;
			
			// we have some kind of match, so find it
			$data = self::read_file_contents("{$path}/{$file}", 'json.php');

			if (isset($data->$key) && $data->$key === $search) return $data;
		}
	}

	/**
	 * Fetch Remote File
	 * Retrieves remote file contents and returns the data
	 *
	 * @param string $url The full url path of the file to fetch
	 * @return mixed Data string or false on fail
	 */
	public static function fetch_remote_file($url){
		// get host name and url path //
		$parsedUrl = parse_url($url);
		$host = $parsedUrl['host'];
		if (isset($parsedUrl['path'])) {
			$path = $parsedUrl['path'];
		} else {
			// url is pointing to host //
			$path = '/';
		}
		if (isset($parsedUrl['query'])) {
			$path.= '?' . $parsedUrl['query'];
		}
		if (isset($parsedUrl['port'])) {
			$port = $parsedUrl['port'];
		} else {
			$port = '80';
		}
		$timeOut = 10;
		$reply = '';
		// connect to remote server //
		$fp = @fsockopen($host, '80', $errno, $errstr, $timeOut );
		if ( !$fp ) throw new Exception("Failed to connect to remote server {$host}");
		else 
		{
			// send headers //
			$headers = "GET $path HTTP/1.0\r\n";
			$headers.= "Host: $host\r\n";
			$headers.= "Referer: http://$host\r\n";
			$headers.= "Connection: Close\r\n\r\n";
			fwrite($fp, $headers);
			// retrieve the reply //
			while (!feof($fp)) {
				$reply.= fgets($fp, 256);
			}
			fclose($fp);
			// strip headers //
			$tempStr = strpos($reply, "\r\n\r\n");
			$reply = substr($reply, $tempStr + 4);
		}
		// return content //
		return $reply;
	}

	/**
	 * Delete File
	 * Removes a file from the structure
	 *
	 * @param string $path The full data path
	 * @return bool True on pass false on fail
	 */
	public static function delete_file($path) 
	{
		if (@unlink($path)) return true;

		throw new Exception("Failed to delete {$path}");
	}


	/**
	 * Delete Directory
	 * Removes a directory and all files and folders in it
	 *
	 * @param string $path Full path of directory to remove
	 * @return bool True on pass false on fail
	 */
	public static function delete_directory($path) {
		$readDir = $path;
		if (is_dir($readDir)) {
			$filesArray = array();
			$filesArray = self::read_dir_contents($readDir);
			// do recursive delete if dir contains files //
			foreach($filesArray as $name) {
				if (is_dir($path.'/'.$name)) {
					self::delete_directory($path.'/'.$name);

				} elseif (file_exists($readDir.'/'.$name)) {
					self::delete_file($path.'/'.$name, false);
				}
			}
			// remove dir //
			if (rmdir($readDir)) {
				return true;
			} else {
				throw new Exception("Failed remove directory {$readDir}");
			}
		} else {
			throw new Exception("Failed to find directory {$readDir}");

			return false;
		}
	}


	/**
	 * Read Dir Contents
	 * Reads the contants of a directory and returns array structure
	 *
	 * @param string $dir Full path to directory
	 * @param string $type Recurrance setting to allow function to reuse itself (NOT TO BE USED)
	 * @return array Directory contents in array format
	 */
	public static function read_dir_contents($dir, $type = 'all') {
		$files = array();
		$dir_handler = opendir($dir);

		if (! $dir_handler)
		{
			throw new Exception("Failed to open directory {$dir}");

			return false;
		}

		while ($filename = readdir($dir_handler)) {
			if ($filename == '.' || $filename == '..' || $filename == '.svn' || $filename == '.git') {
				continue;
			}

			switch ($type)
			{
				case 'all':
					$files[$filename] = $filename;
				break;
				case 'files':
					if (is_file($dir.'/'.$filename)) $files[$filename] = $filename;
				break;
				case 'folder':
					if (is_dir($dir.'/'.$filename)) $files[$filename] = $filename;
				break;
			}
   		}
		closedir($dir_handler);

		return $files;
	}


	/**
	 * Read File Contents
	 * Reads a local file and returns contents
	 *
	 * @param string $path Full path to file
	 * @param string $type Optional string or array format for retun value (default string)
	 * @return mixed String or array of file data, or false on fail
	 */
	public static function read_file_contents($path, $type = 'string')
	{
		if (!is_file($path)) return false;

		switch ($type)
		{
			case "array":
				return file($path);
			break;
			case "json":
				return json_decode(file_get_contents($path));
			break;
			case "json.php":
				$data = file_get_contents($path);				
				return json_decode(trim(substr($data, strpos($data, "\n"))));
			break;
		}

		return file_get_contents($path);
	}


	/**
	 * Write File Contents
	 * Write contents to a fail, creates file if not exist, if exist, overwrites
	 *
	 * @param string $path Full path to file
	 * @param string $data Data to write
	 * @return bool True on pass, false on fail
	 */
	public static function write_file_contents($path, $data)
	{
		if (empty($path))
		{
			throw new Exception("File path is empty");

			return false;
		}

		if (empty($data))
		{
			throw new Exception("File data is empty");

			return false;
		}

		return file_put_contents($path, $data);
	}


	/**
	 * Rename File
	 * Renames a file or folder
	 *
	 * @param string $old_name File to change
	 * @param string $new_name New file name
	 * @return bool True on pass, false on fail
	 */
	public static function rename_file($old_name, $new_name)
	{
		if (@rename($old_name, $new_name)) return true;

		throw new Exception("Failed to rename '{$old_name}' to '{$new_name}'");
		return false;
	}


	/**
	 * Unix File Permissions
	 * Get UNIX file permissions
	 *
	 * @param string $file Full path to file
	 * @return mixed file permissions string (e.g. 0777) or false on fail
	 */
	public static function unix_file_permissions($file)
	{
		if(!file_exists($file)) {
			return false;
		}
		$perms = sprintf('%o', fileperms($file));
		if ( substr($perms, 0, 2) == '40' ) {
			$perms = substr($perms, 1, 4);
		} elseif ( substr($perms, 0, 2) == '10' ) {
			$perms = substr($perms, 2, 4);
		}
		return $perms;
	}


	/**
	 * Create Dir
	 * Creates a directory
	 *
	 * @param string $dir_to_create Full path to new file, will force safe permissions if on linux server
	 * @return bool True on pass, false on fail
	 */
	public static function create_dir($dir_to_create)
	{
		if (mkdir($dir_to_create))
		{
			if (self::findServerOS() == 'LINUX')
			{
				$perms = self::unix_file_permissions($dir_to_create);
				if ( $perms != '0755') @chmod($dirPath, 0755);
			}

			return true;
		} else {
			throw new Exception("Error creating directory '{$dir_to_create}'");

			return false;
		}
	}


	/**
	 * Copy Dir
	 * Copy a directory and all its contents
	 *
	 * @param string $fromDir Full path to dir to copy
	 * @param string $toDir Full path to new location of copy
	 * @return bool True on pass, false on fail
	 */
	public static function copy_dir($fromDir, $toDir) {
		$file_tools = new RazorFileTools(get_class($this));

		$result = false;
		$readFromDir = $fromDir;
		$readToDir = $toDir;
		$file_tools->create_dir($readToDir);
		if (is_dir($readFromDir)) {
			$filesArray = array();
			$filesArray = $file_tools->read_dir_contents($readFromDir);
			// do recursive delete if dir contains files //
			foreach($filesArray as $name) {
				if (is_dir($readFromDir.'/'.$name)) {
					$result = self::copy_dir($fromDir.'/'.$name, $toDir.'/'.$name);
				} elseif (file_exists($readFromDir.'/'.$name)) {
					$result = self::copy_file($fromDir.'/'.$name, $toDir.'/'.$name, false);
				}
			}
		}
		return $result;
	}


	/**
	 * Copy File
	 * Copy a single file
	 *
	 * @param string $copyFrom Full path to file to copy
	 * @param string $copyTo Full path to new location of file to be copied
	 * @return bool True on pass, false on fail
	 */
	public static function copy_file($copyFrom, $copyTo) {
		$fileFrom = $copyFrom;
		$fileTo = $copyTo;
		if (copy($fileFrom, $fileTo)) {
			if (self::findServerOS() == 'LINUX') {
				$perms = file_perms($fileTo);
				if ( $perms != '0644') {
					@chmod($fileTo, 0644);
				}
			}
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Upload File
	 * Upload a file from form upload
	 *
	 * @param string $remoteFile Full path of new file
	 * @param string $uploadFile Full temporary path of file that was uploaded
	 * @return bool True on pass, false on fail
	 */
	public static function upload_file($remoteFile, $uploadFile) {
		$filename = $remoteFile;
		if (move_uploaded_file($uploadFile, $filename)) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * find server OS type
	 * detects server OS type
	 *
	 * @return string WIN on LINUX
	 */
    public static function findServerOS() 
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'WIN';
        }
        
        return 'LINUX';
    }
}

/* EOF */