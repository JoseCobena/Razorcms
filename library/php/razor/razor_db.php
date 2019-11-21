<?php if (!defined('RAZOR_BASE_PATH')) exit('No direct script access allowed');

/**
 * razorCMS FBCMS
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */
 
class RazorDB 
{
	private $connected = null;
	private $handle = null;
	private $lock = null;
	private $file = null;
	private $bck_file = null;
	private $table = null;
	private $counter = null;
	private $columns = null;
	private $row_count = null;
	private $protect = null;
	private $order = null;

	/* construct/destruct */

	function __destruct()
	{
		if ($this->connected) $this->disconnect();
	}


	/* Private Local Functions */

	private function sort($a, $b) 
	{
		if ($this->order["direction"] == "asc") return $a[$this->order["column"]] - $b[$this->order["column"]];
		if ($this->order["direction"] == "desc") return $b[$this->order["column"]] - $a[$this->order["column"]];
		return 0;
	}

	private function open($type = 'r')
	{
		// try file open
		$this->handle = fopen($this->file, $type);
		if ($this->handle)  {
	  		return true;
	  	}

	  	// if editing is going on, try to open temp if file not present, this will only happen if master is removed during edit and will always be updated first.
		$this->handle = fopen($this->bck_file, $type);
		if ($this->handle)  {
	  		return true;
	  	}

	  	// return error on non bad con
	  	trigger_error("Failed to open db file for '{$this->table}'");
		return false;
	}

	private function close()
	{
		if ($this->handle)
		{
			fflush($this->handle);
			fclose($this->handle);
			$this->handle = null;
			return true;
		}

		// no connection, cannot disconnect
	  	trigger_error("Failed to close db file for '{$this->table}'");
		return false;
	}

	private function lock()
	{
		// try for lock
		$c = 0; // retries
		while ($this->lock) {
			// refresh headers
			$this->headers();

			// carry on
			if ($c >= 100)
			{
		  		trigger_error("Failed to obtain lock for {$this->table} db file, file in use");
				return false;
			}
			usleep(rand(100, 10000));
			$c++;
		}

		// continue with locking //

		$this->open('r+');

		// move pointer to lock
		fseek($this->handle, 9, SEEK_SET);

		// check found lock
		if (stream_get_line($this->handle, 5) != 'lock:')
		{
			// no lock found
		  	trigger_error("Failed to find lock for '{$this->table}'");
		  	$this->close();
			return false;
		}

		// apply lock
		fwrite($this->handle, '1', 1);
		$this->lock = true;
		$this->close();
		return true;
	}

	private function unlock()
	{
		if ($this->lock)
		{
			$this->open('r+');
			// move pointer to lock
			fseek($this->handle, 9, SEEK_SET);
			// check found lock
			if (stream_get_line($this->handle, 5) != 'lock:')
			{
				// no lock found
			  	trigger_error("Failed to find lock for '{$this->table}'");
			  	$this->close();
				return false;
			}
			// apply lock
			fwrite($this->handle, '0', 1);
			$this->lock = false;
			$this->close();
			return true;
		}

		return false;
	}

	private function update_counter()
	{
		$this->open('r+');

		// move pointer to row count (not counter, that is for id's, count is for row count)
		fseek($this->handle, 19, SEEK_SET);

		// check found lock
		if (stream_get_line($this->handle, 4) != 'inc:')
		{
			// no count found
		  	trigger_error("Failed to find count for '{$this->table}'");
		  	$this->close();
			return false;
		}

		// prepare counter
		$str_counter = (string) $this->counter;
		while (strlen($str_counter) <= 30) $str_counter.= '-';

		// apply lock
		fwrite($this->handle, $str_counter, 30);
		$this->close();
		return true;
	}

	private function update_row_count()
	{
		$this->open('r+');

		// move pointer to row count (not counter, that is for id's, count is for row count)
		fseek($this->handle, 57, SEEK_SET);

		// check found lock
		if (stream_get_line($this->handle, 10) != 'row_count:')
		{
			// no count found
		  	trigger_error("Failed to find count for '{$this->table}'");
		  	$this->close();
			return false;
		}

		// prepare counter
		$str_row_count = (string) $this->row_count;
		while (strlen($str_row_count) <= 30) $str_row_count.= '-';

		// apply lock
		fwrite($this->handle, $str_row_count, 30);
		$this->close();
		return true;
	}

	private function data_in($data)
	{
		// clean unwanted carriage returns or pipe chars they hurt db, quotes, slashes and html is fine
		return (!is_string($data) ? $data : str_replace(array('|', "\n", "\r", "/*", "*/", '`', '<?', '?>', '&#10;'), array('[[pipe]]', '[[slash-n]]', '[[slash-r]]', '[[comment-on]]', '[[comment-off]]', "'", '', '', ''), $data));
	}

	private function data_out($data)
	{
		$data = substr($data, 0, -1);
		return (!is_string($data) ? $data : str_replace(array('[[pipe]]', '[[slash-n]]', '[[slash-r]]', '[[comment-on]]', '[[comment-off]]'), array('|', "\n", "\r", "/*", "*/"), $data));
	}

