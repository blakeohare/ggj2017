<?php
	/*
		REQUEST:
		{
			"action": "RESET"
		}

		RESPONSE:
		{
			"err": "OK",
		}

	*/
	function action_reset($request) {
		$game_id = db()->insert('games', array('time' => time()));
		db()->delete('games', "`game_id` < $game_id");
		db()->delete('events', "`game_id` < $game_id");
		db()->delete('users', "`game_id` < $game_id");
		db()->delete('states', "`game_id` < $game_id");
		db()->delete('waves', "`game_id` < $game_id");
		db()->delete('network', "`game_id` < $game_id");
		
		return array('err' => 'OK');
	}
?>