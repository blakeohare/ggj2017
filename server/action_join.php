<?php
	/*
		
		REQUEST:
		{
			"action": "JOIN",
			"name": "Desired Username",
			"join_token": "just make up a big random string"
		}

		RESPONSE:
		{
			"err": "OK" | "NAME_IN_USE" | "INVALID_NAME" | "SERVER_FULL" | "JOIN_TOKEN_REQUIRED"
			"token": "PASSWORD TOKEN FOR FUTURE REQUESTS",
			"user_id": "YOUR USER ID",
			"name": "DesiredUsername" (canonicalized version)
			"game_id": Game ID,
			"poll": (see common.php#add_poll_response)
		}

	*/
	function action_join($request) {
		$name = name_canonicalize(trim($request->json['name']));
		$join_token = trim($request->join['join_token'] . '');
		
		if (strlen($name) < 3) {
			return array('err' => "INVALID_NAME");
		}
		
		if (strlen($join_token) == 0) {
			return array('err' => "JOIN_TOKEN_REQUIRED");
		}
		
		$existing_user = db()->select_by_id('users', 'name', $name, array('name', 'token', 'join_token', 'user_id', 'game_id'));
		if ($existing_user !== null) {
			if ($join_token == $existing_user['join_token']) {
				// this is actually the same user
				$output = array(
					'err' => "OK",
					'is_dup' => 1,
					'token' => $existing_user['token'],
					'user_id' => $existing_user['user_id'],
					'name' => $existing_user['name'],
					'game_id' => $existing_user['game_id']);
				return add_latest_poll($request, $output);
			}
			return array("err" => "NAME_IN_USE");
		}
		
		$result = db()->select("SELECT COUNT(`user_id`) AS 'c' FROM `users`");
		$result = $result->next();
		if (intval($result['c']) > 100) return array('err' => 'SERVER_FULL');
		
		$token = generate_user_token();
		
		$x = intval(random_float() * 1000 - 500);
		$y = intval(random_float() * 1000 - 500);
		
		$active_game = db()->select("SELECT `game_id` FROM `games` ORDER BY `game_id` LIMIT 1");
		$game = $active_game->next();
		$game_id = $game['game_id'];
		
		$user_id = db()->insert('users', array(
			'name' => $name,
			'token' => $token,
			'location' => $x . '|' . $y,
			'score' => 0,
			'msg_received' => 0,
			'game_id' => $game_id,
			'join_token' => $join_token,
			));
		
		$output = array(
			'err' => 'OK',
			'token' => $token,
			'user_id' => $user_id,
			'name' => $name,
			'game_id' => $game_id,
			);
		
		return add_latest_poll($request, $output);
	}
?>