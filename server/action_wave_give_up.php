<?php
	function action_wave_give_up($request) {
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
		$wave_id = intval($request->json['wave_id']);
		
		$wave = db()->select_by_id('waves', 'wave_id', $wave_id, array('wave_id', 'is_received', 'game_id'));
		if ($wave == null || $wave['game_id'] != $game_id) {
			// that's okay, just roll with it.
		} else if ($wave['is_received'] == 1) {
			// that's also okay, let poll data set them straight
		} else {
			// bye bye
			db()->delete('waves', "`wave_id` = " . $wave_id, 1);
			db()->insert(
				'events',
				array(
					'type' => 'WAVE_GIVE_UP',
					'data' => $wave_id,
					'time' => time(),
					'game_id' => $game_id));
		}
		

		$poll = get_poll_data($request);
		if ($poll['old']) return array('err' => 'GAME_RESET');
		
		return array(
			'err' => 'OK',
			'poll' => $poll);
	}
?>