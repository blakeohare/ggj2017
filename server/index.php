<?php
	require 'config.php';
	require 'util.php';
	require 'database.php';
	require 'common.php';
	
	class HttpRequest {
		function __construct() {
			$this->method = strtoupper(trim($_SERVER['REQUEST_METHOD']));
			$this->ip = $_SERVER['REMOTE_ADDR'];
			$this->content = file_get_contents("php://input");
			$this->set_json_string($this->content);
		}
		
		function set_json_string($json_string) {
			$this->json = json_decode($json_string, true);
			$this->action = trim($this->json['action']);
		}
	}
	
	function route_action($request) {
		
		switch (strtoupper(trim($request->action))) {
			case 'JOIN':
				require 'action_join.php';
				return action_join($request);
			
			case 'PART':
				require 'action_part.php';
				return action_part($request);
			
			case 'MOVE':
				require 'action_move.php';
				return action_move($request);
			
			case 'WAVE_INIT':
				require 'action_wave_init.php';
				return action_wave_init($request);
				
			case 'WAVE_RECV':
				require 'action_wave_recv.php';
				return action_wave_recv($request);
			
			case 'WAVE_GIVE_UP':
				require 'action_wave_give_up.php';
				return action_wave_give_up($request);
			
			case 'POLL':
				require 'action_poll.php';
				return action_poll($request);
			
			default:
				return array('err' => "UNKNOWN_ACTION");
		}
	}
	
	$request = new HttpRequest();
	
	if ($request->method == 'GET' || isset($_POST['submit'])) {
		require 'debug_page.php';
		exit;
	}
	
	$result = route_action($request);
	
	echo json_encode($result, JSON_PRETTY_PRINT);
?>