<?php
	/*
		REQUEST:
		{
			"action": "POLL",
			"token": User Token,
			"user_id": User ID,
			"game_id": Game ID,
			"event_id": "last known event ID"
		}
		
		RESPONSE:
		{
			"err": OK | GAME_RESET
			"poll": {
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
		}

	*/
	function action_poll($request) {
		if (is_old_game($request))
			return array('err' => 'GAME_RESET');
		
		$user = get_user(
			intval($request->json['user_id']),
			trim($request->json['token']),
			intval($request->json['game_id']));
		
		if ($user === null) {
			return array('err' => 'GAME_RESET');
		}
		
		$output = array('err' => 'OK');
		$output = add_poll_response($request, $output);
		return $output;
	}
?>