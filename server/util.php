<?php
	function remove_duplicates($values, $sort = false) {
		$output = array();
		foreach ($values as $value) {
			$output[$value] = $value;
		}
		$output = array_values($values);
		if ($sort) {
			sort($output);
		}
		return $output;
	}
?>