	private function headers()
	{
		$this->open();
		fseek($this->handle, 0, SEEK_SET);

		// fetch headers
		for ($i = 0; $i < 10; $i++)
		{
			switch ($i)
			{
				case 0:
					// php tag
					stream_get_line($this->handle, 100, "\n");
				break;
				case 1:
					// lock
					$lock = str_replace(array("\r"), array(''), stream_get_line($this->handle, 20, "\n"));
					if (substr($lock, 3, 5) !== 'lock:')
					{
						trigger_error("Failed to find table lock in '{$this->table}' db file, db file corrupt");
						$this->close();
						return false;
					}
					$this->lock = (bool) substr($lock, 8, 1);
				break;
				case 2:
					// counter
					$counter = str_replace(array("\r"), array(''), stream_get_line($this->handle, 100, "\n"));
					if (substr($counter, 3, 4) !== 'inc:')
					{
						trigger_error("Failed to find counter in '{$this->table}' db file, db file corrupt");
						$this->close();
						return false;
					}
					$this->counter = (int) str_replace('-', '', substr($counter, 7, 30));
				break;
				case 3:
					// row count
					$count = str_replace(array("\r"), array(''), stream_get_line($this->handle, 100, "\n"));
					if (substr($count, 3, 10) !== 'row_count:')
					{
						trigger_error("Failed to find row count in '{$this->table}' db file, db file corrupt");
						$this->close();
						return false;
					}
					$this->row_count = (int) str_replace('-', '', substr($count, 13, 30));
				break;
				case 4:
					// start headers
					stream_get_line($this->handle, 100, "\n");
				break;
				case 5:
					// table name
					$table = str_replace(array("\r"), array(''), stream_get_line($this->handle, 1024, "\n"));
					if (substr($table, 3, 6) !== 'table:')
					{
						trigger_error("Failed to find table name '{$this->table}' in db file, db file corrupt");
						$this->close();
						return false;
					}
					$this->table = substr($table, 9);
				break;
				case 6:
					// columns
					$cols = str_replace(array("\r"), '', stream_get_line($this->handle, 1024, "\n"));
					if (substr($cols, 3, 8) !== 'columns:')
					{
						trigger_error("Failed to find columns in '{$this->table}' db file, db file corrupt");
						$this->close();
						return false;
					}
					$col_data = explode('|',substr($cols, 11));
					foreach ($col_data as $property)
					{
						$props = explode(':', $property);
						$this->columns[$props[1]] = array(
							'column'	=> $props[0],
							'name'		=> $props[1],
							'type'		=> $props[2],
							'nullable'	=> ($props[3] == 1 ? true : false)
						);
					}
				break;
				case 7:
					// spare
					stream_get_line($this->handle, 1024, "\n");
				break;
				case 8:
					// spare
					stream_get_line($this->handle, 1024, "\n");
				break;
				case 9:
					// end headers
					stream_get_line($this->handle, 1024, "\n");
				break;
			}
		}

		$this->close();
		return true;
	}

	// perform pre-query before commiting to full on query, can do on multi lines or single line
	private function pre_query($rows_string, $search, $single = false)
	{
		// grab all search matches
		$pos = null;
		foreach ($search as $data)
		{
			if (isset($data['not']) && $data['not'] === true && $single === false) return true; // force matching if not found, so it does full check

			$s_data = array(
				'value'				=> (isset($data['value']) ? ($data['value'] === true ? '1' : ($data['value'] === false ? '0' : (string) $data['value'])) : 'null'),
				'case_insensitive'	=> (isset($data['case_insensitive']) ? $data['case_insensitive'] : false),
				'wildcard'			=> (isset($data['wildcard']) ? $data['wildcard'] : false),
				'not'				=> (isset($data['not']) ? $data['not'] : false)
			);

			if (!$s_data['wildcard'])
			{
				// try for exact match
				if (!$s_data['case_insensitive']) $pos = strpos($rows_string, (string)$this->columns[$data['column']]['column'].'`'.$s_data['value'].'`'); // case sensitive
				else $pos = stripos($rows_string, (string)$this->columns[$data['column']]['column'].'`'.$s_data['value'].'`'); // case insensitive
			}
			else
			{
				// try for wildcard match
				if (!$s_data['case_insensitive']) $pos = strpos($rows_string, (string) $s_data['value']); // case sensitive
				else $pos = stripos($rows_string, (string) $s_data['value']); // case insensitive
			}

			// check for match, if not revert it for single line pre-query not multi line
			if (!$s_data['not'])
			{
				if ($pos !== false) return true;
			}
			else
			{
				if ($pos === false) return true;
			}
		}

		// no match
		return false;
	}

