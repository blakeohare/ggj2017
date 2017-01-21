<?php
	/*
		{
			"state": { 
				// this is seldomly populated and is like a keyframe that occurs every 20 seconds
				"user_data": "{USER_ID}:{NAME}:{X}:{Y}|...",
				"wave_data": "{WAVE_ID}:{USER_ID}:{X1}:{Y1}:{X2}:{Y2}:{X3}:{Y3}|..."
			},
			"event_id_min": LOWEST_EVENT_ID // only present if state is not present
			"event_id_max": HIGHEST_EVENT_ID
			"events": { // only present if state is not present
				"e{EVENT_ID}": { "type": type, "data": data }
			},
			"scores": {
				{
				  "u{user ID}": score
				}
			}
		}
	*/
	function get_poll_data($game_id = null) {
		if ($game_id !== null && is_old_game($game_id)) {
			return array('old' => true);
		}
		
		$output = array();
		$now = time();
		$event_id = intval($request->json['event_id']);
		
		$events_db = db()->select("
			SELECT
				`event_id`,
				`game_id`,
				`type`,
				`data`
			FROM `events`
			WHERE `event_id` >= $event_id
			ORDER BY `event_id`");
		$events = $events_db->as_table();
		if (count($events) == 0 || $events[0]['event_id'] != $event_id) {
			// just do a full sync since your data is super old
			$latest_state = generate_latest_state($game_id);
			return array(
				'state' => array(
					'user_data' => $latest_state['user_data'],
					'wave_data' => $latest_state['wave_data']),
				'event_id_max' => $latest_state['includes_event_id'],
				'scores' => get_leaderboard($game_id)
			);
		}
		
		// no new events (this event is the old one you knew about)
		if (count($events) == 1) {
			return array();
		}
		
		// otherwise you can get the list of all events that have transpired since the last event
		
		$event_id_min = intval($events[1]['event_id']);
		$event_id_max = intval($events[count($events) - 1]['event_id']);
		$events_lookup = array();
		for ($i = 1; $i < count($events); ++$i) {
			$event = $events[$i];
			$event_id = $event['event_id'];
			$events_lookup['e' . $event_id] = array(
				'type' => $event['type'],
				'data' => $event['data'],
			);
		}
		return array(
			'event_id_min' => $event_id_min,
			'event_id_max' => $event_id_max,
			'events' => $events_lookup,
			'scores' => get_leaderboard($game_id),
		);
	}
	
	function get_leaderboard($game_id) {
		$users_db = db()->select("SELECT `user_id`,`score` FROM `users` ORDER BY `score`");
		$output = array();
		while ($users_db->has_more()) {
			$user = $users_db->next();
			array_push($output, $user['user_id']);
			array_push($output, intval($user['score'] * 10));
		}
		return implode(":", $output);
	}
	
	function get_current_game_id() {
		$game_info = db()->select("SELECT `game_id` FROM `games` ORDER BY `game_id` DESC LIMIT 1")->next();
		return intval($game_info['game_id']);
	}
	
	function get_latest_db_state($game_id) {
		$query = "
			SELECT
				`state_id`,
				`game_id`,
				`includes_event_id`,
				`time`,
				`user_data`,
				`wave_data`
			FROM `states`
			WHERE `populated` = 1
			ORDER BY `state_id` DESC
			LIMIT 1";
		
		$last_available_state = db()->select($query)->next();
		
		if ($last_available_state === null) {
			return array(
				'includes_event_id' => 0,
				'user_data' => '',
				'wave_data' => '',
				'time' => 1,
				'game_id' => $game_id,
				'state_id' => 0);
		}
		
		return $last_available_state;
	}
	
	
	function generate_latest_state($game_id) {
		$locations = array();
		$last_full_state = get_latest_db_state($game_id);
		$last_full_state_event_id = $last_full_state['includes_event_id'];
		
		$new_db_state_id = 0;
		if ($last_full_state['time'] < time() - 20) {
			$new_db_state_id = db()->insert('states', array('game_id' => $game_id, 'time' => time(), 'populated' => 0));
		}
		
		// get the existing data
		$user_info_by_id = array();
		foreach (explode('|', $last_full_state['user_data']) as $raw_user_info) {
			$parts = explode(':', $raw_user_info);
			if (count($parts) == 4) {
				$user_id = intval($parts[0]);
				$user_name = trim($parts[1]);
				$x = intval($parts[2]);
				$y = intval($parts[3]);
				$user_info_by_id[$user_id] = array($user_id, $user_name, $x, $y);
			}
		}
		$wave_info_by_id = array();
		foreach (explode('|', $last_full_state['wave_data']) as $raw_wave_info) {
			$parts = explode(':', $raw_wave_info);
			if (count($parts) == 8) {
				for ($i = 0; $i < 8; ++$i) {
					$parts[$i] = intval($parts[$i]);
				}
				$wave_info_by_id[$parts[0]] = $parts;
			}
		}
		
		// now iterate through all events since then and flatten them.
		$events = db()->select("SELECT `event_id`,`game_id`,`time`,`type`,`data` FROM `events` WHERE `event_id` > $last_full_state_event_id ORDER BY `event_id`");
		$last_event_id = null;
		while ($events->has_more()) {
			$event = $events->next();
			$last_full_state_event_id = intval($event['event_id']);
			switch (trim($event['type'])) {
				case 'MOVE':
					$move_data = parse_move_and_get_last($event['data']);
					$user_id = $move_data['user_id'];
					if (isset($user_info__by_id[$user_id])) { // just in case a move was sent after a part
						$user_info_by_id[$user_id][2] = $move_data['x'];
						$user_info_by_id[$user_id][3] = $move_data['y'];
					}
					break;
				case 'JOIN':
					$join_data = explode(':', $event['data']);
					$user_id = intval($join_data[0]);
					$name = trim($join_data[1]);
					$x = $join_data[2];
					$y = $join_data[3];
					$user_info_by_id[$user_id] = array($user_id, $name, $x, $y);
					break;
				case 'PART':
					$user_id = intval($event['data']);
					unset($user_info_by_id[$user_id]);
					break;
			}
		}
		
		$user_data = array();
		$wave_data = array();
		foreach ($user_info_by_id as $ignored => $user_info) {
			if ($user_info !== null) {
				array_push($user_data, implode(':', $user_info));
			}
		}
		foreach ($wave_info_by_id as $ignored => $wave_info) {
			if ($wave_info !== null) {
				array_push($wave_data, implode(':', $wave_info));
			}
		}
		
		$user_data = implode('|', $user_data);
		$wave_data = implode('|', $wave_data);
		if ($new_db_state_id != 0 && $last_event_id !== null) {
			db()->update('states', 
				array(
					'includes_event_id' => $last_event_id,
					'populated' => 1,
					'user_data' => $user_data,
					'wave_data' => $wave_data),
				"`state_id` = $new_db_state_id",
				1);
		}
		
		return array(
			'event_id' => $last_full_state_event_id,
			'user_data' => $user_data,
			'wave_data' => $wave_data);
	}
	
	function parse_join($join_raw) {
		$parts = explode(':', $join_raw);
		return array(
			'user_id' => intval($parts[0]),
			'name' => trim($parts[1]),
			'x' => intval($parts[2]),
			'y' => intval($parts[3]));
	}
	
	function parse_move_and_get_last($move_raw) {
		$parts = explode(':', $move_raw);
		$pts = array();
		$length = count($parts);
		return array(
			'user_id' => intval($parts[0]),
			'x' => intval($parts[$length - 2]),
			'y' => intval($parts[$length - 1]));
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
	
	function is_old_game($game_id) {
		$latest_game_id = get_current_game_id();
		return ($game_id != $latest_game_id);
	}
?>