#!/usr/bin/php
<?php

// Detect caller (IP or hostname)
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';


$db_host = '127.0.0.1';
$db_user = 'testuser';
$db_pass = 'rv9991$#';
$db_name = 'testdb';
//databasee connection
$mydb = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mydb->connect_errno != 0) {
    echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
    exit(0);
}

echo "Successfully connected to database as user: $db_user".PHP_EOL
	//handling of registration of information
	function doRegister($username,$password) {
		global $mydb; 
		$stmt = $mydb -> prepare("select * from users where usernname = ? limit 1");
		$stmt->bind_param("s",$username);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result && $resut->num_rows>0){
			return array("returnCode"=>1,"message"=>"Username already exists");
		}
		
		$stmt = $mydb->prepare("insert into  users(username,password) values(?,?)");
		$stmt->bind_param("ss",$username,$password);
		if($stmt->execute()){
			return array("returnCode"=>0,"message"=>"Registration Successful");
		}
		else{
			return array("returnCode"=>1,"message"=>"Registration Failed");
		}
		
	}
	//handling of login if passed as typee with accessing database
	function doLogin($username,$password){
		global $mydb;
		$stmt = $mydb -> prepare ("select password from users where usernanme = ? limit 1");
		$stmt->bind_param("s",$username);
		$stmt->execute();
		$result = $stmt->get_result();

		if(!$result|| $result->num_rows===0){
			return array("returnCode"=>1,"message"=>"User not found");
		}

		$row = $result->fetch_assoc();
		if($row['password']===$password){
			$session_id = bin2hex(random_bytes(16));
			$expiration = date('Y-m-d H:i:s',time()+3600);
			$stmt = $mydb->prepare("insert into user_cookies(session_id,username,expiration_time) values(?,?,?)");
			$stmt->execute();

			return array("returnCode"=>0,"message"=>"Login Successful", "sessionID"=>$session_id);
		}
		else{
			return array("returnCode"=>1,"message"=>"Failed Login");
		}
	}
	//handling of sessionId if passed as type
	function doValidate($sessionId){
		global $mydb;
		$stmt = $mydb->prepare("select username,expiration_time from user_cookies where session_id = ? limit 1");
		$stmt->bind_param("s",$sessionId);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result||$result->num_rows === 0){
			return array ("returnCode"=>1,"message"=>"Invalid Session");
		}

		$row = $result->fetch_assoc();
		if(strtotime($row['expiration_time'])<time()){
			return array("returnCode"=>1,"message"=>"Session Expired");
		}
		return array("returnCode"=>0,"message"=>"Valid Session","username"=>$row['username']);
	}

	function requestProcessor($request){
		echo "received request: ".PHP_EOL;
		var_dump($request);

		if(!isset($request['type'])){
			return array("returnCode"=>1,"message"=>"No type provided");
		}
		//handling of request types
		switch(strtolower($request['type'])){
			case "register":
				return doRegister($request['username'],$request['password']);
			case "login":
				return doLogin($request['username'],$request['password']);
			case "validate_session":
				return doValidate($request['sessionId');
			default:
				return array("returnCode"=>0,"message"=>"Invalid Request");
		}
	}

	//Set up of cient
	$client = new rabbitMQClient("testRabbitMQ.ini","testServer");
	echo "Client active, waiting for inputs from frontend and broker".PHP_EOL;
	$client->process_requests('requestProcessor');

	echo "Client deactivated".PHP_EOL;
	exit();
?>

