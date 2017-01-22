<?php
	/*
		For debugging purposes.
		
		REQUEST:
		{
			"action": "LATEST_STATE"
		}

		RESPONSE:
		{
			"err": "OK",
			"poll": {
				"state": ...
			}
		}

	*/
	function action_latest_state($request) {
		$game_id = get_current_game_id();
		
		return array('err' => 'OK', 'poll' => generate_latest_state($game_id));
	}
?>