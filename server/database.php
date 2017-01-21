<?php

	class Database {
		var $db;
		
		function __construct() {
			global $CONFIG;
			
			$this->db = new mysqli(
				$CONFIG['mysql_host'],
				$CONFIG['mysql_user'],
				$CONFIG['mysql_password'],
				$CONFIG['mysql_database']);
			if ($this->connection->connect_errno) {
				header('HTTP/1.1 500 Internal Server Error', true, 500);
				echo '{"err":"API_DOWN"}';
				exit;
			}
		}
		
		function raw_query($sql, $show_error = true) {
			$output = $this->db->query($sql);
			if ($this->db->errno !== 0) {
				echo "ERROR:\n";
				echo $this->db->error . "\n";
				echo $sql . "\n\n";
			}
			return $output;
		}
		
		function select($sql) {
			$results = $this->raw_query($sql);
			return new DbSelectResult($results);
		}
		
		function sanitize_string($value) {
			return $this->db->real_escape_string($value);
		}
		
		function sanitize($value) {
			return $this->db->real_escape_string($value);
		}
		
		function insert($table, $assoc_values, $show_error = true) {
			$cols = array();
			$values = array();
			foreach ($assoc_values as $key => $value) {
				array_push($cols, "`" . $this->sanitize_string($key) . "`");
				array_push($values, "'" . $this->sanitize_string($value) . "'");
			}
			
			$this->raw_query(
				"INSERT INTO `" . $this->sanitize_string($table) . "` (" . implode(",", $cols) . ") VALUES (" . implode(', ', $values) . ")",
				$show_error);
			
			if ($this->db->errno == 0) {
				return $this->db->insert_id;
			}
			
			return null;
		}
		
		function try_insert($table, $assoc_values) {
			return $this->insert($table, $assoc_values, false);
		}
		
		function update($table, $assoc_values, $where, $limit = null) {
			$query = array("UPDATE `", $this->sanitize($table), "` SET ");
			$first = true;
			foreach ($assoc_values as $key => $value) {
				if ($first) $first = false;
				else array_push($query, ', ');
				array_push($query, "`" . $this->sanitize($key) . "` = '" . $this->sanitize($value) . "'");
			}
			array_push($query, "WHERE " . $where);
			if ($limit !== null) {
				array_push($query, " LIMIT " . $limit);
			}
			$query = implode('', $query);
			$this->raw_query($query);
			
			return $this->db->affected_rows;
		}
		
		function delete($table, $where, $limit = null) {
			$query = "DELETE FROM `" . $this->sanitize($table) . "` WHERE $where";
			if ($limit !== null) {
				$query .= ' LIMIT ' . $limit;
			}
			$this->raw_query($query);
			return $this->db->affected_rows;
		}
		
		function select_by_ids($table, $column, $values, $select_columns = null) {
			$values = remove_duplicates($values);
			if (count($values) == 0) return array();
			
			if ($select_columns === null) $select_columns = array($column);
			else array_push($select_columns, $column);
			$select_columns = remove_duplicates($select_columns);
			
			$query = array("SELECT ");
			$first = true;
			foreach ($select_columns as $select_column) {
				if ($first) $first = false;
				else array_push($query, ', ');
				array_push($query, "`" . $this->sanitize($select_column) . "`");
			}
			array_push($query, " FROM `" . $this->sanitize($table) . "`");
			array_push($query, " WHERE `" . $this->sanitize($column) . "` IN (");
			for ($i = 0; $i < count($values); ++$i) {
				if ($i > 0) array_push($query, ", ");
				array_push($query, "'" . $this->sanitize($values[$i]) . "'");
			}
			array_push($query, ") LIMIT " . count($values));
			
			$output = array();
			$result = $this->raw_query(implode('', $query));
			for ($i = $result->num_rows - 1; $i >= 0; --$i) {
				$row = $result->fetch_assoc();
				$output[$row[$column]] = $row;
			}
			return $output;
		}
		
		function select_by_id($table, $column, $value, $select_columns = null) {
			$result = $this->select_by_ids($table, $column, array($value), $select_columns);
			if (count($result) == 0) return null;
			return $result[$value];
		}
	}
	
	class DbSelectResult {
		var $result;
		var $size;
		var $index;
		function __construct($result) {
			$this->result = $result;
			$this->size = $result->num_rows;
			$this->index = 0;
		}
		
		function has_more() {
			return $this->index < $this->size;
		}
		
		function has_next() {
			return $this->index < $this->size;
		}
		
		function next() {
			if ($this->index < $this->size) {
				$this->index++;
				return $this->result->fetch_assoc();
			}
			return null;
		}
		
		function as_table() {
			$output = array();
			for ($i = 0; $i < $this->size; ++$i) {
				array_push($output, $this->result->fetch_assoc());
			}
			$this->index = $this->size;
			return $output;
		}
	}
	
	$_db = new Database();
	
	function db() {
		global $_db;
		return $_db;
	}
?>