	// perform search query
	private function query($row_data, $search, $return_match = true)
	{
		// quick dirty check to see if in row
		if (!$this->pre_query($row_data, $search, true)) return false;

		/* continue with proper query if found */

		$row_values = explode('|',substr($row_data, 7));

		$row = array();
		$match = null;
		$i = 0;
		foreach ($this->columns as $column)
		{
			// cast values correctly
			if ($row_values[$i] === 'null')
			{
				$value = null;
			}
			else
			{
				switch ($column['type'])
				{
					case 'int':
						$value = (int) $this->data_out(substr($row_values[$i], strlen($column['column']) + 1));
					break;
					case 'float':
						$value = (float) $this->data_out(substr($row_values[$i], strlen($column['column']) + 1));
					break;
					case 'bool':
						$value = (bool) $this->data_out(substr($row_values[$i], strlen($column['column']) + 1));
					break;
					default:
						$value = (string) $this->data_out(substr($row_values[$i], strlen($column['column']) + 1));
					break;
				}
			}

			// check search values to see if we return the row
			if (!is_array($search) || empty($search))
			{
				trigger_error("No search data found for query on {$this->table} db file");
				return false;
			}

			// grab all search matches
			foreach ($search as $data)
			{
				$s_data = array(
					'column'			=> (isset($data['column']) ? $data['column'] : null),
					'value'				=> (isset($data['value']) ? $data['value'] : null),
					'not'				=> (isset($data['not']) ? $data['not'] : false),
					'and'				=> (isset($data['and']) ? $data['and'] : false),
					'wildcard'			=> (isset($data['wildcard']) ? $data['wildcard'] : false),
					'case_insensitive'	=> (isset($data['case_insensitive']) ? $data['case_insensitive'] : false),
				);

				if ($column['name'] !== $s_data['column']) continue;

				// perform matching
				if ($column['type'] === 'string')
				{
					if ($s_data['wildcard'])
					{
						if ($s_data['case_insensitive'])
						{
							if ($s_data['not'] && stripos($value, $s_data['value']) === false)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif ($s_data['not'] && stripos($value, $s_data['value']) >= 0)
							{
								if ($s_data['and']) $match = false;
							}

							if (!$s_data['not'] && stripos($value, $s_data['value']) !== false)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif (!$s_data['not'] && stripos($value, $s_data['value']) >= 0)
							{
								if ($s_data['and']) $match = false;
							}
						}
						else
						{
							if ($s_data['not'] && strpos($value, $s_data['value']) === false)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif ($s_data['not'] && strpos($value, $s_data['value']) >= 0)
							{
								if ($s_data['and']) $match = false;
							}

							if (!$s_data['not'] && strpos($value, $s_data['value']) !== false)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif (!$s_data['not'] && strpos($value, $s_data['value']) >= 0)
							{
								if ($s_data['and']) $match = false;
							}
						}
					}
					else
					{
						if ($s_data['case_insensitive'])
						{
							if ($s_data['not'] && strcasecmp($value, $s_data['value']) !== 0)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif ($s_data['not'] && strcasecmp($value, $s_data['value']) === 0)
							{
								if ($s_data['and']) $match = false;
							}

							if (!$s_data['not'] && strcasecmp($value, $s_data['value']) === 0)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif (!$s_data['not'] && strcasecmp($value, $s_data['value']) !== 0)
							{
								if ($s_data['and']) $match = false;
							}
						}
						else
						{
							if ($s_data['not'] && strcmp($value, $s_data['value']) !== 0)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif ($s_data['not'] && strcmp($value, $s_data['value']) === 0)
							{
								if ($s_data['and']) $match = false;
							}

							if (!$s_data['not'] && strcmp($value, $s_data['value']) === 0)
							{
								if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
							}
							elseif (!$s_data['not'] && strcmp($value, $s_data['value']) !== 0)
							{
								if ($s_data['and']) $match = false;
							}
						}
					}
				}
				else
				{
					// non string, no case or wildcard allowed
					if ($s_data['not'] && $value !== $s_data['value'])
					{
						if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
					}
					elseif ($s_data['not'] && $value === $s_data['value'])
					{
						if ($s_data['and']) $match = false;
					}

					if (!$s_data['not'] && $value === $s_data['value'])
					{
						if (!$s_data['and'] || ($s_data['and'] && $match === null)) $match = true;
					}
					elseif (!$s_data['not'] && $value !== $s_data['value'])
					{
						if ($s_data['and']) $match = false;
					}
				}
			}
			$row[$column['name']] = $value;
			$i++;
		}

		if ($match && $return_match) return $row;
		if ($match) return true;
		return false;
	}


	/* Management Functions */


	/**
	 * add_table Adds a table to the database
	 * @param string $table The name of the table to add
	 * @param array $columns The columns to add in the new table [['column':string, 'datatype':string, 'nullable':bool],...]
	 * datatype as string, bool, int, float
	 * @return bool True on pass, false on fail
	 */
	public function add_table($table, $columns)
	{
		// check all data present
		if (empty($table) || !is_string($table) || empty($columns) || !is_array($columns))
		{
		  	// return error bad data
		  	trigger_error("Add new table data is invalid, please check your data");
			return false;
		}

		// check no collisions
		if (is_file(RAZOR_BASE_PATH."storage/database/{$table}.db.php"))
		{
		  	// return error table found
		  	trigger_error("Cannot add new table, the name '{$table}' is already being used");
			return false;
		}

		// construct column data
		$c = 2;
		$columns_string = "// columns:1:id:int:0";
		foreach ($columns as $col)
		{
			// check for id
			if ($col['column'] === 'id')
			{
			  	// return error table found
			  	trigger_error("Cannot add column 'id' to table, this is a reserved column name");
				return false;
			}

			// check datatype
			if (!in_array($col['datatype'], array('string', 'bool', 'int', 'float')))
			{
			  	// return error table found
			  	trigger_error("Datatype '{$col['datatype']}' invalid, please use valid datatype [string, bool, int, float]");
				return false;
			}

			// force datatypes
			$col['column'] = (string) $col['column'];
			$col['datatype'] = (string) $col['datatype'];
			$col['nullable'] = ((bool) $col['nullable'] === true ? 1 : 0);

			// add deliminator before data if not first
			$columns_string.= "|{$c}:{$col['column']}:{$col['datatype']}:{$col['nullable']}";

			$c++;
		}

		// build string for file
		$file_data = "<?php\n";
		$file_data.= "// lock:0\n";
		$file_data.= "// inc:1-----------------------------\n";
		$file_data.= "// row_count:0-----------------------------\n";
		$file_data.= "// --- start headers ---\n";
		$file_data.= "// table:{$table}\n";
		$file_data.= "{$columns_string}\n";
		$file_data.= "// ---\n";
		$file_data.= "// ---\n";
		$file_data.= "// --- end headers ---";

		// write string to new file
		$new_handle = fopen(RAZOR_BASE_PATH."storage/database/{$table}.db.php", "c");
		fwrite($new_handle, $file_data, 2048);
		fflush($new_handle);
	   	fclose($new_handle);

		return true;
	}


