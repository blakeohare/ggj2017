<?php
	require 'config.php';
	require 'util.php';
	require 'database.php';
	
	class HttpRequest {
		function __construct() {
			$this->method = strtoupper(trim($_SERVER['REQUEST_METHOD']));
			$this->ip = $_SERVER['REMOTE_ADDR'];
			$this->content = file_get_contents("php://input");
			$this->set_json(json_decode($this->content, true));
		}
		
		function set_json($json) {
			$this->json = $json;
			$this->action = trim($this->json['action']);
		}
	}
	
	function route_action($request) {
		switch (strtoupper(trim($request->action))) {
			case 'REGISTER':
				require 'action_register.php';
				return action_register($request);
				
			default:
				return array('err' => "UNKNOWN_ACTION");
		}
	}
	
	$request = new HttpRequest();
	
	if ($request->method == 'GET') {
		require 'debug_page.php';
		exit;
	}
	
	$result = route_action($request);
	
	echo json_encode($result, JSON_PRETTY_PRINT);
?>