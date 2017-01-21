<?php
	// TODO: guard this page off with an IP check
	
	$api_result = null;
	if (isset($_POST['submit'])) {
		$dbg_request = new HttpRequest();
		$dbg_request->set_json_string(trim($_POST['dbg_request']));
		$api_result = route_action($dbg_request);
	}
	
?><html>
<head>

</head>
<body>

<h1>API Debug</h1>

<form action="/index.php", method="POST">
<value type="hidden" name="debug" value="1"/>

<div>
<textarea rows="10" style="width:100%" name="dbg_request"><?
	if (isset($_POST['dbg_request'])) {
		echo htmlspecialchars($_POST['dbg_request']);
	} else {
		echo htmlspecialchars('{
  "action": "ACTION_NAME"
}');
	}
?></textarea>
</div>

<div><pre>
<?
	if ($api_result !== null) {	
		echo json_encode($api_result, JSON_PRETTY_PRINT);
	}
?>
</pre></div>

<div>
<input type="submit" name="submit" value="Send"/>
</div>

</form>

</body>
</html>