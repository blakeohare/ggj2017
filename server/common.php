<?php
	function add_poll_response($request, $raw_response) {
		$poll_response = array();
		
		$raw_response['poll'] = $poll_response;
		
		return $raw_response;
	}
	
	function add_latest_poll($request, $raw_response) {
		$poll_response = array();
		
		$raw_response['poll'] = $poll_response;
		
		return $raw_response;
	}
	
	function generate_user_token() {
		$letters = 'a b c d e f g h i j k l m n o p q r s t u v w x y z 0 1 2 3 4 5 6 7 8 9';
		$chars = explode(' ', $letters);
		shuffle($chars);
		$output = '';
		for ($i = 0; $i < 15; ++$i) {
			$output .= $chars[$i];
		}
		return $output;
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