	/**
	 * delete_table Remove a table from the database
	 * @param string $table The name of the table to delete
	 * @return bool True on pass, false on fail
	 */
	public function delete_table($table)
	{
		$table = str_replace(array('../', '..', '/', ' '), array('', '', '', '_'), $table);

		// check all table name present
		if (empty($table) || !is_string($table))
		{
		  	// return error bad data
		  	trigger_error("No table name provided, cannot delete table");
			return false;
		}

		// check no collisions
		if (!is_file(RAZOR_BASE_PATH."storage/database/{$table}.db.php"))
		{
		  	// return error table found
		  	trigger_error("Cannot delete table, '{$table}' is not a valid table");
			return false;
		}

		// remove table file
		unlink(RAZOR_BASE_PATH."storage/database/{$table}.db.php");

		return true;
	}


	/**
	 * rename_table Rename a table to something else, does not require a connection to use.
	 *
	 * @param string $table Name of the table to rename
	 * @param string $rename_table New name for the table
	 * @return bool True on pass, false on fail
	 */
	public function rename_table($table, $rename_table)
	{
		// check table name present
		if (empty($table) || !is_string($table) || empty($rename_table) || !is_string($rename_table))
		{
		  	// return error bad data
		  	trigger_error("Both old table name and new table name need to be provided");
			return false;
		}

		// check filename present
		if (!is_file(RAZOR_BASE_PATH."storage/database/{$table}.db.php"))
		{
		  	// return error table found
		  	trigger_error("Cannot rename table, '{$table}' is not a valid table");
			return false;
		}

		// check new name does not exist
		if (is_file(RAZOR_BASE_PATH."storage/database/{$rename_table}.db.php"))
		{
		  	// return error table found
		  	trigger_error("Cannot rename table '{$table}' to '{$rename_table}', new table name is already present");
			return false;
		}

		// open table
		$handle = fopen(RAZOR_BASE_PATH."storage/database/{$table}.db.php", 'r');
		$rename_handle = fopen(RAZOR_BASE_PATH."storage/database/{$rename_table}.db.php", 'c');

		fseek($handle, 0, SEEK_SET);

		// do first line
		$line = stream_get_line($handle, 1049000, "\n");
		fwrite($rename_handle, $line);

		// do other lines
		$c = 1;
		while (!feof($handle))
		{
			// read in line
			$line = stream_get_line($handle, 1049000, "\n");

			// rename the table bit in the file
			if ($c <= 10)
			{
				// table name
				$table_line = str_replace(array("\r"), array(''), $line);
				if (substr($table_line, 3, 6) === 'table:')
				{
					$line = str_replace($table, $rename_table, $line);
				}
			}

			// write to new file
			fwrite($rename_handle, "\n".$line);
			$c++;
		}

		// close both files
		fclose($handle);
		fclose($rename_handle);

		// remove old table
		unlink(RAZOR_BASE_PATH."storage/database/{$table}.db.php");
		return true;
	}


	/* TBD */
	public function add_columns($table, $add_columns)
	{

	}


	/* TBD */
	public function delete_columns($table, $del_columns)
	{

	}


	/* TBD */
	public function rename_columns($table, $rename_columns)
	{

	}


	/* Database Functions */


	/**
	 * Connect to table
	 *
	 * Connect manually to a table with default read only permission
	 * @param string $table The name of the table to connect to
	 * @param bool $write Optional, connect with write permissions
	 * @return bool True on connection
	 */
	public function connect($table)
	{
		// resolve table
		$this->table = strtolower($table);
		$this->file = RAZOR_BASE_PATH."storage/database/{$this->table}.db.php";
		$this->bck_file = RAZOR_BASE_PATH."storage/database/~{$this->table}.db.php";

		if (!is_file($this->file))
		{
		  	// return error on non bad con
		  	trigger_error("Failed to connect to {$this->table} db file");
			return false;
		}

		$this->connected = true;
		return true;
	}


	/**
	 * Disconnect from table
	 *
	 * Disconnect manually from the table (auto disconnect on object destroy if still connected)
	 * @return bool True on disconnect
	 */
	public function disconnect()
	{
		// clean up in case obaject reused
		$this->connected = null;
		$this->handle = null;
		$this->lock = null;
		$this->file = null;
		$this->table = null;
		$this->counter = null;
		$this->columns = null;

		return true;
	}


	/**
	 * Get headers for table
	 *
	 * Fetch all the headers for the table
	 * @return mixed The headers in array format or false on fail
	 */
	public function get_headers()
	{
		// check connected
		if (!$this->connected)
		{
			trigger_error("Not connected to table, cannot perform query");
			return false;
		}

		// resolve table name and file
		$time = microtime(true);

		// get headers
		if (empty($this->columns))
		{
			$this->headers();
		}

		$result = array(
			'table' 		=> $this->table,
			'lock' 			=> $this->lock,
			'next_id'		=> $this->counter,
			'row_count'		=> $this->row_count,
			'time'			=> microtime(true) - $time,
			'result'		=> $this->columns
		);
		return $result;
	}


