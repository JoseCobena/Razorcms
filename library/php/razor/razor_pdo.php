<?php if (!defined("RARS_BASE_PATH") && !defined("RAZOR_BASE_PATH")) die("No direct script access to this content");

/**
 * razorCMS PDO
 *
 * Copywrite 2014 to Present Day - Paul Smith (aka smiffy6969, razorcms)
 *
 * @author Paul Smith
 * @site ulsmith.net
 * @created Feb 2014
 */

class RazorPDO extends PDO
{
	function __construct($pdo = RAZOR_PDO)
	{
		parent::__construct($pdo);
	}

	/**
	 * Perform a standard query from query string
	 *
	 * @example $result_obj = $db->query_execute('SELECT * FROM user WHERE id = :id', array(':id' => 1));
	 * @example $data = $result_obj->fetch(PDO::FETCH_ASSOC);
	 *
	 * @param string $query The query string to process using :placeholders for data matching
	 * @param mixed $data The data to bind to query placeholders as array [:name => value,...] or int/string (which binds to :id)
	 * @return mixed Returns the result object of executing the query or execution result when query not executed correctly (false)
	 */
	public function query_execute($query, $data = array())
	{
		$db_query = $this->prepare($query);
		$this->bind_data($data, $db_query);
		$result = $db_query->execute();
		return (empty($result) ? $result : $db_query);
	}

	/**
	 * Perform a standard query from query string and returns the first result found forcing type on results
	 *
	 * @example $data = $db->query_first('SELECT * FROM user WHERE id = :id', array(':id' => 1));
	 *
	 * @param string $query The query string to process using :placeholders for data matching
	 * @param mixed $data The data to bind to query placeholders as array [:name => value,...] or int/string (which binds to :id)
	 * @return array Returns the result found or empty array if no result found
	 */
	public function query_first($query, $data = array())
	{
		$db_query = $this->prepare($query);
		$this->bind_data($data, $db_query);
		$db_query->execute();
		//$meta_type = $this->find_type($db_query);
		$result = $db_query->fetch(PDO::FETCH_ASSOC);
		//$this->force_type($meta_type, $result);

		return $result;
	}

	/**
	 * Perform a standard query from query string and returns the last result found forcing type on results
	 *
	 * @example $data = $db->query_last('SELECT * FROM page WHERE id IS NOT NULL');
	 *
	 * @param string $query The query string to process using :placeholders for data matching
	 * @param mixed $data The data to bind to query placeholders as array [:name => value,...] or int/string (which binds to :id)
	 * @return array Returns the result found or empty array if no result found
	 */
	public function query_last($query, $data = array())
	{
		$db_query = $this->prepare("{$query} ORDER BY id DESC");
		$this->bind_data($data, $db_query);
		$db_query->execute();
		//$meta_type = $this->find_type($db_query);
		$result = $db_query->fetch(PDO::FETCH_ASSOC);
		//$this->force_type($meta_type, $result);

		return $result;
	}

	/**
	 * Perform a standard query from query string and returns all results found forcing type on results
	 *
	 * @example $data = $db->query_all('SELECT * FROM user WHERE id = :id', array(':id' => 1));
	 *
	 * @param string $query The query string to process using :placeholders for data matching
	 * @param mixed $data The data to bind to query placeholders as array [:name => value,...] or int/string (which binds to :id)
	 * @return array Returns the results found or empty array if no results found
	 */
	public function query_all($query, $data = array())
	{
		$db_query = $this->prepare($query);
		$this->bind_data($data, $db_query);
		$db_query->execute();
		//$meta_type = $this->find_type($db_query);
		$result = $db_query->fetchAll(PDO::FETCH_ASSOC);
		//$this->force_type($meta_type, $result);

		return $result;
	}

	/**
	 * Perform a SELECT query and returns first result found forcing type on results
	 *
	 * @example $data = $db->get_first('user', array('id', 'password'), array('email_address' => 'razorcms@razorcms.co.uk'));
	 *
	 * @param string $table The table to query
	 * @param mixed $columns The columns you want as a comma seperated string, '*' for all or an array of column names
	 * @param array $where The where data to filter results on as ['col_name' => 'value',...]
	 * @return array Returns the result found or false if no results found
	 */
	public function get_first($table, $columns = '*', $where = array())
	{
		// work out columns
		if (is_array($columns)) $columns = implode(',', $columns);

		// work out where
		$where_string = '';
		if (is_array($where) && !empty($where))
		{
			$where_string = 'WHERE ';
			foreach ($where as $key => $val) $where_string.= "{$key} = :{$key} AND ";
			$where_string = substr($where_string, 0, -5);
		}

		$db_query = $this->prepare("SELECT {$columns} FROM {$table} {$where_string}");
		$this->bind_data($where, $db_query);
		$db_query->execute();
		//$meta_type = $this->find_type($db_query);
		$result = $db_query->fetch(PDO::FETCH_ASSOC);
		//$this->force_type($meta_type, $result);

		return $result;
	}

