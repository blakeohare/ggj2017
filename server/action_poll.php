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
		$poll = get_poll_data($request);
		if ($poll['old']) return array('err' => 'GAME_RESET');
		return array(
			'err' => 'OK',
			'poll' => $poll,
		);
	}
?>