	/**
	 * Get rows from search
	 *
	 * Fetch all the rows found from a search on a columns
	 * Using extra parameters you can specify, not, wildcard, case insensitive as well as specify how to use one search with another (default is or)
	 * @param array $search Search [column => string, value => mixed[, not => bool, and => bool, case_insensitive => bool, wildcard => bool]]
	 * @param array $options Any options to use ['filter': array['col1', 'col2'...], 'amount': int, 'join': array['table' => string, 'join_to' => string]] multiple joins can be added to array, all joins are inner
	 * @return mixed The data in array format ('table' => 'name, 'count' => resultCount, 'result' => rows(('col' => 'val',...)) or false on fail
	 */
	public function get_rows($search, $options = null)
	{
		// check connected
		if (!$this->connected)
		{
			trigger_error("Not connected to table, cannot perform query");
			return false;
		}

		// resolve table name and file
		$time = microtime(true);

		// options
		$amount = (isset($options['amount']) ? $options['amount'] : null);
		$filter = (isset($options['filter']) ? $options['filter'] : null);
		$joins = (isset($options['join']) ? $options['join'] : null );
		$order = (isset($options['order']) ? $options['order'] : null );
		$join_filters = array();

		// get table headers
		if (empty($this->columns))
		{
			if (!$this->headers()) return false;
		}

		// check for single or multi array
		if (isset($search['column'])) $search = array($search);
		foreach ($search as $search_data)
		{
			if (!isset($search_data['column']))
			{
				trigger_error("'column' not provided in search");
				return false;
			}

			if (!isset($this->columns[$search_data['column']]))
			{
				trigger_error("Column '{$search_data['column']}' does not exist in table '{$this->table}'");
				return false;
			}
		}

		// resolve join data
		if ($joins !== null)
		{
			if (isset($joins['table'])) $joins = array($joins);

			foreach ($joins as $join_data)
			{
				// check all join data present
				if (!isset($join_data['table']) || !isset($join_data['join_to']))
				{
					trigger_error("Join needs 'table', 'join_to' in order to join a table");
					return false;
				}

				// check join table exists
				if (!is_file(RAZOR_BASE_PATH."storage/database/{$join_data['table']}.db.php"))
				{
				  	// return error on non bad con
				  	trigger_error("Cannot join table '{$join_data['table']}', table does not exist");
					return false;
				}

				// check join_to column exists in parent table
				if (!isset($this->columns[$join_data['join_to']]))
				{
					trigger_error("Cannot join to column '{$join_data['join_to']}' in parent table '{$this->table}', column does not exist");
					return false;
				}

				// collate filters if set
				if (!empty($filter))
				{
					$join_filters[$join_data["table"]] = array("id");
					
					foreach ($filter as $f)
					{
						$filter_name = explode(".", $f);
						if (count($filter_name) != 2 || $filter_name[0] != $join_data["join_to"]) continue;
						$join_filters[$join_data["table"]][] = $filter_name[1];
					}
				}
			}
		}

		// open and move pointer to beginning
		$this->open();

		// work out how many markers to use and size (16ms without prequery on test3)
		$markers_amount = (int) round($this->row_count / 150);
		if ($markers_amount < 1) $markers_amount = 1;
		fseek($this->handle, 0, SEEK_END);
		$size = ftell($this->handle);
		$marker_size = round($size / $markers_amount);

		fseek($this->handle, 0, SEEK_SET);

		// perform query
		$matches = array();
		$cpq = 0;

		// skip headers
		stream_get_line($this->handle, 2048, "--- end headers ---\n");

		while (!feof($this->handle))
		{
			// read in block, if not first, clean up to first \n
			$pq_cache = stream_get_line($this->handle, $marker_size);
			if ($cpq > 0)
			{
				if (($find_me = strpos($pq_cache, "\n")) === false) break; // no more complete lines left
				$pq_cache = substr($pq_cache, $find_me + 1); // clean up to first \n
			}
			$cpq++;

			// cache file pointer
			$pre_query_position = ftell($this->handle);

			// read in to next \n to finish line off (at least a meg)
			$pq_cache.= stream_get_line($this->handle, 1049000, "\n");

			// rewind file pointer
			fseek($this->handle, $pre_query_position, SEEK_SET);

			// send pq cache off for pre-query
			if ($this->pre_query($pq_cache, $search) !== false)
			{
				// if match, iterate over $pq_cache, maybe explode it by \n and query each value
				$rows = explode("\n", $pq_cache);

				foreach ($rows as $row_data)
				{
					// Check row data
					if (substr($row_data, 3, 4) !== 'row:')
					{
						trigger_error("Failed to read line in '{$this->table}' db file, db file corrupt");
						return false;
					}

					// query further
					$match = $this->query($row_data, $search, true);

					// filter
					if (!empty($match))
					{
						// collate join data for search later
						if ($joins !== null)
						{
							$j = 0;
							foreach ($joins as $join)
							{
								$joins[$j]['join_data'][$match[$join['join_to']]] = array(
									'column' => 'id',
									'value' => $match[$join['join_to']]
								);

								$j++;
							}
						}

						// filter out any cols do not want in results
						if (!empty($filter))
						{
							foreach ($match as $match_col => $match_value)
							{
								if ((is_string($filter) && $match_col !== $filter) || (is_array($filter) && !in_array($match_col, $filter)))
								{
									unset($match[$match_col]);
								}
							}
						}
						$matches[] = $match;
					}
				}
			}
		}

		$this->close();

		// perform any join searchs now
		if ($joins !== null)
		{
			foreach ($joins as $join)
			{
				$join_ob = new RazorDB('razor_db:join:'.$this->table.'>'.$join['table']);

				if (!$join_ob->connect($join['table']))
				{
					trigger_error("Failed to join table '{$join['table']}' omitting this data from results");
					continue;
				}

				// no join data found, skip this join
				if (!isset($join['join_data'])) continue;
				$get_filters = (isset($join_filters[$join['table']]) ? array("filter" => $join_filters[$join['table']]) : array());
				$found = $join_ob->get_rows($join['join_data'], $get_filters);

				// attach data to matches
				foreach ($matches as $key => $match)
				{
					if ($found['count'] > 0)
					{
						foreach ($found['result'] as $found_result)
						{
							if ($match[$join['join_to']] === $found_result['id'])
							{
								foreach ($found_result as $col => $val)
								{
									$matches[$key][$join['join_to'].'.'.$col] = $val;
								}
							}
						}
					}
					else
					{
						// no results so no match
						unset($matches[$key]);
					}
				}

				$join_ob->disconnect();
			}
		}

		// sort results
		if (!empty($order))
		{
			$this->order = $order;
			usort($matches, array($this, 'sort'));
		}

		// get limit
		if (!empty($amount)) $matches = array_slice($matches, 0, $amount);

		$result = array(
			'table' 		=> $this->table,
			'count'			=> count($matches),
			'time'			=> microtime(true) - $time,
			'result'		=> $matches,
			'order'			=> $order
		);
		return $result;
	}


