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
	
	function get_user($user_id, $token, $game_id, $columns = null) {
		if ($columns === null) $columns = array();
		array_push($columns, 'token');
		array_push($columns, 'game_id');
		$user = db()->select_by_id('users', 'user_id', $user_id, $columns);
		$game_id = intval($game_id);
		
		if ($user === null ||
			$user['token'] !== $token ||
			intval($user['game_id']) !== $game_id) return null;
		return $user;
	}
	
	function is_old_game($request) {
		$games = db()->select("SELECT `game_id` FROM `games` ORDER BY `game_id` DESC LIMIT 1");
		$game = $games->next();
		$latest_game_id = intval($game['game_id']);
		$user_game_id = intval($request->json['game_id']);
		return ($game_id != $latest_game_id);
	}
?>