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
	
	$NAME_VALID_CHARS = array();
	for ($i = 0; $i < 26; ++$i) {
		$c = chr(ord('a') + $i);
		$NAME_VALID_CHARS[$c] = $c;
		$c = chr(ord('A') + $i);
		$NAME_VALID_CHARS[$c] = $c;
	}
	for ($i = 0; $i < 10; ++$i) {
		$c = '' . $i;
		$NAME_VALID_CHARS[$c] = $c;
	}
	
	function name_canonicalize($value) {
		global $NAME_VALID_CHARS;
		$output = array();
		for ($i = 0; $i < strlen($value); ++$i) {
			array_push($output, $NAME_VALID_CHARS[$value[$i]]);
		}
		return implode('', $output);
	}
	
	function random_float() {
		return
			rand(0, 999) / 1000.0 +
			rand(0, 999) / 1000000.0 +
			rand(0, 999) / 1000000000.0;
	}
	
	function round_float($value) {
		return intval($value * 1000) / 1000.0;
	}
	
?>