	/**
	 * Create new row
	 *
	 * Adds a new row file and updates the cache
	 * @param array $rows ["col_name" => "value",...]
	 * @return mixed Returns id of row on pass or false on fail, use === when evaluating result
	 */
	public function add_rows($rows = array())
	{
		// resolve table name and file
		$time = microtime(true);

		// get table headers
		if (empty($this->columns))
		{
			if (!$this->headers()) return false;
		}

		// check for changes array, put into multi array if needed
		if (!empty($rows) && !is_array(reset($rows))) $rows = array($rows);

		// prepare line from data
		$new_rows = array();
		$lines = array();
		foreach ($rows as $row)
		{
			// do not let them set id, set manually
			if (isset($row['id']))
			{
				trigger_error("Cannot set id column, column not writable for table '{$this->table}'");
				return false;
			}

			// manually set id to next id and update counter
			$line = array(
				'id'		=> '1`'.$this->counter.'`'
			);
			$new_row = array(
				'id'		=> $this->counter
			);
			$this->counter++;

			foreach ($this->columns as $column)
			{
				// bypass id column
				if ($column['name'] == 'id') continue;

				// check set, if not check nullable, if so set to nullable
				if (isset($row[$column['name']]))
				{
					// cast value as type into array
					$error = false;
					switch ($column['type'])
					{
						case 'int':
							if (!is_int($row[$column['name']])) $error = true;
							else
							{
								$line[] = $column['column'].'`'.$this->data_in($row[$column['name']]).'`';
								$new_row[$column['name']] = (int) $this->data_in($row[$column['name']]);
							}
						break;
						case 'float':
							if (!is_float($row[$column['name']])) $error = true;
							else
							{
								$line[] = $column['column'].'`'.$this->data_in($row[$column['name']]).'`';
								$new_row[$column['name']] = (float) $this->data_in($row[$column['name']]);
							}
						break;
						case 'bool':
							if (!is_bool($row[$column['name']])) $error = true;
							else
							{
								$line[] = $column['column'].'`'.(int)$this->data_in($row[$column['name']]).'`';
								$new_row[$column['name']] = (bool) $this->data_in($row[$column['name']]);
							}
						break;
						default:
							if (!is_string($row[$column['name']])) $error = true;
							else
							{
								$line[] = $column['column'].'`'.$this->data_in($row[$column['name']]).'`';
								$new_row[$column['name']] = (string) $this->data_in($row[$column['name']]);
							}
						break;
					}

					if ($error)
					{
						trigger_error("Type miss-match for column '{$column['name']}' not of type '{$column['type']}'");
						return false;
					}
				}
				elseif ($column['nullable'] === true)
				{
					$line[] = 'null';
					$new_row[$column['name']] = null;
				}
				else
				{
					trigger_error("No value set for column '{$column['name']}' and column not nullable");
					return false;
				}
			}
			$new_rows[] = $new_row;
			$comp_line = implode('|', $line);
			if (strlen($comp_line) >= 1046000)
			{
				trigger_error("Data to be saved is over 1Mb per line limit for table '{$this->table}'");
				return false;
			}
			$lines[] = $comp_line;
		}

		// apply lock
		if (!$this->lock()) return false;

		// move to end
		$this->open('r+');
		fseek($this->handle, 0, SEEK_END);

		// add new lines
		foreach ($lines as $line)
		{
			fwrite($this->handle, "\n// row:".$line);
			$this->row_count++;
		}

		// close temp file
	   	$this->close();
		$this->update_counter();
		$this->update_row_count();
		$this->unlock();

		// return results
		$result = array(
			'table' 		=> $this->table,
			'count'			=> count($new_rows),
			'result'		=> $new_rows,
			'time'			=> round(microtime(true) - $time, 6)
		);

		return $result;
	}