	/**
	 * Perform a SELECT query and returns last result found forcing type on results
	 *
	 * @example $data = $db->get_last('page', '*');
	 *
	 * @param string $table The table to query
	 * @param mixed $columns The columns you want as a comma seperated string, '*' for all or an array of column names
	 * @param array $where The where data to filter results on as ['col_name' => 'value',...]
	 * @return array Returns the result found or false if no results found
	 */
	public function get_last($table, $columns = '*', $where = array())
	{
		// work out columns
		if (is_array($columns)) $columns = implode(',', $columns);

		// work out where
		$where_string = '';
		if (is_array($where) && !empty($where))
		{
			$where_string = 'WHERE ';
			foreach ($where as $key => $val) $where_string.= "{$key} = :{$key} AND ";
			$where_string = substr($where_string, 0, -5);
		}

		$db_query = $this->prepare("SELECT {$columns} FROM {$table} {$where_string} ORDER BY id DESC");
		$this->bind_data($where, $db_query);
		$db_query->execute();
		//$meta_type = $this->find_type($db_query);
		$result = $db_query->fetch(PDO::FETCH_ASSOC);
		//$this->force_type($meta_type, $result);

		return $result;
	}

	/**
	 * Perform a SELECT query and returns all result found forcing type on results
	 *
	 * @example $data = $db->get_all('user', '*', array('email_address' => 'razorcms@razorcms.co.uk'));
	 *
	 * @param string $table The table to query
	 * @param mixed $columns The columns you want as a comma seperated string, '*' for all or an array of column names
	 * @param array $where The where data to filter results on as ['col_name' => 'value',...]
	 * @return array Returns the results found or empty array if no results found
	 */
	public function get_all($table, $columns = '*', $where = array())
	{
		// work out columns
		if (is_array($columns)) $columns = implode(',', $columns);

		// work out where
		$where_string = '';
		if (is_array($where) && !empty($where))
		{
			$where_string = 'WHERE ';
			foreach ($where as $key => $val) $where_string.= "{$key} = :{$key} AND ";
			$where_string = substr($where_string, 0, -5);
		}

		$db_query = $this->prepare("SELECT {$columns} FROM {$table} {$where_string}");
		$this->bind_data($where, $db_query);
		$db_query->execute();
		//$meta_type = $this->find_type($db_query);
		$result = $db_query->fetchAll(PDO::FETCH_ASSOC);
		//$this->force_type($meta_type, $result);

		return $result;
	}

	/**
	 * Add data to the table and return inserted id/s
	 *
	 * @example $data = $db->add_data('banned', array('ip_address' => '111111111', 'user_agent' => 'test test test'), array('id', 'ip_address'));
	 *
	 * @param string $table The table to add to
	 * @param array $data The data you want add as ['col_name' => 'value',...] or as many rows [['col_name' => 'value'],...]
	 * @param array $return_inserted Default is to return id's, add array of columns or '*' to return columns of inserted rows ['col1', col2'...] or '*'
	 * @return mixed Returns the results id/s of the insert row as int or array of ints, execution result on fail (false)
	 */
	public function add_data($table, $data = array(), $return_inserted = array())
	{
		if (!is_array($data) || empty($data)) return false;


		// work out data
		reset($data);
		if (is_array(current($data)))
		{
			// work out columns
			$columns = implode(',', array_keys(current($data)));

			// work out values
			$new_data = array();
			$values = '';
			foreach ($data as $key => $val)
			{
				$values.= '(';
				foreach ($val as $key2 => $val2)
				{
					$values.= ":{$key}_{$key2},";
					$new_data["{$key}_{$key2}"] = $val2;
				}
				$values = substr($values, 0, -1).'),';
			}
			$values = substr($values, 0, -1);
		}
		else
		{
			// work out columns
			$columns = implode(',', array_keys($data));

			// work out values
			$values = '(';
			foreach ($data as $key => $val) $values.= ":{$key},";
			$values = substr($values, 0, -1).')';
			$new_data = $data;
		}

		// run query
		$this->beginTransaction();

		try
		{
			$db_query = $this->prepare("INSERT INTO {$table} ({$columns}) VALUES {$values}");
			$this->bind_data($new_data, $db_query);
			$result = $db_query->execute();
		    $this->commit();
		}
		catch(PDOException $e)
		{
		    $this->rollBack();
		    return false;
		}

		if ($result)
		{
			$amount = 1;
			$last = (int) $this->lastInsertId();
			$result = array($last);

			// if insertion good, get row ids
			reset($data);
			if (is_array(current($data)))
			{
				$amount = count($data); // update amount

				$ids = array();
				for ($i = ($last - $amount) + 1; $i <= $last; $i++) $ids[] = $i;
				$result = $ids;
			}

			// and if return inserted true, fetch the data instead of returning id's
			if (!empty($return_inserted))
			{
				$columns = (is_array($return_inserted) ? implode(',', $return_inserted) : $return_inserted);
				$result = array_reverse($this->query_all("SELECT {$columns} FROM {$table} ORDER BY id DESC LIMIT {$amount}"));
			}
		}

		return $result;
	}

