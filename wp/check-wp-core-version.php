#!/usr/bin/env php
<?php

if($argc != 3) {
	print "usage: check-wp-core-version.php <wp-cli bin path> <path to wp installation>\n";
	exit(3);
}

$wp_cli = $argv[1];
chdir($argv[2]);

//0 OK
//1 WARNING
//2 CRITICAL
//3 UNKNOWN
$exit_code = 0;
$message = "OK - This Wordpress has the last core version.";
$vv = "";


$json = shell_exec('wp core check-update --allow-root --format=json' );
if ( $json )
{

	$versions = json_decode($json, true);
	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		print "UNKNOWN - An error occurred during json reading\n\n$json";
		exit(3);
	}

	if ( count( $versions ) )
	{
		foreach ( $versions as $item )
		{
			$type = $item['update_type'];
			$vv .= "$type: {$item['version']}, ";

			switch( $type ){
				case 'minor':
					$exit_code = $exit_code < 1 ? 1 : $exit_code;
				break;
				case 'major':
					$exit_code = 2;
				break;
			}
		}
		$vv = substr($vv,0,-2);

		if ($exit_code == 2 )
		{
			$message = "CRITICAL - You have a major update to do";
		}
		elseif($exit_code == 1 )
		{
			$message = "WARNING - You have a minor update to do.";
		}
	}
}

print "$message\n$vv\n";
exit($exit_code);

