#!/usr/bin/env php
<?php

if($argc != 3) {
	print "usage: check-wp-core-version.php <wp-cli bin path> <path to wp installation>\n";
	exit(3);
}

$wp_cli = $argv[1];
$check = chdir($argv[2]);

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
$message = "[OK] - This Wordpress has the last core version.";
$vv = "";


$sv = shell_exec("$wp_cli core version");

$performance_data = [
	'current' => $sv,
	'major' => $sv,
	'minor' => $sv
];

$json = shell_exec("$wp_cli core check-update --format=json" );
if ( $json )
{

	$versions = json_decode($json, true);
	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		print "[UNKNOWN] - An error occurred during json reading\n\n$json";
		exit(3);
	}

	if ( count( $versions ) )
	{
		foreach ( $versions as $item )
		{
			$type = $item['update_type'];
			switch( $type ){
				case 'minor':
					$exit_code = $exit_code < 1 ? 1 : $exit_code;
					$vv .= "[WARNING] ";
				break;
				case 'major':
					$exit_code = 2;
					$vv .= "[CRITICAL] ";
				break;
			}
			$performance_data[$type] = $item['version'];
			$vv .= "$type: {$item['version']}\n";
		}

		if ($exit_code == 2 )
		{
			$message = "[CRITICAL] - You have a major update to do";
		}
		elseif($exit_code == 1 )
		{
			$message = "[WARNING] - You have a minor update to do.";
		}
	}
}

$t = "";
foreach ($performance_data as $key => $value)
{
	$v = strtr( $value , ['.','']);
	$t .= "'$key'=$v ";
}

$t = strpos($t,0,-1);

print "{$message}|{$t}";
print "Current Version\n";
print shell_exec( "$wp_cli core version --extra" );
print "\n";
if ( $vv )
{
	print "Available updates\n";
	print "$vv\n";
}
exit($exit_code);

