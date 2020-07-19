#!/usr/bin/env php
<?php

if($argc != 5) {
	print "usage: check-wp-jwt-login.php <wp-cli bin path> <path to wp installation> <wp_user_login> <wp_user_password>\n";
	exit(3);
}

$wp_cli = $argv[1];
$check = chdir($argv[2]);
$user = $argv[3];
$pwd = $argv[4];

if ( ! $check )
{
	print "[UNKNOWN] - Impossible change directory in: " . $argv[2];
	exit(3);
}

if ( ! realpath($wp_cli) )
{
	print "[UNKNOWN] - Impossible get wp bin in: " . $wp_cli;
	exit(3);
}

//0 OK
//1 WARNING
//2 CRITICAL
//3 UNKNOWN
$exit_code = 0;
$message = "[OK] - JWT login success";



$api_endpoint = trim( shell_exec("$wp_cli eval \"echo rest_url();\"") );
$jwt_token_endpoint = $api_endpoint . "jwt-auth/v1/token";

$json_body = json_encode(['username' => $user,'password'=> $pwd]);

$curl_response = shell_exec("curl -sL -X POST -H 'Content-Type: application/json' --data '$json_body' -w '|||%{http_code}' $jwt_token_endpoint 2>/dev/null");

if ( strlen($curl_response ) < 3 )
{
	$message = "[CRITICAL] - Impossible read curl data from $jwt_token_endpoint";
	$exit_code = 2;
}
else
{
	$curl_arr = explode('|||', $curl_response);
	if ( !$curl_arr || count( $curl_arr ) != 2 )
	{
		$message = "[CRITICAL] - Impossible read curl data from $jwt_token_endpoint";
		$exit_code = 2;
	}
	else
	{
		$content = $curl_arr[0];
		$status = $curl_arr[1];
		if ( $status != 200 )
		{
			$message = "[CRITICAL] - Jwt login endpoint returns status $status";
			$message .= "\n $content";
			$exit_code = 2;
		}
	}

}


print "{$message}\n";

exit($exit_code);

