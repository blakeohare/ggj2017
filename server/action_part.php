<?php
	/*
	
	request {
		"action": "PART"
		"user_id": your user_id,
		"token": Your user token,
		"game_id": game ID,
	}

	response {
		"err": "OK",
	}

	*/
	function action_part($request) {
		$user = get_user(
			intval($request->json['user_id']),
			trim($request->json['token']),
			intval($request->json['game_id']));
		
		if ($user !== null) {
			db()->insert('events', array(
				'game_id' => $user['game_id'],
				'type' => 'PART',
				'data' => $user['user_id'] . ''
				));
			db()->delete('users', "`user_id` = " . $user['user_id'], 1);
		}
		
		return array('err' => 'OK');
	}
?>