	/**
	 * Edit rows from search
	 *
	 * Edit the rows found from a search on a columns
	 * @param array $search Search [column => string, value => mixed[, not => bool, and => bool, case_insensitive => bool, wildcard => bool]], null if multiple edits
	 * @param array $changes Changes to make [column_name => value]... chain changes by wrapping in array, or array of changes including id, with no search to update multiple rows with different data
	 * @return mixed The data in array format ('table' => 'name, 'count' => resultCount, 'result' => rows(('col' => 'val',...)) or false on fail
	 */
	public function edit_rows($search = null, $changes)
	{
		// create backup
		@copy($this->file, $this->bck_file);
	
		// check connected
		if (!$this->connected)
		{
			trigger_error("Not connected to table, cannot perform query");
			return false;
		}

		// resolve table name and file
		$time = microtime(true);

		// get table headers
		if (empty($this->columns))
		{
			if (!$this->headers()) return false;
		}

		if (!empty($changes) && !is_array(reset($changes))) $changes = array($changes);

		// check for single or multi array
		if (!empty($search))		
		{
			if (isset($search['column'])) $search = array($search);
			foreach ($search as $search_data)
			{
				if (!isset($search_data['column']))
				{
					trigger_error("'column' not provided in search");
					return false;
				}

				if (!isset($this->columns[$search_data['column']]))
				{
					trigger_error("Column '{$search_data['column']}' does not exist in table '{$this->table}'");
					return false;
				}
			}
		}
		else
		{
			// build a search up from all rows
			$id_search = array();
			foreach ($changes as $change)
			{
				if (!isset($change["id"]))
				{
					trigger_error("'id' not provided, must provide id with null search");
					return false;
				}

				$id_search[] = array("column" => "id", "value" => $change["id"]);
			}
		}

		// check for changes array, put into multi array if needed
		if (empty($changes))
		{
			trigger_error("No change data found for '{$this->table}'");
			return false;
		}

		// check change values to ensure they are correct type for column
		$error = false;
		foreach ($changes as $change)
		{
			foreach ($change as $change_col => $change_val)
			{
				if (empty($search) && $change_col == 'id') continue;

				if ($change_col == 'id')
				{
					trigger_error("Cannot change the column 'id' in table '{$this->table}' as this is primary key, cannot perform edit");
					return false;
				}

				if ($change_val === null)
				{
					if ($this->columns[$change_col]['nullable'] !== true) $error = true;
				}
				else
				{
					switch ($this->columns[$change_col]['type'])
					{
						case 'int':
							if (!is_int($change_val)) $error = true;
						break;
						case 'float':
							if (!is_float($change_val)) $error = true;
						break;
						case 'bool':
							if (!is_bool($change_val))
							{
								$error = true;
							}
							else
							{
								$changes[$change_col] = (int) $change_val; // force int for bool, gets changed back at later date
							}
						break;
						default:
							if (!is_string($change_val)) $error = true;
						break;
					}
				}

				if ($error)
				{
					trigger_error("Type miss-match for change data on column '{$change_col}' in table '{$this->table}', cannot perform edit.");
					return false;
				}
			}
		}

		// switch filenames to read to the copy, this should stop permission issues in windows
		$original_file = $this->file;
		$this->file = $this->bck_file;

		// lock and move to start
		if (!$this->lock()) return false;

		// setup temp file
		$temp_ext = rand(1, 1000000);
		$temp_file = RAZOR_BASE_PATH."storage/database/{$this->table}_{$temp_ext}.db.php";
		$temp_handle = fopen($temp_file, 'c');
		if (!$temp_handle)
		{
			trigger_error("Failed to create temp transfer file for '{$this->table}'");
			$this->unlock();
			return false;
	  	}

		// open and move pointer to beginning
		$this->open();

		// work out how many markers to use and size (16ms without prequery on test3)
		$markers_amount = (int) round($this->row_count / 150);
		if ($markers_amount < 1) $markers_amount = 1;
		fseek($this->handle, 0, SEEK_END);
		$size = ftell($this->handle);
		$marker_size = round($size / $markers_amount);

		fseek($this->handle, 0, SEEK_SET);

		// perform query
		$matches = array();
		$cpq = 0;

		// skip headers and change lock
		$headers = stream_get_line($this->handle, 2048, "--- end headers ---\n");
		$headers = str_replace("// lock:1", "// lock:0", $headers);
		fwrite($temp_handle, $headers."--- end headers ---");

		while (!feof($this->handle))
		{
			// read in block, if not first, clean up to first \n
			$pq_cache = stream_get_line($this->handle, $marker_size);
			if ($cpq > 0)
			{
				if (($find_me = strpos($pq_cache, "\n")) === false) break; // no more complete lines left
				$pq_cache = substr($pq_cache, $find_me + 1); // clean up to first \n
			}
			$cpq++;

			// cache file pointer
			$pre_query_position = ftell($this->handle);

			// read in to next \n to finish line off (at least a meg)
			$pq_cache.= stream_get_line($this->handle, 1049000, "\n");

			// rewind file pointer
			fseek($this->handle, $pre_query_position, SEEK_SET);

			// send pq cache off for pre-query
			if ($this->pre_query($pq_cache, (!empty($search) ? $search : $id_search)) !== false)
			{
				// if match, iterate over $pq_cache, maybe explode it by \n and query each value
				$rows = explode("\n", $pq_cache);

				foreach ($rows as $row_data)
				{
					// Check row data
					if (substr($row_data, 3, 4) !== 'row:')
					{
						trigger_error("Failed to read line in '{$this->table}' db file, db file corrupt");
						fclose($temp_handle);
						@unlink($temp_file);
						return false;
					}

					// query further
					$match = $this->query($row_data, (!empty($search) ? $search : $id_search), true);

					// if match, update the data
					if (!empty($match))
					{
						// search through all changes to find match
						$change = $changes[0];
						if (empty($search))
						{
							foreach ($changes as $c)
							{
								if ($c["id"] === $match["id"])
								{
									$change = $c;
									unset($change["id"]);
									break;
								}
							}							
						}

						$temp_match = array();
						$row_data = "// row:";
						foreach ($this->columns as $column)
						{
							if ($column['column'] > 1) $row_data.= '|';

							// make change if one
							$row_data.= $column['column'].'`'.$this->data_in((isset($change[$column['name']]) ? $change[$column['name']] : $match[$column['name']])).'`';
							// update matches
							$temp_match[$column['name']] = $this->data_out($this->data_in((isset($change[$column['name']]) ? $change[$column['name']] : $match[$column['name']])));
						}
						$matches[] = $temp_match;

						if (strlen($row_data) >= 1046000)
						{
							trigger_error("Data to be saved is over 1Mb per line limit for table '{$this->table}'");
							fclose($temp_handle);
							@unlink($temp_file);
							return false;
						}

					}

					// write row to temp file
					fwrite($temp_handle, "\n".$row_data);
				}
			}
			else
			{
				// no match, just write to temp file
				fwrite($temp_handle, "\n".$pq_cache);
			}
		}

		// close both files
		$this->close();
		$this->unlock();
		fflush($temp_handle);
		fclose($temp_handle);

		// now switch filename back to original and write temp to original to save changes
		$this->file = $original_file;
		rename($temp_file, $this->file);

		$result = array(
			'table' 		=> $this->table,
			'count'			=> count($matches),
			'time'			=> round(microtime(true) - $time, 6),
			'result'		=> $matches
		);
		return $result;
	}


