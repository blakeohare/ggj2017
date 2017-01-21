<?php
	/*
		
		REQUEST:
		{
			"user_id": user ID
			"token": user token
			"pts": "123,45|123,67" <-- list of absolute points. points are pipe delimited. coords are comma delimited
			"msg_id": ...
			"event_id": ...
		}

		RESPONSE:
		{
			"err": OK | GAME_RESET | INVALID | OLD
			"poll": ...
		}

	*/
	function action_move($request) {
		if (is_old_game($request))
			return array('err' => 'GAME_RESET');
		
		$msg_id = intval($request->json['msg_received']);
		$pts_raw = explode('|', trim($request->json['pts']));
		$pts = array();
		$last_pt = null;
		$data = array($user['user_id']);
		foreach ($pts_raw as $pt_raw) {
			$pt = array();
			$parts = explode(',', $pt_raw);
			if (count($parts) != 2) {
				return array('err' => 'INVALID');
			}
			$x = intval($parts[0]);
			$y = intval($parts[1]);
			$pt = array($x, $y);
			$last_pt = $pt;
			array_push($pts, $pt);
			array_push($data, $x);
			array_push($data, $y);
		}
		$user = get_user(
			intval($request->json['user_id']),
			trim($request->json['token']),
			intval($request->json['game_id']),
			array('msg_received'));
		
		if ($user === null) return array('err' => 'GAME_RESET');
		if (intval($msg_id) <= intval($user['msg_received'])) {
			return array('err' => 'OLD');
		}
		
		db()->update('user', array(
				'location' => $last_pt[0] . '|' . $last_pt[1],
				'msg_id' => 
			),
			"`user_id` = " . $user['user_id'], 1);
		db()->insert('events', array(
			'game_id' => $user['game_id'],
			'type' => 'MOVE',
			'data' => implode(':', $data)));
	}
?>