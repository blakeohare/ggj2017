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
	
	function is_point_in_triangle($px, $py, $ax, $ay, $bx, $by, $cx, $cy) {
		
		// these handle 99% of cases
		if ($px < $ax && $px < $bx && $px < $cx) return false;
		if ($px > $ax && $px > $bx && $px > $cx) return false;
		if ($py < $ay && $py < $by && $py < $cy) return false;
		if ($py > $ay && $py > $by && $py > $cy) return false;
		
		$a = cross_product($ax, $ay, $bx, $by, $px, $py);
		$b = cross_product($bx, $by, $cx, $cy, $px, $py);
		$c = cross_product($cx, $cy, $ax, $ay, $px, $py);
		
		if ($a < 0 && $b < 0 && $c < 0) return true;
		if ($a > 0 && $b > 0 && $c > 0) return true;
		return false;
	}
	
	function cross_product($ox, $oy, $ax, $ay, $bx, $by) {
		$vax = $ax - $ox;
		$vay = $ay - $oy;
		$vbx = $bx - $ox;
		$vby = $by - $oy;
		return $vax * $vby - $vbx * $vay;
	}
?>