	/**
	 * Delete row
	 *
	 * Deletes entire row in table and adjusts cache
	 * @param array $search Search [column => string, value => mixed[, not => bool, and => bool, case_insensitive => bool, wildcard => bool]]
	 * @return mixed The data deleted in array format ('table' => 'name, 'count' => resultCount, 'result' => rows(('col' => 'val',...)) or false on fail
	 */
	public function delete_rows($search)
	{
		// create backup
		@copy($this->file, $this->bck_file);

		// check connected
		if (!$this->connected)
		{
			trigger_error("Not connected to table, cannot perform query");
			return false;
		}

		// resolve table name and file
		$time = microtime(true);

		// get table headers
		if (empty($this->columns))
		{
			if (!$this->headers()) return false;
		}

		// check for single or multi array
		if (isset($search['column'])) $search = array($search);
		foreach ($search as $search_data)
		{
			if (!isset($search_data['column']))
			{
				trigger_error("'column' not provided in search");
				return false;
			}

			if (!isset($this->columns[$search_data['column']]))
			{
				trigger_error("Column '{$search_data['column']}' does not exist in table '{$this->table}'");
				return false;
			}
		}
		
		// lock and move to start
		// if (!$this->lock()) return false;

		// switch filenames to read to the copy, this should stop permission issues in windows
		$original_file = $this->file;
		$this->file = $this->bck_file;

		// setup temp file
		$temp_ext = rand(1, 1000000);
		$temp_file = RAZOR_BASE_PATH."storage/database/{$this->table}_{$temp_ext}.db.php";
		$temp_handle = fopen($temp_file, 'c');
		if (!$temp_handle)
		{
			trigger_error("Failed to create temp transfer file for '{$this->table}'");
			$this->unlock();
			return false;
	  	}

		// open and move pointer to beginning
		$this->open();

		// work out how many markers to use and size (16ms without prequery on test3)
		$markers_amount = (int) round($this->row_count / 150);
		if ($markers_amount < 1) $markers_amount = 1;
		fseek($this->handle, 0, SEEK_END);
		$size = ftell($this->handle);
		$marker_size = round($size / $markers_amount);

		fseek($this->handle, 0, SEEK_SET);

		// perform query
		$matches = array();
		$cpq = 0;

		// skip headers
		$headers = stream_get_line($this->handle, 2048, "--- end headers ---\n");
		fwrite($temp_handle, $headers."--- end headers ---");

		while (!feof($this->handle))
		{
			// read in block, if not first, clean up to first \n
			$pq_cache = stream_get_line($this->handle, $marker_size);
			if ($cpq > 0)
			{
				if (($find_me = strpos($pq_cache, "\n")) === false) break; // no more complete lines left
				$pq_cache = substr($pq_cache, $find_me + 1); // clean up to first \n
			}
			$cpq++;

			// cache file pointer
			$pre_query_position = ftell($this->handle);

			// read in to next \n to finish line off (at least a meg)
			$pq_cache.= stream_get_line($this->handle, 1049000, "\n");

			// rewind file pointer
			fseek($this->handle, $pre_query_position, SEEK_SET);

			// send pq cache off for pre-query
			if ($this->pre_query($pq_cache, $search) !== false)
			{
				// if match, iterate over $pq_cache, maybe explode it by \n and query each value
				$rows = explode("\n", $pq_cache);

				foreach ($rows as $row_data)
				{
					// Check row data
					if (substr($row_data, 3, 4) !== 'row:')
					{
						trigger_error("Failed to read line in '{$this->table}' db file, db file corrupt");
						fclose($temp_handle);
						@unlink($temp_file);
						return false;
					}

					// query further
					$match = $this->query($row_data, $search, true);

					// if match, update the data
					$row_data = "\n".$row_data;
					if (!empty($match))
					{
						$row_data = "";
						$matches[] = $match;
					}

					// write row to temp file
					fwrite($temp_handle, $row_data);
				}
			}
			else
			{
				// no match, just write to temp file
				fwrite($temp_handle, "\n".$pq_cache);
			}
		}

		// close both files
		fclose($temp_handle);
		$this->close();

		// now switch filename back to original and rename temp to original to write changes
		$this->file = $original_file;
		rename($temp_file, $this->file);

		// update and unlock
		$this->row_count-= count($matches);
		$this->update_row_count();

		$result = array(
			'table' 		=> $this->table,
			'count'			=> count($matches),
			'time'			=> round(microtime(true) - $time, 6),
			'result'		=> $matches
		);
		return $result;
	}
}
/* EOF */