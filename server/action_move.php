<?php
	/*
		
		REQUEST:
		{
			"action": "MOVE",
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
		$user_info = authenticate_request($request);
		switch ($user_info['err']) {
			case 'OK': break;
			case 'MISSING': return array('err' => 'GAME_RESET', 'info' => $user_info);
			case 'LATENCY': return array('err' => 'OLD');
			case 'GAME_RESET': return array('err' => 'GAME_RESET');
			default: return array('err' => "NOT_IMPLEMENTED", 'info' => $user_info['err']);
		}
		$user_id = $user_info['user_id'];
		
		$pts_raw = explode('|', trim($request->json['pts']));
		$pts = array();
		$last_pt = null;
		$data = array($user_id);
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
		
		$affected_rows = db()->update('users', array(
				'location' => $last_pt[0] . '|' . $last_pt[1],
				'msg_received' => intval($request->json['msg_id']),
			),
			"`user_id` = " . $user_id,
			1);
		if ($affected_rows == 0) {
			return array('err' => 'GAME_RESET', 'info' => 'update did not affect user');
		}
		$event_id = db()->insert('events', array(
			'game_id' => $user['game_id'],
			'type' => 'MOVE',
			'data' => implode(':', $data)));
		
		$poll = get_poll_data($request);
		
		if ($poll['old']) return array('err' => 'GAME_RESET');
		
		return array(
			'err' => 'OK',
			'poll' => $poll);
	}
?>