	/**
	 * Edit data in the table
	 *
	 * @example $data = $db->edit_data('banned', array('ip_address' => 32323232), array('id' => 1), array('id','ip_address'));
	 *
	 * @param string $table The table to add to
	 * @param array $data The data you want to change as ['col_name' => 'value',...]
	 * @param array $where The where clause you want to filter by as ['col_name' => 'value',...]
	 * @param array $return_edited Default is to return id's, add array of columns to return or '*' to return all ['col1', col2'...] or '*'
	 * @return mixed Returns the results id/s of the insert row as int or array of ints, execution result on fail (false)
	 */
	public function edit_data($table, $data = array(), $where = array(), $return_edited = array())
	{
		if (!is_array($data) || empty($data)) return false;
		if (!is_array($where) || empty($where)) return false;

		// work out where
		$where_string = '';
		foreach ($where as $key => $val) $where_string.= "{$key} = :{$key} AND ";
		$where_string = substr($where_string, 0, -5);

		// work out data
		$data_string = '';
		foreach ($data as $key => $val)
		{
			$data_string.= "{$key} = :d_{$key},";
			$data["d_{$key}"] = $val;
			unset($data[$key]); // change mapping to stop colliions with where clause
		}
		$data_string = substr($data_string, 0, -1);

		// run query
		$this->beginTransaction();

		try
		{
			$db_query = $this->prepare("UPDATE {$table} SET {$data_string} WHERE {$where_string}");
			$this->bind_data(array_merge($data, $where), $db_query);
			$result = $db_query->execute();
		    $this->commit();
		}
		catch(PDOException $e)
		{
		    $this->rollBack();
		    return false;
		}

		if ($result)
		{
			$columns = (!empty($return_edited) ? (is_array($return_edited) ? implode(',', $return_edited) : $return_edited) : 'id');
			$result = $this->query_all("SELECT {$columns} FROM {$table} WHERE {$where_string}", $where);
		}

		return $result;
	}

	/**
	 * Delete data from table
	 *
	 * @example $db->delete_data('banned', array('ip_address' => 32323232));
	 *
	 * @param string $table The table to delete from
	 * @param array $where The where clause you want to filter by as ['col_name' => 'value',...]
	 * @return bool Returns the results of the deletion
	 */
	public function delete_data($table, $where = array())
	{
		if (!is_array($where) || empty($where)) return false;

		// work out where
		$where_string = '';
		foreach ($where as $key => $val) $where_string.= "{$key} = :{$key} AND ";
		$where_string = substr($where_string, 0, -5);

		// run query
		$this->beginTransaction();

		try
		{
			$db_query = $this->prepare("DELETE FROM {$table} WHERE {$where_string}");
			$this->bind_data($where, $db_query);
			$result = $db_query->execute();
		    $this->commit();
		}
		catch(PDOException $e)
		{
		    $this->rollBack();
		    return false;
		}

		return $result;
	}

	// bind any data to this query
	private function bind_data($data, &$db_query)
	{
		if (!is_array($data)) $db_query->bindParam(':id', $data);
		else foreach ($data as $key => $val) $db_query->bindValue((substr($key, 0, 1) == ':' ? $key : ":{$key}"), $val);
	}

	// grab meta for columns from this query
	private function find_type(&$db_query)
	{
		$meta_type = array();

		// grab meta data
		for ($i = 0; ($meta = $db_query->getColumnMeta($i)) != false; $i++)
		{
			$meta_type[$meta['name']] = $meta['native_type'];
		}

		return $meta_type;
	}

	// force the type of the results found in this results
	private function force_type(&$meta_type, &$result)
	{
		if (empty($result)) return;

		// force types and return
		foreach ($result as $key => $val)
		{
			if (is_array($val))
			{
				foreach ($val as $key2 => $val2)
				{
					if ($meta_type[$key2] == 'integer') $result[$key][$key2] = (int) $val2;
					elseif ($meta_type[$key2] == 'null') $result[$key][$key2] = null;
					else $result[$key][$key2] = $val2;
				}
			}
			else
			{
				if ($meta_type[$key] == 'integer') $result[$key] = (int) $val;
				elseif ($meta_type[$key] == 'null') $result[$key] = null;
				else $result[$key] = $val;
			}
		}
	}
}
