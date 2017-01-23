<?php
	/*
		
		REQUEST
		{
			"user_id": user ID
			"token": token
			"game_id": game ID
			"event_id": event ID
			"target": "OX,OY,AX,AY,BX,BY"
			"msg_id": message ID
		}

		RESPONSE
		{
			"err": OK | GAME_RESET | INVALID
			"wave_id": WAVE_ID
			"ack_for_wave_id": OTHER_WAVE_ID
			"poll": ...
		}

	*/
	function action_wave_init($request) {
		$user_info = authenticate_request($request);
		
		switch ($user_info['err']) {
			case 'OK': break;
			case 'MISSING': return array('err' => 'GAME_RESET', 'info' => $user_info);
			case 'LATENCY': return array('err' => 'OLD');
			case 'GAME_RESET': return array('err' => 'GAME_RESET');
			default: return array('err' => "NOT_IMPLEMENTED", 'info' => $user_info['err']);
		}
		
		$user_id = intval($user_info['user_id']);
		$game_id = intval($user_info['game_id']);
		
		$pts = explode(',', $request->json['target']);
		if (count($pts) != 6) return array('err' => 'INVALID');
		
		$origin_x = intval($pts[0]);
		$origin_y = intval($pts[1]);
		$a_x = intval($pts[2]);
		$a_y = intval($pts[3]);
		$b_x = intval($pts[4]);
		$b_y = intval($pts[5]);
		
		$old_waves = db()->select("SELECT * FROM `waves` WHERE `from_user_id` = $user_id AND `is_received` = 0");
		while ($old_waves->has_more()) {
			$wave = $old_waves->next();
			db()->delete('waves', "`wave_id` = " . $wave['wave_id'], 1);
			if ($wave['game_id'] == $game_id) {
				db()->insert('events', array('type' => 'WAVE_GIVE_UP', 'data' => $wave['wave_id'], 'time' => time(), 'game_id' => $game_id));
			}
		}
		
		// Check for existing waves that you are in. Generate a WAVE_RECV event and then
		// return a response that says this is actually an ACK rather than a new wave. 
		$existing_waves = db()->select("SELECT * FROM `waves` WHERE `is_received` = 0");
		while ($existing_waves->has_more()) {
			$wave = $existing_waves->next();
			if ($wave['from_user_id'] == $user_id) continue;
			if ($wave['game_id'] != $game_id) continue; // TODO: delete!
			$pts = explode(',', $wave['location']);
			if (is_point_in_triangle(
				$origin_x, $origin_y,
				intval($pts[0]), intval($pts[1]),
				intval($pts[2]), intval($pts[3]),
				intval($pts[4]), intval($pts[5]))) {
				
				db()->update('waves',
					array(
						'is_received' => 1,
						'to_user_id' => $user_id),
					"`wave_id` = " . $wave['wave_id'],
					1);
				
				db()->insert(
					'events',
					array(
						'type' => 'WAVE_RECV',
						'time' => time(),
						'game_id' => $game_id,
						'data' => implode(':', array($wave['wave_id'], $user_id, $wave['from_user_id']))));
				
				refresh_connection_between_users($wave['from_user_id'], $user_id, $game_id);
				
				$poll = get_poll_data($request);
				if ($poll['old']) return array('err' => 'GAME_RESET');
				
				return array(
					'err' => 'OK',
					'wave_id' => 0,
					'ack_wave_id' => $wave['wave_id'],
					'poll' => $poll);
			}
				
		}
		
		
		$wave_id = db()->insert(
			'waves',
			array(
				'game_id' => $game_id,
				'from_user_id' => $user_id,
				'location' => implode(',', array($origin_x, $origin_y, $a_x, $a_y, $b_x, $b_y)),
			));
		$event_id = db()->insert(
			'events',
			array(
				'type' => 'WAVE_INIT',
				'time' => time(),
				'game_id' => $game_id,
				'data' => implode(':', array($user_id, $wave_id, $origin_x, $origin_y, $a_x, $a_y, $b_x, $b_y))
			));
		
		$poll = get_poll_data($request);
		
		if ($poll['old']) return array('err' => 'GAME_RESET');
		
		return array(
			'err' => 'OK',
			'wave_create_id' => $wave_id,
			'poll' => $poll);
	}
?>